<?php
if (isset($_SESSION['success'])) {
    echo $_SESSION['success'];
    unset($_SESSION['success']);
}
$res = $conn->query("SELECT * FROM hersteller ORDER BY id");
?>

<section class="card">
    <div class="card-header">
        <h2>Hersteller</h2>
        <a href="index.php?site=hersteller_anlegen" class="btn-primary">+ Neuer Hersteller</a>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th class="left" style="width:5%;">ID</th>
                <th class="left" style="width:15%;">Name</th>
				<th class="center" style="width:15%;padding-right:10px;">Gewicht Leerspule in g</th>
				<th class="left" style="width: 25%;">Kommentar</th>
				<th class="left">hinzugefügt am</th>
                <th class="center">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php while($h = $res->fetch_assoc()): ?>
            <tr>
                <td class="left"><?php echo $h['id']; ?></td>
                <td class="left"><?php echo htmlspecialchars($h['hr_name']); ?></td>
				<td class="right" style="width:15%;padding-right:5%;"><?php echo htmlspecialchars($h['hr_leerspule']); ?> g</td>
				<td class="left" style="width: 25%;"><?php echo htmlspecialchars($h['hr_kommentar']); ?></td>
				<td class="left"><?php echo date("d.m.Y H:i:s", strtotime($h['hr_eingetragen'])); ?></td>
				<td class="actions center">
				<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin', 'user'])): ?>
					<a href="index.php?site=hersteller_bearbeiten&id=<?php echo $h['id']; ?>" class="btn-action edit" title="Bearbeiten">
						<i class="fa-solid fa-pen-to-square"></i>
					</a>
					<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin'])): ?>
						<a href="index.php?site=hersteller_loeschen&id=<?php echo $h['id']; ?>" class="btn-action delete" title="Löschen">
							<i class="fa-solid fa-trash"></i>
						</a>
					<?php endif; ?>
				<?php endif; ?>
				</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>


