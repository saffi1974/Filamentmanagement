-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 15. Jan 2026 um 17:29
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `3d_druck`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `auftraege`
--

CREATE TABLE `auftraege` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `kunde_id` int(11) DEFAULT NULL,
  `anzahl` int(11) NOT NULL DEFAULT 1,
  `preis_vereinbart` decimal(10,2) DEFAULT NULL,
  `preis_notiz` text DEFAULT NULL,
  `druckzeit_seconds` int(10) UNSIGNED DEFAULT 0,
  `status` enum('offen','in_bearbeitung','fertig') NOT NULL DEFAULT 'offen',
  `datum` date DEFAULT NULL,
  `projekt_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `auftrag_filamente`
--

CREATE TABLE `auftrag_filamente` (
  `id` int(11) NOT NULL,
  `auftrag_id` int(11) NOT NULL,
  `filament_id` int(11) NOT NULL,
  `menge_geplant` decimal(10,2) NOT NULL,
  `menge_gebucht` decimal(10,2) DEFAULT 0.00,
  `status` enum('geplant','teilweise_gebucht','gebucht') DEFAULT 'geplant'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `betriebskosten`
--

CREATE TABLE `betriebskosten` (
  `id` int(11) NOT NULL,
  `kostenart` varchar(100) NOT NULL,
  `beschreibung` text DEFAULT NULL,
  `standard_betrag` decimal(10,2) DEFAULT 0.00,
  `einheit` enum('pauschal','pro_stunde','pro_stueck') DEFAULT 'pauschal',
  `erstellt_am` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `drucker`
--

CREATE TABLE `drucker` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `hersteller` varchar(100) DEFAULT NULL,
  `stromverbrauch_watt` decimal(10,2) NOT NULL,
  `kosten_pro_kwh` decimal(10,2) NOT NULL,
  `kommentar` text DEFAULT NULL,
  `angelegt_am` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `filamente`
--

CREATE TABLE `filamente` (
  `id` int(11) NOT NULL,
  `name_des_filaments` varchar(100) NOT NULL,
  `hersteller_id` int(11) NOT NULL,
  `material` varchar(50) DEFAULT NULL,
  `anzahl_farben` tinyint(4) DEFAULT NULL,
  `farben` varchar(255) DEFAULT NULL,
  `preis` decimal(10,2) DEFAULT NULL,
  `dichte` decimal(4,2) DEFAULT NULL,
  `durchmesser` decimal(4,2) DEFAULT NULL,
  `gewicht_des_filaments` int(11) DEFAULT NULL,
  `gewicht_spule` int(11) DEFAULT NULL,
  `duesentemperatur` int(11) DEFAULT NULL,
  `betttemperatur` int(11) DEFAULT NULL,
  `artikelnummer_des_herstellers` varchar(50) DEFAULT NULL,
  `kommentar` text DEFAULT NULL,
  `erstellt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `firmendaten`
--

