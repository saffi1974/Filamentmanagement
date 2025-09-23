<?php
include "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$rechnung_id = $_GET['id'] ?? null;
if (!$rechnung_id) {
    die("Keine Rechnung gewählt.");
}
// Firmendaten für Kopf einlesen
$res = $conn->query("SELECT * FROM firmendaten WHERE id=1");
$firma = $res->fetch_assoc();

// Rechnung + Kunde laden
$sql = "SELECT r.*, 
               k.firma, k.ansprechpartner, k.strasse, k.plz, k.ort, k.telefon,
			   a.name AS auftragsname,
			   p.projektname
        FROM rechnungen r
        LEFT JOIN kunden k ON r.kunde_id = k.id
		LEFT JOIN auftraege a ON r.auftrag_id = a.id
		LEFT JOIN projekte p ON a.projekt_id = p.id
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $rechnung_id);
$stmt->execute();
$res = $stmt->get_result();
$rechnung = $res->fetch_assoc();

if (!$rechnung) {
    die("Rechnung nicht gefunden.");
}

// Positionen laden
$sql = "SELECT * FROM rechnungspositionen WHERE rechnung_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $rechnung_id);
$stmt->execute();
$res = $stmt->get_result();
$positionen = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Rechnung <?= htmlspecialchars($rechnung['rechnungsnummer']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1, h2, h3 { margin: 0; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .kunde { margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px 8px; font-size: 14px; }
        th { background: #eee; }
        tfoot td { font-weight: bold; }
        .right { text-align: right; }
        .center { text-align: center; }
        .footer { margin-top: 40px; font-size: 12px; color: #555; }
    </style>
</head>
<body onload="window.print()">

<div class="header">
    <div>
        <h1>Rechnung</h1>
        <p>Nr.: <?= htmlspecialchars($rechnung['rechnungsnummer']) ?><br>
        Datum: <?= htmlspecialchars($rechnung['datum']) ?><br>
		Auftrag:  <?= htmlspecialchars($rechnung['auftragsname'] ?? '-') ?><br>
		Vorlage:  <?= htmlspecialchars($rechnung['projektname'] ?? '-') ?><br>
		</p>
		
    </div>
    <div class="invoice-header" style="display:flex; justify-content:space-between; margin-bottom:20px;">
		<div>
			<?php if (!empty($firma['logo'])): ?>
				<img src="<?= htmlspecialchars($firma['logo']) ?>" alt="Logo" style="max-height:80px;"><br>
			<?php endif; ?>
			<p>
				<strong><?= htmlspecialchars($firma['firmenname']) ?></strong><br>
				<?= htmlspecialchars($firma['strasse']) ?><br>
				<?= htmlspecialchars($firma['plz'].' '.$firma['ort']) ?><br>
				Tel: <?= htmlspecialchars($firma['telefon']) ?><br>
				<?= htmlspecialchars($firma['email']) ?>
			</p>
		</div>
	</div>

</div>

<div class="kunde">
    <h3>Kunde</h3>
    <p>
        <?= htmlspecialchars($rechnung['firma'] ?: $rechnung['ansprechpartner']) ?><br>
        <?= htmlspecialchars($rechnung['strasse']) ?><br>
        <?= htmlspecialchars($rechnung['plz'].' '.$rechnung['ort']) ?><br>
        Tel: <?= htmlspecialchars($rechnung['telefon']) ?>
    </p>
</div>

<table>
    <thead>
        <tr>
            <th style="width:50%;">Beschreibung</th>
            <th style="width:10%;">Menge</th>
            <th style="width:10%;">Einheit</th>
            <th style="width:15%;">Einzelpreis (€)</th>
            <th style="width:15%;">Gesamt (€)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($positionen as $pos): ?>
        <tr>
            <td><?= htmlspecialchars($pos['beschreibung']) ?></td>
            <td class="right"><?= number_format($pos['menge'], 2, ',', '.') ?></td>
            <td class="center"><?= htmlspecialchars($pos['einheit']) ?></td>
            <td class="right"><?= number_format($pos['preis_pro_einheit'], 2, ',', '.') ?></td>
            <td class="right"><?= number_format($pos['gesamt'], 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="right">Gesamtbetrag</td>
            <td class="right"><?= number_format($rechnung['gesamtbetrag'], 2, ',', '.') ?> €</td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    <p>Zahlbar innerhalb von 14 Tagen ohne Abzug.<br>
    Bankverbindung: <?= htmlspecialchars($firma['name_bank']) ?> IBAN: <?= htmlspecialchars($firma['konto_nummer']) ?> · BIC: <?= htmlspecialchars($firma['bic']) ?><br>
	Bei Überweisung im Betreff stets die Rechnungsnummer mit angeben!</p>
</div>

</body>
</html>
