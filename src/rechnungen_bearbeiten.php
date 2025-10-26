<?php
require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

$rechnung_id = $_GET['id'] ?? null;
if (!$rechnung_id) {
    die("Keine Rechnung gewählt.");
}

// Rechnung + Kunde + Auftrag laden
$sql = "SELECT r.*, 
               k.firma, k.ansprechpartner, k.strasse, k.plz, k.ort, k.telefon,
               a.name AS auftragsname,
               p.projektname
        FROM rechnungen r
        LEFT JOIN kunden k ON r.kunde_id = k.id
        LEFT JOIN auftraege a ON r.auftrag_id = a.id
        LEFT JOIN projekte p ON a.projekt_id = p.id
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $rechnung_id);
$stmt->execute();
$rechnung = $stmt->get_result()->fetch_assoc();

if (!$rechnung) {
    die("Rechnung nicht gefunden.");
}

// Alle Positionen laden
$sql = "SELECT id, typ, beschreibung, menge, einheit, preis_pro_einheit, gesamt
        FROM rechnungspositionen
        WHERE rechnung_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $rechnung_id);
$stmt->execute();
$res = $stmt->get_result();
$positionen = $res->fetch_all(MYSQLI_ASSOC);

// Betriebskosten laden (für neue hinzufügen)
$res = $conn->query("SELECT * FROM betriebskosten WHERE kostenart != 'Strompreis' ORDER BY kostenart");
$betriebskosten = $res->fetch_all(MYSQLI_ASSOC);

// -----------------------------
// Betriebskosten-Position löschen
// -----------------------------
if (isset($_GET['delete'])) {
    $pos_id = (int)$_GET['delete'];

    // Nur Betriebskosten löschen
    $sql = "DELETE FROM rechnungspositionen WHERE id=? AND rechnung_id=? AND typ='betriebskosten'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $pos_id, $rechnung_id);
    $stmt->execute();

    // Gesamtsumme neu berechnen
    $sql = "SELECT SUM(gesamt) AS summe FROM rechnungspositionen WHERE rechnung_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $rechnung_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $gesamtbetrag = $row['summe'] ?? 0;

    $sql = "UPDATE rechnungen SET gesamtbetrag=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $gesamtbetrag, $rechnung_id);
    $stmt->execute();

    $_SESSION['success'] = '<div class="info-box"><i class="fa-solid fa-trash"></i> Position gelöscht.</div>';
    header("Location: index.php?site=rechnungen");
    exit;
}

