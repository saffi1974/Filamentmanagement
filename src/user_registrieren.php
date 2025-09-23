<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "db.php";

// Nur Admin oder Superuser dürfen neue User anlegen
if (
    !isset($_SESSION['username']) || 
    !in_array($_SESSION['rolle'], ['superuser', 'admin'])
) {
    die('<div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Sie haben keine ausreichenden Benutzerrechte dafür!</span>
				<button type="button" class="btn-primary" onclick="history.back()">← Zurück</button>
            </div>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $passwort = $_POST['passwort'];
    $rolle = $_POST['rolle'];

    if ($name && $passwort && $rolle) {
        // Passwort hashen
        $hash = password_hash($passwort, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO user (Name, Passwort, rolle) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $hash, $rolle);

        if ($stmt->execute()) {
            $_SESSION['success'] = '<div class="info-box"><i class="fa-solid fa-circle-check"></i> User erfolgreich angelegt.</div>';
        } else {
            $_SESSION['error'] = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> Fehler: ' . htmlspecialchars($stmt->error) . '</div>';
        }

        header("Location: index.php?site=user_registrieren");
        exit;
    } else {
        $_SESSION['error'] = "Bitte alle Felder ausfüllen.";
    }
}
?>

<section class="card">
    <div class="card-header">
        <h2>Neuen Benutzer anlegen</h2>
        <a href="index.php?site=start" class="btn-primary">← Zurück</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="post" style="width:20%;">
		<div class="form-group">
        <label for="name">Benutzername</label>
        <input type="text" id="name" name="name" required>

        <label for="passwort">Passwort</label>
        <input type="password" id="passwort" name="passwort" required>
		</div>
		<div class="form-group">
        <label for="rolle">Rolle</label>
        <select id="rolle" name="rolle" required>
            <option value="user">User</option>
            <option value="readonly">Readonly</option>
            <option value="admin">Admin</option>
            <option value="superuser">Superuser</option>
        </select>
		</div>
        <button type="submit" class="btn-primary">Benutzer anlegen</button>
    </form>
</section>
