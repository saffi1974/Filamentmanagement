<?php
require_once "auth.php";
require_role(['superuser','admin', 'user']);
// Alle Filamente laden (mit Material gleich aus Join)
$filamente = $conn->query("
    SELECT f.id, f.name_des_filaments, f.material, h.hr_name, m.name AS material_name
    FROM filamente f 
    LEFT JOIN hersteller h ON f.hersteller_id = h.id
    LEFT JOIN materialien m ON f.material = m.id
    ORDER BY h.hr_name, f.name_des_filaments
");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? null; // beim Login in anmelden.php setzen!

// Formular absenden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $spulen = $_POST['spule'] ?? [];

    foreach ($spulen as $spule) {
        $filament_id = (int)$spule['filament_id'];
        $preis = (float)$spule['preis'];
        $lagerort = $conn->real_escape_string($spule['lagerort'] ?? '');
        $chargennummer = $conn->real_escape_string($spule['chargennummer'] ?? '');
        $kommentar = $conn->real_escape_string($spule['kommentar'] ?? '');

        // passendes Material automatisch aus Filament holen
        $material_id = null;
        $matRes = $conn->query("SELECT material FROM filamente WHERE id = $filament_id");
        if ($matRes && $mat = $matRes->fetch_assoc()) {
            $material_id = (int)$mat['material'];
        }

        if ($filament_id > 0 && $material_id > 0) {
            // Spule einfügen
            $sql = "INSERT INTO spulenlager
                        (filament_id, material_id, preis, verbrauchtes_filament, verbleibendes_filament, lagerort, chargennummer, kommentar)
                    VALUES
                        ($filament_id, $material_id, $preis, 0, 1000, '$lagerort', '$chargennummer', '$kommentar')";
            $conn->query($sql);

            $spule_id = $conn->insert_id;

            // --- NEU: Wareneingang in Historie erfassen ---
            $sql = "INSERT INTO lagerbewegungen (spule_id, filament_id, user_id, bewegungsart, menge, kommentar) 
                    VALUES ($spule_id, $filament_id, $user_id, 'wareneingang', 1000, '$kommentar')";
            $conn->query($sql);
        }
    }

    echo '<div class="info-box">
            <i class="fa-solid fa-circle-info"></i>
            <span>Neue Spule(n) erfolgreich eingetragen und in der Historie Lagerbewegungen erfasst!</span>
        </div>';
}
?>

<section class="card">
    <div class="card-header">
        <h2>Neue Spulen einbuchen</h2>
        <a href="index.php?site=spulenlager" class="btn-primary">← Zum Lager</a>
    </div>

    <form method="post" class="form">
        <div id="spulen-container">
            <div class="spule-item">
                <div class="form-group">
                    <label>Filament</label>
                    <select name="spule[0][filament_id]" required>
                        <option value="">-- wählen --</option>
                        <?php while($f = $filamente->fetch_assoc()): ?>
                            <option value="<?= $f['id'] ?>">
                                <?= htmlspecialchars($f['hr_name'] . ' - ' . $f['name_des_filaments'] . ' - ' . $f['material_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Preis (€)</label>
                    <input type="number" step="0.01" name="spule[0][preis]" required>
                </div>

                <div class="form-group">
                    <label>Lagerort</label>
                    <input type="text" name="spule[0][lagerort]">
                </div>

                <div class="form-group">
                    <label>Chargennummer</label>
                    <input type="text" name="spule[0][chargennummer]">
                </div>

                <div class="form-group">
                    <label>Kommentar</label>
                    <textarea name="spule[0][kommentar]" rows="2"></textarea>
                </div>

                <hr>
            </div>
        </div>

        <button type="button" id="add-spule" class="btn-primary">+ Weitere Spule</button>
        <div style="margin-top:20px;">
            <button type="submit" class="btn-primary">Einbuchen</button>
        </div>
    </form>
</section>

<script>
// Dynamisch weitere Spulen hinzufügen
let counter = 1;
document.getElementById('add-spule').addEventListener('click', () => {
    const container = document.getElementById('spulen-container');
    const item = document.querySelector('.spule-item').cloneNode(true);
    item.querySelectorAll('input, select, textarea').forEach(el => {
        const name = el.getAttribute('name');
        el.setAttribute('name', name.replace(/\d+/, counter));
        el.value = '';
    });
    container.appendChild(item);
    counter++;
});
</script>
