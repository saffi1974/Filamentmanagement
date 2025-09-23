<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$projekt_id = $_GET['id'] ?? null;
if (!$projekt_id) {
    die("Kein Projekt gewählt.");
}

// Projekt laden
$sql = "SELECT * FROM projekte WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $projekt_id);
$stmt->execute();
$res = $stmt->get_result();
$projekt = $res->fetch_assoc();
if (!$projekt) {
    die("Projekt nicht gefunden.");
}
// Druckzeit zerlegen
$secs = (int)($projekt['druckzeit_seconds'] ?? 0);
$druckzeit_days = floor($secs / 86400);
$druckzeit_hours = floor(($secs % 86400) / 3600);
$druckzeit_minutes = floor(($secs % 3600) / 60);
$druckzeit_seconds = $secs % 60;

// Alle Filamente laden
$sql = "SELECT f.id, CONCAT(h.hr_name, ' | ', f.name_des_filaments, ' | ', m.name) AS bezeichnung
        FROM filamente f
        JOIN hersteller h ON f.hersteller_id = h.id
        JOIN materialien m ON f.material = m.id
        ORDER BY h.hr_name, f.name_des_filaments";
$res = $conn->query($sql);
$alleFilamente = $res->fetch_all(MYSQLI_ASSOC);

// Projekt-Filamente laden
$sql = "SELECT pf.id, pf.filament_id, pf.menge_geplant 
        FROM projekt_filamente pf 
        WHERE pf.projekt_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $projekt_id);
$stmt->execute();
$res = $stmt->get_result();
$projektFilamente = $res->fetch_all(MYSQLI_ASSOC);

// Formular absenden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projektname = trim($_POST['projektname']);
    $kommentar   = trim($_POST['kommentar']);
	
	// Druckzeit in Sekunden umrechnen
    $tage    = (int)($_POST['druckzeit_days'] ?? 0);
    $stunden = (int)($_POST['druckzeit_hours'] ?? 0);
    $minuten = (int)($_POST['druckzeit_minutes'] ?? 0);
    $sekunden = (int)($_POST['druckzeit_seconds'] ?? 0);
    $druckzeit_seconds = ($tage * 86400) + ($stunden * 3600) + ($minuten * 60) + $sekunden;

    if ($projektname === '') {
        $_SESSION['error'] = "Projektname darf nicht leer sein.";
    } else {
        // Projekt aktualisieren
        $sql = "UPDATE projekte SET projektname = ?, kommentar = ?, druckzeit_seconds = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $projektname, $kommentar, $druckzeit_seconds, $projekt_id);
        $stmt->execute();

        // Alte Zuordnungen löschen
        $sql = "DELETE FROM projekt_filamente WHERE projekt_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $projekt_id);
        $stmt->execute();

        // Neue Zuordnungen einfügen
        if (!empty($_POST['filament_id'])) {
            foreach ($_POST['filament_id'] as $idx => $filament_id) {
                $menge = (float)$_POST['menge_geplant'][$idx];
                if ($filament_id && $menge > 0) {
                    $sql = "INSERT INTO projekt_filamente (projekt_id, filament_id, menge_geplant, menge_gebucht, status) 
                            VALUES (?, ?, ?, 0, 'geplant')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iid", $projekt_id, $filament_id, $menge);
                    $stmt->execute();
                }
            }
        }

		$_SESSION['success'] = '
		<div class="info-box">
			<i class="fa-solid fa-circle-info"></i>
			<span>Projekt <strong>' . htmlspecialchars($projektname) . '</strong> erfolgreich geändert!</span>
		</div>';
        header("Location: index.php?site=projekte");
        exit;
    }
}
?>

<section class="card">
    <div class="card-header">
        <h2>Projekt bearbeiten</h2>
        <a href="index.php?site=projekte" class="btn-primary">← Zurück zur Projektliste</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="post" class="form-section">
        <div class="form-group">
            <label for="projektname">Projektname</label>
            <input type="text" id="projektname" name="projektname" 
                   value="<?= htmlspecialchars($projekt['projektname']) ?>" required>
        </div>

        <div class="form-group">
            <label for="kommentar">Kommentar</label>
            <textarea id="kommentar" name="kommentar" rows="4"><?= htmlspecialchars($projekt['kommentar']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Druckzeit (für 1 Stück)</label>
            <div style="display:flex; gap:5px; align-items:center;">
                <input type="number" id="druckzeit_days" name="druckzeit_days" min="0" value="<?= $druckzeit_days ?>" style="width:80px;"> Tage
                <input type="number" id="druckzeit_hours" name="druckzeit_hours" min="0" max="23" value="<?= $druckzeit_hours ?>" style="width:80px;"> Std
                <input type="number" id="druckzeit_minutes" name="druckzeit_minutes" min="0" max="59" value="<?= $druckzeit_minutes ?>" style="width:80px;"> Min
                <input type="number" id="druckzeit_seconds" name="druckzeit_seconds" min="0" max="59" value="<?= $druckzeit_seconds ?>" style="width:80px;"> Sek
            </div>
        </div>

        <h3>Filamente</h3>
        <div id="filamentContainer">
            <?php if ($projektFilamente): ?>
                <?php foreach ($projektFilamente as $pf): ?>
                    <div class="filament-row" style="display:flex; gap:10px; align-items:center;">
                        <select name="filament_id[]">
                            <option value="">-- Filament wählen --</option>
                            <?php foreach ($alleFilamente as $f): ?>
                                <option value="<?= $f['id'] ?>" <?= ($pf['filament_id'] == $f['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['bezeichnung']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="menge_geplant[]" value="<?= $pf['menge_geplant'] ?>" step="0.01" min="1" max="1500">
                        <button type="button" class="btn-action delete" onclick="this.closest('.filament-row').remove();">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="filament-row" style="display:flex; gap:10px; align-items:center;">
                    <select name="filament_id[]">
                        <option value="">-- Filament wählen --</option>
                        <?php foreach ($alleFilamente as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['bezeichnung']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="menge_geplant[]" value="0" step="0.01" min="1" max="1500">
                    <button type="button" class="btn-action delete" onclick="this.closest('.filament-row').remove();">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <button type="button" onclick="addFilamentRow()" class="btn-primary">+ Filament hinzufügen</button>

        <div style="margin-top:15px;">
            <button type="submit" class="btn-primary">Speichern</button>
        </div>
    </form>
</section>

<script>
function addFilamentRow() {
    const wrapper = document.getElementById('filamentContainer');
    const row = document.createElement('div');
    row.classList.add('filament-row');
    row.style.display = 'flex';
    row.style.gap = '10px';
    row.style.alignItems = 'center';

    row.innerHTML = `
        <select name="filament_id[]">
            <option value="">-- Filament wählen --</option>
            <?php foreach ($alleFilamente as $f): ?>
                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['bezeichnung']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="menge_geplant[]" value="0" step="0.01" min="1" max="1500">
        <button type="button" class="btn-action delete" onclick="this.closest('.filament-row').remove();">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    wrapper.appendChild(row);
}
</script>
