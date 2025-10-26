<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "auth.php";
require_role(['superuser','admin','user','readonly']);

// Kennzahlen aus DB

$offene_auftraege = $conn->query("SELECT COUNT(*) AS c FROM auftraege WHERE status='offen'")->fetch_assoc()['c'];
$res = $conn->query("SELECT COUNT(*) AS total FROM auftraege");
$auftraegeCount = $res->fetch_assoc()['total'] ?? 0;
$fertige_auftraege = $conn->query("SELECT COUNT(*) AS c FROM auftraege WHERE status='fertig'")->fetch_assoc()['c'];

$anzahl_hersteller = $conn->query("SELECT COUNT(*) AS c FROM hersteller")->fetch_assoc()['c'];

$anzahl_kunden = $conn->query("SELECT COUNT(*) AS c FROM kunden")->fetch_assoc()['c'];

$anzahl_drucker = $conn->query("SELECT COUNT(*) AS c FROM drucker")->fetch_assoc()['c'];

$anzahl_benutzer = $conn->query("SELECT COUNT(*) AS c FROM user")->fetch_assoc()['c'];

$anzahl_spulen = $conn->query("SELECT COUNT(*) AS c FROM spulenlager")->fetch_assoc()['c'];
$verbrauch_filament = $conn->query("SELECT SUM(verbrauchtes_filament) AS summe FROM spulenlager")->fetch_assoc()['summe'] ?? 0;

$anzahl_filament = $conn->query("SELECT COUNT(*) AS c FROM filamente")->fetch_assoc()['c'];

$umsatz_gesamt = $conn->query("SELECT SUM(gesamtbetrag) AS summe FROM rechnungen WHERE status='bezahlt'")->fetch_assoc() ['summe'] ?? 0;
$umsatz_jahr = $conn->query("SELECT SUM(gesamtbetrag) AS summe FROM rechnungen WHERE status='bezahlt' AND YEAR(datum)=YEAR(CURDATE())")->fetch_assoc() ['summe'] ?? 0;
$umsatz_monat = $conn->query("SELECT SUM(gesamtbetrag) AS summe FROM rechnungen WHERE status='bezahlt' AND YEAR(datum)=YEAR(CURDATE()) AND MONTH(datum)=MONTH(CURDATE())")->fetch_assoc() ['summe'] ?? 0;