// -----------------------------
// Rechnung speichern (Update / Neue Positionen)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if (!validate_form_token($_POST['form_token'] ?? '')) {
        $_SESSION['error'] = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> Ungültiges Formular.</div>';
        header("Location: index.php?site=rechnungen_bearbeiten&id=" . $rechnung_id);
        exit;
    }

    // Existierende Betriebskosten-Positionen aktualisieren
    foreach ($_POST['menge'] as $id => $menge) {
        $preis = $_POST['preis'][$id] ?? null;
        if ($preis === null) continue;

        $menge = (float)$menge;
        $preis = (float)$preis;
        $gesamt = $menge * $preis;

        $sql = "UPDATE rechnungspositionen 
                SET menge=?, preis_pro_einheit=?, gesamt=? 
                WHERE id=? AND typ='betriebskosten'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dddi", $menge, $preis, $gesamt, $id);
        $stmt->execute();
    }

    // Neue Betriebskosten hinzufügen
    if (!empty($_POST['neue_kosten'])) {
        foreach ($_POST['neue_kosten'] as $kosten_id => $pos) {
            if (empty($pos['selected'])) continue;

            $anzahl = (float)($pos['anzahl'] ?? 1);

            $stmt = $conn->prepare("SELECT kostenart, standard_betrag, einheit FROM betriebskosten WHERE id=?");
            $stmt->bind_param("i", $kosten_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            $beschreibung = $row['kostenart'];
            $preis_pro_einheit = (float)$row['standard_betrag'];

            if ($row['einheit'] === 'pauschal') {
                $anzahl = 1;
                $gesamt = $preis_pro_einheit;
            } else {
                $gesamt = $preis_pro_einheit * $anzahl;
            }

            $sql = "INSERT INTO rechnungspositionen 
                       (rechnung_id, typ, beschreibung, menge, einheit, preis_pro_einheit, gesamt)
                    VALUES (?, 'betriebskosten', ?, ?, ?, ?, ?)";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("isdsdd", $rechnung_id, $beschreibung, $anzahl, $row['einheit'], $preis_pro_einheit, $gesamt);
            $stmt2->execute();
        }
    }

    // Gesamtsumme neu berechnen
    $sql = "SELECT SUM(gesamt) AS summe FROM rechnungspositionen WHERE rechnung_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $rechnung_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $gesamtbetrag = $row['summe'] ?? 0;

    $sql = "UPDATE rechnungen SET gesamtbetrag=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $gesamtbetrag, $rechnung_id);
    $stmt->execute();

    $_SESSION['success'] = '<div class="info-box"><i class="fa-solid fa-circle-check"></i> Rechnung aktualisiert.</div>';
    header("Location: index.php?site=rechnungen");
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Rechnung bearbeiten – <?= htmlspecialchars($rechnung['rechnungsnummer']) ?></h2>
        <a href="index.php?site=rechnungen" class="btn-primary">← Zurück</a>
    </div>

    <form method="post">
        <h3>Bestehende Positionen</h3>
        <div class="table-wrapper">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Beschreibung</th>
                        <th class="right">Menge</th>
                        <th>Einheit</th>
                        <th class="right">Einzelpreis (€)</th>
                        <th class="right">Gesamt (€)</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($positionen as $pos): ?>
                    <tr>
                        <td><?= htmlspecialchars($pos['beschreibung']) ?></td>
                        <td class="right">
                            <?php if ($pos['typ'] === 'material' || $pos['typ'] === 'stromkosten'): ?>
                                <?= number_format($pos['menge'], 2, ',', '.') ?>
                            <?php else: ?>
                                <input type="number" name="menge[<?= $pos['id'] ?>]" 
                                       value="<?= $pos['menge'] ?>" step="0.1" min="0">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($pos['einheit']) ?></td>
                        <td class="right">
                            <?php if ($pos['typ'] === 'material' || $pos['typ'] === 'stromkosten'): ?>
                                <?= number_format($pos['preis_pro_einheit'], 2, ',', '.') ?>
                            <?php else: ?>
                                <input type="number" name="preis[<?= $pos['id'] ?>]" 
                                       value="<?= $pos['preis_pro_einheit'] ?>" step="0.01" min="0">
                            <?php endif; ?>
                        </td>
                        <td class="right"><?= number_format($pos['gesamt'], 2, ',', '.') ?></td>
                        <td class="center">
                            <?php if ($pos['typ'] === 'betriebskosten'): ?>
                                <a href="index.php?site=rechnungen_bearbeiten&id=<?= $rechnung_id ?>&delete=<?= $pos['id'] ?>" 
                                   class="btn-action delete" 
                                   onclick="return confirm('Position wirklich löschen?');">
                                   <i class="fa-solid fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"><strong>Gesamtbetrag</strong></td>
                        <td class="right"><strong><?= number_format($rechnung['gesamtbetrag'], 2, ',', '.') ?> €</strong></td>
						</td> </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <h3>Neue Betriebskosten hinzufügen</h3>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Art</th>
                    <th>Beschreibung</th>
                    <th>Betrag (€)</th>
                    <th>Einheit</th>
                    <th>Anzahl</th>
                    <th>Auswählen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($betriebskosten as $k): ?>
                <tr>
                    <td><?= htmlspecialchars($k['kostenart']) ?></td>
                    <td><?= htmlspecialchars($k['beschreibung']) ?></td>
                    <td class="right"><?= number_format($k['standard_betrag'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($k['einheit']) ?></td>
                    <td><input type="number" name="neue_kosten[<?= $k['id'] ?>][anzahl]" value="1" min="1" step="1"></td>
                    <td><input type="checkbox" name="neue_kosten[<?= $k['id'] ?>][selected]" value="1"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">
        <button type="submit" name="save" class="btn-primary">💾 Änderungen speichern</button>
    </form>
</section>
