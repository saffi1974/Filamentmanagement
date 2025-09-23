<?php
require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auftrag_id = $_GET['id'] ?? null;
if (!$auftrag_id) {
    die("Kein Auftrag gewählt.");
}

// Auftrag laden
$sql = "SELECT * FROM auftraege WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auftrag_id);
$stmt->execute();
$auftrag = $stmt->get_result()->fetch_assoc();
if (!$auftrag) {
    die("Auftrag nicht gefunden.");
}

// Kunden laden
$kunden = $conn->query("SELECT id, firma, ansprechpartner FROM kunden ORDER BY firma, ansprechpartner")
               ->fetch_all(MYSQLI_ASSOC);

// -----------------------------
// Auftrag updaten
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_auftrag'])) {
    if (!validate_form_token($_POST['form_token'] ?? '')) {
        $_SESSION['error'] = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> Ungültiges Formular.</div>';
        header("Location: index.php?site=auftraege");
        exit;
    }

    $kunde_id = (int)$_POST['kunde_id'];
    $name     = trim($_POST['name']);
    $datum    = $_POST['datum'];
    $anzahl   = (int)$_POST['anzahl'];
    $status   = $_POST['status'];

    $sql = "UPDATE auftraege SET kunde_id=?, name=?, datum=?, anzahl=?, status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issisi", $kunde_id, $name, $datum, $anzahl, $status, $auftrag_id);
    $stmt->execute();

    $_SESSION['success'] = '<div class="info-box"><i class="fa-solid fa-circle-check"></i> Auftrag aktualisiert.</div>';
    header("Location: index.php?site=auftraege");
    exit;
}

// -----------------------------
// Positionen laden
// -----------------------------
$sql = "SELECT af.id, af.menge_geplant, af.menge_gebucht, af.status,
               f.name_des_filaments, h.hr_name, m.name AS material_name
        FROM auftrag_filamente af
        JOIN filamente f ON af.filament_id = f.id
        JOIN hersteller h ON f.hersteller_id = h.id
        JOIN materialien m ON f.material = m.id
        WHERE af.auftrag_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auftrag_id);
$stmt->execute();
$auftrag_pos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<section class="card">
    <div class="card-header">
        <h2>Auftrag bearbeiten</h2>
        <a href="index.php?site=auftraege" class="btn-primary">← Zurück zur Liste</a>
    </div>

    <form method="post" class="form">
        <div class="form-group">
            <label for="kunde_id">Kunde</label>
            <select name="kunde_id" id="kunde_id" required>
                <?php foreach ($kunden as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= ($auftrag['kunde_id'] == $k['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['firma'] ?: $k['ansprechpartner']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="name">Auftragsname</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($auftrag['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="datum">Datum</label>
            <input type="date" id="datum" name="datum" value="<?= htmlspecialchars($auftrag['datum']) ?>" required>
        </div>

        <div class="form-group">
            <label for="anzahl">Anzahl</label>
            <input type="number" id="anzahl" name="anzahl" value="<?= htmlspecialchars($auftrag['anzahl']) ?>" min="1" required>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" required>
                <option value="offen" <?= $auftrag['status']=='offen'?'selected':'' ?>>Offen</option>
                <option value="in_bearbeitung" <?= $auftrag['status']=='in_bearbeitung'?'selected':'' ?>>In Bearbeitung</option>
                <option value="fertig" <?= $auftrag['status']=='fertig'?'selected':'' ?>>Fertig</option>
            </select>
        </div>

        <input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">
        <button type="submit" name="update_auftrag" class="btn-primary">💾 Änderungen speichern</button>
    </form>
</section>

<section class="card">
    <div class="card-header">
        <h3>Positionen zum Auftrag</h3>
    </div>

    <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Filament</th>
                    <th>Menge geplant (g)</th>
                    <th>Menge gebucht (g)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($auftrag_pos)): ?>
                    <tr><td colspan="4">Keine Positionen vorhanden.</td></tr>
                <?php else: ?>
                    <?php foreach ($auftrag_pos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['hr_name']." | ".$p['name_des_filaments']." | ".$p['material_name']) ?></td>
                            <td class="right"><?= number_format($p['menge_geplant'], 2, ',', '.') ?></td>
                            <td class="right"><?= number_format($p['menge_gebucht'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($p['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
