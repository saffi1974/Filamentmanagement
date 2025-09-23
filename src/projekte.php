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

// Gesamteinträge zählen
$totalRes = $conn->query("SELECT COUNT(*) as total FROM projekte");
$totalRow = $totalRes->fetch_assoc();
$totalEntries = $totalRow['total'];
$totalPages = ceil($totalEntries / $limit);

// Abfrage mit JOINs für Filament-Anzahl und -Menge
$res = $conn->query("
    SELECT p.id, p.projektname, p.kommentar, p.datum, p.druckzeit_seconds,
           COUNT(pf.id) AS filament_count,
           COALESCE(SUM(pf.menge_geplant), 0) AS gesamt_menge
    FROM projekte p
    LEFT JOIN projekt_filamente pf ON p.id = pf.projekt_id
    GROUP BY p.id
    ORDER BY p.id DESC
    LIMIT $start, $limit
");
?>

<section class="card">
    <div class="card-header">
        <h2>Projektliste</h2>
        <a href="index.php?site=projekte_anlegen" class="btn-primary">+ Neues Projekt</a>
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
                    <th class="left" style="width:5%;">ID</th>
                    <th class="left" style="width:18%;">Projektname</th>
                    <th class="left" style="width:18%;">Kommentar</th>
                    <th class="center" style="width:5%;">Filamente</th>
                    <th class="right" style="width:10%;">Gesamtmenge (g)</th>
                    <th class="center" style="width:11%;">Druckzeit</th>
                    <th class="center" style="width:15%;">Erstellt</th>
                    <th class="center" style="width:10%;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $res->fetch_assoc()): ?>
                <?php
                    $secs = (int)$p['druckzeit_seconds'];
                    $days = floor($secs / 86400);
                    $hours = floor(($secs % 86400) / 3600);
                    $minutes = floor(($secs % 3600) / 60);
                    $seconds = $secs % 60;

                    $druckzeit_str = "";
                    if ($days > 0) $druckzeit_str .= $days . " T ";
                    if ($hours > 0) $druckzeit_str .= $hours . " h ";
                    if ($minutes > 0) $druckzeit_str .= $minutes . " m ";
                    if ($seconds > 0 || $druckzeit_str === "") $druckzeit_str .= $seconds . " s";
                ?>
                <tr>
                    <td class="left"><?= $p['id'] ?></td>
                    <td class="left"><?= htmlspecialchars($p['projektname']) ?></td>
                    <td class="left"><?= htmlspecialchars($p['kommentar']) ?></td>
                    <td class="center"><?= $p['filament_count'] ?></td>
                    <td class="center"><?= $p['gesamt_menge'] ?> g</td>
                    <td class="center"><?= $druckzeit_str ?></td>
                    <td class="center"><?= htmlspecialchars($p['datum']) ?></td>
                    <td class="actions center">
					<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin', 'user'])): ?>
                        
						<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin'])): ?>
						<a href="index.php?site=projekte_bearbeiten&id=<?= $p['id'] ?>" class="btn-action edit" title="Bearbeiten">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
						<?php endif; ?>
						<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin'])): ?>
						<a href="index.php?site=projekte_loeschen&id=<?= $p['id'] ?>" class="btn-action delete" title="Löschen">
							<i class="fa-solid fa-trash"></i>
						</a>
						<?php endif; ?>
                        <a href="index.php?site=projekte_buchen&id=<?= $p['id'] ?>" class="btn-action book" title="Buchen">
                            <i class="fa-solid fa-box-open"></i>
                        </a>

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
            <a href="?site=projekte&page=<?= $page - 1 ?>">&laquo; Zurück</a>
        <?php endif; ?>

        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <?php if($i == $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?site=projekte&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if($page < $totalPages): ?>
            <a href="?site=projekte&page=<?= $page + 1 ?>">Weiter &raquo;</a>
        <?php endif; ?>
    </div>
</section>
