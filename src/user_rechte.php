<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "db.php";
require_once "auth.php";

// Superuser oder Admin darf rein
require_role(['superuser','admin']);

// Alle Benutzer laden
$res = $conn->query("SELECT id, Name, rolle FROM user ORDER BY Name");
$users = $res->fetch_all(MYSQLI_ASSOC);

// Update verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $rolle   = $_POST['rolle'];

    // Prüfen: gültige Rolle?
    $allowedRoles = ['admin','user','readonly'];
    if ($_SESSION['rolle'] === 'superuser') {
        $allowedRoles[] = 'superuser'; // nur Superuser darf Superuser setzen
    }

    if ($user_id > 0 && in_array($rolle, $allowedRoles)) {
        $stmt = $conn->prepare("UPDATE user SET rolle = ? WHERE id = ?");
        $stmt->bind_param("si", $rolle, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = '<div class="info-box"><i class="fa-solid fa-circle-check"></i> Rolle erfolgreich geändert.</div>';
        } else {
            $_SESSION['error'] = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> Fehler: ' . htmlspecialchars($stmt->error) . '</div>';
        }
    } else {
        $_SESSION['error'] = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> Ungültige Rolle oder keine Berechtigung.</div>';
    }

    header("Location: index.php?site=user_rechte");
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Benutzerrechte verwalten</h2>
        <a href="index.php?site=start" class="btn-primary">← Zurück</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
    <?php endif; ?>

    <table class="styled-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Aktuelle Rolle</th>
                <th>Ändern</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['Name']) ?></td>
                <td><?= htmlspecialchars($u['rolle']) ?></td>
                <td>
                    <form method="post" style="display:inline-flex; gap:5px;">
							<input type="hidden" name="user_id" value="<?= $u['id'] ?>">
							<select name="rolle" id="rolle">
								<option value="user" <?= $u['rolle']=='user'?'selected':'' ?>>User</option>
								<option value="readonly" <?= $u['rolle']=='readonly'?'selected':'' ?>>Readonly</option>
								<option value="admin" <?= $u['rolle']=='admin'?'selected':'' ?>>Admin</option>
								<?php if ($_SESSION['rolle'] === 'superuser'): ?>
									<option value="superuser" <?= $u['rolle']=='superuser'?'selected':'' ?>>Superuser</option>
								<?php endif; ?>
							</select>
							<button type="submit" class="btn-primary">Speichern</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
