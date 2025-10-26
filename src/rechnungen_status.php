<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rechnung_id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;

if (!$rechnung_id || !$status) {
    $_SESSION['error'] = '
	    <div class="info-box">
            <i class="fa-solid fa-circle-check"></i>
            <span>Ungültiger Aufruf.</span>
        </div>';
    header("Location: index.php?site=rechnungen");
    exit;
}

// Nur erlaubte Statuswerte
$allowed = ['offen','bezahlt','storniert'];
if (!in_array($status, $allowed)) {
    $_SESSION['error'] = '
	    <div class="info-box">
            <i class="fa-solid fa-circle-check"></i>
            <span>Ungültiger Status.</span>
        </div>';
    header("Location: index.php?site=rechnungen");
    exit;
}

// Update durchführen
$sql = "UPDATE rechnungen SET status=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $rechnung_id);
$stmt->execute();

$_SESSION['success'] = '
	    <div class="info-box">
            <i class="fa-solid fa-circle-check"></i>
            <span>Rechnung erfolgreich auf '. $status .' gesetzt.</span>
        </div>';
header("Location: index.php?site=rechnungen_details&id=".$rechnung_id);
exit;
