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

	<div class="info-box">
		<i class="fa-solid fa-circle-info"></i>
		<span>Beim Anlegen eines Projektes gehen wir immer von 1 Stück aus. Die Anzahl bzw. Menge wird dann im Auftrag vermerkt!</span>
	</div>

    <form method="post" class="form" id="projektForm">
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
				<div class="zeit-eingabe">
					<label>Gesamtdruckzeit (berechnet)</label>
					<div id="gesamtzeitAnzeige" style="font-weight:bold; padding:6px 0;">0 T 0 h 0 min 0 s</div>
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

				<div class="form-group" style="flex:1;">
					<label>Druckzeit</label>
					<div class="zeit-eingabe">
						<label><span>Tage</span><input type="number" name="tage[0]" min="0" value="0" class="druckzeit-tag" style="width:60px;"></label>
						<label><span>Stunden</span><input type="number" name="stunden[0]" min="0" max="23" value="0" class="druckzeit-hour" style="width:60px;"></label>
						<label><span>Minuten</span><input type="number" name="minuten[0]" min="0" max="59" value="0" class="druckzeit-minute" style="width:60px;"></label>
						<label><span>Sekunden</span><input type="number" name="sekunden[0]" min="0" max="59" value="0" class="druckzeit-second" style="width:60px;"></label>
					</div>
				</div>
				
                <div class="form-group" style="flex:0.5;">
                    <button type="button" class="btn-action delete removeBlock" style="height:42px;width:42px;" title="Entfernen">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <button type="button" class="btn-primary" id="addFilament">+ Weiteres Filament</button>

        <div style="margin-top:20px;">
			<!-- Token -->
			<input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">

			<!-- Versteckte Felder für Gesamtdruckzeit -->
			<input type="hidden" name="druckzeit_days" value="0">
			<input type="hidden" name="druckzeit_hours" value="0">
			<input type="hidden" name="druckzeit_minutes" value="0">
			<input type="hidden" name="druckzeit_seconds" value="0">		

            <button type="submit" name="submit" class="btn-primary">Projekt speichern</button>
        </div>
    </form>
</section>

<script>
let blockIndex = 1;

// Optionen für Dropdown
const filamentOptions = `<?php foreach($filamente as $f): ?>
<option value="<?= $f['id'] ?>"><?= addslashes(htmlspecialchars($f['hr_name'] . " - " . $f['name_des_filaments'] . " (" . $f['material'] . ")")) ?></option>
<?php endforeach; ?>`;

// === Dynamisch neue Filament-Zeile hinzufügen ===
document.getElementById('addFilament').addEventListener('click', () => {
    const container = document.createElement('div');
    container.className = 'filament-block form-row';
    container.style = "display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;";
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

        <div class="form-group" style="flex:1;">
            <label>Druckzeit</label>
            <div class="zeit-eingabe">
                <label><span>T</span><input type="number" name="tage[${blockIndex}]" min="0" value="0" class="druckzeit-tag" style="width:60px;"></label>
                <label><span>H</span><input type="number" name="stunden[${blockIndex}]" min="0" max="23" value="0" class="druckzeit-hour" style="width:60px;"></label>
                <label><span>M</span><input type="number" name="minuten[${blockIndex}]" min="0" max="59" value="0" class="druckzeit-minute" style="width:60px;"></label>
                <label><span>S</span><input type="number" name="sekunden[${blockIndex}]" min="0" max="59" value="0" class="druckzeit-second" style="width:60px;"></label>
            </div>
        </div>

        <div class="form-group" style="flex:0.5;">
            <button type="button" class="btn-action delete removeBlock" style="height:42px;width:42px;" title="Entfernen">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    `;
    document.getElementById('filamentContainer').appendChild(container);
    blockIndex++;
});

// === Entfernen-Button ===
document.getElementById('projektForm').addEventListener('click', function(e) {
    if (e.target && (e.target.classList.contains('removeBlock') || e.target.closest('.removeBlock'))) {
        const block = e.target.closest('.filament-block');
        if (block) block.remove();
        updateGesamtzeit(); // Nach Entfernen neu berechnen
    }
});

// === Gesamtdruckzeit berechnen ===
function updateGesamtzeit() {
    let totalSeconds = 0;

    document.querySelectorAll('.filament-block').forEach(block => {
        const tage = parseInt(block.querySelector('.druckzeit-tag')?.value || 0);
        const stunden = parseInt(block.querySelector('.druckzeit-hour')?.value || 0);
        const minuten = parseInt(block.querySelector('.druckzeit-minute')?.value || 0);
        const sekunden = parseInt(block.querySelector('.druckzeit-second')?.value || 0);

        totalSeconds += (tage * 86400) + (stunden * 3600) + (minuten * 60) + sekunden;
    });

    const totalDays = Math.floor(totalSeconds / 86400);
    const totalHours = Math.floor((totalSeconds % 86400) / 3600);
    const totalMinutes = Math.floor((totalSeconds % 3600) / 60);
    const totalSecs = totalSeconds % 60;

    // In versteckte Felder schreiben (werden vom PHP-Code genutzt)
    document.querySelector('[name="druckzeit_days"]').value = totalDays;
    document.querySelector('[name="druckzeit_hours"]').value = totalHours;
    document.querySelector('[name="druckzeit_minutes"]').value = totalMinutes;
    document.querySelector('[name="druckzeit_seconds"]').value = totalSecs;

    // Live-Anzeige aktualisieren
    const gesamtDiv = document.getElementById('gesamtzeitAnzeige');
    if (gesamtDiv) {
        gesamtDiv.textContent = `${totalDays} T ${totalHours} h ${totalMinutes} min ${totalSecs} s`;
    }
}

// === Eventlistener für Änderungen in Zeitfeldern ===
document.getElementById('projektForm').addEventListener('input', function(e) {
    if (
        e.target.classList.contains('druckzeit-tag') ||
        e.target.classList.contains('druckzeit-hour') ||
        e.target.classList.contains('druckzeit-minute') ||
        e.target.classList.contains('druckzeit-second')
    ) {
        updateGesamtzeit();
    }
});
// Beim Laden einmalig initialisieren
document.addEventListener('DOMContentLoaded', () => {
    updateGesamtzeit();
});
</script>
