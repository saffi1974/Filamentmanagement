<?php
require_once "auth.php";
require_role(['superuser','admin']);

// Prüfen, ob ID übergeben wurde
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: index.php?site=filamente");
    exit;
}

$id = (int) $_GET['id'];

// Herstellerdaten abrufen für Anzeige
$res = $conn->query("SELECT * FROM filamente WHERE id=$id");
$h = $res->fetch_assoc();

// Prüfen, ob Formular abgesendet wurde
if(isset($_POST['confirm'])){
    $stmt = $conn->prepare("DELETE FROM filamente WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
	$_SESSION['success'] = '
		<div class="info-box">
			<i class="fa-solid fa-circle-check"></i>
			<span>Filament erfolgreich gelöscht.</span>
		</div>';
    header("Location: index.php?site=filamente");
    exit;
}

if(isset($_POST['cancel'])){
    header("Location: index.php?site=filamente");
    exit;
}
?>

<section class="form-section">
    <h2>Filament löschen</h2>
    <p>Sind Sie sicher, dass Sie das Filament <strong><?php echo htmlspecialchars($h['name_des_filaments']); ?></strong> löschen möchten?</p>
    <div style="display:flex; justify-content:flex-start; gap:20px; margin-top:15px;">
    <form method="post" style="display:flex; gap:10px;">
		<button type="submit" name="confirm" value="ja" class="btn-action delete">Ja, löschen</button>
        <a href="index.php?site=filamente" class="btn-primary">Abbrechen</a>
    </form>
	</div>
</section>