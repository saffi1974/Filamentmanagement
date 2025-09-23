<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['success'])) {
    echo $_SESSION['success'];
    unset($_SESSION['success']);
}
$rechnung_id = $_GET['id'] ?? null;
if (!$rechnung_id) {
    die("Keine Rechnung gewählt.");
}

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

<section class="card">
    <div class="card-header">
        <h2>Rechnung <?= htmlspecialchars($rechnung['rechnungsnummer']) ?></h2>
        <a href="index.php?site=rechnungen" class="btn-primary">← Zurück zur Übersicht</a>
    </div>

    <h3>Kunde</h3>
    <p>
        <?= htmlspecialchars($rechnung['firma'] ?: $rechnung['ansprechpartner']) ?><br>
        <?= htmlspecialchars($rechnung['strasse']) ?><br>
        <?= htmlspecialchars($rechnung['plz'].' '.$rechnung['ort']) ?><br>
        Tel: <?= htmlspecialchars($rechnung['telefon']) ?>
    </p>

    <h3>Rechnungsdaten</h3>
    <p>
        Nummer: <strong><?= htmlspecialchars($rechnung['rechnungsnummer']) ?></strong><br>
        Datum: <?= htmlspecialchars($rechnung['datum']) ?><br>
		Auftrag:  <?= htmlspecialchars($rechnung['auftragsname'] ?? '-') ?><br>
		Vorlage:  <?= htmlspecialchars($rechnung['projektname'] ?? '-') ?><br>
        Status: 
        <?php
        $status = $rechnung['status'];
        if ($status === 'offen') echo "<span class='status-badge status-open'>Offen</span>";
        elseif ($status === 'bezahlt') echo "<span class='status-badge status-paid'>Bezahlt</span>";
        elseif ($status === 'storniert') echo "<span class='status-badge status-cancel'>Storniert</span>";
        ?>
        <br>
        Betrag: <strong><?= number_format($rechnung['gesamtbetrag'], 2, ',', '.') ?> €</strong>
    </p>

    <h3>Positionen</h3>
    <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th style="width:50%;">Beschreibung</th>
                    <th style="width:10%;" class="right">Menge</th>
                    <th style="width:10%;">Einheit</th>
                    <th style="width:15%;" class="right">Einzelpreis (€)</th>
                    <th style="width:15%;" class="right">Gesamt (€)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($positionen as $pos): ?>
                <tr>
                    <td><?= htmlspecialchars($pos['beschreibung']) ?></td>
                    <td class="right"><?= number_format($pos['menge'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($pos['einheit']) ?></td>
                    <td class="right"><?= number_format($pos['preis_pro_einheit'], 2, ',', '.') ?></td>
                    <td class="right"><?= number_format($pos['gesamt'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="right"><strong>Gesamtsumme</strong></td>
                    <td class="right"><strong><?= number_format($rechnung['gesamtbetrag'], 2, ',', '.') ?> €</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div style="margin-top:20px;">
        <?php if ($rechnung['status'] === 'offen'): ?>
            <a href="index.php?site=rechnungen_status&id=<?= $rechnung['id'] ?>&status=bezahlt" 
               class="btn-primary">✅ Als bezahlt markieren</a>
            <a href="index.php?site=rechnungen_status&id=<?= $rechnung['id'] ?>&status=storniert" 
               class="btn-primary">❌ Stornieren</a>
			<a href="index.php?site=rechnungen_bearbeiten&id=<?= $rechnung['id'] ?>" class="btn-primary">📝 Ändern</a>			   
        <?php endif; ?>

        <a href="rechnungen_details_print.php?id=<?= $rechnung['id'] ?>" 
           class="btn-primary" target="_blank">🖨 Druckansicht</a>
    </div>
</section>

<style>
.status-badge {
    padding: 3px 8px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: bold;
}
.status-open { background:#f39c12; color:#fff; }
.status-paid { background:#27ae60; color:#fff; }
.status-cancel { background:#e74c3c; color:#fff; }
</style>
