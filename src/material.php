<?php
if (isset($_SESSION['success'])) {
    echo $_SESSION['success'];
    unset($_SESSION['success']);
}

$res = $conn->query("SELECT * FROM materialien ORDER BY id");
?>

<section class="card">
    <div class="card-header">
        <h2>Materialien</h2>
        <a href="index.php?site=material_anlegen" class="btn-primary">+ Neues Material</a>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th class="center">ID</th>
                <th class="left">Name</th>
				<th class="left">Kommentar</th>
                <th class="center">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php while($h = $res->fetch_assoc()): ?>
            <tr>
                <td class="center"><?php echo $h['id']; ?></td>
                <td><?php echo htmlspecialchars($h['name']); ?></td>
				<td><?php echo htmlspecialchars($h['kommentar']); ?></td>
				<td class="actions">
				<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin', 'user'])): ?>
					<a href="index.php?site=material_bearbeiten&id=<?php echo $h['id']; ?>" class="btn-action edit" title="Bearbeiten">
						<i class="fa-solid fa-pen-to-square"></i>
					</a>
					<?php if (isset($_SESSION['rolle']) && in_array($_SESSION['rolle'], ['superuser','admin'])): ?>
						<a href="index.php?site=material_loeschen&id=<?php echo $h['id']; ?>" class="btn-action delete" title="Löschen">
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