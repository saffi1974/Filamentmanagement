<?php
require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------
// Betriebskosten anlegen
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!validate_form_token($_POST['form_token'] ?? '')) {
        $_SESSION['error'] = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> Ungültiges Formular.</div>';
        header("Location: index.php?site=betriebskosten");
        exit;
    }

    $kostenart = trim($_POST['kostenart']);
    $beschreibung = $_POST['beschreibung'] ?? null;
    $standard_betrag = (float)$_POST['standard_betrag'];
    $einheit = $_POST['einheit'];

    $stmt = $conn->prepare("INSERT INTO betriebskosten (kostenart, beschreibung, standard_betrag, einheit) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $kostenart, $beschreibung, $standard_betrag, $einheit);
    $stmt->execute();

    $_SESSION['success'] = '<div class="info-box"><i class="fa-solid fa-circle-check"></i> Betriebskostenposition hinzugefügt.</div>';
    header("Location: index.php?site=betriebskosten");
    exit;
}

// -----------------------------
// Betriebskosten bearbeiten
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    if (!validate_form_token($_POST['form_token'] ?? '')) {
        $_SESSION['error'] = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> Ungültiges Formular.</div>';
        header("Location: index.php?site=betriebskosten");
        exit;
    }

    $id = (int)$_POST['edit_id'];
    $kostenart = trim($_POST['kostenart']);
    $beschreibung = $_POST['beschreibung'] ?? null;
    $standard_betrag = (float)$_POST['standard_betrag'];
    $einheit = $_POST['einheit'];

    $stmt = $conn->prepare("UPDATE betriebskosten 
                            SET kostenart=?, beschreibung=?, standard_betrag=?, einheit=? 
                            WHERE id=?");
    $stmt->bind_param("ssdsi", $kostenart, $beschreibung, $standard_betrag, $einheit, $id);
    $stmt->execute();

    $_SESSION['success'] = '<div class="info-box"><i class="fa-solid fa-circle-check"></i> Betriebskostenposition aktualisiert.</div>';
    header("Location: index.php?site=betriebskosten");
    exit;
}

// -----------------------------
// Betriebskosten löschen
// -----------------------------
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM betriebskosten WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $_SESSION['success'] = '<div class="info-box"><i class="fa-solid fa-circle-check"></i> Betriebskostenposition gelöscht.</div>';
    header("Location: index.php?site=betriebskosten");
    exit;
}

// -----------------------------
// Betriebskosten laden
// -----------------------------
$res = $conn->query("SELECT * FROM betriebskosten ORDER BY kostenart");
$kosten = $res->fetch_all(MYSQLI_ASSOC);

// Wenn eine Bearbeitung angefordert ist
$edit_item = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    foreach ($kosten as $k) {
        if ($k['id'] == $id) {
            $edit_item = $k;
            break;
        }
    }
}
?>

<section class="card">
    <div class="card-header">
        <h2>Betriebskosten – Stammdaten</h2>
        <a href="index.php?site=start" class="btn-primary">← Zurück</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if ($edit_item): ?>
        <h3>Betriebskostenposition bearbeiten</h3>
        <form method="post" class="form">
            <input type="hidden" name="edit_id" value="<?= $edit_item['id'] ?>">

            <div class="form-group">
                <label for="kostenart">Kostenart</label>
                <input type="text" id="kostenart" name="kostenart" value="<?= htmlspecialchars($edit_item['kostenart']) ?>" required>
            </div>

            <div class="form-group">
                <label for="beschreibung">Beschreibung</label>
                <textarea id="beschreibung" name="beschreibung" rows="2"><?= htmlspecialchars($edit_item['beschreibung']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="standard_betrag">Standardbetrag (€)</label>
                <input type="number" step="0.01" id="standard_betrag" name="standard_betrag" value="<?= htmlspecialchars($edit_item['standard_betrag']) ?>" required>
            </div>

            <div class="form-group">
                <label for="einheit">Einheit</label>
                <select id="einheit" name="einheit" required>
                    <option value="pauschal" <?= $edit_item['einheit']=='pauschal'?'selected':'' ?>>Pauschal</option>
                    <option value="pro_stunde" <?= $edit_item['einheit']=='pro_stunde'?'selected':'' ?>>Pro Stunde</option>
                    <option value="pro_stueck" <?= $edit_item['einheit']=='pro_stueck'?'selected':'' ?>>Pro Stück</option>
                </select>
            </div>

            <input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">
            <button type="submit" class="btn-primary">💾 Speichern</button>
            <a href="index.php?site=betriebskosten" class="btn-primary">Abbrechen</a>
        </form>
    <?php else: ?>
        <h3>Neue Betriebskostenposition anlegen</h3>
        <form method="post" class="form">
            <div class="form-group">
                <label for="kostenart">Kostenart</label>
                <input type="text" id="kostenart" name="kostenart" required>
            </div>

            <div class="form-group">
                <label for="beschreibung">Beschreibung</label>
                <textarea id="beschreibung" name="beschreibung" rows="2"></textarea>
            </div>

            <div class="form-group">
                <label for="standard_betrag">Standardbetrag (€)</label>
                <input type="number" step="0.01" id="standard_betrag" name="standard_betrag" value="0.00" required>
            </div>

            <div class="form-group">
                <label for="einheit">Einheit</label>
                <select id="einheit" name="einheit" required>
                    <option value="pauschal">Pauschal</option>
                    <option value="pro_stunde">Pro Stunde</option>
                    <option value="pro_stueck">Pro Stück</option>
                </select>
            </div>

            <input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">
            <button type="submit" name="add" class="btn-primary">➕ Anlegen</button>
        </form>
    <?php endif; ?>
</section>

<section class="card">
    <div class="card-header">
        <h3>Vorhandene Betriebskosten</h3>
    </div>

    <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Art</th>
                    <th>Beschreibung</th>
                    <th>Betrag (€)</th>
                    <th>Einheit</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kosten)): ?>
                    <tr><td colspan="5">Noch keine Betriebskosten definiert.</td></tr>
                <?php else: ?>
                    <?php foreach ($kosten as $k): ?>
                        <tr>
                            <td><?= htmlspecialchars($k['kostenart']) ?></td>
                            <td><?= htmlspecialchars($k['beschreibung']) ?></td>
                            <td class="right"><?= number_format($k['standard_betrag'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($k['einheit']) ?></td>
                            <td>
                                <a href="index.php?site=betriebskosten&edit=<?= $k['id'] ?>" class="btn-action edit" title="Bearbeiten">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="index.php?site=betriebskosten&delete=<?= $k['id'] ?>" class="btn-action delete" title="Löschen"
                                   onclick="return confirm('Wirklich löschen?');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
