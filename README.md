# 🎛️ Filamentlager – Verwaltungssystem für 3D-Druck
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Ein webbasiertes Verwaltungssystem für **Filamente, Spulen, Aufträge, Projekte und Rechnungen**.  
Geschrieben in **PHP + MySQL**, optimiert für XAMPP/Apache auf Windows oder Linux.

---

## 🚀 Features
- ✅ Hersteller, Materialien, Filamente und Spulen verwalten
- 📦 Aufträge anlegen, buchen und Rechnungen erstellen
- 📂 Projekte als Vorlagen nutzen
- 📊 Lagerbewegungen dokumentieren
- 👤 Benutzerverwaltung mit Rollen (User, Admin, Superuser, Readonly)
- 💾 Backup- und Restore-Funktionen (Projekt + Datenbank)

---

## 🛠 Voraussetzungen
- PHP ≥ 8.1  
- MySQL/MariaDB ≥ 10.x  
- Apache oder Nginx  
- Benötigte PHP-Extensions:
  - `mysqli`
  - `zip`
  - `mbstring`
  - optional: `gd`, `curl`

👉 Installation unter Ubuntu/Debian:
```bash
sudo apt install php php-mysqli php-zip php-mbstring php-gd php-curl mariadb-client

📦 Installation

Datenbank anlegen

CREATE DATABASE 3d_druck CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


Tabellen importieren

mysql -u USERNAME -p 3d_druck < sql/database-insert-sql.sql


Projektdateien kopieren

In das Webserver-Root legen:

XAMPP: htdocs/filamentlager-iii/src/

Linux/Apache: /var/www/src/

Datenbankzugang konfigurieren

src/db-example.php in db.php umbenennen und Zugangsdaten anpassen:

$host = "localhost";
$user = "deinuser";
$pass = "deinpasswort";
$dbname = "3d_druck"; // nicht ändern!

👣 Erste Schritte

Projekt im Browser öffnen:

XAMPP: http://localhost/filamentlager-iii/src/

Server: http://IP-ADRESSE/

Mit Standard-Login anmelden:

Benutzername: admin
Passwort: admin

Eigenen Benutzer anlegen → alten Admin auf readonly setzen.

Firmendaten unter Stammdaten → Firmendaten pflegen (benötigt für Rechnungen).

📸 Screenshots
<p align="center">
  <img src="docs/screenshots/3d-druck-dashboard.png" alt="Dashboard" width="300">
  <img src="docs/screenshots/3d-druck-filament-ansicht.png" alt="Filamente Ansicht" width="300">
  <img src="docs/screenshots/3d-druck-filament-anlegen.png" alt="Filamente anlegen" width="300">
</p>
<p align="center">
  <img src="docs/screenshots/3d-druck-projekt-anlegen.png" alt="Projekte anlegen" width="300">
  <img src="docs/screenshots/3d-druck-auftrag-anlegen.png" alt="Auftrag anlegen" width="300">
  <img src="docs/screenshots/3d-druck-hersteller-ansicht.png" alt="Hersteller Ansicht" width="300">
</p>
<p align="center">
  <img src="docs/screenshots/3d-druck-betriebskosten-ansicht.png" alt="Betriebskosten Ansicht" width="300">
  <img src="docs/screenshots/3d-druck-betriebskosten-anlegeng.png" alt="Betriebskosten anlegen" width="300">
  <img src="docs/screenshots/3d-druck-drucker-anlegen.png" alt="Drucker anlegen" width="300">
</p>
<p align="center">
  <img src="docs/screenshots/3d-druck-kunden-anlegen.png" alt="Kunden anlegen" width="300">
</p>

📂 Projektstruktur
filamentlager-iii/
├─ docs/
│   └─ screenshots/
├─ sql/
│   └─ database-insert-sql.sql
├─ src/
│   ├─ index.php
│   ├─ db-example.php
│   ├─ db.php (ignoriert, lokal)
│   ├─ includes/
│   ├─ images/
│   ├─ css/
│   ├─ js/
│   └─ backups/
├─ .gitignore
├─ LICENSE
└─ README.md

📋 Menüpunkte

Dashboard → Überblick über Lager & Aufträge

Aufträge & Rechnungen → Aufträge erstellen, Rechnungen generieren

Vorlagen → Projekte als Vorlagen speichern

Lager → Spulen, Wareneingänge, Warenbewegungen

Materialstammdaten → Filamente, Materialarten, Hersteller

Stammdaten → Kunden, Drucker, Betriebskosten, Firmendaten, Benutzer & Rechte

Backup erstellen → Projekt & Datenbank sichern (ZIP-Datei)

⚠️ Hinweis

Dies ist ein Privatprojekt.
Die Nutzung erfolgt auf eigene Gefahr – trotz großer Sorgfalt können Fehler im Code oder Abläufen bestehen.
Das Projekt wird aktiv weiterentwickelt. Feedback und Unterstützung sind willkommen!

🤝 Mitwirken

Pull Requests sind willkommen

Fehler oder Verbesserungsvorschläge bitte als GitHub Issues
 einreichen

📄 Lizenz

Dieses Projekt steht unter der MIT License
.