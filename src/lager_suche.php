<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "auth.php";
require_role(['superuser','admin','user','readonly']);

// Filter
$farbe = $_GET['farbe'] ?? '';
$hersteller_id = $_GET['hersteller_id'] ?? '';
$material_id = $_GET['material_id'] ?? '';
$name = trim($_GET['name'] ?? '');

// Hersteller & Materialien laden
$herstellerRes = $conn->query("SELECT id, hr_name FROM hersteller ORDER BY hr_name");
$materialRes = $conn->query("SELECT id, name FROM materialien ORDER BY name");

// --- Farb-Hilfsfunktionen ---
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
        $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
        $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return [$r, $g, $b];
}

function getColorRange($mainColor) {
    switch (strtolower($mainColor)) {
        case 'rot':      return [[160,255],[0,100],[0,100]];
        case 'orange':   return [[190,255],[90,170],[0,70]];
        case 'gelb':     return [[210,255],[210,255],[0,80]];
        case 'grün':     return [[0,110],[130,255],[0,110]];
        case 'blau':     return [[0,110],[0,110],[130,255]];
        case 'violett':  return [[120,210],[0,110],[150,255]];
        case 'braun':    return [[110,190],[60,130],[20,90]];
        case 'grau':     return [[100,180],[100,180],[100,180]];
        case 'schwarz':  return [[0,60],[0,60],[0,60]];
        case 'weiß':     return [[220,255],[220,255],[220,255]];
        default:         return [[0,255],[0,255],[0,255]];
    }
}


$hauptfarben = [
    'Schwarz' => '#000000',
    'Weiß' => '#FFFFFF',
    'Grau' => '#808080',
    'Rot' => '#FF0000',
    'Orange' => '#FFA500',
    'Gelb' => '#FFFF00',
    'Grün' => '#00FF00',
    'Blau' => '#0000FF',
    'Violett' => '#800080',
    'Braun' => '#A52A2A'
];

// --- Haupt-SQL ---
$sql = "
    SELECT 
        f.id, 
        f.name_des_filaments, 
        f.farben,
        h.hr_name, 
        m.name AS material_name,
        SUM(s.verbleibendes_filament) AS menge,
        SUM(s.verbleibendes_filament * (s.preis / f.gewicht_des_filaments)) AS lagerwert,
        lb.letzte_verwendung
    FROM spulenlager s
    JOIN filamente f ON s.filament_id = f.id
    JOIN hersteller h ON f.hersteller_id = h.id
    JOIN materialien m ON f.material = m.id
	LEFT JOIN (
		SELECT filament_id, MAX(datum) AS letzte_verwendung
		FROM lagerbewegungen
		GROUP BY filament_id
	) lb ON lb.filament_id = f.id
    WHERE 1=1
";

// --- Farbfilter ---
if ($farbe && strtolower($farbe) !== 'alle') {
    $rgbRange = getColorRange($farbe);
    $ids = [];
    $filamentRes = $conn->query("SELECT id, farben FROM filamente");
    while ($frow = $filamentRes->fetch_assoc()) {
        $farben = json_decode($frow['farben'], true);
        if (is_array($farben)) {
            foreach ($farben as $hex) {
                [$r,$g,$b] = hexToRgb($hex);
                if (
                    $r >= $rgbRange[0][0] && $r <= $rgbRange[0][1] &&
                    $g >= $rgbRange[1][0] && $g <= $rgbRange[1][1] &&
                    $b >= $rgbRange[2][0] && $b <= $rgbRange[2][1]
                ) {
                    $ids[] = (int)$frow['id'];
                    break;
                }
            }
        }
    }
    if ($ids) $sql .= " AND f.id IN (" . implode(',', $ids) . ")";
    else $sql .= " AND 0";
}

if ($hersteller_id) $sql .= " AND h.id = " . (int)$hersteller_id;
if ($material_id) $sql .= " AND m.id = " . (int)$material_id;
if ($name) $sql .= " AND f.name_des_filaments LIKE '%" . $conn->real_escape_string($name) . "%'";

