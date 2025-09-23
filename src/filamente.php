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
$totalRes = $conn->query("SELECT COUNT(*) as total FROM filamente");
$totalRow = $totalRes->fetch_assoc();
$totalEntries = $totalRow['total'];
$totalPages = ceil($totalEntries / $limit);

// Abfrage mit LIMIT
$res = $conn->query("
    SELECT f.*, h.hr_name, m.name AS material_name
    FROM filamente f
    LEFT JOIN hersteller h ON f.hersteller_id = h.id
    LEFT JOIN materialien m ON f.material = m.id
    ORDER BY f.id
    LIMIT $start, $limit
");
?>

<section class="card">
    <div class="card-header">
        <h2>Filamente</h2>
        <a href="index.php?site=filament_anlegen" class="btn-primary">+ Neues Filament</a>
    </div>

    <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th class="left" style="width:5%;">ID</th>
                    <th class="left" style="width: 10%;">Hersteller</th>
                    <th class="center">Farben</th>
                    <th class="left" style="width: 15%;">Name</th>
                    <th class="left">Material</th>
                    <th class="center">Preis</th>
                    <th class="center">Gewicht</th>
                    <th class="center">Artikelnummer</th>
                    <th class="right">Düse</th>
                    <th class="right">Bett</th>
                    <th class="left">Kommentar</th>
                    <th class="center">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php while($h = $res->fetch_assoc()): ?>
                <tr>
                    <td class="left"><?= $h['id'] ?></td>
                    <td class="left"><?= htmlspecialchars($h['hr_name']) ?></td>
                    <td class="center">
                        <?php 
                            $farben = json_decode($h['farben'], true);
                            if (!empty($farben)) {
                                foreach ($farben as $hex) {
                                    echo "<span style='display:inline-block;width:20px;height:20px;background:$hex;border:1px solid #000;margin-right:3px;'></span>";
                                }
                            }
                        ?>
                    </td>
                    <td class="left"><?= htmlspecialchars($h['name_des_filaments']) ?></td>
                    <td class="left"><?= htmlspecialchars($h['material_name']) ?></td>
                    <td class="right"><?= htmlspecialchars($h['preis']) ?> €</td>
                    <td class="right"><?= htmlspecialchars($h['gewicht_des_filaments']) ?> g</td>
                    <td class="center"><?= htmlspecialchars($h['artikelnummer_des_herstellers']) ?></td>
                    <td class="right"><?= htmlspecialchars($h['duesentemperatur']) ?> °C</td>
                    <td class="right"><?= htmlspecialchars($h['betttemperatur']) ?> °C</td>
                    <td class="left"><?= htmlspecialchars($h['kommentar']) ?></td>
                    <td class="actions center">
					<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin', 'user'])): ?>
                        <a href="index.php?site=filament_bearbeiten&id=<?= $h['id'] ?>" class="btn-action edit" title="Bearbeiten">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin'])): ?>
                            <a href="index.php?site=filament_loeschen&id=<?= $h['id'] ?>" class="btn-action delete" title="Löschen">
                                <i class="fa-solid fa-trash"></i>
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
            <a href="?site=filamente&page=<?= $page - 1 ?>">&laquo; Zurück</a>
        <?php endif; ?>

        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <?php if($i == $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?site=filamente&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if($page < $totalPages): ?>
            <a href="?site=filamente&page=<?= $page + 1 ?>">Weiter &raquo;</a>
        <?php endif; ?>
    </div>
</section>
