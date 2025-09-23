<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$kunde_id = $_GET['id'] ?? null;
if (!$kunde_id) {
    die("Kein Kunde gewählt.");
}

// Kunde laden
$sql = "SELECT * FROM kunden WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kunde_id);
$stmt->execute();
$res = $stmt->get_result();
$kunde = $res->fetch_assoc();
if (!$kunde) {
    die("Kunde nicht gefunden.");
}

// Formular absenden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $sql = "UPDATE kunden 
                SET firma = ?, ansprechpartner = ?, telefon = ?, strasse = ?, plz = ?, ort = ?, versandart = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $firma, $ansprechpartner, $telefon, $strasse, $plz, $ort, $versandart, $kunde_id);
        $stmt->execute();
		$_SESSION['success'] = '
		<div class="info-box">
			<i class="fa-solid fa-circle-info"></i>
			<span>Kunden erfolgreich aktualisiert!</span>
		</div>';
        header("Location: index.php?site=kunden");
        exit;
    }
}
?>

<section class="card">
    <div class="card-header">
        <h2>Kunde bearbeiten</h2>
        <a href="index.php?site=kunden" class="btn-secondary">← Zurück zur Kundenliste</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="post" class="form">
        <div class="form-group">
            <label for="firma">Firma</label>
            <input type="text" id="firma" name="firma" value="<?= htmlspecialchars($kunde['firma'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="ansprechpartner">Ansprechpartner</label>
            <input type="text" id="ansprechpartner" name="ansprechpartner" value="<?= htmlspecialchars($kunde['ansprechpartner'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="telefon">Telefon</label>
            <input type="text" id="telefon" name="telefon" value="<?= htmlspecialchars($kunde['telefon'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="strasse">Straße</label>
            <input type="text" id="strasse" name="strasse" value="<?= htmlspecialchars($kunde['strasse'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="plz">PLZ</label>
            <input type="text" id="plz" name="plz" value="<?= htmlspecialchars($kunde['plz'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="ort">Ort</label>
            <input type="text" id="ort" name="ort" value="<?= htmlspecialchars($kunde['ort'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="versandart">Versandart</label>
            <select id="versandart" name="versandart" required>
                <option value="Versand" <?= $kunde['versandart']=='Versand'?'selected':'' ?>>Versand</option>
                <option value="Abholung" <?= $kunde['versandart']=='Abholung'?'selected':'' ?>>Abholung</option>
            </select>
        </div>

        <button type="submit" class="btn-primary">Speichern</button>
    </form>
</section>
