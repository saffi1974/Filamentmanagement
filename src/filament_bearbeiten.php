<?php
// Filament ID aus GET
$id = (int)$_GET['id'];

// Hersteller und Materialien für Dropdown
$herstellerRes = $conn->query("SELECT id, hr_name FROM hersteller ORDER BY hr_name");
$materialRes = $conn->query("SELECT id, name FROM materialien ORDER BY name");

// Bestehendes Filament laden
$res = $conn->query("SELECT * FROM filamente WHERE id = $id");
$filament = $res->fetch_assoc();

// Farben aus JSON
$farbenArray = json_decode($filament['farben'], true) ?? [];
$anzahl_farben = count($farbenArray);

// Update
if(isset($_POST['submit'])){
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

    // Farben korrekt verarbeiten
    $farbenArray = array_slice($_POST['farben'], 0, (int)$_POST['anzahl_farben']);
    $farbenArray = array_filter($farbenArray, fn($hex) => $hex !== '' && $hex !== '#ffffff');
    $farben = json_encode($farbenArray);

    $stmt = $conn->prepare("UPDATE filamente SET 
        hersteller_id=?, name_des_filaments=?, material=?, preis=?, durchmesser=?,
        gewicht_des_filaments=?, artikelnummer_des_herstellers=?, duesentemperatur=?,
        betttemperatur=?, anzahl_farben=?, farben=?, kommentar=?
        WHERE id=?");

	$anzahl_farben = count($farbenArray);

    $stmt->bind_param(
        "isidddsiisssi",
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
        $kommentar,
        $id
    );

    $stmt->execute();
	$_SESSION['success'] = '
	<div class="info-box">
		<i class="fa-solid fa-circle-check"></i>
		<span>Filament erfolgreich geändert.</span>
	</div>';
    header("Location: index.php?site=filamente");
    exit;
}
?>

<section class="form-section">
  <h2>Filament bearbeiten</h2>
  <form method="post" action="index.php?site=filament_bearbeiten&id=<?= $id ?>">
    
    <div class="form-group">
      <label for="hersteller_id">Hersteller</label>
      <select id="hersteller_id" name="hersteller_id" required>
        <option value="">Hersteller auswählen</option>
        <?php while($h = $herstellerRes->fetch_assoc()): ?>
          <option value="<?= $h['id'] ?>" <?= ($filament['hersteller_id']==$h['id'])?'selected':'' ?>><?= htmlspecialchars($h['hr_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="name_des_filaments">Name des Filaments</label>
      <input type="text" id="name_des_filaments" name="name_des_filaments" value="<?= htmlspecialchars($filament['name_des_filaments']) ?>" required>
    </div>

    <div class="form-group">
      <label for="material_id">Material</label>
      <select id="material_id" name="material_id" required>
        <option value="">Material auswählen</option>
        <?php while($m = $materialRes->fetch_assoc()): ?>
          <option value="<?= $m['id'] ?>" <?= ($filament['material']==$m['id'])?'selected':'' ?>><?= htmlspecialchars($m['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="preis">Preis (€)</label>
      <input type="number" step="0.01" id="preis" name="preis" value="<?= $filament['preis'] ?>">
    </div>

    <div class="form-group">
      <label for="durchmesser">Durchmesser (mm)</label>
      <input type="number" step="0.01" id="durchmesser" name="durchmesser" value="<?= $filament['durchmesser'] ?>">
    </div>

    <div class="form-group">
      <label for="gewicht_des_filaments">Gewicht (g)</label>
      <input type="number" id="gewicht_des_filaments" name="gewicht_des_filaments" value="<?= $filament['gewicht_des_filaments'] ?>">
    </div>

    <div class="form-group">
      <label for="artikelnummer_des_herstellers">Artikelnummer</label>
      <input type="text" id="artikelnummer_des_herstellers" name="artikelnummer_des_herstellers" value="<?= htmlspecialchars($filament['artikelnummer_des_herstellers']) ?>">
    </div>

    <div class="form-group">
      <label for="duesentemperatur">Düsentemperatur (°C)</label>
      <input type="number" id="duesentemperatur" name="duesentemperatur" value="<?= $filament['duesentemperatur'] ?>">
    </div>

    <div class="form-group">
      <label for="betttemperatur">Betttemperatur (°C)</label>
      <input type="number" id="betttemperatur" name="betttemperatur" value="<?= $filament['betttemperatur'] ?>">
    </div>

    <div class="form-group">
      <label for="anzahl_farben">Anzahl Farben</label>
      <input type="number" id="anzahl_farben" name="anzahl_farben" min="1" max="4" value="<?= $anzahl_farben ?>">
    </div>

    <div class="form-group">
      <label>Farben</label>
      <div id="farbenContainer" style="display:flex; gap:10px;">
        <?php for($i=0;$i<4;$i++): ?>
            <input type="color" name="farben[]" value="<?= $farbenArray[$i] ?? '#ffffff' ?>" style="<?= ($i<$anzahl_farben)?'':'display:none;' ?>">
        <?php endfor; ?>
      </div>
    </div>

    <div class="form-group">
      <label for="kommentar">Kommentar</label>
      <textarea id="kommentar" name="kommentar" rows="3"><?= htmlspecialchars($filament['kommentar']) ?></textarea>
    </div>

    <div style="display:flex; gap:20px; margin-top:15px;">
      <button type="submit" name="submit" class="btn-submit">Speichern</button>
      <a href="index.php?site=filamente" class="btn-submit" style="text-decoration:none; display:inline-block;">Zurück</a>
    </div>
  </form>
</section>

<script>
const anzahlInput = document.getElementById('anzahl_farben');
const farbenInputs = document.querySelectorAll('#farbenContainer input[type="color"]');

function updateColorInputs() {
    let count = parseInt(anzahlInput.value) || 1;
    count = Math.min(Math.max(count, 1), 4);

    farbenInputs.forEach((input, index) => {
        input.style.display = (index < count) ? 'inline-block' : 'none';
    });
}
anzahlInput.addEventListener('change', updateColorInputs);
updateColorInputs();
</script>
