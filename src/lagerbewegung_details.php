<?php
require_once "auth.php";
require_role(['superuser','admin','user']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = (int)($_GET['id'] ?? 0);
$returnUrl = $_GET['return'] ?? 'index.php?site=lagerbewegungen';

if ($id <= 0) {
    echo "<div class='toast error'>Ungültige Lagerbewegung.</div>";
    return;
}

$stmt = $conn->prepare("
    SELECT
        lb.*,
        u.Name AS user_name,

        s.id AS spule_id,
        s.preis,
        s.lagerort,
        s.chargennummer,
        s.status,

        f.name_des_filaments,
        h.hr_name,
        m.name AS material_name,
		p.projektname,
		a.name AS auftrag_name

    FROM lagerbewegungen lb

    LEFT JOIN user u
        ON lb.user_id = u.id

    LEFT JOIN spulenlager s
        ON lb.spule_id = s.id

    LEFT JOIN filamente f
        ON lb.filament_id = f.id

    LEFT JOIN hersteller h
        ON f.hersteller_id = h.id

    LEFT JOIN materialien m
        ON f.material = m.id
		
	LEFT JOIN projekte p
		ON lb.projekt_id = p.id

	LEFT JOIN auftraege a
		ON lb.auftrag_id = a.id
	
    WHERE lb.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$bewegung = $stmt->get_result()->fetch_assoc();

if (!$bewegung) {
    echo "<div class='toast error'>Lagerbewegung nicht gefunden.</div>";
    return;
}
?>

<section class="card">

    <div class="card-header">
        <h2 >Lagerbewegung zu #<?= (int)$bewegung['id'] ?></h2>

        <div class="card-actions">

            <a href="<?= htmlspecialchars($returnUrl) ?>"
               class="btn-primary">
                ← Zurück
            </a>

            <?php
            if (
                in_array($_SESSION['rolle'], ['superuser','admin'])
                && $bewegung['bewegungsart'] === 'wareneingang'
                && $bewegung['status'] !== 'korrigiert'
            ):
            ?>
                <a href="index.php?site=lagerbewegung_korrigieren&id=<?= (int)$bewegung['id'] ?>&return=<?= urlencode($returnUrl) ?>"
                   class="btn-primary">
                    Korrigieren
                </a>
            <?php endif; ?>

        </div>
    </div>

    <div class="detail-section-title">Allgemeine Informationen</div>

    <table class="detail-table">
        <tbody>

        <tr>
            <th class="left">ID</th>
            <td>#<?= (int)$bewegung['id'] ?></td>
        </tr>

        <tr>
            <th class="left">Datum</th>
            <td><?= date('d.m.Y H:i', strtotime($bewegung['datum'])) ?></td>
        </tr>

        <tr>
            <th class="left">Bewegungsart</th>
            <td><?= htmlspecialchars($bewegung['bewegungsart']) ?></td>
        </tr>

        <tr>
            <th class="left">Menge</th>
            <td><?= htmlspecialchars($bewegung['menge']) ?> g</td>
        </tr>

        <tr>
            <th class="left">Benutzer</th>
            <td><?= htmlspecialchars($bewegung['user_name'] ?? '') ?></td>
        </tr>

        <tr>
            <th class="left">Kommentar</th>
            <td><?= nl2br(htmlspecialchars($bewegung['kommentar'] ?? '')) ?></td>
        </tr>

        </tbody>
    </table>

    <br>

    <div class="detail-section-title">Spule</div>
	
	<table class="detail-table">
        <tbody>

        <tr>
            <th class="left">Spule-ID</th>
            <td>#<?= (int)$bewegung['spule_id'] ?></td>
        </tr>

        <tr>
            <th class="left">Status</th>
            <td><?= htmlspecialchars($bewegung['status']) ?></td>
        </tr>

        <tr>
            <th class="left">Preis</th>
            <td><?= number_format((float)$bewegung['preis'], 2, ',', '.') ?> €</td>
        </tr>

        <tr>
            <th class="left">Lagerort</th>
            <td><?= htmlspecialchars($bewegung['lagerort'] ?? '') ?></td>
        </tr>

        <tr>
            <th class="left">Chargennummer</th>
            <td><?= htmlspecialchars($bewegung['chargennummer'] ?? '') ?></td>
        </tr>

        </tbody>
    </table>

    <br>

	<div class="detail-section-title">Filament</div>

	<table class="detail-table">
        <tbody>

        <tr>
            <th class="left">Hersteller</th>
            <td><?= htmlspecialchars($bewegung['hr_name'] ?? '') ?></td>
        </tr>

        <tr>
            <th class="left">Filament</th>
            <td><?= htmlspecialchars($bewegung['name_des_filaments'] ?? '') ?></td>
        </tr>

        <tr>
            <th class="left">Material</th>
            <td><?= htmlspecialchars($bewegung['material_name'] ?? '') ?></td>
        </tr>

        </tbody>
    </table>

    <?php if (!empty($bewegung['referenz_bewegung_id'])): ?>

        <br>

		<div class="detail-section-title">Korrekturbezug</div>

		<table class="detail-table">
            <tbody>

            <tr>
                <th class="left">Referenzbewegung</th>
                <td>
                    <a href="index.php?site=lagerbewegung_details&id=<?= (int)$bewegung['referenz_bewegung_id'] ?>">
                        #<?= (int)$bewegung['referenz_bewegung_id'] ?>
                    </a>
                </td>
            </tr>

            </tbody>
        </table>

    <?php endif; ?>

	<?php if (!empty($bewegung['projektname']) || !empty($bewegung['auftrag_name'])): ?>

		<div class="detail-section-title">Bezug</div>

		<table class="detail-table">
			<tbody>

			<?php if (!empty($bewegung['projektname'])): ?>
				<tr>
					<th>Projekt</th>
					<td><?= htmlspecialchars($bewegung['projektname']) ?></td>
				</tr>
			<?php endif; ?>

			<?php if (!empty($bewegung['auftrag_name'])): ?>
				<tr>
					<th>Auftrag</th>
					<td><?= htmlspecialchars($bewegung['auftrag_name']) ?></td>
				</tr>
			<?php endif; ?>

			</tbody>
		</table>

	<?php endif; ?>

</section>