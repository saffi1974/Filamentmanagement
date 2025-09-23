<?php
require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
	if (!validate_form_token($_POST['form_token'] ?? '')) {
		$_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Das Formular wurde bereits verarbeitet oder ist ungültig.</span>
            </div>';
        header("Location: index.php?site=drucker");
        exit;
	}
// Formular absenden

    $name = trim($_POST['name']);
    $hersteller = trim($_POST['hersteller']);
    $stromverbrauch = (float)$_POST['stromverbrauch'];
    $kosten_kwh = (float)$_POST['kosten_kwh'];
    $kommentar = trim($_POST['kommentar'] ?? '');

    $stmt = $conn->prepare("
        INSERT INTO drucker (name, hersteller, stromverbrauch_watt, kosten_pro_kwh, kommentar)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssdds", $name, $hersteller, $stromverbrauch, $kosten_kwh, $kommentar);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = '<div class="info-box">
            <i class="fa-solid fa-circle-info"></i>
            <span>Drucker <strong>' . htmlspecialchars($name) . ' </strong> erfolgreich eingetragen!</span>
        </div>';
    header("Location: index.php?site=drucker");
    exit;}
?>

<section class="card">
    <div class="card-header">
        <h2>Drucker hinzufügen</h2>
        <a href="index.php?site=drucker" class="btn-primary">← Zurück zur Druckerliste</a>
    </div>

    <form method="post" class="form">
        <div class="form-group">
            <label for="name">Druckername</label>
            <input type="text" name="name" id="name" required>
        </div>

        <div class="form-group">
            <label for="hersteller">Hersteller</label>
            <input type="text" name="hersteller" id="hersteller" required>
        </div>

        <div class="form-group">
            <label for="stromverbrauch">Stromverbrauch (Watt)</label>
            <input type="number" step="1" min="1" name="stromverbrauch" id="stromverbrauch" required>
        </div>

        <div class="form-group">
            <label for="kosten_kwh">Kosten pro kWh (€)</label>
            <input type="number" step="0.01" min="0" name="kosten_kwh" id="kosten_kwh" required>
        </div>

        <div class="form-group">
            <label for="kommentar">Kommentar</label>
            <textarea name="kommentar" id="kommentar" rows="3"></textarea>
        </div>

        <div class="form-actions">
			<!-- Token -->
			<input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">

            <button type="submit" name="submit" class="btn-primary">Drucker speichern</button>
        </div>
    </form>
</section>
