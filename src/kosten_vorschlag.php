<?php
require_once "auth.php";
require_role(['superuser','admin','user']);

// Filamente, Drucker, Betriebskosten laden
$filamente = $conn->query("
    SELECT f.id, f.name_des_filaments, h.hr_name, m.name AS material, f.preis, f.gewicht_des_filaments
    FROM filamente f
    LEFT JOIN hersteller h ON f.hersteller_id = h.id
    LEFT JOIN materialien m ON f.material = m.id
    ORDER BY h.hr_name, f.name_des_filaments
")->fetch_all(MYSQLI_ASSOC);

$drucker = $conn->query("SELECT id, name, stromverbrauch_watt FROM drucker ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$res = $conn->query("SELECT standard_betrag FROM betriebskosten WHERE kostenart='Strompreis' LIMIT 1");
$strompreis = (float)($res->fetch_assoc()['standard_betrag'] ?? 0.30);

$betriebskosten = $conn->query("
    SELECT * FROM betriebskosten 
    WHERE kostenart != 'Strompreis'
    ORDER BY kostenart
")->fetch_all(MYSQLI_ASSOC);

$gesamt = 0;
$gesamt_betrieb = 0;
$gesamt_filament = 0;
$gesamt_stromkosten = 0;
$gesamt_verbrauch_kwh = 0;
$gesamt_druckzeit_s = 0;
$ergebnis = [];
$extraKosten = [];
$positionen = $_POST['position'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Filamentpositionen ---
    foreach ($positionen as $i => $pos) {
        $fid = (int)($pos['filament_id'] ?? 0);
        if (!$fid) continue;

        $menge_g = (float)($pos['menge'] ?? 0);
        $tage = (int)($pos['tage'] ?? 0);
        $stunden = (int)($pos['stunden'] ?? 0);
        $minuten = (int)($pos['minuten'] ?? 0);
        $sekunden = (int)($pos['sekunden'] ?? 0);

        $druckzeit_s = ($tage * 86400) + ($stunden * 3600) + ($minuten * 60) + $sekunden;
        $druckzeit_h = $druckzeit_s / 3600;
        $gesamt_druckzeit_s += $druckzeit_s;

        $drucker_id = (int)($_POST['drucker_id'] ?? 0);
        $leistung = (float)($conn->query("SELECT stromverbrauch_watt FROM drucker WHERE id=$drucker_id")->fetch_assoc()['stromverbrauch_watt'] ?? 0);

        $f = $conn->query("SELECT preis, gewicht_des_filaments, name_des_filaments FROM filamente WHERE id=$fid")->fetch_assoc();
        $preis = (float)$f['preis'];
        $gewicht = (float)$f['gewicht_des_filaments'];
        $filament_name = $f['name_des_filaments'];

        $materialkosten = ($menge_g / max($gewicht, 1)) * $preis;

        $verbrauch_kwh = ($leistung * $druckzeit_h) / 1000;
        $stromkosten = $verbrauch_kwh * $strompreis;

        $summe = $materialkosten + $stromkosten;

        $gesamt_filament += $materialkosten;
        $gesamt_stromkosten += $stromkosten;
        $gesamt_verbrauch_kwh += $verbrauch_kwh;
        $gesamt += $summe;

        $ergebnis[] = [
            'filament_name' => $filament_name,
            'menge' => $menge_g,
            'tage' => $tage,
            'stunden' => $stunden,
            'minuten' => $minuten,
            'sekunden' => $sekunden,
            'materialkosten' => $materialkosten,
            'stromkosten' => $stromkosten,
            'summe' => $summe
        ];
    }

    // --- Betriebskosten ---
    if (!empty($_POST['kosten'])) {
        foreach ($_POST['kosten'] as $kosten_id => $pos) {
            if (empty($pos['selected'])) continue;

            $kosten_id = (int)$kosten_id;
            $anzahl = (float)($pos['anzahl'] ?? 1);

            $stmt = $conn->prepare("SELECT kostenart, standard_betrag, einheit FROM betriebskosten WHERE id=?");
            $stmt->bind_param("i", $kosten_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            $beschreibung = $row['kostenart'];
            $betrag = (float)$row['standard_betrag'];
            $kosten = 0;

            switch ($row['einheit']) {
                case 'pro_stunde':
                    $total_hours = $gesamt_druckzeit_s / 3600;
                    $kosten = $betrag * $total_hours * $anzahl;
                    break;
                default:
                    $kosten = $betrag * $anzahl;
                    break;
            }

            $gesamt_betrieb += $kosten;
            $extraKosten[] = [
                'beschreibung' => $beschreibung,
                'einheit' => $row['einheit'],
                'betrag' => $kosten
            ];
        }
    }

    $gesamt += $gesamt_betrieb;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    $projektname = trim($_POST['projektname'] ?? '');
    $kommentar = trim($_POST['kommentar'] ?? '');
    $druckzeit_seconds = (int)($_POST['gesamt_druckzeit'] ?? 0);

    if ($projektname === '') {
        $_SESSION['error'] = '<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> Bitte einen Projektnamen angeben.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO projekte (projektname, kommentar, druckzeit_seconds) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $projektname, $kommentar, $druckzeit_seconds);
        $stmt->execute();
        $projekt_id = $stmt->insert_id;

        // Filamente übernehmen
        if (!empty($_POST['position'])) {
            foreach ($_POST['position'] as $p) {
                $fid = (int)($p['filament_id'] ?? 0);
                $menge = (float)($p['menge'] ?? 0);
                if ($fid && $menge > 0) {
                    $stmt2 = $conn->prepare("INSERT INTO projekt_filamente (projekt_id, filament_id, menge_geplant) VALUES (?, ?, ?)");
                    $stmt2->bind_param("iid", $projekt_id, $fid, $menge);
                    $stmt2->execute();
                }
            }
        }

        $_SESSION['success'] = '
        <div class="info-box">
            <i class="fa-solid fa-circle-check"></i>
            <span>Projekt <strong>' . htmlspecialchars($projektname) . '</strong> wurde erfolgreich aus dem Kostenvorschlag erstellt.</span>
        </div>';
        header("Location: index.php?site=projekte");
        exit;
    }
}

?>

<section class="card">
  <div class="card-header">
    <h2>Kostenvorschlag</h2>
    <a href="index.php?site=start" class="btn-primary">← Zurück</a>
  </div>

	<div class="info-box">
		<i class="fa-solid fa-circle-info"></i>
		<span>Hier kann ein Auftrag kalkuliert werden und ist noch nicht im System! Wenn der Auftrag final wird, kann am Ende der Vorschlag als Projekt übernommen werden.</span>
	</div>

  <?php if ($gesamt > 0): ?>
  <div class="info-box" style="font-size:1.2em; margin-bottom:15px;">
    <i class="fa-solid fa-coins"></i>
    <strong>Gesamtkosten: <?= number_format($gesamt, 2, ',', '.') ?> €</strong><br>
    <small>
      Gesamtdruckzeit: 
      <?= floor($gesamt_druckzeit_s/86400) ?> T 
      <?= floor(($gesamt_druckzeit_s%86400)/3600) ?> h 
      <?= floor(($gesamt_druckzeit_s%3600)/60) ?> min 
      <?= $gesamt_druckzeit_s%60 ?> s
      | Stromverbrauch: <?= number_format($gesamt_verbrauch_kwh,2,',','.') ?> kWh 
      | Stromkosten: <?= number_format($gesamt_stromkosten,2,',','.') ?> €
    </small>
  </div>
  <?php endif; ?>

  <!-- Formular -->
  <form method="post" id="kostenForm">
    <div class="form-group">
      <label for="drucker_id">Drucker</label>
      <select name="drucker_id" id="drucker_id" required>
        <option value="">– Drucker wählen –</option>
        <?php foreach($drucker as $d): ?>
          <option value="<?= $d['id'] ?>" <?= (($_POST['drucker_id'] ?? '') == $d['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['name']) ?> (<?= $d['stromverbrauch_watt'] ?> W)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <h3>Filamentpositionen</h3>
    <div id="positionContainer">
      <?php
      if (empty($positionen)) $positionen = [['filament_id' => '', 'menge' => '', 'tage' => 0, 'stunden' => 0, 'minuten' => 0, 'sekunden' => 0]];
      foreach ($positionen as $i => $pos):
      ?>
      <div class="form-row filament-block" style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:10px;">
        <div class="form-group" style="flex:2;">
          <label>Filament</label>
          <select name="position[<?= $i ?>][filament_id]" required>
            <option value="">Bitte wählen</option>
            <?php foreach($filamente as $f): ?>
              <option value="<?= $f['id'] ?>" <?= ($pos['filament_id'] ?? '') == $f['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($f['hr_name'].' - '.$f['name_des_filaments'].' ('.$f['material'].')') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="flex:1;">
          <label>Menge (g)</label>
          <input type="number" name="position[<?= $i ?>][menge]" min="1" step="0.1" value="<?= htmlspecialchars($pos['menge'] ?? '') ?>" required>
        </div>
        <div class="form-group" style="flex:3;">
          <label>Druckzeit</label>
          <div style="display:flex; gap:5px; flex-wrap:wrap;">
            <input type="number" name="position[<?= $i ?>][tage]" value="<?= htmlspecialchars($pos['tage'] ?? 0) ?>" min="0" style="width:60px;">T
            <input type="number" name="position[<?= $i ?>][stunden]" value="<?= htmlspecialchars($pos['stunden'] ?? 0) ?>" min="0" max="23" style="width:60px;">h
            <input type="number" name="position[<?= $i ?>][minuten]" value="<?= htmlspecialchars($pos['minuten'] ?? 0) ?>" min="0" max="59" style="width:60px;">m
            <input type="number" name="position[<?= $i ?>][sekunden]" value="<?= htmlspecialchars($pos['sekunden'] ?? 0) ?>" min="0" max="59" style="width:60px;">s
          </div>
        </div>
        <div class="form-group" style="flex:0.5;">
          <button type="button" class="btn-action delete removeBlock" title="Entfernen">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <button type="button" class="btn-primary" id="addPosition">+ Position hinzufügen</button>

    <h3>Weitere Betriebskosten</h3>
    <table class="styled-table">
      <thead><tr><th>Art</th><th>Betrag (€)</th><th>Einheit</th><th>Anzahl</th><th>Auswahl</th></tr></thead>
      <tbody>
        <?php foreach($betriebskosten as $b): ?>
        <tr>
          <td><?= htmlspecialchars($b['kostenart']) ?></td>
          <td class="right"><?= number_format($b['standard_betrag'],2,',','.') ?></td>
          <td><?= htmlspecialchars($b['einheit']) ?></td>
          <td><input type="number" name="kosten[<?= $b['id'] ?>][anzahl]" value="<?= htmlspecialchars($_POST['kosten'][$b['id']]['anzahl'] ?? 1) ?>" min="1"></td>
          <td><input type="checkbox" name="kosten[<?= $b['id'] ?>][selected]" value="1" <?= !empty($_POST['kosten'][$b['id']]['selected']) ? 'checked' : '' ?>></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div style="margin-top:20px;">
      <button type="submit" class="btn-primary">Berechnen</button>
    </div>
  </form>

  <?php if ($ergebnis): ?>
  <h3>Kostenaufstellung</h3>

  <!-- I. Filamentkosten -->
  <h4>I. Filamentkosten</h4>
  <table class="styled-table">
    <thead><tr><th>Filament</th><th>Menge (g)</th><th>Druckzeit</th><th>Material (€)</th><th>Strom (€)</th><th>Summe (€)</th></tr></thead>
    <tbody>
      <?php foreach($ergebnis as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['filament_name']) ?></td>
        <td class="right"><?= number_format($r['menge'],2,',','.') ?></td>
        <td><?= "{$r['tage']}T {$r['stunden']}h {$r['minuten']}m {$r['sekunden']}s" ?></td>
        <td class="right"><?= number_format($r['materialkosten'],2,',','.') ?></td>
        <td class="right"><?= number_format($r['stromkosten'],2,',','.') ?></td>
        <td class="right"><strong><?= number_format($r['summe'],2,',','.') ?></strong></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3"><strong>Zwischensumme Filament</strong></td>
        <td class="right"><strong><?= number_format($gesamt_filament,2,',','.') ?></strong></td>
        <td class="right"><strong><?= number_format($gesamt_stromkosten,2,',','.') ?></strong></td>
        <td class="right"><strong><?= number_format($gesamt_filament + $gesamt_stromkosten,2,',','.') ?></strong></td>
      </tr>
    </tfoot>
  </table>

  <!-- II. Betriebskosten -->
  <?php if ($extraKosten): ?>
  <h4>II. Betriebskosten</h4>
  <table class="styled-table">
    <thead><tr><th>Art</th><th>Einheit</th><th>Betrag (€)</th></tr></thead>
    <tbody>
      <?php foreach($extraKosten as $k): ?>
      <tr>
        <td><?= htmlspecialchars($k['beschreibung']) ?></td>
        <td><?= htmlspecialchars($k['einheit']) ?></td>
        <td class="right"><?= number_format($k['betrag'],2,',','.') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="2"><strong>Zwischensumme Betriebskosten</strong></td><td class="right"><strong><?= number_format($gesamt_betrieb,2,',','.') ?></strong></td></tr>
    </tfoot>
  </table>
  <?php endif; ?>

  <!-- III. Gesamtsumme -->
  <h4>III. Gesamtsumme</h4>
  <table class="styled-table">
    <tr><td><strong>Gesamtbetrag aller Positionen:</strong></td><td class="right"><strong><?= number_format($gesamt,2,',','.') ?> €</strong></td></tr>
  </table>
  <?php endif; ?>

	<?php if ($ergebnis): ?>
	<hr style="margin:30px 0;">
	<h3>Projekt aus diesem Kostenvorschlag erstellen</h3>
	<p class="text-muted">
		Wenn der Kostenvorschlag angenommen wurde, kannst du die Daten als Projektvorlage übernehmen.
		Es werden nur die relevanten Projektinformationen gespeichert (Name, Druckzeit, Filamente).
	</p>

	<form method="post">
	  <input type="hidden" name="gesamt_druckzeit" value="<?= $gesamt_druckzeit_s ?>">

	  <div class="form-group">
		<label for="projektname">Projektname</label>
		<input type="text" id="projektname" name="projektname" placeholder="z. B. Musterteil Rot" required>
	  </div>

	  <div class="form-group">
		<label for="kommentar">Kommentar</label>
		<textarea id="kommentar" name="kommentar" rows="3" placeholder="Beschreibung oder Notizen"></textarea>
	  </div>

	  <!-- versteckte Übernahme der Filamente -->
	  <?php foreach($positionen as $i => $p): ?>
		<input type="hidden" name="position[<?= $i ?>][filament_id]" value="<?= htmlspecialchars($p['filament_id'] ?? '') ?>">
		<input type="hidden" name="position[<?= $i ?>][menge]" value="<?= htmlspecialchars($p['menge'] ?? '') ?>">
	  <?php endforeach; ?>

	  <button type="submit" name="save_project" class="btn-primary">✅ Als Projekt speichern</button>
	</form>
	<?php endif; ?>

</section>

<script>
let index = <?= count($positionen) ?>;
const options = `<?php foreach($filamente as $f): ?>
<option value="<?= $f['id'] ?>"><?= addslashes(htmlspecialchars($f['hr_name'].' - '.$f['name_des_filaments'].' ('.$f['material'].')')) ?></option>
<?php endforeach; ?>`;

document.getElementById('addPosition').addEventListener('click', () => {
  const c = document.getElementById('positionContainer');
  const b = document.createElement('div');
  b.className = 'form-row filament-block';
  b.style = "display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;border-bottom:1px solid #eee;padding-bottom:10px;margin-bottom:10px;";
  b.innerHTML = `
    <div class="form-group" style="flex:2;">
      <label>Filament</label>
      <select name="position[${index}][filament_id]" required>
        <option value="">Bitte wählen</option>${options}
      </select>
    </div>
    <div class="form-group" style="flex:1;">
      <label>Menge (g)</label>
      <input type="number" name="position[${index}][menge]" min="1" step="0.1" required>
    </div>
    <div class="form-group" style="flex:3;">
      <label>Druckzeit</label>
      <div style="display:flex;gap:5px;flex-wrap:wrap;">
        <input type="number" name="position[${index}][tage]" min="0" style="width:60px;">T
        <input type="number" name="position[${index}][stunden]" min="0" max="23" style="width:60px;">h
        <input type="number" name="position[${index}][minuten]" min="0" max="59" style="width:60px;">m
        <input type="number" name="position[${index}][sekunden]" min="0" max="59" style="width:60px;">s
      </div>
    </div>
    <div class="form-group" style="flex:0.5;">
      <button type="button" class="btn-action delete removeBlock" title="Entfernen">
        <i class="fa-solid fa-trash"></i>
      </button>
    </div>`;
  c.appendChild(b);
  index++;
});

document.getElementById('kostenForm').addEventListener('click', e => {
  if (e.target.closest('.removeBlock')) e.target.closest('.filament-block').remove();
});
</script>
