<?php
// ID aus URL
$drucker_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($drucker_id <= 0) {
    die("❌ Ungültige Drucker-ID.");
}

// Druckerdaten laden
$stmt = $conn->prepare("SELECT * FROM drucker WHERE id = ?");
$stmt->bind_param("i", $drucker_id);
$stmt->execute();
$res = $stmt->get_result();
$drucker = $res->fetch_assoc();
$stmt->close();

if (!$drucker) {
    die("❌ Drucker nicht gefunden.");
}

// Formular absenden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $hersteller = trim($_POST['hersteller']);
    $stromverbrauch = (float)$_POST['stromverbrauch'];
    $kosten_kwh = (float)$_POST['kosten_kwh'];
    $kommentar = trim($_POST['kommentar'] ?? '');

    $stmt = $conn->prepare("
        UPDATE drucker
        SET name = ?, hersteller = ?, stromverbrauch_watt = ?, kosten_pro_kwh = ?, kommentar = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssddsi", $name, $hersteller, $stromverbrauch, $kosten_kwh, $kommentar, $drucker_id);
    $stmt->execute();
    $stmt->close();

    // Neu laden
    $stmt = $conn->prepare("SELECT * FROM drucker WHERE id = ?");
    $stmt->bind_param("i", $drucker_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $drucker = $res->fetch_assoc();
    $stmt->close();

    $_SESSION['success'] = '<div class="info-box">
            <i class="fa-solid fa-circle-info"></i>
            <span>Drucker <strong>' . htmlspecialchars($name) . ' </strong> erfolgreich bearbeitet!</span>
        </div>';
    header("Location: index.php?site=drucker");
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Drucker bearbeiten</h2>
        <a href="index.php?site=drucker" class="btn-primary">← Zurück zur Druckerliste</a>
    </div>

    <form method="post" class="form">
        <div class="form-group">
            <label for="name">Druckername</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($drucker['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="hersteller">Hersteller</label>
            <input type="text" name="hersteller" id="hersteller" value="<?= htmlspecialchars($drucker['hersteller']) ?>" required>
        </div>

        <div class="form-group">
            <label for="stromverbrauch">Stromverbrauch (Watt)</label>
            <input type="number" step="1" min="1" name="stromverbrauch" id="stromverbrauch" value="<?= htmlspecialchars($drucker['stromverbrauch_watt']) ?>" required>
        </div>

        <div class="form-group">
            <label for="kosten_kwh">Kosten pro kWh (€)</label>
            <input type="number" step="0.01" min="0" name="kosten_kwh" id="kosten_kwh" value="<?= htmlspecialchars($drucker['kosten_pro_kwh']) ?>" required>
        </div>

        <div class="form-group">
            <label for="kommentar">Kommentar</label>
            <textarea name="kommentar" id="kommentar" rows="3"><?= htmlspecialchars($drucker['kommentar']) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Änderungen speichern</button>
        </div>
    </form>
</section>
