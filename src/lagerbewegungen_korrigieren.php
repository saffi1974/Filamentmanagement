<?php
require_once "auth.php";
require_role(['superuser','admin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
$returnUrl = $_POST['return'] ?? $_GET['return'] ?? 'index.php?site=lagerbewegungen';

if ($id <= 0) {
    echo "<div class='toast error'>Ungültige Lagerbewegung.</div>";
    return;
}

/* Originalbuchung inkl. Spulendaten laden */
$stmt = $conn->prepare("
    SELECT 
        lb.*,
        s.preis,
        s.lagerort,
        s.chargennummer,
        s.kommentar AS spulen_kommentar,
        s.verbrauchtes_filament,
        s.verbleibendes_filament,
        s.status,
        CONCAT(h.hr_name, ' - ', f.name_des_filaments, ' - ', m.name) AS filament_name
    FROM lagerbewegungen lb
    JOIN spulenlager s ON lb.spule_id = s.id
    JOIN filamente f ON lb.filament_id = f.id
    LEFT JOIN hersteller h ON f.hersteller_id = h.id
    LEFT JOIN materialien m ON f.material = m.id
    WHERE lb.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$original = $stmt->get_result()->fetch_assoc();

if (!$original) {
    echo "<div class='toast error'>Lagerbewegung wurde nicht gefunden.</div>";
    return;
}

if ($original['bewegungsart'] !== 'wareneingang') {
    echo "<div class='toast error'>Nur Wareneingänge können korrigiert werden.</div>";
    return;
}

if ((float)$original['verbrauchtes_filament'] > 0 || (float)$original['verbleibendes_filament'] < 1000) {
    echo "<div class='toast error'>Diese Spule wurde bereits verwendet und kann nicht mehr korrigiert werden.</div>";
    return;
}

if ($original['status'] === 'korrigiert') {
    echo "<div class='toast error'>Diese Spule wurde bereits korrigiert.</div>";
    return;
}

/* Filamente für Dropdown laden */
$filamente = $conn->query("
    SELECT 
        f.id,
        f.material,
        CONCAT(h.hr_name, ' - ', f.name_des_filaments, ' - ', m.name) AS filament_name
    FROM filamente f
    LEFT JOIN hersteller h ON f.hersteller_id = h.id
    LEFT JOIN materialien m ON f.material = m.id
    ORDER BY h.hr_name, f.name_des_filaments
");

/* Speichern */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $neues_filament_id = (int)($_POST['filament_id'] ?? 0);
    $preis = (float)($_POST['preis'] ?? 0);
    $lagerort = trim($_POST['lagerort'] ?? '');
    $chargennummer = trim($_POST['chargennummer'] ?? '');
    $kommentar = trim($_POST['kommentar'] ?? '');

    if ($neues_filament_id <= 0) {
        echo "<div class='toast error'>Bitte ein gültiges Filament auswählen.</div>";
    } else {
        /* Material des neuen Filaments holen */
        $stmtMat = $conn->prepare("SELECT material FROM filamente WHERE id = ?");
        $stmtMat->bind_param("i", $neues_filament_id);
        $stmtMat->execute();
        $mat = $stmtMat->get_result()->fetch_assoc();

        if (!$mat) {
            echo "<div class='toast error'>Material zum Filament konnte nicht ermittelt werden.</div>";
        } else {
            $material_id = (int)$mat['material'];
            $alte_spule_id = (int)$original['spule_id'];
            $alte_filament_id = (int)$original['filament_id'];
            $original_menge = (float)$original['menge'];
            $menge = abs($original_menge);

            $conn->begin_transaction();

            try {
                /* 1. Alte Spule als korrigiert markieren */
                $stmtUpdate = $conn->prepare("
                    UPDATE spulenlager
                    SET status = 'korrigiert',
                        verbleibendes_filament = 0
                    WHERE id = ?
                ");
                $stmtUpdate->bind_param("i", $alte_spule_id);
                $stmtUpdate->execute();

                /* 2. Korrekturbuchung für alte Spule */
                $gegenmenge = -abs($original_menge);
                $kommentar_gegen = "Korrekturbuchung wegen Falschbuchung zu Wareneingang #" . $id;

                if ($kommentar !== '') {
                    $kommentar_gegen .= " - " . $kommentar;
                }

                $stmtGegen = $conn->prepare("
                    INSERT INTO lagerbewegungen
                    (spule_id, filament_id, user_id, bewegungsart, menge, kommentar, referenz_bewegung_id)
                    VALUES (?, ?, ?, 'korrektur', ?, ?, ?)
                ");
                $stmtGegen->bind_param(
                    "iiidsi",
                    $alte_spule_id,
                    $alte_filament_id,
                    $user_id,
                    $gegenmenge,
                    $kommentar_gegen,
                    $id
                );
                $stmtGegen->execute();

                /* 3. Neue korrigierte Spule anlegen */
                $stmtNeueSpule = $conn->prepare("
                    INSERT INTO spulenlager
                    (
                        filament_id,
                        material_id,
                        preis,
                        verbrauchtes_filament,
                        verbleibendes_filament,
                        lagerort,
                        chargennummer,
                        kommentar,
                        status,
                        korrigiert_von_spule_id
                    )
                    VALUES (?, ?, ?, 0, ?, ?, ?, ?, 'aktiv', ?)
                ");
                $stmtNeueSpule->bind_param(
                    "iidisssi",
                    $neues_filament_id,
                    $material_id,
                    $preis,
                    $menge,
                    $lagerort,
                    $chargennummer,
                    $kommentar,
                    $alte_spule_id
                );
                $stmtNeueSpule->execute();

                $neue_spule_id = $conn->insert_id;

                /* 4. Neue Wareneingangsbuchung erzeugen */
                $kommentar_neu = "Ersatzbuchung wegen Falschbuchung zu Wareneingang #" . $id;

                if ($kommentar !== '') {
                    $kommentar_neu .= " - " . $kommentar;
                }

                $stmtNeu = $conn->prepare("
                    INSERT INTO lagerbewegungen
                    (spule_id, filament_id, user_id, bewegungsart, menge, kommentar, referenz_bewegung_id)
                    VALUES (?, ?, ?, 'wareneingang', ?, ?, ?)
                ");
                $stmtNeu->bind_param(
                    "iiidsi",
                    $neue_spule_id,
                    $neues_filament_id,
                    $user_id,
                    $menge,
                    $kommentar_neu,
                    $id
                );
                $stmtNeu->execute();

                $conn->commit();

                $_SESSION['success'] = '<div class="info-box">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Wareneingang wurde erfolgreich korrigiert.</span>
                </div>';

                header("Location: " . $returnUrl);
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                echo "<div class='toast error'>Fehler bei der Korrektur: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}
?>

<section class="card">
    <div class="card-header">
        <h2>Wareneingang korrigieren</h2>
        <a href="<?= htmlspecialchars($returnUrl) ?>" class="btn-primary">← Zurück</a>
    </div>

    <div class="info-box">
        <i class="fa-solid fa-circle-info"></i>
        <span>
            Originalbuchung:
            <?= htmlspecialchars($original['filament_name']) ?> |
            Menge: <?= htmlspecialchars($original['menge']) ?> g |
            Spule-ID: <?= (int)$original['spule_id'] ?>
        </span>
    </div>

    <form method="post" class="form-section">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>">

        <div class="form-group">
            <label for="filament_id">Richtiges Filament</label>
            <select name="filament_id" id="filament_id" required>
                <?php while ($f = $filamente->fetch_assoc()): ?>
                    <option value="<?= (int)$f['id'] ?>" <?= (int)$f['id'] === (int)$original['filament_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['filament_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="preis">Preis (€)</label>
            <input type="number" step="0.01" name="preis" id="preis" value="<?= htmlspecialchars($original['preis']) ?>" required>
        </div>

        <div class="form-group">
            <label for="lagerort">Lagerort</label>
            <input type="text" name="lagerort" id="lagerort" value="<?= htmlspecialchars($original['lagerort'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="chargennummer">Chargennummer</label>
            <input type="text" name="chargennummer" id="chargennummer" value="<?= htmlspecialchars($original['chargennummer'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="kommentar">Kommentar zur Korrektur</label>
            <textarea name="kommentar" id="kommentar" rows="3"><?= htmlspecialchars($original['spulen_kommentar'] ?? 'Falscher Wareneingang korrigiert.') ?></textarea>
        </div>

        <button type="submit" class="btn-primary">Korrektur buchen</button>
        <a href="<?= htmlspecialchars($returnUrl) ?>" class="btn-primary">Abbrechen</a>
    </form>
</section>