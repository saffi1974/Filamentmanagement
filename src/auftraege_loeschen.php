<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "auth.php";
require_role(['superuser','admin']);

$auftrag_id = $_GET['id'] ?? null;
if (!$auftrag_id) {
    die("Kein Auftrag gewählt.");
}

// Sicherheitsabfrage: existiert der Auftrag?
$sql = "SELECT id, name FROM auftraege WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auftrag_id);
$stmt->execute();
$res = $stmt->get_result();
$auftrag = $res->fetch_assoc();

if (!$auftrag) {
    $_SESSION['error'] = "Auftrag nicht gefunden.";
    header("Location: index.php?site=auftraege");
    exit;
}

// Wenn bestätigt: löschen
if (isset($_POST['bestaetigen'])) {
    $sql = "DELETE FROM auftraege WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $auftrag_id);
    $stmt->execute();

    $_SESSION['success'] = '<div class="info-box">
            <i class="fa-solid fa-circle-info"></i>
            <span>Auftrag wurde erfolgreich gelöscht.</span>
        </div>';
    header("Location: index.php?site=auftraege");
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Auftrag löschen</h2>
        <a href="index.php?site=auftraege" class="btn-primary">← Zurück zur Auftragsliste</a>
    </div>

    <p>Soll der Auftrag <strong><?= htmlspecialchars($auftrag['name']) ?></strong> wirklich gelöscht werden?</p>

    <form method="post">
        <button type="submit" name="bestaetigen" class="btn-action delete">Ja, löschen</button>
        <a href="index.php?site=auftraege" class="btn-primary">Abbrechen</a>
    </form>
</section>
