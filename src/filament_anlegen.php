<?php

require_once "form_token.php";

require_once "auth.php";
require_role(['superuser','admin']);

// ✅ Token erzeugen, wenn noch keiner existiert
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(16));
}

// Hersteller und Materialien für Dropdown
$herstellerRes = $conn->query("SELECT id, hr_name FROM hersteller ORDER BY hr_name");
$materialRes = $conn->query("SELECT id, name FROM materialien ORDER BY name");


// ✅ Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    // Token prüfen
    if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        $_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Das Formular wurde bereits verarbeitet oder ist ungültig.</span>
            </div>';
        header("Location: index.php?site=filamente");
        exit;
    }

 	    // ✅ Token sofort verbrauchen → kein erneutes Absenden möglich
    	unset($_SESSION['form_token']);

		// Werte aus POST lesen
		$hersteller_id = (int)$_POST['hersteller_id'];
		$name = $_POST['name_des_filaments'];
		$material_id = (int)$_POST['material_id'];
		$preis = $_POST['preis'];
		$durchmesser = $_POST['durchmesser'];
		$gewicht = $_POST['gewicht_des_filaments'];
		$artikelnummer = $_POST['artikelnummer_des_herstellers'];
		$duesentemp = $_POST['duesentemperatur'];
		$betttemp = $_POST['betttemperatur'];
		$kommentar = $_POST['kommentar'];

		// Anzahl Farben und HEX-Werte
		$anzahl_farben = (int)$_POST['anzahl_farben'];
		$farbenArray = array_slice($_POST['farben'], 0, (int)$_POST['anzahl_farben']);
		$farben = json_encode($farbenArray);

		$stmt = $conn->prepare("INSERT INTO filamente (hersteller_id, name_des_filaments, material, preis, durchmesser, gewicht_des_filaments, artikelnummer_des_herstellers, duesentemperatur, betttemperatur, anzahl_farben, farben, kommentar) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

		$stmt->bind_param("isidddsiisss",
			$hersteller_id,
			$name,
			$material_id,
			$preis,
			$durchmesser,
			$gewicht,
			$artikelnummer,
			$duesentemp,
			$betttemp,
			$anzahl_farben,
			$farben,
			$kommentar
		);
	
		$stmt->execute();

		$_SESSION['success'] = '
		<div class="info-box">
			<i class="fa-solid fa-circle-info"></i>
			<span>Filament erfolgreich angelegt!</span>
		</div>';
		header("Location: index.php?site=filamente");
		exit;
}
?>

<section class="form-section">
  <h2>Neues Filament anlegen</h2>
  <form method="post" action="index.php?site=filament_anlegen">
    
    <div class="form-group">
      <label for="hersteller_id">Hersteller</label>
      <select id="hersteller_id" name="hersteller_id" required>
        <option value="">Hersteller auswählen</option>
        <?php while($h = $herstellerRes->fetch_assoc()): ?>
          <option value="<?php echo $h['id']; ?>"><?php echo htmlspecialchars($h['hr_name']); ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="name_des_filaments">Name des Filaments</label>
      <input type="text" id="name_des_filaments" name="name_des_filaments" placeholder="Filamentname eingeben" required>
    </div>

    <div class="form-group">
      <label for="material_id">Material</label>
      <select id="material_id" name="material_id" required>
        <option value="">Material auswählen</option>
        <?php while($m = $materialRes->fetch_assoc()): ?>
          <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['name']); ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="preis">Preis (€)</label>
      <input type="number" step="0.01" id="preis" name="preis" placeholder="Preis eingeben">
    </div>

    <div class="form-group">
      <label for="durchmesser">Durchmesser (mm)</label>
      <input type="number" step="0.01" id="durchmesser" name="durchmesser" value="1.75">
    </div>

    <div class="form-group">
      <label for="gewicht_des_filaments">Gewicht (g)</label>
      <input type="number" id="gewicht_des_filaments" name="gewicht_des_filaments" placeholder="Nettogewicht ohne Spule">
    </div>

    <div class="form-group">
      <label for="artikelnummer_des_herstellers">Artikelnummer</label>
      <input type="text" id="artikelnummer_des_herstellers" name="artikelnummer_des_herstellers" placeholder="Artikelnummer eingeben">
    </div>

    <div class="form-group">
      <label for="duesentemperatur">Düsentemperatur (°C)</label>
      <input type="number" id="duesentemperatur" name="duesentemperatur" placeholder="Düsentemperatur">
    </div>

    <div class="form-group">
      <label for="betttemperatur">Betttemperatur (°C)</label>
      <input type="number" id="betttemperatur" name="betttemperatur" placeholder="Betttemperatur">
    </div>

	<div class="form-group">
		<label for="anzahl_farben">Anzahl Farben</label>
		<input type="number" id="anzahl_farben" name="anzahl_farben" min="1" max="4" value="1">
	</div>

	<div class="form-group">
		<label>Farben</label>
		<div id="farbenContainer" style="display:flex; gap:10px;">
			<input type="color" name="farben[]" value="#ffffff">
			<input type="color" name="farben[]" value="#ffffff" style="display:none;">
			<input type="color" name="farben[]" value="#ffffff" style="display:none;">
			<input type="color" name="farben[]" value="#ffffff" style="display:none;">
		</div>
	</div>
	
    <div class="form-group">
      <label for="kommentar">Kommentar</label>
      <textarea id="kommentar" name="kommentar" rows="3" cols="40" placeholder="Kommentar"></textarea>
    </div>

    <div style="display:flex; gap:20px; margin-top:15px;">
		<input type="hidden" name="form_token" value="<?= htmlspecialchars($_SESSION['form_token']) ?>">
		<button type="submit" name="submit" class="btn-submit">Eintragen</button>
		<a href="index.php?site=filamente" class="btn-submit" style="text-decoration:none; display:inline-block;">Zurück</a>
    </div>
  </form>
</section>
<script>
const anzahlInput = document.getElementById('anzahl_farben');
const farbenInputs = document.querySelectorAll('#farbenContainer input[type="color"]');

function updateColorInputs() {
    let count = parseInt(anzahlInput.value) || 1;
    if(count > 4) count = 4;
    if(count < 1) count = 1;

    farbenInputs.forEach((input, index) => {
        input.style.display = (index < count) ? 'inline-block' : 'none';
    });
}
anzahlInput.addEventListener('change', updateColorInputs);
updateColorInputs();
</script>
