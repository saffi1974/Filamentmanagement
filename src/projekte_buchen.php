<?php
require_once "form_token.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? null; // beim Login in anmelden.php setzen!

// Projekt-ID aus URL
$projekt_id = $_GET['id'] ?? null;
if (!$projekt_id) {
    die("Kein Projekt gewählt.");
}

// --- Projekt-Filamente laden ---
$sql = "SELECT pf.id, pf.projekt_id, pf.filament_id, pf.menge_geplant, pf.menge_gebucht, pf.status, 
               CONCAT(h.hr_name, ' | ', f.name_des_filaments, ' | ', m.name) AS filament_name
        FROM projekt_filamente pf
        JOIN filamente f ON pf.filament_id = f.id
        JOIN hersteller h ON f.hersteller_id = h.id
        JOIN materialien m ON f.material = m.id
        WHERE pf.projekt_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $projekt_id);
$stmt->execute();
$result = $stmt->get_result();
$filamente = $result->fetch_all(MYSQLI_ASSOC);

// --- Buchung auslösen ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buchen'])) {
	if (!validate_form_token($_POST['form_token'] ?? '')) {
		$_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Das Formular wurde bereits verarbeitet oder ist ungültig.</span>
            </div>';
        header("Location: index.php?site=drucker");
        exit;
	}

    foreach ($filamente as $pf) {
        $rest = $pf['menge_geplant'];
        if ($rest <= 0) continue;

        // Spulen für dieses Filament holen (angebrochene zuerst)
        $sql = "SELECT * FROM spulenlager 
                WHERE filament_id = ? AND verbleibendes_filament > 0
                ORDER BY verbleibendes_filament ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pf['filament_id']);
        $stmt->execute();
        $resSpulen = $stmt->get_result();
        $spulen = $resSpulen->fetch_all(MYSQLI_ASSOC);

        foreach ($spulen as $spule) {
            if ($rest <= 0) break;

            $verbrauch = min($rest, $spule['verbleibendes_filament']);
            $neuesVerbleibend = $spule['verbleibendes_filament'] - $verbrauch;
            $neuesVerbraucht  = $spule['verbrauchtes_filament'] + $verbrauch;

            // Spule aktualisieren
            $sql = "UPDATE spulenlager 
                    SET verbleibendes_filament = ?, verbrauchtes_filament = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ddi", $neuesVerbleibend, $neuesVerbraucht, $spule['id']);
            $stmt->execute();

            $rest -= $verbrauch;
			
			// --- NEU: Buchung in Historie schreiben ---
			$sql = "INSERT INTO lagerbewegungen (spule_id, filament_id, projekt_id, user_id, bewegungsart, menge) 
					VALUES (?, ?, ?, ?, 'abbuchung_projekt', ?)";
			$stmt = $conn->prepare($sql);
			$negMenge = -1 * $verbrauch;
			$stmt->bind_param("iiiid", $spule['id'], $pf['filament_id'], $projekt_id, $user_id, $negMenge);
			$stmt->execute();
        }
    }

	$_SESSION['success'] = '
		<div class="info-box">
			<i class="fa-solid fa-circle-info"></i>
			<span>Filamente erfolgreich gebucht.</span>
		</div>';
    header("Location: index.php?site=projekte");
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Projekt buchen</h2>
        <a href="index.php?site=projekte" class="btn-primary">← Zurück zur Projektliste</a>
    </div>
	<div class="info-box">
		<i class="fa-solid fa-circle-info"></i>
		<span>Dies ist die <strong>Projekt-Buchungsansicht</strong>. 
		Projekte sind Vorlagen – die geplanten Mengen bleiben erhalten. 
		Beim Buchen werden nur die entsprechenden Mengen 
		aus dem <strong>Spulenlager</strong> abgezogen.</span>
	</div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Filament</th>
                    <th class="right">Geplant (g)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filamente as $pf): 
                    $rest = $pf['menge_geplant'] - $pf['menge_gebucht'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($pf['filament_name']) ?></td>
                    <td class="right"><?= $pf['menge_geplant'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form method="post" style="margin-top:15px;">
		<!-- Token -->
		<input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">

        <button type="submit" name="buchen" class="btn-primary">
            ausgewiesene Mengen buchen
        </button>
    </form>
</section>
