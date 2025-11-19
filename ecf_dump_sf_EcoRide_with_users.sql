-- MySQL dump 10.13  Distrib 8.0.43, for Linux (aarch64)
--
-- Host: localhost    Database: sf_EcoRide
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `sf_EcoRide`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sf_EcoRide` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `sf_EcoRide`;

--
-- Table structure for table `avis`
--

DROP TABLE IF EXISTS `avis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `avis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `auteur_id` int NOT NULL,
  `covoiturage_id` int NOT NULL,
  `cible_id` int NOT NULL,
  `note` int NOT NULL,
  `commentaire` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime NOT NULL,
  `valide` tinyint(1) NOT NULL,
  `booking_id` int DEFAULT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8F91ABF060BB6FE6` (`auteur_id`),
  KEY `IDX_8F91ABF062671590` (`covoiturage_id`),
  KEY `IDX_8F91ABF0A96E5E09` (`cible_id`),
  KEY `IDX_8F91ABF03301C60` (`booking_id`),
  CONSTRAINT `FK_8F91ABF03301C60` FOREIGN KEY (`booking_id`) REFERENCES `reservation` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_8F91ABF060BB6FE6` FOREIGN KEY (`auteur_id`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_8F91ABF062671590` FOREIGN KEY (`covoiturage_id`) REFERENCES `covoiturage` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_8F91ABF0A96E5E09` FOREIGN KEY (`cible_id`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avis`
--

