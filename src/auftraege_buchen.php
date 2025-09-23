<?php
require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

$auftrag_id = $_GET['id'] ?? ($_POST['auftrag_id'] ?? null);
if (!$auftrag_id) {
    die("Kein Auftrag gewählt.");
}

// Auftrag laden
$sql = "SELECT * FROM auftraege WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auftrag_id);
$stmt->execute();
$res = $stmt->get_result();
$auftrag = $res->fetch_assoc();
if (!$auftrag) {
    die("Auftrag nicht gefunden.");
}

// Filamente laden
$sql = "SELECT af.*, f.name_des_filaments, h.hr_name, m.name AS material_name
        FROM auftrag_filamente af
        JOIN filamente f ON af.filament_id = f.id
        JOIN hersteller h ON f.hersteller_id = h.id
        JOIN materialien m ON f.material = m.id
        WHERE af.auftrag_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auftrag_id);
$stmt->execute();
$res = $stmt->get_result();
$auftrag_filamente = $res->fetch_all(MYSQLI_ASSOC);

// -----------------------------
// Buchung (POST)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buchen'])) {
    if (!validate_form_token($_POST['form_token'] ?? '')) {
        $_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Das Formular wurde bereits verarbeitet oder ist ungültig.</span>
            </div>';
        header("Location: index.php?site=auftraege");
        exit;
    }

    // Schritt 1: Prüfen, ob für alle Positionen genug im Lager ist
    $fehlende = [];
    foreach ($auftrag_filamente as $af) {
        $gesamt_geplant = $af['menge_geplant'] * $auftrag['anzahl'];
        $rest = $gesamt_geplant - $af['menge_gebucht'];

        if ($rest > 0) {
            $sql = "SELECT SUM(verbleibendes_filament) AS verfuegbar
                    FROM spulenlager WHERE filament_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $af['filament_id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $verfuegbar = $row['verfuegbar'] ?? 0;

            if ($verfuegbar < $rest) {
                $fehlende[] = [
                    'name' => $af['hr_name'].' | '.$af['name_des_filaments'].' | '.$af['material_name'],
                    'fehlend' => $rest - $verfuegbar
                ];
            }
        }
    }

    if (!empty($fehlende)) {
        // Abbruch mit Fehlermeldung
        $msg = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i><span>';
        $msg .= 'Nicht genug Filament für folgende Position(en):<br>';
        foreach ($fehlende as $f) {
            $msg .= htmlspecialchars($f['name']).' (Fehlend: '.number_format($f['fehlend'], 2, ',', '.').' g)<br>';
        }
        $msg .= '</span></div>';

        $_SESSION['error'] = $msg;
        header("Location: index.php?site=auftraege");
        exit;
    }

    // Schritt 2: Buchen (Alles-oder-nichts)
    $conn->begin_transaction();
    try {
        foreach ($auftrag_filamente as $af) {
            $gesamt_geplant = $af['menge_geplant'] * $auftrag['anzahl'];
            $rest = $gesamt_geplant - $af['menge_gebucht'];
            if ($rest <= 0) continue;

            $sql = "SELECT * FROM spulenlager 
                    WHERE filament_id=? AND verbleibendes_filament > 0
                    ORDER BY id ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $af['filament_id']);
            $stmt->execute();
            $spulenRes = $stmt->get_result();

            while ($rest > 0 && $spule = $spulenRes->fetch_assoc()) {
                $abbuchung = min($spule['verbleibendes_filament'], $rest);
                $rest -= $abbuchung;

                // Spule aktualisieren
                $sql = "UPDATE spulenlager 
                        SET verbleibendes_filament = verbleibendes_filament - ?, 
                            verbrauchtes_filament = verbrauchtes_filament + ? 
                        WHERE id = ?";
                $stmt2 = $conn->prepare($sql);
                $stmt2->bind_param("ddi", $abbuchung, $abbuchung, $spule['id']);
                $stmt2->execute();

                // Historie erfassen
                $sql = "INSERT INTO lagerbewegungen 
                        (spule_id, filament_id, auftrag_id, user_id, bewegungsart, menge) 
                        VALUES (?, ?, ?, ?, 'abbuchung_auftrag', ?)";
                $stmt2 = $conn->prepare($sql);
                $negMenge = -1 * $abbuchung;
                $stmt2->bind_param("iiiid", $spule['id'], $af['filament_id'], $auftrag['id'], $user_id, $negMenge);
                $stmt2->execute();

                // Auftrag-Filament aktualisieren
                $sql = "UPDATE auftrag_filamente 
                        SET menge_gebucht = menge_gebucht + ?, 
                            status = CASE 
                                WHEN menge_gebucht + ? >= ? THEN 'gebucht'
                                ELSE 'teilweise_gebucht'
                            END
                        WHERE id = ?";
                $stmt2 = $conn->prepare($sql);
                $stmt2->bind_param("dddi", $abbuchung, $abbuchung, $gesamt_geplant, $af['id']);
                $stmt2->execute();
            }
        }

        // Auftrag als fertig markieren, wenn alle Positionen gebucht sind
        $sql = "SELECT COUNT(*) AS offen 
                FROM auftrag_filamente 
                WHERE auftrag_id=? AND status!='gebucht'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $auftrag_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row['offen'] == 0) {
            $sql = "UPDATE auftraege SET status='fertig' WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $auftrag_id);
            $stmt->execute();
        }

        $conn->commit();
        $_SESSION['success'] = '<div class="info-box">
                <i class="fa-solid fa-circle-check"></i>
                <span>Auftrag erfolgreich gebucht.</span>
            </div>';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = '<div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Fehler bei der Buchung: '.htmlspecialchars($e->getMessage()).'</span>
            </div>';
    }

    header("Location: index.php?site=auftraege");
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Auftrag buchen – Übersicht</h2>
        <a href="index.php?site=auftraege" class="btn-primary">← Zurück zu Aufträgen</a>
    </div>

    <h3>Auftrag</h3>
    <p>
        Auftrag-Nr: <?= htmlspecialchars($auftrag['id']) ?><br>
        Anzahl: <?= htmlspecialchars($auftrag['anzahl']) ?><br>
        Status: <?= htmlspecialchars($auftrag['status']) ?>
    </p>

    <h3>Filamente & Bedarf</h3>
    <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Filament</th>
                    <th class="right">Menge pro Stück (g)</th>
                    <th class="right">Gesamtbedarf (g)</th>
                    <th class="right">Bereits gebucht (g)</th>
                    <th class="right">Rest (g)</th>
                    <th class="right">Im Lager (g)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auftrag_filamente as $af): ?>
                    <?php
                        $gesamt_geplant = $af['menge_geplant'] * $auftrag['anzahl'];
                        $rest = $gesamt_geplant - $af['menge_gebucht'];

                        $sql = "SELECT SUM(verbleibendes_filament) AS verfuegbar
                                FROM spulenlager WHERE filament_id=?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $af['filament_id']);
                        $stmt->execute();
                        $row = $stmt->get_result()->fetch_assoc();
                        $verfuegbar = $row['verfuegbar'] ?? 0;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($af['hr_name'].' | '.$af['name_des_filaments'].' | '.$af['material_name']) ?></td>
                        <td class="right"><?= number_format($af['menge_geplant'], 2, ',', '.') ?></td>
                        <td class="right"><?= number_format($gesamt_geplant, 2, ',', '.') ?></td>
                        <td class="right"><?= number_format($af['menge_gebucht'], 2, ',', '.') ?></td>
                        <td class="right"><?= number_format($rest, 2, ',', '.') ?></td>
                        <td class="right"><?= number_format($verfuegbar, 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form method="post" style="margin-top:20px;">
        <input type="hidden" name="auftrag_id" value="<?= $auftrag_id ?>">
        <input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">
        <button type="submit" name="buchen" class="btn-primary">✅ Buchen starten</button>
    </form>
</section>
