<?php
require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Projekte laden
$projekte = $conn->query("SELECT id, projektname FROM projekte ORDER BY projektname")
                 ->fetch_all(MYSQLI_ASSOC);

// Kunden laden
$kunden = $conn->query("SELECT id, firma, ansprechpartner FROM kunden ORDER BY firma, ansprechpartner")
               ->fetch_all(MYSQLI_ASSOC);

$projekt_id = $_POST['projekt_id'] ?? null;
$kunde_id   = $_POST['kunde_id'] ?? null;
$name       = $_POST['name'] ?? '';
$datum      = $_POST['datum'] ?? date('Y-m-d');
$anzahl     = $_POST['anzahl'] ?? 1;

// -----------------------------
// Auftrag speichern
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_auftrag'])) {
    if (!validate_form_token($_POST['form_token'] ?? '')) {
        $_SESSION['error'] = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> Ungültiges Formular.</div>';
        header("Location: index.php?site=auftraege");
        exit;
    }

    $projekt_id = (int)$_POST['projekt_id'];
    $kunde_id   = (int)$_POST['kunde_id'];
    $name       = trim($_POST['name']);
    $datum      = $_POST['datum'];
    $anzahl     = (int)$_POST['anzahl'];

    // Auftrag speichern
    $sql = "INSERT INTO auftraege (projekt_id, name, kunde_id, datum, anzahl, druckzeit_seconds, status) 
            VALUES (?, ?, ?, ?, ?, 0, 'offen')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isisi", $projekt_id, $name, $kunde_id, $datum, $anzahl);
    $stmt->execute();
    $auftrag_id = $stmt->insert_id;

    // Positionen speichern + Druckzeit summieren
    $gesamt_druckzeit = 0;
    if (!empty($_POST['position'])) {
        foreach ($_POST['position'] as $pos) {
            $filament_id   = (int)$pos['filament_id'];
            $menge_geplant = (float)$pos['menge_geplant'];

            // Druckzeit berechnen
            $tage     = (int)($pos['days'] ?? 0);
            $stunden  = (int)($pos['hours'] ?? 0);
            $minuten  = (int)($pos['minutes'] ?? 0);
            $sekunden = (int)($pos['seconds'] ?? 0);

            $druckzeit_seconds = $tage*86400 + $stunden*3600 + $minuten*60 + $sekunden;
            $gesamt_druckzeit += $druckzeit_seconds * $anzahl;

            if ($filament_id > 0 && $menge_geplant > 0) {
                $sql = "INSERT INTO auftrag_filamente (auftrag_id, filament_id, menge_geplant, menge_gebucht, status) 
                        VALUES (?, ?, ?, 0, 'geplant')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iid", $auftrag_id, $filament_id, $menge_geplant);
                $stmt->execute();
            }
        }
    }

    // Update Gesamtdruckzeit
    $sql = "UPDATE auftraege SET druckzeit_seconds = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $gesamt_druckzeit, $auftrag_id);
    $stmt->execute();

    $_SESSION['success'] = '<div class="info-box"><i class="fa-solid fa-circle-check"></i> Auftrag erfolgreich angelegt.</div>';
    header("Location: index.php?site=auftraege");
    exit;
}

// -----------------------------
// Projekt-Positionen laden, wenn Projekt gewählt
// -----------------------------
$projekt_filamente = [];
if (!empty($projekt_id)) {
    $sql = "SELECT pf.filament_id, pf.menge_geplant, 
                   CONCAT(h.hr_name,' | ', f.name_des_filaments,' | ', m.name) AS filament_name
            FROM projekt_filamente pf
            JOIN filamente f ON pf.filament_id = f.id
            JOIN hersteller h ON f.hersteller_id = h.id
            JOIN materialien m ON f.material = m.id
            WHERE pf.projekt_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projekt_id);
    $stmt->execute();
    $projekt_filamente = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<section class="card">
    <div class="card-header">
        <h2>Auftrag anlegen</h2>
        <a href="index.php?site=auftraege" class="btn-primary">← Zurück zur Liste</a>
    </div>

    <form method="post" class="form">
        <div class="form-group">
            <label for="projekt_id">Projekt auswählen</label>
            <select name="projekt_id" id="projekt_id" required onchange="this.form.submit()">
                <option value="">-- Projekt wählen --</option>
                <?php foreach ($projekte as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($projekt_id == $p['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['projektname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="kunde_id">Kunde auswählen</label>
            <select name="kunde_id" id="kunde_id" required>
                <option value="">-- Kunde wählen --</option>
                <?php foreach ($kunden as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= ($kunde_id == $k['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['firma'] ?: $k['ansprechpartner']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="name">Auftragsname</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
        </div>

        <div class="form-group">
            <label for="datum">Datum</label>
            <input type="date" id="datum" name="datum" value="<?= htmlspecialchars($datum) ?>" required>
        </div>

        <div class="form-group">
            <label for="anzahl">Anzahl</label>
            <input type="number" id="anzahl" name="anzahl" value="<?= htmlspecialchars($anzahl) ?>" min="1" required>
        </div>

        <?php if (!empty($projekt_filamente)): ?>
            <h3>Positionen bearbeiten</h3>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Filament</th>
                        <th>Menge geplant (g)</th>
                        <th>Druckzeit (1 Stück)</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projekt_filamente as $i => $pf): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="position[<?= $i ?>][filament_id]" value="<?= $pf['filament_id'] ?>">
                                <?= htmlspecialchars($pf['filament_name']) ?>
                            </td>
                            <td>
                                <input type="number" step="0.01" name="position[<?= $i ?>][menge_geplant]" value="<?= $pf['menge_geplant'] ?>" required>
                            </td>
                            <td>
                                <div style="display:flex; gap:5px; align-items:center;">
                                    <input type="number" name="position[<?= $i ?>][days]" min="0" value="0" style="width:60px;"> T
                                    <input type="number" name="position[<?= $i ?>][hours]" min="0" max="23" value="0" style="width:60px;"> h
                                    <input type="number" name="position[<?= $i ?>][minutes]" min="0" max="59" value="0" style="width:60px;"> m
                                    <input type="number" name="position[<?= $i ?>][seconds]" min="0" max="59" value="0" style="width:60px;"> s
                                </div>
                            </td>
                            <td>
                                <button type="button" onclick="this.closest('tr').remove()">❌ Entfernen</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">

        <?php if (!empty($projekt_filamente)): ?>
            <button type="submit" name="save_auftrag" class="btn-primary">💾 Auftrag speichern</button>
        <?php endif; ?>
    </form>
</section>
