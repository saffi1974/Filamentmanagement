-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: 3d_druck
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `auftraege`
--

DROP TABLE IF EXISTS `auftraege`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auftraege` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `kunde_id` int(11) DEFAULT NULL,
  `anzahl` int(11) NOT NULL DEFAULT 1,
  `druckzeit_seconds` int(10) unsigned DEFAULT 0,
  `status` enum('offen','in_bearbeitung','fertig') NOT NULL DEFAULT 'offen',
  `datum` date DEFAULT NULL,
  `projekt_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `auftraege_ibfk_kunde` (`kunde_id`),
  KEY `fk_auftraege_projekt` (`projekt_id`),
  CONSTRAINT `auftraege_ibfk_kunde` FOREIGN KEY (`kunde_id`) REFERENCES `kunden` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_auftraege_projekt` FOREIGN KEY (`projekt_id`) REFERENCES `projekte` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auftraege`
--

LOCK TABLES `auftraege` WRITE;
/*!40000 ALTER TABLE `auftraege` DISABLE KEYS */;
/*!40000 ALTER TABLE `auftraege` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auftrag_filamente`
--

DROP TABLE IF EXISTS `auftrag_filamente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auftrag_filamente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `auftrag_id` int(11) NOT NULL,
  `filament_id` int(11) NOT NULL,
  `menge_geplant` decimal(10,2) NOT NULL,
  `menge_gebucht` decimal(10,2) DEFAULT 0.00,
  `status` enum('geplant','teilweise_gebucht','gebucht') DEFAULT 'geplant',
  PRIMARY KEY (`id`),
  KEY `auftrag_id` (`auftrag_id`),
  KEY `filament_id` (`filament_id`),
  CONSTRAINT `auftrag_filamente_ibfk_1` FOREIGN KEY (`auftrag_id`) REFERENCES `auftraege` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auftrag_filamente_ibfk_2` FOREIGN KEY (`filament_id`) REFERENCES `filamente` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auftrag_filamente`
--

LOCK TABLES `auftrag_filamente` WRITE;
/*!40000 ALTER TABLE `auftrag_filamente` DISABLE KEYS */;
/*!40000 ALTER TABLE `auftrag_filamente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `betriebskosten`
--

DROP TABLE IF EXISTS `betriebskosten`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `betriebskosten` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kostenart` varchar(100) NOT NULL,
  `beschreibung` text DEFAULT NULL,
  `standard_betrag` decimal(10,2) DEFAULT 0.00,
  `einheit` enum('pauschal','pro_stunde','pro_stueck') DEFAULT 'pauschal',
  `erstellt_am` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `betriebskosten`
--

LOCK TABLES `betriebskosten` WRITE;
/*!40000 ALTER TABLE `betriebskosten` DISABLE KEYS */;
/*!40000 ALTER TABLE `betriebskosten` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drucker`
--

DROP TABLE IF EXISTS `drucker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drucker` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `hersteller` varchar(100) DEFAULT NULL,
  `stromverbrauch_watt` decimal(10,2) NOT NULL,
  `kosten_pro_kwh` decimal(10,2) NOT NULL,
  `kommentar` text DEFAULT NULL,
  `angelegt_am` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `drucker`
--

LOCK TABLES `drucker` WRITE;
/*!40000 ALTER TABLE `drucker` DISABLE KEYS */;
/*!40000 ALTER TABLE `drucker` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filamente`
--

DROP TABLE IF EXISTS `filamente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `filamente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `erstellt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `hersteller_id` (`hersteller_id`),
  CONSTRAINT `filamente_ibfk_1` FOREIGN KEY (`hersteller_id`) REFERENCES `hersteller` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filamente`
--

LOCK TABLES `filamente` WRITE;
/*!40000 ALTER TABLE `filamente` DISABLE KEYS */;
/*!40000 ALTER TABLE `filamente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `firmendaten`
--

DROP TABLE IF EXISTS `firmendaten`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `firmendaten` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firmendaten`
--

LOCK TABLES `firmendaten` WRITE;
/*!40000 ALTER TABLE `firmendaten` DISABLE KEYS */;
/*!40000 ALTER TABLE `firmendaten` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hersteller`
--

DROP TABLE IF EXISTS `hersteller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hersteller` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hr_name` varchar(255) NOT NULL,
  `hr_leerspule` int(3) NOT NULL,
  `hr_kommentar` varchar(255) NOT NULL,
  `hr_eingetragen` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hersteller`
--

LOCK TABLES `hersteller` WRITE;
/*!40000 ALTER TABLE `hersteller` DISABLE KEYS */;
/*!40000 ALTER TABLE `hersteller` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kunden`
--

DROP TABLE IF EXISTS `kunden`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kunden` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma` varchar(255) DEFAULT NULL,
  `ansprechpartner` varchar(255) DEFAULT NULL,
  `telefon` varchar(50) DEFAULT NULL,
  `strasse` varchar(255) DEFAULT NULL,
  `plz` varchar(10) DEFAULT NULL,
  `ort` varchar(255) DEFAULT NULL,
  `versandart` enum('Versand','Abholung') DEFAULT 'Abholung',
  `angelegt_am` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kunden`
--

LOCK TABLES `kunden` WRITE;
/*!40000 ALTER TABLE `kunden` DISABLE KEYS */;
/*!40000 ALTER TABLE `kunden` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lagerbewegungen`
--

DROP TABLE IF EXISTS `lagerbewegungen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lagerbewegungen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spule_id` int(11) NOT NULL,
  `filament_id` int(11) NOT NULL,
  `projekt_id` int(11) DEFAULT NULL,
  `auftrag_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bewegungsart` enum('wareneingang','abbuchung_projekt','abbuchung_auftrag','korrektur') NOT NULL,
  `menge` decimal(10,2) NOT NULL,
  `datum` timestamp NOT NULL DEFAULT current_timestamp(),
  `kommentar` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `spule_id` (`spule_id`),
  KEY `filament_id` (`filament_id`),
  KEY `projekt_id` (`projekt_id`),
  KEY `auftrag_id` (`auftrag_id`),
  KEY `fk_lagerbewegungen_user` (`user_id`),
  CONSTRAINT `fk_lagerbewegungen_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lagerbewegungen_ibfk_1` FOREIGN KEY (`spule_id`) REFERENCES `spulenlager` (`id`),
  CONSTRAINT `lagerbewegungen_ibfk_2` FOREIGN KEY (`filament_id`) REFERENCES `filamente` (`id`),
  CONSTRAINT `lagerbewegungen_ibfk_3` FOREIGN KEY (`projekt_id`) REFERENCES `projekte` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lagerbewegungen_ibfk_4` FOREIGN KEY (`auftrag_id`) REFERENCES `auftraege` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lagerbewegungen`
--

LOCK TABLES `lagerbewegungen` WRITE;
/*!40000 ALTER TABLE `lagerbewegungen` DISABLE KEYS */;
/*!40000 ALTER TABLE `lagerbewegungen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materialien`
--

DROP TABLE IF EXISTS `materialien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materialien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `kommentar` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materialien`
--

LOCK TABLES `materialien` WRITE;
/*!40000 ALTER TABLE `materialien` DISABLE KEYS */;
INSERT INTO `materialien` VALUES (1,'PLA',''),(2,'PLA+',''),(3,'ABS',''),(4,'ASA',''),(6,'PETG',''),(7,'PETG+',''),(9,'ABS+',''),(10,'TPU','');
/*!40000 ALTER TABLE `materialien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projekt_filamente`
--

DROP TABLE IF EXISTS `projekt_filamente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projekt_filamente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projekt_id` int(11) NOT NULL,
  `filament_id` int(11) NOT NULL,
  `menge_geplant` decimal(10,2) NOT NULL,
  `menge_gebucht` decimal(10,2) DEFAULT 0.00,
  `status` enum('geplant','teilweise_gebucht','gebucht') DEFAULT 'geplant',
  `preis_pro_gramm` decimal(10,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projekt_id` (`projekt_id`),
  KEY `filament_id` (`filament_id`),
  CONSTRAINT `projekt_filamente_ibfk_1` FOREIGN KEY (`projekt_id`) REFERENCES `projekte` (`id`) ON DELETE CASCADE,
  CONSTRAINT `projekt_filamente_ibfk_2` FOREIGN KEY (`filament_id`) REFERENCES `filamente` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projekt_filamente`
--

LOCK TABLES `projekt_filamente` WRITE;
/*!40000 ALTER TABLE `projekt_filamente` DISABLE KEYS */;
/*!40000 ALTER TABLE `projekt_filamente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projekte`
--

DROP TABLE IF EXISTS `projekte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projekte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projektname` varchar(255) NOT NULL,
  `kommentar` text NOT NULL DEFAULT '',
  `druckzeit_seconds` int(10) unsigned DEFAULT 0,
  `datum` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projekte`
--

LOCK TABLES `projekte` WRITE;
/*!40000 ALTER TABLE `projekte` DISABLE KEYS */;
/*!40000 ALTER TABLE `projekte` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rechnungen`
--

DROP TABLE IF EXISTS `rechnungen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rechnungen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `auftrag_id` int(11) NOT NULL,
  `kunde_id` int(11) NOT NULL,
  `rechnungsnummer` varchar(50) NOT NULL,
  `datum` date NOT NULL DEFAULT curdate(),
  `gesamtbetrag` decimal(10,2) DEFAULT 0.00,
  `status` enum('offen','bezahlt','storniert') DEFAULT 'offen',
  PRIMARY KEY (`id`),
  KEY `auftrag_id` (`auftrag_id`),
  KEY `kunde_id` (`kunde_id`),
  CONSTRAINT `rechnungen_ibfk_1` FOREIGN KEY (`auftrag_id`) REFERENCES `auftraege` (`id`),
  CONSTRAINT `rechnungen_ibfk_2` FOREIGN KEY (`kunde_id`) REFERENCES `kunden` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rechnungen`
--

LOCK TABLES `rechnungen` WRITE;
/*!40000 ALTER TABLE `rechnungen` DISABLE KEYS */;
/*!40000 ALTER TABLE `rechnungen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rechnungspositionen`
--

DROP TABLE IF EXISTS `rechnungspositionen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rechnungspositionen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rechnung_id` int(11) NOT NULL,
  `typ` enum('material','druckzeit','arbeitszeit','versand','sonstiges','betriebskosten','stromkosten') NOT NULL,
  `beschreibung` varchar(255) NOT NULL,
  `menge` decimal(10,2) NOT NULL,
  `einheit` varchar(50) NOT NULL,
  `preis_pro_einheit` decimal(10,2) NOT NULL,
  `gesamt` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rechnung_id` (`rechnung_id`),
  CONSTRAINT `rechnungspositionen_ibfk_1` FOREIGN KEY (`rechnung_id`) REFERENCES `rechnungen` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rechnungspositionen`
--

LOCK TABLES `rechnungspositionen` WRITE;
/*!40000 ALTER TABLE `rechnungspositionen` DISABLE KEYS */;
/*!40000 ALTER TABLE `rechnungspositionen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `spulenlager`
--

DROP TABLE IF EXISTS `spulenlager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spulenlager` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filament_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `preis` decimal(10,2) NOT NULL,
  `verbrauchtes_filament` decimal(10,2) DEFAULT 0.00,
  `verbleibendes_filament` decimal(10,2) DEFAULT 0.00,
  `lagerort` varchar(50) DEFAULT NULL,
  `erstmals_verwendet` timestamp NOT NULL DEFAULT current_timestamp(),
  `letzte_verwendung` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `chargennummer` varchar(50) DEFAULT NULL,
  `kommentar` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_material` (`material_id`),
  KEY `spulenlager_ibfk_1` (`filament_id`),
  CONSTRAINT `fk_material` FOREIGN KEY (`material_id`) REFERENCES `materialien` (`id`),
  CONSTRAINT `spulenlager_ibfk_1` FOREIGN KEY (`filament_id`) REFERENCES `filamente` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spulenlager`
--

LOCK TABLES `spulenlager` WRITE;
/*!40000 ALTER TABLE `spulenlager` DISABLE KEYS */;
/*!40000 ALTER TABLE `spulenlager` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) NOT NULL,
  `Passwort` varchar(255) NOT NULL,
  `rolle` enum('superuser','admin','user','readonly') NOT NULL DEFAULT 'user',
  `Erstelldatum` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (8,'admin','$2y$10$ZOXCUuBrZjbBIHeK5Td.Eeuct7DaOY9.I62sqKOYZATmfFEFUIb6K','admin','2025-09-21 18:16:38');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-21 20:26:06
