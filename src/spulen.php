<?php

if (isset($_SESSION['success'])) {
    echo $_SESSION['success'];
    unset($_SESSION['success']);
}
// Anzahl Einträge pro Seite
$limit = 13;

// Aktuelle Seite aus URL, Standard = 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;

// Startwert für LIMIT
$start = ($page - 1) * $limit;

// Anzahl Einträge
$totalRes = $conn->query("SELECT COUNT(*) as total FROM spulenlager");
$totalRow = $totalRes->fetch_assoc();
$totalEntries = $totalRow['total'];
$totalPages = ceil($totalEntries / $limit);

// Abfrage mit JOIN
$sql = "SELECT s.*, f.name_des_filaments, f.preis AS filament_preis, m.name AS material, h.hr_name
        FROM spulenlager s
        LEFT JOIN filamente f ON s.filament_id = f.id
        LEFT JOIN materialien m ON f.material = m.id
        LEFT JOIN hersteller h ON f.hersteller_id = h.id
        ORDER BY s.id ASC
        LIMIT $start, $limit";
$res = $conn->query($sql);
?>

<section class="card">
    <div class="card-header">
        <h2>Spulenlager</h2>
        <div class="right"><a href="index.php?site=spulen_anlegen" class="btn-primary">+ Spule anlegen</a> <a href="index.php?site=buchungen" class="btn-primary">+ Wareneingang buchen</a></div>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th class="left" style="width:5%;">ID</th>
                <th class="left" style="width:20%;">Filament</th>
                <th class="left">Material</th>
                <th class="right">Verbleibend</th>
				<th class="right">Verbraucht</th>
                <th class="right">Preis pro Spule</th>
				<th class="center">Lagerort</th>
				<th class="center">erste Verwendung</th>
				<th class="center">letzte Verwendung</th>
                <th class="right">Lagerwert</th>
                <th class="center">Aktionen</th>
            </tr>
        </thead>
		<tbody>
			<?php while($row = $res->fetch_assoc()): ?>
			<tr>
				<td class="left"><?= htmlspecialchars($row['id'] ?? '') ?></td>
				<td class="left">
					<?= htmlspecialchars($row['hr_name'] ?? '') ?> - <?= htmlspecialchars($row['name_des_filaments'] ?? '') ?>
				</td>
				<td class="left"><?= htmlspecialchars($row['material'] ?? '') ?></td>
				<td class="right"><?= number_format($row['verbleibendes_filament'] ?? 0, 2, ',', '.') ?> g</td>
				<td class="right"><?= number_format($row['verbrauchtes_filament'] ?? 0, 2, ',', '.') ?> g</td>
				<td class="right"><?= number_format($row['preis'] ?? 0, 2, ',', '.') ?> €</td>
				<td class="center"><?= htmlspecialchars($row['lagerort'] ?? '') ?></td>
				<td class="center"><?php echo date("d.m.Y H:i:s", strtotime($row['erstmals_verwendet'])); ?></td>
				<td class="center"><?php echo date("d.m.Y H:i:s", strtotime($row['letzte_verwendung'])); ?></td>
				<td class="right">
					<?= number_format((($row['verbleibendes_filament'] ?? 0) / 1000) * ($row['preis'] ?? 0), 2, ',', '.') ?> €
				</td>
				<td class="actions center">
				<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin', 'user'])): ?>
					<a href="index.php?site=spulen_bearbeiten&id=<?= $row['id'] ?? '' ?>" class="btn-action edit" title="Bearbeiten">
						<i class="fa-solid fa-pen-to-square"></i>
					</a>
					<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin'])): ?>
					<a href="index.php?site=spulen_loeschen&id=<?= $row['id'] ?? '' ?>" class="btn-action delete" title="Löschen">
						<i class="fa-solid fa-trash"></i>
					</a>
					<?php endif; ?>
				<?php endif; ?>
				</td>
			</tr>
			<?php endwhile; ?>
		</tbody>

    </table>

    <!-- Pagination -->
    <div class="pagination" style="margin-top:15px;">
        <?php if($page > 1): ?>
            <a href="?site=spulen&page=<?= $page - 1 ?>">&laquo; Zurück</a>
        <?php endif; ?>

        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <?php if($i == $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?site=spulen&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if($page < $totalPages): ?>
            <a href="?site=spulen&page=<?= $page + 1 ?>">Weiter &raquo;</a>
        <?php endif; ?>
    </div>
</section>