$sql .= " GROUP BY f.id ORDER BY lagerwert DESC";
$res = $conn->query($sql);

// --- Farblegende vorbereiten ---
$farblegende = [];
$filamentRes = $conn->query("
	SELECT DISTINCT f.id, f.farben
	FROM filamente f
	JOIN spulenlager s ON s.filament_id = f.id
	WHERE s.verbleibendes_filament > 0
");

while ($frow = $filamentRes->fetch_assoc()) {
    $farben = json_decode($frow['farben'], true);
    if (is_array($farben) && count($farben) > 0) {
        $hex = $farben[0];
        [$r, $g, $b] = hexToRgb($hex);
        [$mainName, $mainHex] = ['Sonstige', '#ccc'];
        foreach ($hauptfarben as $name => $hexRef) {
            $range = getColorRange($name);
            if (
                $r >= $range[0][0] && $r <= $range[0][1] &&
                $g >= $range[1][0] && $g <= $range[1][1] &&
                $b >= $range[2][0] && $b <= $range[2][1]
            ) {
                $mainName = $name;
                $mainHex = $hexRef;
                break;
            }
        }
        $farblegende[$mainName]['farbe'] = $mainHex;
        $farblegende[$mainName]['count'] = ($farblegende[$mainName]['count'] ?? 0) + 1;
    }
}
ksort($farblegende);
?>

<section class="card">
  <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h2><i class="fa-solid fa-magnifying-glass"></i> Lager durchsuchen</h2>
    <a href="index.php?site=spulen" class="btn-primary">← Zurück zum Lager</a>
  </div>

  <form method="get" action="index.php" class="form" style="margin-bottom:20px;">
    <input type="hidden" name="site" value="lager_suche">

    <!-- 🎨 Farbfilter -->
    <div class="form-group">
      <label for="farbe">Farbe / Hauptfarbe</label>
      <div class="color-dropdown" id="colorDropdown">
        <div class="selected" id="selectedColor">
          <?= $farbe && isset($hauptfarben[$farbe]) ? '<span class="color-box" style="background:'.$hauptfarben[$farbe].';"></span> '.htmlspecialchars($farbe) : 'Alle' ?>
        </div>
        <div class="dropdown-list" id="dropdownList">
          <div class="dropdown-item" data-value="alle">
            <span class="color-box" style="background:#ccc;"></span> Alle
          </div>
          <?php foreach ($hauptfarben as $name => $hex): ?>
            <div class="dropdown-item" data-value="<?= htmlspecialchars($name) ?>">
              <span class="color-box" style="background:<?= htmlspecialchars($hex) ?>;"></span>
              <?= htmlspecialchars($name) ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <input type="hidden" name="farbe" id="farbe" value="<?= htmlspecialchars($farbe) ?>">
    </div>

    <!-- 🏭 Hersteller -->
    <div class="form-group">
      <label for="hersteller_id">Hersteller</label>
      <select id="hersteller_id" name="hersteller_id">
        <option value="">Alle</option>
        <?php while($h = $herstellerRes->fetch_assoc()): ?>
          <option value="<?= $h['id'] ?>" <?= ($hersteller_id == $h['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($h['hr_name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <!-- ⚙️ Material -->
    <div class="form-group">
      <label for="material_id">Material</label>
      <select id="material_id" name="material_id">
        <option value="">Alle</option>
        <?php while($m = $materialRes->fetch_assoc()): ?>
          <option value="<?= $m['id'] ?>" <?= ($material_id == $m['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($m['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <!-- 🔍 Freitextsuche -->
    <div class="form-group">
      <label for="name">Freitextsuche</label>
		<input type="text" id="name" name="name" value="<?= isset($_GET['name']) ? htmlspecialchars($name) : '' ?>" placeholder="z. B. Transparent, Matt, etc." autocomplete="off">
    </div>

    <div style="display:flex; gap:10px; margin-top:10px;">
      <button type="submit" class="btn-submit"><i class="fa-solid fa-search"></i> Suchen</button>
      <a href="index.php?site=lager_suche" class="btn-submit" style="background:#7f8c8d;">Zurücksetzen</a>
    </div>
  </form>

  <!-- 🌈 Farblegende -->
  <?php if (!empty($farblegende)): ?>
    <div class="color-legend" style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:15px;">
      <?php foreach ($farblegende as $name => $info): ?>
        <div class="color-legend-item" data-farbe="<?= htmlspecialchars($name) ?>" style="display:flex; align-items:center; gap:6px; background:#fff; border:1px solid #ddd; border-radius:8px; padding:6px 10px; box-shadow:0 1px 3px rgba(0,0,0,0.05); cursor:pointer;">
          <span class="color-box" style="width:16px; height:16px; border:1px solid #999; border-radius:3px; background-color:<?= htmlspecialchars($info['farbe']) ?>;"></span>
          <strong><?= htmlspecialchars($name) ?></strong> (<?= $info['count'] ?>)
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- 📋 Ergebnisliste -->
  <div class="table-wrapper">
    <table class="styled-table">
      <thead>
        <tr>
          <th>Filament</th>
          <th>Hersteller</th>
          <th>Material</th>
          <th class="right">Menge (g)</th>
          <th class="right">Lagerwert (€)</th>
          <th class="center">Letzte Verwendung</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($res && $res->num_rows > 0): ?>
          <?php while($row = $res->fetch_assoc()): ?>
            <tr>
              <td>
                <?= htmlspecialchars($row['name_des_filaments']) ?>
                <?php
                $farben = json_decode($row['farben'], true);
                if (is_array($farben)) {
                    foreach ($farben as $f) {
                        echo '<span title="'.htmlspecialchars($f).'" style="display:inline-block;width:14px;height:14px;margin-left:4px;border:1px solid #aaa;border-radius:3px;background-color:'.htmlspecialchars($f).';vertical-align:middle;"></span>';
                    }
                }
                ?>
              </td>
              <td><?= htmlspecialchars($row['hr_name']) ?></td>
              <td><?= htmlspecialchars($row['material_name']) ?></td>
              <td class="right"><?= number_format($row['menge'], 0, ',', '.') ?></td>
              <td class="right"><?= number_format($row['lagerwert'], 2, ',', '.') ?></td>
              <td class="center"><?= $row['letzte_verwendung'] ? date('d.m.Y H:i', strtotime($row['letzte_verwendung'])) : '–' ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="6" class="center">Keine passenden Datensätze gefunden.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- 🎨 Dropdown & Legendenlogik -->
<script>
const colorDropdown = document.getElementById('colorDropdown');
const selected = document.getElementById('selectedColor');
const dropdownList = document.getElementById('dropdownList');
const hiddenInput = document.getElementById('farbe');

colorDropdown.addEventListener('click', () => {
  colorDropdown.classList.toggle('open');
});

dropdownList.addEventListener('click', (e) => {
  const item = e.target.closest('.dropdown-item');
  if (!item) return;
  const value = item.getAttribute('data-value');
  const colorBox = item.querySelector('.color-box').outerHTML;
  selected.innerHTML = (value === 'alle') ? 'Alle' : colorBox + ' ' + item.textContent.trim();
  hiddenInput.value = value;
  colorDropdown.classList.remove('open');
});

document.addEventListener('click', (e) => {
  if (!colorDropdown.contains(e.target)) {
    colorDropdown.classList.remove('open');
  }
});

// Klick auf Farblegende → Filter setzen
document.querySelectorAll('.color-legend-item').forEach(item => {
  item.addEventListener('click', () => {
    const farbe = item.dataset.farbe;
    const url = new URL(window.location.href);
    url.searchParams.set('farbe', farbe);
    url.searchParams.set('site', 'lager_suche');
    window.location.href = url.toString();
  });
});
</script>
