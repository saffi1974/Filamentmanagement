<?php
require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Projekte laden
$projekte = $conn->query("SELECT id, projektname, druckzeit_seconds FROM projekte ORDER BY projektname")
                 ->fetch_all(MYSQLI_ASSOC);

// Kunden laden
$kunden = $conn->query("SELECT id, firma, ansprechpartner FROM kunden ORDER BY firma, ansprechpartner")
               ->fetch_all(MYSQLI_ASSOC);

$projekt_id = $_POST['projekt_id'] ?? null;
$kunde_id   = $_POST['kunde_id'] ?? null;
$name       = $_POST['name'] ?? '';
$datum      = $_POST['datum'] ?? date('Y-m-d');
$anzahl     = $_POST['anzahl'] ?? 1;

$projekt = null;
$projekt_filamente = [];

// -----------------------------
// Projekt-Daten laden (wenn Projekt gewählt)
// -----------------------------
if (!empty($projekt_id)) {
    $sql = "SELECT id, projektname, druckzeit_seconds 
            FROM projekte 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projekt_id);
    $stmt->execute();
    $projekt = $stmt->get_result()->fetch_assoc();

    // Filament-Positionen des Projekts laden
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

    // Druckzeit (nur eine pro Auftrag)
    $tage     = (int)($_POST['days'] ?? 0);
    $stunden  = (int)($_POST['hours'] ?? 0);
    $minuten  = (int)($_POST['minutes'] ?? 0);
    $sekunden = (int)($_POST['seconds'] ?? 0);

    $druckzeit_pro_stueck = $tage*86400 + $stunden*3600 + $minuten*60 + $sekunden;
    $gesamt_druckzeit = $druckzeit_pro_stueck * $anzahl;

    // Auftrag speichern
    $sql = "INSERT INTO auftraege (projekt_id, name, kunde_id, datum, anzahl, druckzeit_seconds, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'offen')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isisii", $projekt_id, $name, $kunde_id, $datum, $anzahl, $gesamt_druckzeit);
    $stmt->execute();
    $auftrag_id = $stmt->insert_id;

    // Filament-Positionen speichern
    if (!empty($_POST['position'])) {
        foreach ($_POST['position'] as $pos) {
            $filament_id   = (int)$pos['filament_id'];
            $menge_geplant = (float)$pos['menge_geplant'];

            if ($filament_id > 0 && $menge_geplant > 0) {
                $sql = "INSERT INTO auftrag_filamente (auftrag_id, filament_id, menge_geplant, menge_gebucht, status) 
                        VALUES (?, ?, ?, 0, 'geplant')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iid", $auftrag_id, $filament_id, $menge_geplant);
                $stmt->execute();
            }
        }
    }

    $_SESSION['success'] = '<div class="info-box"><i class="fa-solid fa-circle-check"></i> Auftrag erfolgreich angelegt.</div>';
    header("Location: index.php?site=auftraege");
    exit;
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
                        <?= htmlspecialchars($k['firma'] ?: ($k['ansprechpartner'] ?: "Unbekannt")) ?>
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

        <?php if (!empty($projekt)): ?>
            <div class="form-group">
                <label>Druckzeit (1 Stück)</label>
                <div style="display:flex; gap:5px; align-items:center;">
                    <input type="number" name="days"    min="0" value="<?= floor(($projekt['druckzeit_seconds'] ?? 0) / 86400) ?>" style="width:60px;"> T
                    <input type="number" name="hours"   min="0" max="23" value="<?= floor((($projekt['druckzeit_seconds'] ?? 0) % 86400) / 3600) ?>" style="width:60px;"> h
                    <input type="number" name="minutes" min="0" max="59" value="<?= floor((($projekt['druckzeit_seconds'] ?? 0) % 3600) / 60) ?>" style="width:60px;"> m
                    <input type="number" name="seconds" min="0" max="59" value="<?= ($projekt['druckzeit_seconds'] ?? 0) % 60 ?>" style="width:60px;"> s
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($projekt_filamente)): ?>
            <h3>Filament-Positionen</h3>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Filament</th>
                        <th>Menge geplant (g)</th>
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
                                <button type="button" onclick="this.closest('tr').remove()">❌ Entfernen</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">

        <?php if (!empty($projekt)): ?>
            <button type="submit" name="save_auftrag" class="btn-primary">💾 Auftrag speichern</button>
        <?php endif; ?>
    </form>
</section>
