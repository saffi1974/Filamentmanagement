<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "auth.php";
require_role(['superuser','admin']);
$projekt_id = $_GET['id'] ?? null;
if (!$projekt_id) {
    die("Kein Projekt gewählt.");
}

// Projekt prüfen
$sql = "SELECT projektname FROM projekte WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $projekt_id);
$stmt->execute();
$res = $stmt->get_result();
$projekt = $res->fetch_assoc();

if (!$projekt) {
    $_SESSION['error'] = "Projekt nicht gefunden.";
    header("Location: index.php?site=projekte");
    exit;
}

// Wenn bestätigt: löschen
if (isset($_POST['bestaetigen'])) {
    // Falls ON DELETE CASCADE nicht gesetzt ist:
    $sql = "DELETE FROM projekt_filamente WHERE projekt_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projekt_id);
    $stmt->execute();

    // Projekt löschen
    $sql = "DELETE FROM projekte WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projekt_id);
    $stmt->execute();

	$_SESSION['success'] = '
	<div class="info-box">
		<i class="fa-solid fa-circle-info"></i>
		<span>Projekt <strong>' . htmlspecialchars($projektname) . '</strong> erfolgreich gelöscht!</span>
	</div>';
	header("Location: index.php?site=projekte");
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Projekt löschen</h2>
        <a href="index.php?site=projekte" class="btn-primary">← Zurück zur Projektliste</a>
    </div>

    <p>Soll das Projekt <strong><?= htmlspecialchars($projekt['projektname']) ?></strong> wirklich gelöscht werden?</p>

    <form method="post">
        <button type="submit" name="bestaetigen" class="btn-action delete">
            <i class="fa-solid fa-trash"></i> Ja, löschen
        </button>
        <a href="index.php?site=projekte" class="btn-primary">Abbrechen</a>
    </form>
</section>
