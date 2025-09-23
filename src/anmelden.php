<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $passwort = $_POST['passwort'];

    $stmt = $conn->prepare("SELECT * FROM user WHERE Name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($passwort, $row['Passwort'])) {
            // Login erfolgreich → Session setzen
			$_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['Name'];
            $_SESSION['rolle'] = $row['rolle']; // ✅ Rolle mit speichern
            $_SESSION['success'] = '<div class="info-box"><i class="fa-solid fa-circle-check"></i> Willkommen ' . htmlspecialchars($row['Name']) . '!</div>';
            header("Location: index.php?site=start");
            exit;
        } else {
            $_SESSION['error'] = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> Passwort falsch.</div>';
        }
    } else {
        $_SESSION['error'] = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> Benutzer nicht gefunden.</div>';
    }
    header("Location: index.php?site=anmelden");
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Anmelden</h2>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="post" class="form" style="width:20%;">
		<div class="form-group">
			<label for="name">Benutzername</label>
			<input type="text" id="name" name="name" required>
		</div>

		<div class="form-group">
			<label for="passwort">Passwort</label>
			<input type="password" id="passwort" name="passwort" required>
		</div>
		
        <button type="submit" class="btn-primary">Anmelden</button>
    </form>
</section>

