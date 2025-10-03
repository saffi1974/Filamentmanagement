<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Filter aus URL übernehmen
$filterArt = $_GET['bewegungsart'] ?? '';
$filterVon = $_GET['von'] ?? '';
$filterBis = $_GET['bis'] ?? '';

// Anzahl Einträge pro Seite
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// WHERE-Bedingung aufbauen
$where = [];
if ($filterArt) {
    $where[] = "lb.bewegungsart = '".$conn->real_escape_string($filterArt)."'";
}
if ($filterVon) {
    $where[] = "DATE(lb.datum) >= '".$conn->real_escape_string($filterVon)."'";
}
if ($filterBis) {
    $where[] = "DATE(lb.datum) <= '".$conn->real_escape_string($filterBis)."'";
}
$whereSql = $where ? "WHERE ".implode(" AND ", $where) : "";

// Gesamteinträge zählen
$totalRes = $conn->query("SELECT COUNT(*) as total FROM lagerbewegungen lb $whereSql");
$totalRow = $totalRes->fetch_assoc();
$totalEntries = $totalRow['total'];
$totalPages = ceil($totalEntries / $limit);

// Bewegungen abfragen
$sql = "
    SELECT lb.*, 
		   u.Name AS user_name,
           CONCAT(h.hr_name, ' | ', f.name_des_filaments, ' | ', m.name) AS filament_name,
           p.projektname,
           a.name AS auftrag_name
    FROM lagerbewegungen lb
    JOIN filamente f ON lb.filament_id = f.id
    JOIN hersteller h ON f.hersteller_id = h.id
    JOIN materialien m ON f.material = m.id
    LEFT JOIN projekte p ON lb.projekt_id = p.id
    LEFT JOIN auftraege a ON lb.auftrag_id = a.id
	LEFT JOIN user u ON lb.user_id = u.id
    $whereSql
    ORDER BY lb.datum DESC
    LIMIT $start, $limit
";
$res = $conn->query($sql);
?>

<section class="card">
    <div class="card-header">
        <h2>Lagerbewegungen</h2>
    </div>

    <!-- Filterformular -->
    <form method="get" class="form" style="margin-bottom:15px; display:flex; gap:15px; align-items:flex-end;">
        <input type="hidden" name="site" value="lagerbewegungen">

        <div class="form-group">
            <label for="bewegungsart">Bewegungsart</label>
            <select name="bewegungsart" id="bewegungsart">
                <option value="">-- Alle --</option>
                <option value="wareneingang" <?= $filterArt=='wareneingang'?'selected':'' ?>>Wareneingang</option>
                <option value="abbuchung_projekt" <?= $filterArt=='abbuchung_projekt'?'selected':'' ?>>Abbuchung (Projekt)</option>
                <option value="abbuchung_auftrag" <?= $filterArt=='abbuchung_auftrag'?'selected':'' ?>>Abbuchung (Auftrag)</option>
                <option value="korrektur" <?= $filterArt=='korrektur'?'selected':'' ?>>Korrektur</option>
            </select>
        </div>

        <div class="form-group">
            <label for="von">Von</label>
            <input type="date" name="von" id="von" value="<?= htmlspecialchars($filterVon) ?>">
        </div>

        <div class="form-group">
            <label for="bis">Bis</label>
            <input type="date" name="bis" id="bis" value="<?= htmlspecialchars($filterBis) ?>">
        </div>
        <div class="form-group">
			<button type="submit" class="btn-primary">Filtern</button>
			<a href="index.php?site=lagerbewegungen" class="btn-primary" style="margin-top:10px;">Reset</a>
		</div>
    </form>

    <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th style="width:12%;">Datum</th>
                    <th style="width:13%;">Bewegung</th>
                    <th style="width:25%;">Filament</th>
                    <th style="width:7%;">Menge (g)</th>
                    <th style="width:15%;">Bezug</th>
					<th style="width:7%;">Benutzer</th>
                    <th style="width:20%;">Kommentar</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['datum']) ?></td>
                        <td>
                            <?php
                            switch ($row['bewegungsart']) {
                                case 'wareneingang': echo 'Wareneingang'; break;
                                case 'abbuchung_projekt': echo 'Abbuchung (Projekt)'; break;
                                case 'abbuchung_auftrag': echo 'Abbuchung (Auftrag)'; break;
                                case 'korrektur': echo 'Korrektur'; break;
                                default: echo htmlspecialchars($row['bewegungsart']);
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($row['filament_name']) ?></td>
                        <td class="<?= $row['menge'] < 0 ? 'right error' : 'right success' ?>">
                            <?= $row['menge'] ?>
                        </td>
                        <td class="center">
                            <?php if ($row['projekt_id']): ?>
                                Projekt: <?= htmlspecialchars($row['projektname'] ?? 'Unbekannt') ?>
                            <?php elseif ($row['auftrag_id']): ?>
                                Auftrag: <?= htmlspecialchars($row['auftrag_name'] ?? 'Unbekannt') ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
						<td class="center"><?= htmlspecialchars($row['user_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['kommentar'] ?? '') ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?site=lagerbewegungen&page=<?= $page - 1 ?>&bewegungsart=<?= urlencode($filterArt) ?>&von=<?= urlencode($filterVon) ?>&bis=<?= urlencode($filterBis) ?>">&laquo; Zurück</a>
        <?php endif; ?>

        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <?php if($i == $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?site=lagerbewegungen&page=<?= $i ?>&bewegungsart=<?= urlencode($filterArt) ?>&von=<?= urlencode($filterVon) ?>&bis=<?= urlencode($filterBis) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if($page < $totalPages): ?>
            <a href="?site=lagerbewegungen&page=<?= $page + 1 ?>&bewegungsart=<?= urlencode($filterArt) ?>&von=<?= urlencode($filterVon) ?>&bis=<?= urlencode($filterBis) ?>">Weiter &raquo;</a>
        <?php endif; ?>
    </div>
</section>
