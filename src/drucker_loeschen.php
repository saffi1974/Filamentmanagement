<?php
require_once "auth.php";
require_role(['superuser','admin']);

// ID aus URL
$drucker_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($drucker_id <= 0) {
    die("❌ Ungültige Drucker-ID.");
}

// Drucker holen für Bestätigung
$stmt = $conn->prepare("SELECT * FROM drucker WHERE id = ?");
$stmt->bind_param("i", $drucker_id);
$stmt->execute();
$res = $stmt->get_result();
$drucker = $res->fetch_assoc();
$stmt->close();

if (!$drucker) {
    die("❌ Drucker nicht gefunden.");
}

// Löschen bestätigen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === "ja") {
        $stmt = $conn->prepare("DELETE FROM drucker WHERE id = ?");
        $stmt->bind_param("i", $drucker_id);
        $stmt->execute();
        $stmt->close();

		$_SESSION['success'] = '<div class="info-box">
				<i class="fa-solid fa-circle-info"></i>
				<span>Drucker <strong>' . htmlspecialchars($drucker['name']) . ' </strong> erfolgreich gelöscht!</span>
			</div>';
		header("Location: index.php?site=drucker");
		exit;
    } else {
        header("Location: index.php?site=drucker");
        exit;
    }
}
?>

<section class="form-section">
    <h2>Drucker löschen</h2>
	<p>Möchten Sie den Drucker <strong><?= htmlspecialchars($drucker['name']) ?></strong> (Hersteller: <?= htmlspecialchars($drucker['hersteller']) ?>) wirklich löschen?</p>
    <div style="display:flex; justify-content:flex-start; gap:20px; margin-top:15px;">
    <form method="post" style="display:flex; gap:10px;">
        <button type="submit" name="confirm" value="ja" class="btn-action delete">Ja, löschen</button>
        <a href="index.php?site=drucker" class="btn-primary">Abbrechen</a>
    </form>
</section>
