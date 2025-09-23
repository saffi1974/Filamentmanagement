<?php

require_once "form_token.php";

require_once "auth.php";
require_role(['superuser','admin']);

// Filamente und Preise holen
$filamenteRes = $conn->query("
    SELECT f.id, h.hr_name, f.name_des_filaments, f.material AS material_id, f.preis, m.name AS material_name
    FROM filamente f
    LEFT JOIN hersteller h ON f.hersteller_id = h.id
    LEFT JOIN materialien m ON f.material = m.id
    ORDER BY h.hr_name, f.name_des_filaments
");

$filamente = [];
while($row = $filamenteRes->fetch_assoc()){
    $filamente[] = $row;
}

// Wareneingang speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
	if (!validate_form_token($_POST['form_token'] ?? '')) {
		$_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Das Formular wurde bereits verarbeitet oder ist ungültig.</span>
            </div>';
        header("Location: index.php?site=auftraege");
        exit;
	}
    $filament_id = (int)$_POST['filament_id'];
    $anzahl_spulen = (int)$_POST['anzahl_spulen'];
    $gewicht_pro_spule = 1000;
    $preis = (float)$_POST['preis'];

    // Material-ID aus Filament-Tabelle holen
    $matRes = $conn->query("SELECT material FROM filamente WHERE id = $filament_id");
    $matRow = $matRes->fetch_assoc();
    $material_id = (int)$matRow['material'];

    $stmt = $conn->prepare("
        INSERT INTO spulenlager (filament_id, material_id, preis, verbrauchtes_filament, verbleibendes_filament, lagerort, chargennummer, kommentar)
        VALUES (?, ?, ?, 0, ?, ?, ?, ?)
    ");

    for($i=0; $i<$anzahl_spulen; $i++){
        $stmt->bind_param("iidisss", $filament_id, $material_id, $preis, $gewicht_pro_spule, $_POST['lagerort'], $_POST['chargennummer'], $_POST['kommentar']);
        $stmt->execute();
    }
    $_SESSION['success'] = '<div class="info-box">
            <i class="fa-solid fa-circle-info"></i>
            <span>' . $anzahl_spulen . ' Spule(n) erfolgreich eingetragen!</span>
        </div>';
	header("Location: index.php?site=spulen");
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Spulen anlegen</h2>
        <a href="index.php?site=spulen" class="btn-primary">← Zurück zur Spulenliste</a>
    </div>

	<div class="info-box">
		<i class="fa-solid fa-circle-info"></i>
		<span>Dies geschieht ohne Wareneingang und ohne Buchung in Lagerbewegungen! Ideal für Lagerkorrekturen.</span>
	</div>
	
	<form method="post">
		<div class="form-group">
			<label for="filament_id">Filament auswählen:</label>
			<select name="filament_id" id="filament_id" required>
				<option value="">-- auswählen --</option>
				<?php foreach($filamente as $f): ?>
					<option value="<?= $f['id'] ?>" data-preis="<?= $f['preis'] ?>">
						<?= htmlspecialchars($f['hr_name']." | ".$f['name_des_filaments']." | ".$f['material_name']) ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="form-group">
			<label for="preis">Preis pro Spule (€):</label>
			<input type="number" step="0.01" name="preis" id="preis" required>
		</div>

		<div class="form-group">
			<label for="anzahl_spulen">Anzahl Spulen:</label>
			<input type="number" name="anzahl_spulen" id="anzahl_spulen" value="1" min="1" required>
		</div>

		<div class="form-group">
			<label for="lagerort">Lagerort:</label>
			<input type="text" name="lagerort" id="lagerort">
		</div>

		<div class="form-group">
			<label for="chargennummer">Chargennummer:</label>
			<input type="text" name="chargennummer" id="chargennummer">
		</div>

		<div class="form-group">
			<label for="kommentar">Kommentar:</label>
			<textarea name="kommentar" id="kommentar"></textarea>
		</div>
		<input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">
		<button type="submit" name="submit" class="btn-submit">Wareneingang buchen</button>
	</form>
</section>
<script>
// Preis automatisch ausfüllen, wenn ein Filament ausgewählt wird
document.getElementById('filament_id').addEventListener('change', function(){
    const selected = this.selectedOptions[0];
    const preis = selected.getAttribute('data-preis');
    if(preis) document.getElementById('preis').value = preis;
});
</script>
