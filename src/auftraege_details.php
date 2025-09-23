<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_login();

require_once "auth.php";
require_role(['superuser','admin', 'user', 'readonly']);

$auftrag_id = $_GET['id'] ?? null;
if (!$auftrag_id) {
    die("Kein Auftrag gewählt.");
}

// Auftrag laden
$sql = "SELECT a.*, 
               k.firma, k.ansprechpartner, k.telefon, k.strasse, k.plz, k.ort, k.versandart,
			   p.projektname
        FROM auftraege a
        LEFT JOIN kunden k ON a.kunde_id = k.id
		LEFT JOIN projekte p ON a.projekt_id = p.id
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auftrag_id);
$stmt->execute();
$res = $stmt->get_result();
$auftrag = $res->fetch_assoc();
if (!$auftrag) {
    die("Auftrag nicht gefunden.");
}

// Filamente laden
$sql = "SELECT af.*, CONCAT(h.hr_name, ' | ', f.name_des_filaments, ' | ', m.name) AS filament_name
        FROM auftrag_filamente af
        JOIN filamente f ON af.filament_id = f.id
        JOIN hersteller h ON f.hersteller_id = h.id
        JOIN materialien m ON f.material = m.id
        WHERE af.auftrag_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auftrag_id);
$stmt->execute();
$res = $stmt->get_result();
$filamente = $res->fetch_all(MYSQLI_ASSOC);

// Status ändern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];
    $sql = "UPDATE auftraege SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $auftrag_id);
    $stmt->execute();

    $_SESSION['success'] = '<div class="info-box">
            <i class="fa-solid fa-circle-info"></i>
            <span>Status für Auftrag wurde geändert.</span>
        </div>';
    header("Location: index.php?site=auftraege_details&id=".$auftrag_id);
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Auftragsdetails</h2>
        <div>
            <a href="index.php?site=auftraege" class="btn-primary">← Zurück zur Auftragsliste</a>
            <a href="auftraege_details_print.php?id=<?= $auftrag_id ?>" class="btn-primary" target="_blank">
                🖨 Druckansicht
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="info-box">
        <i class="fa-solid fa-circle-info"></i>
        <span>Dies ist die <strong>Detailansicht eines Auftrags</strong>. 
        Hier kannst du Informationen ansehen, den Status ändern oder eine Druckansicht öffnen.</span>
    </div>

    <h3>Allgemeine Infos</h3>
    <div class="form-group">
        <label>Auftragsname:</label>
        <span><?= htmlspecialchars($auftrag['name']) ?> </span>
    </div>
    <div class="form-group">
        <label>Vorlage:</label>
        <span><?= htmlspecialchars($auftrag['projektname'] ?? '-') ?></span>
    </div>

    <div class="form-group">
        <label>Firma / Ansprechpartner:</label>
        <span><?= htmlspecialchars($auftrag['firma'] ?? $auftrag['ansprechpartner'] ?? '') ?></span>
        <label>Strasse:</label>
        <span><?= htmlspecialchars($auftrag['strasse'] ?? '') ?></span>
        <label>PLZ + Ort:</label>
        <span><?= htmlspecialchars(($auftrag['plz'] ?? '').' '.($auftrag['ort'] ?? '')) ?></span>
        <label>Telefon:</label>
        <span><?= htmlspecialchars($auftrag['telefon'] ?? '') ?></span>
        <label>Versandart:</label>
        <span><?= htmlspecialchars($auftrag['versandart'] ?? '') ?></span>
    </div>
    <div class="form-group">
        <label>Datum:</label>
        <span><?= htmlspecialchars($auftrag['datum']) ?></span>
    </div>
    <div class="form-group">
        <label>Anzahl:</label>
        <span><?= $auftrag['anzahl'] ?></span>
    </div>
    <div class="form-group">
        <label>Status:</label>
        <span><?= $auftrag['status'] ?></span>
    </div>
	
	<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin', 'user'])): ?>
		<?php if ($auftrag['status'] !== 'fertig'): ?>
			<form method="post" style="margin-top:15px;">
				<label>Status ändern:</label>
				<select name="status">
					<option value="offen" <?= $auftrag['status']=='offen'?'selected':'' ?>>Offen</option>
					<option value="in_bearbeitung" <?= $auftrag['status']=='in_bearbeitung'?'selected':'' ?>>In Bearbeitung</option>
					<option value="fertig" <?= $auftrag['status']=='fertig'?'selected':'' ?>>Fertig</option>
				</select>
				<button type="submit" class="btn-primary">Speichern</button>
			</form>
		<?php endif; ?>
	<?php endif; ?>
    <h3 style="margin-top:20px;">Filamente</h3>
    <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Filament</th>
                    <th class="right">Geplant (g)</th>
                    <th class="right">Gebucht (g)</th>
                    <th class="right">Rest (g)</th>
                    <th class="center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filamente as $af): 
                    $rest = $af['menge_geplant'] - $af['menge_gebucht'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($af['filament_name']) ?></td>
                    <td class="right"><?= $af['menge_geplant'] ?></td>
                    <td class="right"><?= $af['menge_gebucht'] ?></td>
                    <td class="right"><?= max(0, $rest) ?></td>
                    <td class="center"><?= $af['status'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
