<?php
require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

// ID der Spule aus URL
$spule_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($spule_id <= 0) die("Ungültige Spule.");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? null;

// Daten der Spule holen inkl. Leerspule
$res = $conn->query("
    SELECT s.*, f.name_des_filaments, h.hr_name, h.hr_leerspule, m.name AS material
    FROM spulenlager s
    LEFT JOIN filamente f ON s.filament_id = f.id
    LEFT JOIN hersteller h ON f.hersteller_id = h.id
    LEFT JOIN materialien m ON f.material = m.id
    WHERE s.id = $spule_id
");
$spule = $res->fetch_assoc();
if (!$spule) die("Spule nicht gefunden.");

$leerspule = (float)$spule['hr_leerspule']; // Gewicht Leerspule

// Formular absenden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aktuelles_gewicht = (float)$_POST['aktuelles_gewicht'];
    $verbleibendes = $aktuelles_gewicht - $leerspule; // Filamentgewicht
    if ($verbleibendes < 0) $verbleibendes = 0;

    $verbrauchtes = 1000 - $verbleibendes; // Rest auf der Spule
    if ($verbrauchtes < 0) $verbrauchtes = 0;

    $preis = (float)$_POST['preis'];
    $lagerort = $_POST['lagerort'] ?? null;
    $chargennummer = $_POST['chargennummer'] ?? null;
    $kommentar = $_POST['kommentar'] ?? null;

    // Werte vor Update merken
    $alt_verbleibend = (float)$spule['verbleibendes_filament'];

    // Update durchführen
    $conn->query("
        UPDATE spulenlager SET
            verbleibendes_filament = $verbleibendes,
            verbrauchtes_filament = $verbrauchtes,
            preis = $preis,
            lagerort = " . ($lagerort ? "'".$conn->real_escape_string($lagerort)."'" : "NULL") . ",
            chargennummer = " . ($chargennummer ? "'".$conn->real_escape_string($chargennummer)."'" : "NULL") . ",
            kommentar = " . ($kommentar ? "'".$conn->real_escape_string($kommentar)."'" : "NULL") . "
        WHERE id = $spule_id
    ");

    // Bewegung in Historie schreiben, wenn sich der Bestand geändert hat
    $diff = $verbleibendes - $alt_verbleibend;
    if ($diff != 0) {
        $sql = "INSERT INTO lagerbewegungen
		(spule_id, filament_id, user_id, bewegungsart, menge)
        VALUES (?, ?, ?, 'korrektur', ?)";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("iiid", $spule_id, $spule['filament_id'], $user_id, $diff);
		$stmt->execute();
	}
    $_SESSION['success'] = '<div class="info-box">
            <i class="fa-solid fa-circle-info"></i>
            <span>Spule erfolgreich geändert! '. $bewegungsart . '</span>
        </div>';
    header("Location: index.php?site=spulen");
    exit;
	
    // Daten neu laden
    $res = $conn->query("SELECT s.*, f.name_des_filaments, h.hr_name, h.hr_leerspule, m.name AS material
                         FROM spulenlager s
                         LEFT JOIN filamente f ON s.filament_id = f.id
                         LEFT JOIN hersteller h ON f.hersteller_id = h.id
                         LEFT JOIN materialien m ON f.material = m.id
                         WHERE s.id = $spule_id");
    $spule = $res->fetch_assoc();
}
?>

<section class="card">
    <div class="card-header">
        <h2>Spule bearbeiten (Inventur)</h2>
        <a href="index.php?site=spulen" class="btn-primary">← Zurück zum Lager</a>
    </div>

    <form method="post" class="form">
        <div class="form-group">
            <label>Filament</label>
            <input type="text" value="<?= htmlspecialchars(
                ($spule['hr_name'] ?? '') . " - " . 
                ($spule['name_des_filaments'] ?? '') . " (" . 
                ($spule['material'] ?? '') . ")"
            ) ?>" disabled>
        </div>

        <div class="form-group">
            <label for="preis">Preis (€)</label>
            <input type="number" step="0.01" name="preis" id="preis" 
                   value="<?= htmlspecialchars($spule['preis'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="aktuelles_gewicht">Aktuelles Gewicht (g)</label>
            <input type="number" step="0.01" name="aktuelles_gewicht" id="aktuelles_gewicht"
                   value="<?= htmlspecialchars(($spule['verbleibendes_filament'] ?? 0) + ($leerspule ?? 0)) ?>"
                   min="<?= $leerspule ?? 0 ?>" max="1500" required>
            <small>
                <?= $leerspule ? "Gewicht inklusive Leerspule $leerspule g" : "Leerspule nicht hinterlegt" ?>
            </small>
        </div>

        <div class="form-group">
            <label for="lagerort">Lagerort</label>
            <input type="text" name="lagerort" id="lagerort" 
                   value="<?= htmlspecialchars($spule['lagerort'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="chargennummer">Chargennummer</label>
            <input type="text" name="chargennummer" id="chargennummer" 
                   value="<?= htmlspecialchars($spule['chargennummer'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="kommentar">Kommentar</label>
            <textarea name="kommentar" id="kommentar" rows="3"><?= htmlspecialchars($spule['kommentar'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Spule aktualisieren</button>
        </div>
    </form>
</section>
