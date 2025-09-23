<?php

require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

// Filamente laden (Dropdown)
$filamente_res = $conn->query("
    SELECT f.id, f.name_des_filaments, h.hr_name, m.name AS material
    FROM filamente f
    LEFT JOIN hersteller h ON f.hersteller_id = h.id
    LEFT JOIN materialien m ON f.material = m.id
    ORDER BY h.hr_name, f.name_des_filaments
");
$filamente = $filamente_res->fetch_all(MYSQLI_ASSOC);

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
	if (!validate_form_token($_POST['form_token'] ?? '')) {
		$_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Das Formular wurde bereits verarbeitet oder ist ungültig.</span>
            </div>';
        header("Location: index.php?site=projekte");
        exit;
	}
    $projektname = trim($_POST['projektname'] ?? '');
    $kommentar = trim($_POST['kommentar'] ?? '');
	$tage    = (int)($_POST['druckzeit_days'] ?? 0);
	$stunden = (int)($_POST['druckzeit_hours'] ?? 0);
	$minuten = (int)($_POST['druckzeit_minutes'] ?? 0);
	$sekunden = (int)($_POST['druckzeit_seconds'] ?? 0);

	$druckzeit_seconds = ($tage * 86400) + ($stunden * 3600) + ($minuten * 60) + $sekunden;

    if ($projektname !== '') {
        // Projekt speichern
        $stmt = $conn->prepare("INSERT INTO projekte (projektname, kommentar, druckzeit_seconds) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $projektname, $kommentar, $druckzeit_seconds);
        $stmt->execute();
        $projekt_id = $stmt->insert_id;

        // Filamente speichern
        foreach ($_POST['filament'] as $index => $filamentId) {
            $menge = (float)($_POST['menge'][$index] ?? 0);
            if ($filamentId && $menge > 0) {
                $stmt2 = $conn->prepare("INSERT INTO projekt_filamente (projekt_id, filament_id, menge_geplant) VALUES (?, ?, ?)");
                $stmt2->bind_param("iid", $projekt_id, $filamentId, $menge);
                $stmt2->execute();
            }
        }
		$_SESSION['success'] = '
		<div class="info-box">
			<i class="fa-solid fa-circle-info"></i>
			<span>Projekt <strong>' . htmlspecialchars($projektname) . '</strong> erfolgreich angelegt!</span>
		</div>';
        header("Location: index.php?site=projekte");
        exit;
    } else {
        $_SESSION['error'] = "❌ Projektname darf nicht leer sein!";
        header("Location: index.php?site=projekt_anlegen");
        exit;
    }
}
?>

<section class="card">
    <div class="card-header">
        <h2>Projekt anlegen</h2>
        <a href="index.php?site=projekte" class="btn-primary">← Zur Projektliste</a>
    </div>

    <form method="post" class="form-section" id="projektForm">
        <div class="form-group">
            <label for="projektname">Projektname</label>
            <input type="text" name="projektname" id="projektname" required>
        </div>

        <div class="form-group">
            <label for="kommentar">Kommentar</label>
            <textarea name="kommentar" id="kommentar"></textarea>
        </div>

		<div class="form-group">
			<label>Druckzeit</label>
			<div style="display:flex; gap:5px; align-items:center;">
				<input type="number" name="druckzeit_days" min="0" value="0" style="width:80px;"> Tage
				<input type="number" name="druckzeit_hours" min="0" max="23" value="0" style="width:80px;"> Std
				<input type="number" name="druckzeit_minutes" min="0" max="59" value="0" style="width:80px;"> Min
				<input type="number" name="druckzeit_seconds" min="0" max="59" value="0" style="width:80px;"> Sek
			</div>
		</div>

        <h3>Filamente für dieses Projekt</h3>
        <div id="filamentContainer">
            <!-- erste Zeile -->
            <div class="filament-block form-row" style="display:flex; gap:10px; align-items:flex-end;">
                <div class="form-group" style="flex:2;">
                    <label>Filament</label>
                    <select name="filament[0]" required>
                        <option value="">Bitte wählen</option>
                        <?php foreach($filamente as $f): ?>
                        <option value="<?= $f['id'] ?>">
                            <?= htmlspecialchars($f['hr_name'] . " - " . $f['name_des_filaments'] . " (" . $f['material'] . ")") ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Menge (g)</label>
                    <input type="number" name="menge[0]" min="1" max="1500" required>
                </div>
                <div class="form-group" style="flex:0.5;">
                    <button type="button" class="btn-action delete removeBlock" title="Entfernen">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <button type="button" class="btn-primary" id="addFilament">+ Weiteres Filament</button>

        <div style="margin-top:20px;">
			<!-- Token -->
			<input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">
		
            <button type="submit" name="submit" class="btn-primary">Projekt speichern</button>
        </div>
    </form>
</section>

<script>
let blockIndex = 1;
const filamentOptions = `<?php foreach($filamente as $f): ?>
<option value="<?= $f['id'] ?>"><?= addslashes(htmlspecialchars($f['hr_name'] . " - " . $f['name_des_filaments'] . " (" . $f['material'] . ")")) ?></option>
<?php endforeach; ?>`;

document.getElementById('addFilament').addEventListener('click', () => {
    const container = document.createElement('div');
    container.className = 'filament-block form-row';
    container.style = "display:flex; gap:10px; align-items:flex-end;";
    container.innerHTML = `
        <div class="form-group" style="flex:2;">
            <label>Filament</label>
            <select name="filament[${blockIndex}]" required>
                <option value="">Bitte wählen</option>
                ${filamentOptions}
            </select>
        </div>
        <div class="form-group" style="flex:1;">
            <label>Menge (g)</label>
            <input type="number" name="menge[${blockIndex}]" min="1" max="1500" required>
        </div>
        <div class="form-group" style="flex:0.5;">
            <button type="button" class="btn-action delete removeBlock" title="Entfernen">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    `;
    document.getElementById('filamentContainer').appendChild(container);
    blockIndex++;
});

// Entfernen Button
document.getElementById('projektForm').addEventListener('click', function(e) {
    if (e.target && (e.target.classList.contains('removeBlock') || e.target.closest('.removeBlock'))) {
        const block = e.target.closest('.filament-block');
        if(block) block.remove();
    }
});
</script>
