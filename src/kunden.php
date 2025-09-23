<?php 
if (isset($_SESSION['success'])) {
    echo $_SESSION['success'];
    unset($_SESSION['success']);
}
// Anzahl Einträge pro Seite
$limit = 13;

// Aktuelle Seite
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;

// Startwert
$start = ($page - 1) * $limit;

// Gesamteinträge zählen
$totalRes = $conn->query("SELECT COUNT(*) as total FROM kunden");
$totalRow = $totalRes->fetch_assoc();
$totalEntries = $totalRow['total'];
$totalPages = ceil($totalEntries / $limit);

// Kunden abfragen
$res = $conn->query("
    SELECT id, firma, ansprechpartner, telefon, strasse, plz, ort, versandart, angelegt_am
    FROM kunden
    ORDER BY id DESC
    LIMIT $start, $limit
");
?>

<section class="card">
    <div class="card-header">
        <h2>Kundenliste</h2>
        <a href="index.php?site=kunden_anlegen" class="btn-primary">+ Neuer Kunde</a>
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
                    <th class="left" style="width:15%;">Firma</th>
                    <th class="left" style="width:15%;">Ansprechpartner</th>
                    <th class="left" style="width:10%;">Telefon</th>
                    <th class="left" style="width:20%;">Adresse</th>
                    <th class="left" style="width:10%;">Versandart</th>
                    <th class="left" style="width:10%;">Angelegt am</th>
                    <th class="center" style="width:15%;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php while($k = $res->fetch_assoc()): ?>
                <tr>
                    <td class="center"><?= $k['id'] ?></td>
                    <td><?= htmlspecialchars($k['firma']) ?></td>
                    <td><?= htmlspecialchars($k['ansprechpartner']) ?></td>
                    <td><?= htmlspecialchars($k['telefon']) ?></td>
                    <td>
                        <?= htmlspecialchars($k['strasse']) ?><br>
                        <?= htmlspecialchars($k['plz']) ?> <?= htmlspecialchars($k['ort']) ?>
                    </td>
                    <td><?= $k['versandart'] ?></td>
                    <td><?= $k['angelegt_am'] ?></td>
                    <td class="actions center">
					<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin', 'user'])): ?>
                        <a href="index.php?site=kunden_bearbeiten&id=<?= $k['id'] ?>" class="btn-action edit" title="Bearbeiten">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
						<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin'])): ?>
                        <a href="index.php?site=kunden_loeschen&id=<?= $k['id'] ?>" class="btn-action delete" title="Löschen">
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
            <a href="?site=kunden&page=<?= $page - 1 ?>">&laquo; Zurück</a>
        <?php endif; ?>

        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <?php if($i == $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?site=kunden&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if($page < $totalPages): ?>
            <a href="?site=kunden&page=<?= $page + 1 ?>">Weiter &raquo;</a>
        <?php endif; ?>
    </div>
</section>
