<?php
require_once "form_token.php";
require_once "auth.php";
require_role(['superuser','admin']);

$auftrag_id = $_GET['id'] ?? null;
if (!$auftrag_id) {
    die("Kein Auftrag gewählt.");
}

// Auftrag + Kunde laden
$sql = "SELECT a.*, 
               k.firma, k.ansprechpartner, k.strasse, k.plz, k.ort, k.telefon,
               p.projektname
        FROM auftraege a
        LEFT JOIN kunden k ON a.kunde_id = k.id
        LEFT JOIN projekte p ON a.projekt_id = p.id
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auftrag_id);
$stmt->execute();
$res = $stmt->get_result();
$auftrag = $res->fetch_assoc();

if (!$auftrag) {
    die("Auftrag nicht gefunden.");
}

// Materialpositionen direkt aus lagerbewegungen laden
$sql = "SELECT ABS(lb.menge) AS menge,
               f.name_des_filaments,
               f.gewicht_des_filaments,
               h.hr_name,
               m.name AS material_name,
               s.preis,
               (ABS(lb.menge) * (s.preis / f.gewicht_des_filaments)) AS kosten
        FROM lagerbewegungen lb
        JOIN spulenlager s ON lb.spule_id = s.id
        JOIN filamente f ON lb.filament_id = f.id
        JOIN hersteller h ON f.hersteller_id = h.id
        JOIN materialien m ON f.material = m.id
        WHERE lb.auftrag_id = ?
          AND lb.bewegungsart = 'abbuchung_auftrag'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auftrag_id);
$stmt->execute();
$res = $stmt->get_result();
$positionen = $res->fetch_all(MYSQLI_ASSOC);

// Drucker laden
$druckerRes = $conn->query("SELECT id, name, stromverbrauch_watt FROM drucker ORDER BY name");
$drucker = $druckerRes->fetch_all(MYSQLI_ASSOC);

// Strompreis laden
$strompreis = 0.0;
$res = $conn->query("SELECT standard_betrag FROM betriebskosten WHERE kostenart='Strompreis' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $strompreis = (float)$row['standard_betrag'];
}

// Maschinenkosten ermitteln
$secs = $auftrag['druckzeit_seconds'] * $auftrag['anzahl'];
$stunden = $secs / 3600;
$maschinenkosten = $stunden;

// Weitere Betriebskosten außer Strompreis
$res = $conn->query("SELECT * FROM betriebskosten WHERE kostenart != 'Strompreis' ORDER BY kostenart");
$betriebskosten = $res->fetch_all(MYSQLI_ASSOC);

