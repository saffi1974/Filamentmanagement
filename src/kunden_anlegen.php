<?php 

require_once "form_token.php";

require_once "auth.php";
require_role(['superuser','admin','user']);

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
	if (!validate_form_token($_POST['form_token'] ?? '')) {
		$_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Das Formular wurde bereits verarbeitet oder ist ungültig.</span>
            </div>';
        header("Location: index.php?site=kunden");
        exit;
	}

    $firma           = trim($_POST['firma']);
    $ansprechpartner = trim($_POST['ansprechpartner']);
    $telefon         = trim($_POST['telefon']);
    $strasse         = trim($_POST['strasse']);
    $plz             = trim($_POST['plz']);
    $ort             = trim($_POST['ort']);
    $versandart      = $_POST['versandart'];

    if ($firma === '' && $ansprechpartner === '') {
        $_SESSION['error'] = "Bitte mindestens Firma oder Ansprechpartner angeben.";
    } else {
        $sql = "INSERT INTO kunden (firma, ansprechpartner, telefon, strasse, plz, ort, versandart) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $firma, $ansprechpartner, $telefon, $strasse, $plz, $ort, $versandart);
        $stmt->execute();

		$_SESSION['success'] = '
		<div class="info-box">
			<i class="fa-solid fa-circle-info"></i>
			<span>Kunden erfolgreich angelegt!</span>
		</div>';
		header("Location: index.php?site=kunden");
		exit;
    }
}
?>

<section class="card">
    <div class="card-header">
        <h2>Kunde anlegen</h2>
        <a href="index.php?site=kunden" class="btn-secondary">← Zurück zur Kundenliste</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="post" class="form">
        <div class="form-group">
            <label for="firma">Firma</label>
            <input type="text" id="firma" name="firma">
        </div>

        <div class="form-group">
            <label for="ansprechpartner">Ansprechpartner</label>
            <input type="text" id="ansprechpartner" name="ansprechpartner">
        </div>

        <div class="form-group">
            <label for="telefon">Telefon</label>
            <input type="text" id="telefon" name="telefon">
        </div>

        <div class="form-group">
            <label for="strasse">Straße</label>
            <input type="text" id="strasse" name="strasse">
        </div>

        <div class="form-group">
            <label for="plz">PLZ</label>
            <input type="text" id="plz" name="plz">
        </div>

        <div class="form-group">
            <label for="ort">Ort</label>
            <input type="text" id="ort" name="ort">
        </div>

        <div class="form-group">
            <label for="versandart">Versandart</label>
            <select id="versandart" name="versandart" required>
                <option value="Versand">Versand</option>
                <option value="Abholung" selected>Abholung</option>
            </select>
        </div>
		
		<!-- Token -->
		<input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">

        <button type="submit" name="submit" class="btn-primary">Speichern</button>
    </form>
</section>
