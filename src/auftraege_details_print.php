<?php
include "db.php";

require_once "auth.php";
require_login();

$auftrag_id = $_GET['id'] ?? null;
if (!$auftrag_id) {
    die("Kein Auftrag gewählt.");
}

// Auftrag laden mit Kundendaten
$sql = "SELECT a.*, 
               k.firma, k.ansprechpartner, k.telefon, k.strasse, k.plz, k.ort, k.versandart
        FROM auftraege a
        LEFT JOIN kunden k ON a.kunde_id = k.id
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auftrag_id);
$stmt->execute();
$res = $stmt->get_result();
$auftrag = $res->fetch_assoc();
if (!$auftrag) {
    die("Auftrag nicht gefunden.");
}

// Filamente laden
$sql = "SELECT af.*, CONCAT(h.hr_name, ' | ', f.name_des_filaments, ' | ', m.name) AS filament_name
        FROM auftrag_filamente af
        JOIN filamente f ON af.filament_id = f.id
        JOIN hersteller h ON f.hersteller_id = h.id
        JOIN materialien m ON f.material = m.id
        WHERE af.auftrag_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $auftrag_id);
$stmt->execute();
$res = $stmt->get_result();
$filamente = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Druckansicht Auftrag</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        h1 { margin-bottom: 5px; }
        h3 { margin-top: 25px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f1f1f1; }
    </style>
</head>
<body onload="window.print()">
    <h1>Auftrag: <?= htmlspecialchars($auftrag['name'] ?? '') ?></h1>
    <p><strong>Datum:</strong> <?= htmlspecialchars($auftrag['datum'] ?? '') ?></p>
    <p><strong>Anzahl:</strong> <?= htmlspecialchars($auftrag['anzahl'] ?? '') ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($auftrag['status'] ?? '') ?></p>

    <h3>Kunde</h3>
    <p>
        <?= htmlspecialchars(($auftrag['firma'] ?? '') ?: ($auftrag['ansprechpartner'] ?? '')) ?><br>
        <?= htmlspecialchars($auftrag['strasse'] ?? '') ?><br>
        <?= htmlspecialchars(($auftrag['plz'] ?? '') . ' ' . ($auftrag['ort'] ?? '')) ?><br>
        Tel: <?= htmlspecialchars($auftrag['telefon'] ?? '') ?><br>
        Versandart: <?= htmlspecialchars($auftrag['versandart'] ?? '') ?>
    </p>

    <h3>Filamente</h3>
    <table>
        <thead>
            <tr>
                <th>Filament</th>
                <th>Geplant (g)</th>
                <th>Gebucht (g)</th>
                <th>Rest (g)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filamente as $af): 
                $rest = $af['menge_geplant'] - $af['menge_gebucht'];
            ?>
            <tr>
                <td><?= htmlspecialchars($af['filament_name'] ?? '') ?></td>
                <td><?= $af['menge_geplant'] ?></td>
                <td><?= $af['menge_gebucht'] ?></td>
                <td><?= max(0, $rest) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
