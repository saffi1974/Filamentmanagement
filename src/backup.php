<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rolle aus Session holen (falls vorhanden)
$rolle = $_SESSION['rolle'] ?? null;

// 🔒 Nur Admins / Superuser dürfen Backup machen
if (!in_array($rolle, ['superuser', 'admin'])) {
    die('<div class="info-box"><i class="fa-solid fa-circle-exclamation"></i> keine Berechtigung!</div>');
}

// DB-Zugangsdaten laden
require_once __DIR__ . "/db.php";  // holt $host, $user, $pass, $dbname

// Verzeichnisse und Dateinamen
$backupDir   = __DIR__ . "/backups";
$projectDir  = __DIR__;
$datum       = date("Y-m-d_H-i");
$backupFile  = "$backupDir/backup_$datum.zip";
$sqlDumpFile = "$backupDir/db_$datum.sql";

// Ordner für Backups anlegen
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// 1️⃣ Datenbank-Dump erstellen -- !!!! Achtung, unter Webmin liegt mysqldump wo anders !!!!!!

$mysqldump = "\"C:\\xampp\\mysql\\bin\\mysqldump.exe\" -h$host -u$user ";
if ($pass !== "") {
    $mysqldump .= "-p$pass ";
}
$mysqldump .= "$dbname > \"$sqlDumpFile\"";

exec($mysqldump, $output, $result);

if ($result !== 0 || !file_exists($sqlDumpFile) || filesize($sqlDumpFile) === 0) {
    echo "<div class='info-box'><i class='fa-solid fa-circle-exclamation'></i> Datenbank-Backup fehlgeschlagen!</div>";
}

// 2️⃣ ZIP mit Projekt + SQL-Dump erstellen
$zip = new ZipArchive();
if ($zip->open($backupFile, ZipArchive::CREATE) !== true) {
    echo "<div class='info-box'><i class='fa-solid fa-circle-exclamation'></i> Konnte ZIP-Archiv nicht erstellen!</div>";
}

$dirIterator = new RecursiveDirectoryIterator($projectDir, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($dirIterator);
foreach ($files as $file) {
    $filePath     = $file->getRealPath();
    $relativePath = substr($filePath, strlen($projectDir) + 1);

    if (strpos($relativePath, "backups") === 0) {
        continue; // ⚠️ keine alten Backups ins neue Backup
    }

    $zip->addFile($filePath, $relativePath);
}

$zip->addFile($sqlDumpFile, basename($sqlDumpFile));
$zip->close();

unlink($sqlDumpFile);
echo "<div class='info-box'><i class='fa-solid fa-circle-check'></i> Backup erfolgreich erstellt: " . basename($backupFile) . "</div>";

// 3️⃣ Nur die letzten 5 Backups behalten
$files = glob("$backupDir/backup_*.zip");
usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
if (count($files) > 5) {
    $oldFiles = array_slice($files, 5);
    foreach ($oldFiles as $old) {
        unlink($old);
        echo "<div class='info-box'><i class='fa-solid fa-circle-check'></i> Gelöscht: " . basename($old) . "</div>";
    }
}
