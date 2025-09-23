<?php
require_once "auth.php";
require_role(['superuser','admin']);

// Prüfen, ob ID übergeben wurde
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: index.php?site=hersteller");
    exit;
}

$id = (int) $_GET['id'];

// Herstellerdaten abrufen für Anzeige
$res = $conn->query("SELECT * FROM hersteller WHERE id=$id");
$h = $res->fetch_assoc();

// Prüfen, ob Formular abgesendet wurde
if(isset($_POST['confirm'])){
    $stmt = $conn->prepare("DELETE FROM hersteller WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
	$_SESSION['success'] = '
	<div class="info-box">
		<i class="fa-solid fa-circle-info"></i>
		<span>Hersteller erfolgreich gelöscht!</span>
	</div>';
    header("Location: index.php?site=hersteller");
    exit;
}

if(isset($_POST['cancel'])){
    header("Location: index.php?site=hersteller");
    exit;
}
?>

<section class="form-section">
    <h2>Hersteller löschen</h2>
    <p>Sind Sie sicher, dass Sie den Hersteller <strong><?php echo htmlspecialchars($h['hr_name']); ?></strong> löschen möchten?</p>
    <div style="display:flex; justify-content:flex-start; gap:20px; margin-top:15px;">
    <form method="post" style="display:flex; gap:10px;">
        <button type="submit" name="confirm" value="ja" class="btn-action delete">Ja, löschen</button>
        <a href="index.php?site=hersteller" class="btn-primary">Abbrechen</a>
    </form>
	</div>
</section>