// Verbrauch nach Hersteller
$verbrauch_hersteller = [];
$res = $conn->query("
    SELECT h.hr_name AS hersteller, SUM(s.verbrauchtes_filament) AS gesamt
    FROM spulenlager s
    JOIN filamente f ON s.filament_id = f.id
    JOIN hersteller h ON f.hersteller_id = h.id
    GROUP BY h.hr_name
    ORDER BY gesamt DESC
");
while ($row = $res->fetch_assoc()) {
    $verbrauch_hersteller[] = [
        'hersteller' => $row['hersteller'],
        'gesamt' => (float)$row['gesamt']
    ];
}
// Meistgedruckter Hersteller
$top_hersteller = $conn->query("
    SELECT h.hr_name AS hersteller, SUM(s.verbrauchtes_filament) AS gesamt
    FROM spulenlager s
    JOIN filamente f ON s.filament_id = f.id
    JOIN hersteller h ON f.hersteller_id = h.id
    GROUP BY h.hr_name
    ORDER BY gesamt DESC
    LIMIT 1
")->fetch_assoc();

// Verbrauch nach Filamenttyp
$verbrauch_filamenttypen = [];
$res = $conn->query("
    SELECT m.name AS materialname, SUM(s.verbrauchtes_filament) AS gesamt
    FROM spulenlager s
    JOIN filamente f ON s.filament_id = f.id
    JOIN materialien m ON f.material = m.id
    GROUP BY m.name
    ORDER BY gesamt DESC
");
while ($row = $res->fetch_assoc()) {
    $verbrauch_filamenttypen[] = [
        'typ' => $row['materialname'],
        'gesamt' => (float)$row['gesamt']
    ];
}
// Meistgedrucktes Material
$top_material = $conn->query("
    SELECT m.name AS materialname, SUM(s.verbrauchtes_filament) AS gesamt
    FROM spulenlager s
    JOIN filamente f ON s.filament_id = f.id
    JOIN materialien m ON f.material = m.id
    GROUP BY m.name
    ORDER BY gesamt DESC
    LIMIT 1
")->fetch_assoc();

// Umsatzentwicklung (aktuelles Jahr)
$umsatzLabels = [];
$umsatzDaten = [];
$res = $conn->query("
    SELECT MONTH(datum) AS monat, SUM(gesamtbetrag) AS summe
    FROM rechnungen
    WHERE status='bezahlt' AND YEAR(datum) = YEAR(CURDATE())
    GROUP BY MONTH(datum)
    ORDER BY monat
");
$monatsnamen = [1=>"Jan",2=>"Feb",3=>"Mär",4=>"Apr",5=>"Mai",6=>"Jun",7=>"Jul",8=>"Aug",9=>"Sep",10=>"Okt",11=>"Nov",12=>"Dez"];
while ($row = $res->fetch_assoc()) {
    $umsatzLabels[] = $monatsnamen[(int)$row['monat']];
    $umsatzDaten[] = (float)$row['summe'];
}

// Aufträge nach Status
$auftragLabels = [];
$auftragDaten = [];
$res = $conn->query("
    SELECT status, COUNT(*) AS anzahl
    FROM auftraege
    GROUP BY status
");
while ($row = $res->fetch_assoc()) {
    $auftragLabels[] = ucfirst(str_replace('_',' ',$row['status']));
    $auftragDaten[] = (int)$row['anzahl'];
}
?>

<section class="card">
	<div class="card-header">
		<h1>Dashboard</h1>
	</div>
	
  <!-- Stat Cards -->
  <div class="cards">

    <div class="card">
		<h3><i class="fa-solid fa-record-vinyl"></i> Spulenlager</h3>
		<table class="styled-table">
			<tbody>
				<tr>
					<td style="width:50%;">Spulen im Lager:</td><td class="right"><?= $anzahl_spulen ?></td>
				</tr>
				<tr>
					<td>Filamenttypen:</td><td class="right"><?= $anzahl_filament ?></td>
				</tr>
				<tr>
					<td>Hersteller:</td><td class="right"><?= $anzahl_hersteller ?></td>			
				</tr>
				<tr>
					<td>Filamentverbrauch:</td><td class="right"><?= number_format($verbrauch_filament / 1000, 2, ',', '.') ?> kg</td>			
				</tr>
				<tr>
					<td>Top-Hersteller:</td><td class="right"><?= htmlspecialchars($top_hersteller['hersteller'] ?? '–') ?> (<?= number_format(($top_hersteller['gesamt'] ?? 0) / 1000, 2, ',', '.') ?> kg)</td>			
				</tr>
				<tr>
					<td>Top-Material:</td><td class="right"><?= htmlspecialchars($top_material['materialname'] ?? '–') ?> (<?= number_format(($top_material['gesamt'] ?? 0) / 1000, 2, ',', '.') ?> kg)</td>			
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2" class="card-actions">
						<a href="index.php?site=spulen" class="btn-action edit"><i class="fa-solid fa-list"></i></a>
						<a href="index.php?site=spulen_anlegen" class="btn-action delete"><i class="fa-solid fa-plus"></i></a>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>	

    <div class="card">
		<h3><i class="fa-solid fa-user"></i> Stammdaten</h3>
		<table class="styled-table">
			<tbody>
				<tr>
					<td style="width:50%;">Kunden:</td><td class="right"><?= $anzahl_kunden ?></td>
				</tr>
				<tr>
					<td>Drucker:</td><td class="right"><?= $anzahl_drucker ?></td>
				</tr>
				<tr>
					<td>Benutzer:</td><td class="right"><?= $anzahl_benutzer ?></td>			
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2" class="card-actions">
						<a href="index.php?site=spulen" class="btn-action edit"><i class="fa-solid fa-list"></i></a>
						<a href="index.php?site=auftraege_anlegen" class="btn-action delete"><i class="fa-solid fa-plus"></i></a>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>

    <div class="card">
		<h3><i class="fa-solid fa-print"></i> Druckaufträge</h3>
		<table class="styled-table">
			<tbody>
				<tr>
					<td style="width:50%;">Aufträge gesamt:</td><td class="right"><?= $auftraegeCount ?></td>
				</tr>
				<tr>
					<td>Offene Aufträge:</td><td class="right"><?= $offene_auftraege ?></td>
				</tr>
				<tr>
					<td>Fertige Aufträge:</td><td class="right"><?= $fertige_auftraege ?></td>			
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2" class="card-actions">
						<a href="index.php?site=auftraege" class="btn-action edit"><i class="fa-solid fa-list"></i></a>
						<a href="index.php?site=auftraege_anlegen" class="btn-action delete"><i class="fa-solid fa-plus"></i></a>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>

	<div class="card">
		<h3><i class="fa-solid fa-sack-dollar"></i> Business</h3>
		<table class="styled-table">
			<tbody>
				<tr>
					<td style="width:50%;">Umsatz diesen Monat:</td><td class="right"><?= number_format($umsatz_monat, 2, ',', '.') ?> €</td>
				</tr>
				<tr>
					<td>Umsatz dieses Jahr:</td><td class="right"><?= number_format($umsatz_jahr, 2, ',', '.') ?> €</td>
				</tr>
				<tr>
					<td>Umsatz insgesamt:</td><td class="right"><?= number_format($umsatz_gesamt, 2, ',', '.') ?> €</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2" class="card-actions">
						<a href="index.php?site=spulen" class="btn-action edit"><i class="fa-solid fa-list"></i></a>
						<a href="index.php?site=auftraege_anlegen" class="btn-action delete"><i class="fa-solid fa-plus"></i></a>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>	
 
	<!-- Umsatzentwicklung -->
	<div class="card-diagramm bg-white rounded-2xl shadow p-6 w-full">
		<h2 class="text-xl font-bold text-gray-800 mb-4">Umsatzentwicklung</h2>
		<canvas id="umsatzChart" class="w-full"></canvas>
	</div>

	<!-- Aufträge nach Status -->
	<div class="card-diagramm bg-white rounded-2xl shadow p-6 w-full">
		<h2 class="text-xl font-bold text-gray-800 mb-4">Aufträge nach Status</h2>
		<canvas id="auftraegeChart" class="w-full"></canvas>
	</div>
	
	<div class="card-diagramm bg-white rounded-2xl shadow p-6 w-full">
		<h2 class="text-xl font-bold text-gray-800 mb-4">Hersteller Rating</h2>
		<canvas id="verbrauchHerstellerChart" class="w-full my-4"></canvas>
	</div>
	<div class="card-diagramm bg-white rounded-2xl shadow p-6 w-full">
		<h2 class="text-xl font-bold text-gray-800 mb-4">Filament Rating</h2>
		<canvas id="verbrauchTypChart" class="w-full"></canvas>
	</div>
</div>

</section>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Umsatzdiagramm
if (window.umsatzChartInstance) {
  window.umsatzChartInstance.destroy();
}
window.umsatzChartInstance = new Chart(document.getElementById('umsatzChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($umsatzLabels) ?>,
    datasets: [{
      label: 'Umsatz (€)',
      data: <?= json_encode($umsatzDaten) ?>,
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59,130,246,0.2)',
      tension: 0.3,
      fill: true
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false
  }
});

// Aufträge nach Status
if (window.auftraegeChartInstance) {
  window.auftraegeChartInstance.destroy();
}
window.auftraegeChartInstance = new Chart(document.getElementById('auftraegeChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($auftragLabels) ?>,
    datasets: [{
      label: 'Anzahl Aufträge',
      data: <?= json_encode($auftragDaten) ?>,
      backgroundColor: ['#3b82f6', '#f59e0b', '#10b981'],
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});
// Verbrauch nach Hersteller
const herstellerLabels = <?= json_encode(array_column($verbrauch_hersteller, 'hersteller')) ?>;
const herstellerDaten = <?= json_encode(array_column($verbrauch_hersteller, 'gesamt')) ?>;

new Chart(document.getElementById('verbrauchHerstellerChart'), {
  type: 'bar',
  data: {
    labels: herstellerLabels,
    datasets: [{
      label: 'Verbrauch (g)',
      data: herstellerDaten,
      backgroundColor: '#10b981',
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});

// Verbrauch nach Filamenttyp
const typLabels = <?= json_encode(array_column($verbrauch_filamenttypen, 'typ')) ?>;
const typDaten = <?= json_encode(array_column($verbrauch_filamenttypen, 'gesamt')) ?>;

new Chart(document.getElementById('verbrauchTypChart'), {
  type: 'bar',
  data: {
    labels: typLabels,
    datasets: [{
      label: 'Verbrauch (g)',
      data: typDaten,
      backgroundColor: '#3b82f6',
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});
</script>
