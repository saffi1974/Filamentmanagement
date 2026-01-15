<?php
if (isset($_SESSION['success'])) {
    echo $_SESSION['success'];
    unset($_SESSION['success']);
}
require_once "auth.php";
require_role(['superuser','admin']);

// Anzahl Einträge pro Seite
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// Gesamteinträge zählen
$totalRes = $conn->query("SELECT COUNT(*) as total FROM rechnungen");
$totalRow = $totalRes->fetch_assoc();
$totalEntries = $totalRow['total'];
$totalPages = ceil($totalEntries / $limit);

// Rechnungen laden mit Kunde & vereinbartem Preis
$sql = "SELECT r.*, 
               k.firma, k.ansprechpartner, k.strasse, k.plz, k.ort, k.telefon,
               a.name AS auftragsname,
               a.preis_vereinbart,
               p.projektname
        FROM rechnungen r
        LEFT JOIN kunden k ON r.kunde_id = k.id
        LEFT JOIN auftraege a ON r.auftrag_id = a.id
        LEFT JOIN projekte p ON a.projekt_id = p.id
        ORDER BY r.id DESC
        LIMIT $start, $limit";
$res = $conn->query($sql);
?>

<section class="card">
    <div class="card-header">
        <h2>Rechnungen</h2>
        <div><a href="index.php?site=auftraege" class="btn-primary">← zu den Aufträgen</a></div>
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
                    <th class="center" style="width:8%;">Nr.</th>
                    <th class="left" style="width:12%;">Kunde</th>
                    <th class="left" style="width:15%;">Auftrag</th>
                    <th class="left" style="width:10%;">Vorlage</th>
                    <th class="center" style="width:8%;">Datum</th>
                    <th class="right" style="width:10%;">Betrag (€)</th>
                    <th class="right" style="width:10%;">Preis (vereinbart)</th>
                    <th style="width:5%;">Status</th>
                    <th style="width:10%;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['rechnungsnummer']) ?></td>
                    <td><?= htmlspecialchars($r['firma'] ?: $r['ansprechpartner']) ?></td>
                    <td><?= htmlspecialchars($r['auftragsname'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['projektname'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['datum']) ?></td>

                    <!-- Gesamtbetrag -->
                    <td class="right"><?= number_format($r['gesamtbetrag'], 2, ',', '.') ?> €</td>

                    <!-- Vereinbarter Preis -->
                    <td class="right" style="color:<?= ($r['preis_vereinbart'] ?? 0) != ($r['gesamtbetrag'] ?? 0) ? '#e67e22' : '#27ae60' ?>">
                        <?= $r['preis_vereinbart'] !== null ? number_format($r['preis_vereinbart'], 2, ',', '.') . ' €' : '–' ?>
                    </td>

                    <td>
                        <?php
                        $status = $r['status'];
                        if ($status === 'offen') echo "<span class='status-badge status-open'>Offen</span>";
                        elseif ($status === 'bezahlt') echo "<span class='status-badge status-paid'>Bezahlt</span>";
                        elseif ($status === 'storniert') echo "<span class='status-badge status-cancel'>Storniert</span>";
                        ?>
                    </td>

                    <td class="actions center">
                        <a href="index.php?site=rechnungen_details&id=<?= $r['id'] ?>" class="btn-action view" title="Details">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a href="index.php?site=rechnungen_bearbeiten&id=<?= $r['id'] ?>" class="btn-action edit" title="Bearbeiten">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <?php if(isset($_SESSION['username'])) { ?>
                        <a href="index.php?site=rechnungen_loeschen&id=<?= $r['id'] ?>" class="btn-action delete" title="Löschen">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                        <?php } ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?site=rechnungen&page=<?= $page - 1 ?>">&laquo; Zurück</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?site=rechnungen&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?site=rechnungen&page=<?= $page + 1 ?>">Weiter &raquo;</a>
        <?php endif; ?>
    </div>
</section>

<style>
.status-badge {
    padding: 3px 8px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: bold;
}
.status-open { background:#f39c12; color:#fff; }
.status-paid { background:#27ae60; color:#fff; }
.status-cancel { background:#e74c3c; color:#fff; }
</style>