CREATE TABLE `firmendaten` (
  `id` int(11) NOT NULL,
  `firmenname` varchar(255) NOT NULL,
  `strasse` varchar(255) NOT NULL,
  `plz` varchar(10) NOT NULL,
  `ort` varchar(100) NOT NULL,
  `telefon` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `name_bank` varchar(255) DEFAULT NULL,
  `bic` varchar(50) DEFAULT NULL,
  `konto_nummer` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `hersteller`
--

CREATE TABLE `hersteller` (
  `id` int(11) NOT NULL,
  `hr_name` varchar(255) NOT NULL,
  `hr_leerspule` int(3) NOT NULL,
  `hr_kommentar` varchar(255) NOT NULL,
  `hr_eingetragen` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kunden`
--

CREATE TABLE `kunden` (
  `id` int(11) NOT NULL,
  `firma` varchar(255) DEFAULT NULL,
  `ansprechpartner` varchar(255) DEFAULT NULL,
  `telefon` varchar(50) DEFAULT NULL,
  `strasse` varchar(255) DEFAULT NULL,
  `plz` varchar(10) DEFAULT NULL,
  `ort` varchar(255) DEFAULT NULL,
  `versandart` enum('Versand','Abholung') DEFAULT 'Abholung',
  `angelegt_am` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lagerbewegungen`
--

CREATE TABLE `lagerbewegungen` (
  `id` int(11) NOT NULL,
  `spule_id` int(11) NOT NULL,
  `filament_id` int(11) NOT NULL,
  `projekt_id` int(11) DEFAULT NULL,
  `auftrag_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bewegungsart` enum('wareneingang','abbuchung_projekt','abbuchung_auftrag','korrektur') NOT NULL,
  `menge` decimal(10,2) NOT NULL,
  `datum` timestamp NOT NULL DEFAULT current_timestamp(),
  `kommentar` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `materialien`
--

CREATE TABLE `materialien` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `kommentar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `projekte`
--

CREATE TABLE `projekte` (
  `id` int(11) NOT NULL,
  `projektname` varchar(255) NOT NULL,
  `kommentar` text NOT NULL DEFAULT '',
  `druckzeit_seconds` int(10) UNSIGNED DEFAULT 0,
  `datum` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `projekt_filamente`
--

CREATE TABLE `projekt_filamente` (
  `id` int(11) NOT NULL,
  `projekt_id` int(11) NOT NULL,
  `filament_id` int(11) NOT NULL,
  `menge_geplant` decimal(10,2) NOT NULL,
  `menge_gebucht` decimal(10,2) DEFAULT 0.00,
  `status` enum('geplant','teilweise_gebucht','gebucht') DEFAULT 'geplant',
  `preis_pro_gramm` decimal(10,4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `rechnungen`
--

CREATE TABLE `rechnungen` (
  `id` int(11) NOT NULL,
  `auftrag_id` int(11) NOT NULL,
  `kunde_id` int(11) NOT NULL,
  `rechnungsnummer` varchar(50) NOT NULL,
  `datum` date NOT NULL DEFAULT curdate(),
  `gesamtbetrag` decimal(10,2) DEFAULT 0.00,
  `status` enum('offen','bezahlt','storniert') DEFAULT 'offen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `rechnungspositionen`
--

CREATE TABLE `rechnungspositionen` (
  `id` int(11) NOT NULL,
  `rechnung_id` int(11) NOT NULL,
  `typ` enum('material','druckzeit','arbeitszeit','versand','sonstiges','betriebskosten','stromkosten') NOT NULL,
  `beschreibung` varchar(255) NOT NULL,
  `menge` decimal(10,2) NOT NULL,
  `einheit` varchar(50) NOT NULL,
  `preis_pro_einheit` decimal(10,2) NOT NULL,
  `gesamt` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `spulenlager`
--

CREATE TABLE `spulenlager` (
  `id` int(11) NOT NULL,
  `filament_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `preis` decimal(10,2) NOT NULL,
  `verbrauchtes_filament` decimal(10,2) DEFAULT 0.00,
  `verbleibendes_filament` decimal(10,2) DEFAULT 0.00,
  `lagerort` varchar(50) DEFAULT NULL,
  `erstmals_verwendet` timestamp NOT NULL DEFAULT current_timestamp(),
  `letzte_verwendung` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `chargennummer` varchar(50) DEFAULT NULL,
  `kommentar` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Passwort` varchar(255) NOT NULL,
  `rolle` enum('superuser','admin','user','readonly') NOT NULL DEFAULT 'user',
  `Erstelldatum` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `auftraege`
--
ALTER TABLE `auftraege`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auftraege_ibfk_kunde` (`kunde_id`),
  ADD KEY `fk_auftraege_projekt` (`projekt_id`);

--
-- Indizes für die Tabelle `auftrag_filamente`
--
ALTER TABLE `auftrag_filamente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auftrag_id` (`auftrag_id`),
  ADD KEY `filament_id` (`filament_id`);

--
-- Indizes für die Tabelle `betriebskosten`
--
ALTER TABLE `betriebskosten`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `drucker`
--
ALTER TABLE `drucker`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `filamente`
--
ALTER TABLE `filamente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hersteller_id` (`hersteller_id`);

--
-- Indizes für die Tabelle `firmendaten`
--
ALTER TABLE `firmendaten`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `hersteller`
--
ALTER TABLE `hersteller`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `kunden`
--
ALTER TABLE `kunden`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `lagerbewegungen`
--
ALTER TABLE `lagerbewegungen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `spule_id` (`spule_id`),
  ADD KEY `filament_id` (`filament_id`),
  ADD KEY `projekt_id` (`projekt_id`),
  ADD KEY `auftrag_id` (`auftrag_id`),
  ADD KEY `fk_lagerbewegungen_user` (`user_id`);

--
-- Indizes für die Tabelle `materialien`
--
ALTER TABLE `materialien`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `projekte`
--
ALTER TABLE `projekte`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `projekt_filamente`
--
ALTER TABLE `projekt_filamente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projekt_id` (`projekt_id`),
  ADD KEY `filament_id` (`filament_id`);

--
-- Indizes für die Tabelle `rechnungen`
--
ALTER TABLE `rechnungen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auftrag_id` (`auftrag_id`),
  ADD KEY `kunde_id` (`kunde_id`);

--
-- Indizes für die Tabelle `rechnungspositionen`
--
ALTER TABLE `rechnungspositionen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rechnung_id` (`rechnung_id`);

--
-- Indizes für die Tabelle `spulenlager`
--
ALTER TABLE `spulenlager`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_material` (`material_id`),
  ADD KEY `spulenlager_ibfk_1` (`filament_id`);

--
-- Indizes für die Tabelle `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `auftraege`
--
ALTER TABLE `auftraege`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `auftrag_filamente`
--
ALTER TABLE `auftrag_filamente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `betriebskosten`
--
ALTER TABLE `betriebskosten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `drucker`
--
ALTER TABLE `drucker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `filamente`
--
ALTER TABLE `filamente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `firmendaten`
--
ALTER TABLE `firmendaten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `hersteller`
--
ALTER TABLE `hersteller`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `kunden`
--
ALTER TABLE `kunden`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `lagerbewegungen`
--
ALTER TABLE `lagerbewegungen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `materialien`
--
ALTER TABLE `materialien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `projekte`
--
ALTER TABLE `projekte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `projekt_filamente`
--
ALTER TABLE `projekt_filamente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `rechnungen`
--
ALTER TABLE `rechnungen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `rechnungspositionen`
--
ALTER TABLE `rechnungspositionen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `spulenlager`
--
ALTER TABLE `spulenlager`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `auftraege`
--
ALTER TABLE `auftraege`
  ADD CONSTRAINT `auftraege_ibfk_kunde` FOREIGN KEY (`kunde_id`) REFERENCES `kunden` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_auftraege_projekt` FOREIGN KEY (`projekt_id`) REFERENCES `projekte` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `auftrag_filamente`
--
ALTER TABLE `auftrag_filamente`
  ADD CONSTRAINT `auftrag_filamente_ibfk_1` FOREIGN KEY (`auftrag_id`) REFERENCES `auftraege` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `auftrag_filamente_ibfk_2` FOREIGN KEY (`filament_id`) REFERENCES `filamente` (`id`);

--
-- Constraints der Tabelle `filamente`
--
ALTER TABLE `filamente`
  ADD CONSTRAINT `filamente_ibfk_1` FOREIGN KEY (`hersteller_id`) REFERENCES `hersteller` (`id`);

--
-- Constraints der Tabelle `lagerbewegungen`
--
ALTER TABLE `lagerbewegungen`
  ADD CONSTRAINT `fk_lagerbewegungen_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lagerbewegungen_ibfk_1` FOREIGN KEY (`spule_id`) REFERENCES `spulenlager` (`id`),
  ADD CONSTRAINT `lagerbewegungen_ibfk_2` FOREIGN KEY (`filament_id`) REFERENCES `filamente` (`id`),
  ADD CONSTRAINT `lagerbewegungen_ibfk_3` FOREIGN KEY (`projekt_id`) REFERENCES `projekte` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lagerbewegungen_ibfk_4` FOREIGN KEY (`auftrag_id`) REFERENCES `auftraege` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `projekt_filamente`
--
ALTER TABLE `projekt_filamente`
  ADD CONSTRAINT `projekt_filamente_ibfk_1` FOREIGN KEY (`projekt_id`) REFERENCES `projekte` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projekt_filamente_ibfk_2` FOREIGN KEY (`filament_id`) REFERENCES `filamente` (`id`);

--
-- Constraints der Tabelle `rechnungen`
--
ALTER TABLE `rechnungen`
  ADD CONSTRAINT `rechnungen_ibfk_1` FOREIGN KEY (`auftrag_id`) REFERENCES `auftraege` (`id`),
  ADD CONSTRAINT `rechnungen_ibfk_2` FOREIGN KEY (`kunde_id`) REFERENCES `kunden` (`id`);

--
-- Constraints der Tabelle `rechnungspositionen`
--
ALTER TABLE `rechnungspositionen`
  ADD CONSTRAINT `rechnungspositionen_ibfk_1` FOREIGN KEY (`rechnung_id`) REFERENCES `rechnungen` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `spulenlager`
--
ALTER TABLE `spulenlager`
  ADD CONSTRAINT `fk_material` FOREIGN KEY (`material_id`) REFERENCES `materialien` (`id`),
  ADD CONSTRAINT `spulenlager_ibfk_1` FOREIGN KEY (`filament_id`) REFERENCES `filamente` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
