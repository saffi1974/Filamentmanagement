<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['success'])) {
    echo $_SESSION['success'];
    unset($_SESSION['success']);
}
// Anzahl Einträge pro Seite
$limit = 13;

// Aktuelle Seite
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;

// Filter-Status aus URL
$statusFilter = $_GET['status'] ?? '';

// Startwert
$start = ($page - 1) * $limit;

// Gesamteinträge zählen
$countSql = "SELECT COUNT(*) as total FROM auftraege";
if ($statusFilter) {
    $countSql .= " WHERE status = '".$conn->real_escape_string($statusFilter)."'";
}
$totalRes = $conn->query($countSql);
$totalRow = $totalRes->fetch_assoc();
$totalEntries = $totalRow['total'];
$totalPages = ceil($totalEntries / $limit);

// Aufträge abfragen inkl. Kunden
$sql = "
    SELECT a.id, a.name, a.datum, a.anzahl, a.status,
           k.firma, k.ansprechpartner, k.telefon, k.strasse, k.plz, k.ort, k.versandart,
		   p.projektname,
           COUNT(af.id) AS filament_count,
           COALESCE(SUM(af.menge_geplant), 0) AS gesamt_geplant,
           COALESCE(SUM(af.menge_gebucht), 0) AS gesamt_gebucht
    FROM auftraege a
    LEFT JOIN kunden k ON a.kunde_id = k.id
	LEFT JOIN projekte p ON a.projekt_id = p.id
    LEFT JOIN auftrag_filamente af ON a.id = af.auftrag_id
";
if ($statusFilter) {
    $sql .= " WHERE a.status = '".$conn->real_escape_string($statusFilter)."'";
}
$sql .= " GROUP BY a.id ORDER BY a.id DESC LIMIT $start, $limit";

$res = $conn->query($sql);
?>

<section class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h2>Auftragsliste</h2>
        <div>
            <form method="get" style="display:inline;">
                <input type="hidden" name="site" value="auftraege">
                <select name="status" onchange="this.form.submit()">
                    <option value="">-- Alle --</option>
                    <option value="offen" <?= $statusFilter=='offen'?'selected':'' ?>>Offen</option>
                    <option value="in_bearbeitung" <?= $statusFilter=='in_bearbeitung'?'selected':'' ?>>In Bearbeitung</option>
                    <option value="fertig" <?= $statusFilter=='fertig'?'selected':'' ?>>Fertig</option>
                </select>
            </form>
            <a href="index.php?site=auftraege_anlegen" class="btn-primary">+ Neuer Auftrag</a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th class="center" style="width:5%;">ID</th>
					<th class="left" style="widht:10%">Vorlage</th>
                    <th class="left" style="width:15%;">Auftragsname</th>
                    <th class="left" style="width:15%;">Kunde</th>
                    <th class="left" style="width:5%;">Anzahl</th>
                    <th class="center" style="width:10%;">Status</th>
                    <th class="right" style="width:10%;">Geplant (g)</th>
                    <th class="right" style="width:10%;">Gebucht (g)</th>
                    <th style="width:10%;">Datum</th>
                    <th style="width:10%;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php while($a = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td>
                        <?= htmlspecialchars($a['projektname'] ?? '') ?><br>
                    </td>					
                    <td>
                        <a href="index.php?site=auftraege_details&id=<?= $a['id'] ?>">
                            <?= htmlspecialchars($a['name'] ?? '') ?>
                        </a>
                    </td>
                    <td>
                        <?= htmlspecialchars($a['firma'] ?: $a['ansprechpartner'] ?? '') ?><br>
                        <small><?= htmlspecialchars($a['telefon'] ?? '') ?></small><br>
                    </td>
                    <td class="center"><?= $a['anzahl'] ?></td>
					<td class="center">
					<?php if ($a['status'] !== 'offen'): ?>
						<span class="status-badge status-<?= $a['status'] ?>">
							<?= ucfirst(str_replace('_', ' ', $a['status'])) ?>
						</span>
					<?php else: ?>
					<span class="status-badge status-<?= $a['status'] ?>">
					    <a href="index.php?site=auftraege_details&id=<?= $a['id'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $a['status'])) ?>
                        </a>
					</span>
					<?php endif; ?>
					</td>
                    <td class="right"><?= $a['gesamt_geplant'] ?> g</td>
                    <td class="right"><?= $a['gesamt_gebucht'] ?> g</td>
                    <td class="center"><?= htmlspecialchars($a['datum'] ?? '') ?></td>
                    <td class="actions center">
						<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin', 'user'])): ?>
							<a href="index.php?site=auftraege_bearbeiten&id=<?= $a['id'] ?>" class="btn-action edit" title="Bearbeiten">
								<i class="fa-solid fa-pen-to-square"></i>
							</a>
							
							<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin'])): ?>
							<a href="index.php?site=auftraege_loeschen&id=<?= $a['id'] ?>" class="btn-action delete" title="Löschen">
								<i class="fa-solid fa-trash"></i>
							</a>
							<?php endif; ?>
							
							<?php if ($a['status'] !== 'fertig'): ?>
								<a href="index.php?site=auftraege_buchen&id=<?= $a['id'] ?>" class="btn-action book" title="Buchen">
									<i class="fa-solid fa-box-open"></i>
								</a>
							<?php else: ?>
									<a href="index.php?site=rechnungen_erstellen&id=<?= $a['id'] ?>" class="btn-action book" title="Rechnung erstellen">
										<i class="fa-solid fa-file-invoice-dollar"></i>
									</a>
							<?php endif; ?>
						
						<?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?site=auftraege&page=<?= $page - 1 ?>&status=<?= urlencode($statusFilter) ?>">&laquo; Zurück</a>
        <?php endif; ?>

        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <?php if($i == $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?site=auftraege&page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if($page < $totalPages): ?>
            <a href="?site=auftraege&page=<?= $page + 1 ?>&status=<?= urlencode($statusFilter) ?>">Weiter &raquo;</a>
        <?php endif; ?>
    </div>
</section>
