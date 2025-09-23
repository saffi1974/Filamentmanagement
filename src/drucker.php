<?php
$res = $conn->query("SELECT * FROM drucker ORDER BY id ASC");
if (isset($_SESSION['success'])) {
    echo $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<section class="card">
    <div class="card-header">
        <h2>Drucker</h2>
        <a href="index.php?site=drucker_anlegen" class="btn-primary">+ Neuer Drucker</a>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th class="left" style="width:5%;">ID</th>
                <th class="left" style="width:15%;">Name</th>
				<th class="center" style="width:15%;padding-right:10px;">Stromverbrauch (Watt)</th>
				<th class="center" style="width: 15%;">Kosten pro kWh</th>
				<th class="center">Kommentar</th>
                <th class="center">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php while($h = $res->fetch_assoc()): ?>
            <tr>
                <td class="left"><?php echo $h['id']; ?></td>
                <td class="left"><?php echo htmlspecialchars($h['name']); ?></td>
				<td class="right" style="width:15%;padding-right:5%;"><?= number_format($h['stromverbrauch_watt'], 0, ',', '.') ?> W</td>
				<td class="right" style="width:15%;padding-right:5%;"><?= number_format($h['kosten_pro_kwh'], 2, ',', '.') ?> €</td>
				<td class="left" style="width: 25%;"><?php echo htmlspecialchars($h['kommentar']); ?></td>
				<td class="actions center">
					<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin'])): ?>
						<a href="index.php?site=drucker_bearbeiten&id=<?php echo $h['id']; ?>" class="btn-action edit" title="Bearbeiten">
							<i class="fa-solid fa-pen-to-square"></i>
						</a>
						<a href="index.php?site=drucker_loeschen&id=<?php echo $h['id']; ?>" class="btn-action delete" title="Löschen">
							<i class="fa-solid fa-trash"></i>
						</a>
					<?php endif; ?>
				</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>


