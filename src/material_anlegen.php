<?php

require_once "form_token.php";

require_once "auth.php";
require_role(['superuser','admin','user']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
	if (!validate_form_token($_POST['form_token'] ?? '')) {
		$_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Das Formular wurde bereits verarbeitet oder ist ungültig.</span>
            </div>';
        header("Location: index.php?site=material");
        exit;
	}

    // Werte auslesen
    $name = $_POST['name'];
	$kommentar = $_POST['kommentar'];
    $stmt = $conn->prepare("INSERT INTO materialien (name, kommentar) VALUES (?, ?)");
    $stmt->bind_param("ss",$name, $kommentar);
    $stmt->execute();
	$_SESSION['success'] = '
	<div class="info-box">
		<i class="fa-solid fa-circle-info"></i>
		<span>Material erfolgreich angelegt!</span>
	</div>';
	header("Location: index.php?site=material");
	exit;
} 

?>
<section class="form-section">
  <h2>Neues Material anlegen</h2>
  <form method="post">
    <div class="form-group">
      <label for="name">Materialname</label>
      <input type="text" id="name" name="name" placeholder="Materialname eingeben (bspw. PLA+)" required>
    </div>

    <div class="form-group">
      <label for="kommentar">Kommentar</label>
      <textarea rows="3" cols="40" id="kommentar" name="kommentar" placeholder="Kommentar verfassen"></textarea>
    </div>

    <div style="display:flex; justify-content:flex-start; gap:20px; margin-top:15px;">
		<!-- Token -->
		<input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">
		<button type="submit" name="submit" class="btn-submit">Eintragen</button>
		<a href="index.php?site=material" class="btn-submit" style="text-decoration:none; display:inline-block;">Abbrechen</a>
    </div>
  </form>
</section>
