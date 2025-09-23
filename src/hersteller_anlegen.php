<?php

require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
	if (!validate_form_token($_POST['form_token'] ?? '')) {
		$_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Das Formular wurde bereits verarbeitet oder ist ungültig.</span>
            </div>';
        header("Location: index.php?site=hersteller");
        exit;
	}

    // Werte auslesen
    $name      = trim($_POST['hr_name']);
    $gewicht   = (int)$_POST['hr_leerspule'];
    $kommentar = trim($_POST['hr_kommentar']);

    $stmt = $conn->prepare("INSERT INTO hersteller (hr_name, hr_leerspule, hr_kommentar) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $name, $gewicht, $kommentar);
	$stmt->execute();
	$_SESSION['success'] = '
	<div class="info-box">
		<i class="fa-solid fa-circle-info"></i>
		<span>Hersteller erfolgreich angelegt!</span>
	</div>';
	header("Location: index.php?site=hersteller");
	exit;
} 
?>

<section class="form-section">
  <h2>Neuen Hersteller anlegen</h2>
  <form method="post" action="index.php?site=hersteller_anlegen">

    <div class="form-group">
      <label for="hr_name">Herstellername</label>
      <input type="text" id="hr_name" name="hr_name" placeholder="Herstellername eingeben" required>
    </div>

    <div class="form-group">
      <label for="hr_leerspule">Gewicht der Leerspule (g)</label>
      <input type="number" id="hr_leerspule" name="hr_leerspule" placeholder="Gewicht der Leerspule eingeben" value="0">
    </div>

    <div class="form-group">
      <label for="hr_kommentar">Kommentar</label>
      <textarea rows="3" cols="40" id="hr_kommentar" name="hr_kommentar" placeholder="Kommentar verfassen"></textarea>
    </div>

    <!-- Token -->
    <input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">

    <!-- Buttons inline -->
    <div style="display:flex; justify-content:flex-start; gap:20px; margin-top:15px;">
      <button type="submit" name="submit" class="btn-submit">Eintragen</button>
      <a href="index.php?site=hersteller" class="btn-submit" style="text-decoration:none; display:inline-block;">Abbrechen</a>
    </div>
  </form>
</section>
