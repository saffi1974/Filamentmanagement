<?php
require_once "auth.php";
require_role(['superuser','admin']);

// Prüfen, ob ID übergeben wurde
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: index.php?site=spulenlager");
    exit;
}

$id = (int) $_GET['id'];

// Spulendaten abrufen für Anzeige
$res = $conn->query("
    SELECT s.id, f.name_des_filaments, h.hr_name, m.name AS material, s.verbleibendes_filament
    FROM spulenlager s
    LEFT JOIN filamente f ON s.filament_id = f.id
    LEFT JOIN hersteller h ON f.hersteller_id = h.id
    LEFT JOIN materialien m ON f.material = m.id
    WHERE s.id = $id
");
$spule = $res->fetch_assoc();

// Prüfen, ob Formular abgesendet wurde
if(isset($_POST['confirm'])){
    $stmt = $conn->prepare("DELETE FROM spulenlager WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['success'] = '<div class="info-box">
            <i class="fa-solid fa-circle-info"></i>
            <span>Spule erfolgreich gelöscht!</span>
        </div>';
    header("Location: index.php?site=spulenlager");
    exit;
}

if(isset($_POST['cancel'])){
    header("Location: index.php?site=spulenlager");
    exit;
}
?>

<section class="form-section">
    <h2>Spule löschen</h2>
    <p>Sind Sie sicher, dass Sie die Spule 
        <strong><?php echo htmlspecialchars($spule['hr_name'] . " - " . $spule['name_des_filaments'] . " (" . $spule['material'] . ")"); ?></strong> löschen möchten?
    </p>

    <div style="display:flex; justify-content:flex-start; gap:20px; margin-top:15px;">
        <form method="post" style="display:flex; gap:10px;">
            <button type="submit" name="confirm" value="ja" class="btn-action delete">Ja, löschen</button>
            <a href="index.php?site=spulen" class="btn-primary">Abbrechen</a>
        </form>
    </div>
</section>
