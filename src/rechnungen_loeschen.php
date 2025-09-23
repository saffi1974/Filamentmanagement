<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "auth.php";
require_role(['superuser','admin']);

require_once "form_token.php";

// Wenn POST mit Löschbestätigung kommt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rechnung_id'])) {
    $rechnung_id = (int)$_POST['rechnung_id'];

    // Token prüfen
    if (!validate_form_token($_POST['form_token'] ?? '')) {
        $_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Ungültige oder doppelte Anfrage.</span>
            </div>';
        header("Location: index.php?site=rechnungen");
        exit;
    }

    // Positionen zuerst löschen
    $sql = "DELETE FROM rechnungspositionen WHERE rechnung_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $rechnung_id);
    $stmt->execute();

    // Rechnung löschen
    $sql = "DELETE FROM rechnungen WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $rechnung_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-check"></i>
                <span>Rechnung erfolgreich gelöscht.</span>
            </div>';
    } else {
        $_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Fehler beim Löschen: ' . htmlspecialchars($stmt->error) . '</span>
            </div>';
    }

    header("Location: index.php?site=rechnungen");
    exit;
}

// Wenn GET (z. B. klick auf „löschen“) → Bestätigungsformular anzeigen
$rechnung_id = (int)($_GET['id'] ?? 0);
if ($rechnung_id <= 0) {
    $_SESSION['error'] = "Ungültige Rechnungs-ID.";
    header("Location: index.php?site=rechnungen");
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Rechnung löschen</h2>
        <a href="index.php?site=rechnungen" class="btn-primary">← Zurück</a>
    </div>

    <p>Soll die Rechnung <strong>#<?= $rechnung_id ?></strong> wirklich gelöscht werden? <br>
    Alle zugehörigen Positionen werden ebenfalls entfernt.</p>

    <form method="post">
        <input type="hidden" name="rechnung_id" value="<?= $rechnung_id ?>">
        <input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">
        <button type="submit" class="btn-action delete">Rechnung löschen</button>
        <a href="index.php?site=rechnungen" class="btn-primary">Abbrechen</a>
    </form>
</section>