// -----------------------------
// Rechnung speichern
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if (!validate_form_token($_POST['form_token'] ?? '')) {
        $_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Das Formular wurde bereits verarbeitet oder ist ungültig.</span>
            </div>';
        header("Location: index.php?site=auftraege");
        exit;
    }

    // prüfen, ob schon Rechnung existiert
    $sql = "SELECT id FROM rechnungen WHERE auftrag_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $auftrag_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = '
            <div class="info-box">
                <i class="fa-solid fa-circle-info"></i>
                <span>Für diesen Auftrag existiert bereits eine <strong>Rechnung</strong>.</span>
            </div>';
        header("Location: index.php?site=rechnungen");
        exit;
    }

    $rechnungsnummer = date('Y') . '-' . str_pad($auftrag_id, 4, '0', STR_PAD_LEFT);

    // Rechnungskopf speichern
    $sql = "INSERT INTO rechnungen (auftrag_id, kunde_id, rechnungsnummer, datum, status, gesamtbetrag)
            VALUES (?, ?, ?, CURDATE(), 'offen', 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $auftrag_id, $auftrag['kunde_id'], $rechnungsnummer);
    $stmt->execute();
    $rechnung_id = $stmt->insert_id;

    $gesamtbetrag = 0;

    // Materialpositionen speichern
    foreach ($positionen as $pos) {
        $beschreibung = $pos['hr_name'].' | '.$pos['name_des_filaments'].' | '.$pos['material_name'];
        $menge = $pos['menge'];
        $einheit = "g";
        $gesamt = $pos['kosten'];
        $preis_pro_einheit = $gesamt / max($menge, 1);
        $gesamtbetrag += $gesamt;

        $sql = "INSERT INTO rechnungspositionen 
                   (rechnung_id, typ, beschreibung, menge, einheit, preis_pro_einheit, gesamt)
                VALUES (?, 'material', ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdsdd", $rechnung_id, $beschreibung, $menge, $einheit, $preis_pro_einheit, $gesamt);
        $stmt->execute();
    }

    // Drucker aus Formular
    $drucker_id = (int)$_POST['drucker_id'];
    $stmt = $conn->prepare("SELECT stromverbrauch_watt FROM drucker WHERE id=?");
    $stmt->bind_param("i", $drucker_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $leistung_watt = (int)$res['stromverbrauch_watt'];

    // Maschinenkosten als eigene Position speichern
    if ($maschinenkosten > 0) {
        $beschreibung = "Druckzeit (".number_format($stunden,2,",",".")." h × 5 €/h)";
        $menge = $stunden;
        $einheit = "h";

        $sql = "INSERT INTO rechnungspositionen 
                   (rechnung_id, typ, beschreibung, menge, einheit)
                VALUES (?, 'druckzeit', ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isds", $rechnung_id, $beschreibung, $menge, $einheit);
        $stmt->execute();
    }

    // Stromkosten berechnen
    $druckzeit_seconds = (int)$auftrag['druckzeit_seconds'];
    $stunden = $druckzeit_seconds / 3600;
    $verbrauch_kwh = ($leistung_watt * $stunden) / 1000;
    $stromkosten = $verbrauch_kwh * $strompreis;

    // Stromkosten-Position speichern
    $beschreibung = "Stromkosten (".$leistung_watt." W, ".round($verbrauch_kwh,2)." kWh × ".number_format($strompreis,2,',','.')." €/kWh)";
    $menge = 1;
    $einheit = "faktor_zeit";
    $preis_pro_einheit = $stromkosten;
    $gesamt = $stromkosten;
    $gesamtbetrag += $stromkosten;

    $sql = "INSERT INTO rechnungspositionen 
               (rechnung_id, typ, beschreibung, menge, einheit, preis_pro_einheit, gesamt)
            VALUES (?, 'stromkosten', ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdsdd", $rechnung_id, $beschreibung, $menge, $einheit, $preis_pro_einheit, $gesamt);
    $stmt->execute();

    // Zusätzliche Betriebskosten speichern
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
            $preis_pro_einheit = (float)$row['standard_betrag'];
			if ($row['einheit'] === 'pauschal') {
				$anzahl = 1;
				$gesamt = $preis_pro_einheit;
			} else {
				$gesamt = $preis_pro_einheit * $anzahl;
			}
            $gesamtbetrag += $gesamt;

            $sql = "INSERT INTO rechnungspositionen 
                       (rechnung_id, typ, beschreibung, menge, einheit, preis_pro_einheit, gesamt)
                    VALUES (?, 'betriebskosten', ?, ?, ?, ?, ?)";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("isdsdd", $rechnung_id, $beschreibung, $anzahl, $row['einheit'], $preis_pro_einheit, $gesamt);
            $stmt2->execute();
        }
    }

    // Gesamtsumme speichern
    $sql = "UPDATE rechnungen SET gesamtbetrag=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $gesamtbetrag, $rechnung_id);
    $stmt->execute();

    $_SESSION['success'] = '
        <div class="info-box">
            <i class="fa-solid fa-circle-info"></i>
            <span>Rechnung ' . $rechnungsnummer . ' erfolgreich erstellt.</span>
        </div>';
    header("Location: index.php?site=rechnungen");
    exit;
}
?>

<section class="card">
    <div class="card-header">
        <h2>Rechnung erstellen – Vorschau</h2>
        <a href="index.php?site=auftraege" class="btn-primary">← Zurück zu Aufträgen</a>
    </div>

    <h3>Kunde</h3>
    <p>
        <?= htmlspecialchars($auftrag['firma'] ?: $auftrag['ansprechpartner']) ?><br>
        <?= htmlspecialchars($auftrag['strasse']) ?><br>
        <?= htmlspecialchars($auftrag['plz'].' '.$auftrag['ort']) ?><br>
        Tel: <?= htmlspecialchars($auftrag['telefon']) ?>
    </p>
    
    <p><strong>Vorlage:</strong> <?= htmlspecialchars($auftrag['projektname'] ?? '-') ?></p>

    <h3>Materialpositionen</h3>
    <table class="styled-table">
        <thead>
            <tr>
                <th>Filament</th>
                <th class="right">Menge (g)</th>
                <th class="right">Kosten (€)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($positionen as $pos): ?>
            <tr>
                <td><?= htmlspecialchars($pos['hr_name'].' | '.$pos['name_des_filaments'].' | '.$pos['material_name']) ?></td>
                <td class="right"><?= number_format($pos['menge'], 2, ',', '.') ?></td>
                <td class="right"><?= number_format($pos['kosten'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
			<tr>
				<td>Druckzeit</td>
				
				<td class="right"><?= htmlspecialchars($stunden ?? '-') ?> h</td>
				
				<td></td>
			</tr>
        </tbody>
    </table>

    <form method="post" style="margin-top:20px;">
        <div class="form-group">
            <label for="drucker_id">Drucker auswählen</label>
            <select name="drucker_id" id="drucker_id" required>
                <option value="">-- Drucker wählen --</option>
                <?php foreach ($drucker as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= $d['stromverbrauch_watt'] ?> W)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <h3>Zusätzliche Betriebskosten</h3>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Art</th>
                    <th>Beschreibung</th>
                    <th>Betrag (€)</th>
                    <th>Einheit</th>
                    <th>Anzahl</th>
                    <th>Auswählen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($betriebskosten as $k): ?>
                    <tr>
                        <td><?= htmlspecialchars($k['kostenart']) ?></td>
                        <td><?= htmlspecialchars($k['beschreibung']) ?></td>
                        <td class="right"><?= number_format($k['standard_betrag'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($k['einheit']) ?></td>
                        <td><input type="number" name="kosten[<?= $k['id'] ?>][anzahl]" value="1" min="1" step="0.1"></td>
                        <td><input type="checkbox" name="kosten[<?= $k['id'] ?>][selected]" value="1"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Token -->
        <input type="hidden" name="form_token" value="<?= htmlspecialchars(generate_form_token()) ?>">
        <button type="submit" name="save" class="btn-primary">✅ Rechnung erstellen</button>
    </form>
</section>