LOCK TABLES `avis` WRITE;
/*!40000 ALTER TABLE `avis` DISABLE KEYS */;
/*!40000 ALTER TABLE `avis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `covoiturage`
--

DROP TABLE IF EXISTS `covoiturage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `covoiturage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chauffeur_id` int DEFAULT NULL,
  `voiture_id` int DEFAULT NULL,
  `date_depart` date NOT NULL,
  `heure_depart` time NOT NULL,
  `lieu_depart` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_arrivee` date NOT NULL,
  `heure_arrivee` time NOT NULL,
  `lieu_arrivee` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prix_par_place` double DEFAULT NULL,
  `nb_places_total` int NOT NULL,
  `nb_places_dispo` int NOT NULL,
  `statut` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_28C79E8985C0B3BE` (`chauffeur_id`),
  KEY `IDX_28C79E89181A8BA` (`voiture_id`),
  CONSTRAINT `FK_28C79E89181A8BA` FOREIGN KEY (`voiture_id`) REFERENCES `voiture` (`id`),
  CONSTRAINT `FK_28C79E8985C0B3BE` FOREIGN KEY (`chauffeur_id`) REFERENCES `utilisateur` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `covoiturage`
--

LOCK TABLES `covoiturage` WRITE;
/*!40000 ALTER TABLE `covoiturage` DISABLE KEYS */;
/*!40000 ALTER TABLE `covoiturage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctrine_migration_versions`
--

LOCK TABLES `doctrine_migration_versions` WRITE;
/*!40000 ALTER TABLE `doctrine_migration_versions` DISABLE KEYS */;
INSERT INTO `doctrine_migration_versions` VALUES ('DoctrineMigrations\\Version20251105203356','2025-11-05 21:39:33',299),('DoctrineMigrations\\Version20251105220345','2025-11-05 23:45:31',153),('DoctrineMigrations\\Version20251106224729','2025-11-06 23:48:37',224),('DoctrineMigrations\\Version20251107143918','2025-11-07 15:39:47',39),('DoctrineMigrations\\Version20251107214513','2025-11-07 22:46:06',32),('DoctrineMigrations\\Version20251109104257','2025-11-09 11:43:33',80),('DoctrineMigrations\\Version20251109174750','2025-11-09 18:48:19',30),('DoctrineMigrations\\Version20251111140537','2025-11-11 15:05:40',38),('DoctrineMigrations\\Version20251111141923','2025-11-11 15:19:37',31),('DoctrineMigrations\\Version20251111145836','2025-11-11 15:58:39',31),('DoctrineMigrations\\Version20251111160717','2025-11-11 17:07:28',163),('DoctrineMigrations\\Version20251111163728','2025-11-11 17:37:30',46),('DoctrineMigrations\\Version20251111183225','2025-11-11 19:32:39',34),('DoctrineMigrations\\Version20251111213751','2025-11-11 22:38:05',48),('DoctrineMigrations\\Version20251111224621','2025-11-11 23:46:29',214),('DoctrineMigrations\\Version20251112103013','2025-11-12 11:30:21',24),('DoctrineMigrations\\Version20251113122945','2025-11-13 13:29:49',29),('DoctrineMigrations\\Version20251113144824','2025-11-13 15:48:26',90),('DoctrineMigrations\\Version20251114101110','2025-11-14 11:11:12',77),('DoctrineMigrations\\Version20251117051601','2025-11-17 06:16:03',29),('DoctrineMigrations\\Version20251117052114','2025-11-17 06:21:24',36);
/*!40000 ALTER TABLE `doctrine_migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservation`
--

DROP TABLE IF EXISTS `reservation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `passager_id` int NOT NULL,
  `covoiturage_id` int DEFAULT NULL,
  `date_reservation` datetime NOT NULL,
  `nb_places_reservees` int NOT NULL,
  `statut` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_42C8495571A51189` (`passager_id`),
  KEY `IDX_42C8495562671590` (`covoiturage_id`),
  CONSTRAINT `FK_42C8495562671590` FOREIGN KEY (`covoiturage_id`) REFERENCES `covoiturage` (`id`),
  CONSTRAINT `FK_42C8495571A51189` FOREIGN KEY (`passager_id`) REFERENCES `utilisateur` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservation`
--

LOCK TABLES `reservation` WRITE;
/*!40000 ALTER TABLE `reservation` DISABLE KEYS */;
/*!40000 ALTER TABLE `reservation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `utilisateur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note_moyenne` double DEFAULT NULL,
  `a_propos` longtext COLLATE utf8mb4_unicode_ci,
  `last_password_reset_request_at` datetime DEFAULT NULL,
  `roles` json NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `api_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateur`
--

LOCK TABLES `utilisateur` WRITE;
/*!40000 ALTER TABLE `utilisateur` DISABLE KEYS */;
INSERT INTO `utilisateur` VALUES (1,'Admin','Super','admin@example.com','$2y$10$G2aU.sS6cciGWwUGU8vjBOv9p82AbkDLGfetHXaTDV1SYSChWqrV2',NULL,NULL,NULL,'[\"ROLE_USER\", \"ROLE_EMPLOYE\", \"ROLE_ADMIN\"]',1,'c027573ebc1123c1eedecafe8b88478dbc72fc62'),(2,'Employe','Principal','employe@example.com','$2y$10$z5Gl.ZEvDGmgn0H6JnqwgeCv5uztjHH66Tw6RlckXpJOMSkxEMQIi',NULL,NULL,NULL,'[\"ROLE_USER\", \"ROLE_EMPLOYE\"]',1,'65a5a9ae2baf9403f9e1ba9e25a91c9fc7f8a1f3'),(3,'User','Simple','user@example.com','$2y$10$MQURbUgw7E0LBXW1PsFWM.4hnUwr3SOQ2mgZAAUr7feYg.g2K/J1e',NULL,NULL,NULL,'[\"ROLE_USER\"]',1,'9a7b81422e210b5fb8cf6ca25cb8546c4da2097a');
/*!40000 ALTER TABLE `utilisateur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voiture`
--

DROP TABLE IF EXISTS `voiture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voiture` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proprietaire_id` int DEFAULT NULL,
  `marque` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modele` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `couleur` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_energie` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `immatriculation` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nb_places` int NOT NULL,
  `preferences_chauffeur` json DEFAULT NULL,
  `autres_preferences` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_registration` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E9E2810F76C50E4A` (`proprietaire_id`),
  CONSTRAINT `FK_E9E2810F76C50E4A` FOREIGN KEY (`proprietaire_id`) REFERENCES `utilisateur` (`id`),
  CONSTRAINT `chk_car_fuel` CHECK ((`type_energie` in (_utf8mb4'Électrique',_utf8mb4'Thermique',_utf8mb4'Hybride'))),
  CONSTRAINT `chk_voiture_fuel` CHECK ((`type_energie` in (_utf8mb4'Électrique',_utf8mb4'Thermique',_utf8mb4'Hybride')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voiture`
--

LOCK TABLES `voiture` WRITE;
/*!40000 ALTER TABLE `voiture` DISABLE KEYS */;
/*!40000 ALTER TABLE `voiture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'sf_EcoRide'
--

--
-- Dumping routines for database 'sf_EcoRide'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-18 19:05:27
