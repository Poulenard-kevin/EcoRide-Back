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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avis`
--

LOCK TABLES `avis` WRITE;
/*!40000 ALTER TABLE `avis` DISABLE KEYS */;
INSERT INTO `avis` VALUES (1,1,12,3,5,'Chauffeur au top!!! rien à dire :)','2025-11-12 10:00:43',1,22,''),(4,3,15,1,5,'Merci pour ce trajet.','2025-11-12 11:22:07',1,26,''),(5,3,16,1,5,'Sympa, je recommande.','2025-11-12 11:39:52',1,27,'PENDING'),(6,4,17,1,1,'Ensemble moyen ...','2025-11-12 12:03:18',1,28,'PENDING'),(8,1,25,3,5,'Très bon trajet','2025-11-14 14:23:46',0,NULL,'PENDING');
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
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `covoiturage`
--

LOCK TABLES `covoiturage` WRITE;
/*!40000 ALTER TABLE `covoiturage` DISABLE KEYS */;
INSERT INTO `covoiturage` VALUES (2,4,1,'2025-11-10','10:00:00','Grenoble','2025-11-10','12:00:00','Lyon',30,3,0,'archived',NULL),(12,3,2,'2025-11-11','10:00:00','Valence','2025-11-11','11:00:00','Grenoble',30,3,0,'archived','2025-11-11 19:33:29'),(14,3,2,'2025-11-12','10:00:00','Valence','2025-11-12','11:00:00','Grenoble',25,3,0,'archived','2025-11-12 10:43:46'),(15,1,4,'2025-11-12','08:00:00','Paris','2025-11-12','15:00:00','Toulouse',50,3,0,'archived','2025-11-12 11:02:51'),(16,1,4,'2025-11-12','10:00:00','Marseille','2025-11-12','18:00:00','Paris',40,3,0,'archived','2025-11-12 11:38:36'),(17,1,4,'2025-11-13','10:00:00','Nice','2025-11-13','14:00:00','Lyon',35,3,0,'archived','2025-11-12 12:02:54'),(25,3,2,'2025-11-14','18:00:00','Valence','2025-11-14','19:00:00','Grenoble',65,3,1,'active',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservation`
--

LOCK TABLES `reservation` WRITE;
/*!40000 ALTER TABLE `reservation` DISABLE KEYS */;
INSERT INTO `reservation` VALUES (22,1,12,'2025-11-11 18:25:26',3,'completed','2025-11-12 09:37:00'),(25,1,14,'2025-11-12 10:42:41',3,'completed','2025-11-12 10:45:53'),(26,3,15,'2025-11-12 11:02:18',3,'completed','2025-11-12 11:39:12'),(27,3,16,'2025-11-12 11:38:11',3,'completed','2025-11-12 11:39:30'),(28,4,17,'2025-11-12 12:02:39',3,'completed','2025-11-12 12:03:01'),(38,1,25,'2025-11-14 12:12:03',1,'confirmed',NULL),(39,3,25,'2025-11-14 23:21:09',1,'pending',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateur`
--

LOCK TABLES `utilisateur` WRITE;
/*!40000 ALTER TABLE `utilisateur` DISABLE KEYS */;
INSERT INTO `utilisateur` VALUES (1,'EcoRide','Admin','admin@ecoride.com','$2y$13$aWMxHDPwDo/EjvW3ameN8ukwH13EWc6LgveuUAB3MjigOC3dndd0G',NULL,NULL,NULL,'[\"ROLE_ADMIN\", \"ROLE_USER\", \"ROLE_EMPLOYE\"]',1,'TOKEN_ADMIN_123'),(2,'Support','Employé','employe@ecoride.com','$2y$13$3pWPxa9qtxMh.mZazCCbguKgDbLWCDeyyqIPJbmyispoDaHxfJkyu',NULL,NULL,NULL,'[\"ROLE_EMPLOYE\"]',1,'416edc5b69c5a3df700680985bd7286973cdf4f9'),(3,'Poulenard','kevin','kevin@ecoride.com','$2y$13$.tNtpf3nWsAv8ET248B7GO0xxYhhW/HmcP2kZyzVewvZI0/hJAC3a',NULL,'Coucou c\'est moi Kévin :)',NULL,'[\"ROLE_USER\"]',1,'bea07bc0b5c32e2c736e4cba4a056057420148762af720f431775a30424f1fb2'),(4,'Dupont','Marie','marie@ecoride.com','$2y$13$IaFTWz8KLflzxnEW7DvE..TIB9A08sd77PW/GRwLHd2HPbGUvkQsy',NULL,NULL,NULL,'[\"ROLE_USER\"]',1,'5d8bd3a4bf85f4f17d3c809dc347eeb29e81a6344a4b9954c30f13df3f45650e'),(7,'Dupont','Amandine','amandine@ecoride.com','$2y$13$OZ3Q8/r4STYQP4ZYty6BeeCyJTVJKKeKJdRoItbZlvTI76Ek2fjvK',NULL,NULL,NULL,'[\"ROLE_USER\"]',1,'b294589ecd42e70ee71e7a60ec5cdf5cb3ecdb08bb7628e54c2ef3e37feec9ac'),(9,'Support','EmployeA','employeA@ecoride.com','$2y$13$oni/dUapbV2Bx2j6E3dEk.JB295vtmKvhXnHl9oLAs9oOcx./qxJW',NULL,NULL,NULL,'[\"ROLE_EMPLOYE\", \"ROLE_USER\"]',1,NULL),(29,'Jean','Dupont','test@example.com','$2y$13$yvHmZQzRwi1AhjZKbi/Am.ZONrTJR35AsOlHdaQsYEn8I6HMyNT/a',NULL,NULL,NULL,'[\"ROLE_USER\"]',1,'TOKEN_USER_456'),(33,'Dupont','Nicolas','dupont.nicolas@ecoride.com','$2y$13$EIr1CQ8ls2IdxpMRlt7gLOL7jp2W678ODzfW9nPjIh4ft3TW4Hgzq',NULL,NULL,NULL,'[\"ROLE_USER\"]',1,'98b4e7874c2cebb46f6fab38dfb566d16c20a7b33e6b9d914127c090c93e'),(34,'Dupont','Michel','dupont.michel@ecoride.com','$2y$13$.VTYUkdysDD1lesT8StguOJFODmwAm9Vu0ixbozYsWM8bsTMCAaY.',NULL,NULL,NULL,'[\"ROLE_USER\"]',1,'4baf21211ee3709f66dc50efc1032b26edd0a8251b6c17bb88ee6e6475a2'),(35,'Dupont','Pierre','dupont.pierre@ecoride.com','$2y$13$pdRx5EIExi3y/.VVh3RVb.JXQpTNyICJR8pIxCBpUbseDfEjbk2Nq',NULL,NULL,NULL,'[\"ROLE_USER\"]',1,'94e7fd02342990339f0cd525808b6f31db5bc299b5ba9516e83509dc35c1');
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voiture`
--

LOCK TABLES `voiture` WRITE;
/*!40000 ALTER TABLE `voiture` DISABLE KEYS */;
INSERT INTO `voiture` VALUES (1,4,'Peugeot','3008','Rouge','Hybride','JD-258-DF',3,'[\"Musique\"]','Parler',NULL),(2,3,'Tesla','Model 3','Noir','Électrique','AB-021-HG',3,'[\"Musique\"]','Sport mécanique + sport automobile',NULL),(3,1,'Renault','Clio 3','Bleu','Thermique','DL-589-MP',3,'[\"Fumeur\", \"Animal\"]',NULL,NULL),(4,1,'Ford','Focus','Verte','Thermique','KO-258-PO',3,'[\"Musique\"]',NULL,NULL),(7,3,'Peugeot','406','Bleu','Thermique','HG-258-HT',4,'[\"Musique\"]','Parler',NULL),(9,1,'Peugeot','208','Vert','Thermique','AB-123-CD',4,'[]',NULL,NULL),(31,33,'Tesla','Model 3','Noir','Électrique','JY - 561 - TH',4,'[]','','2025-11-17'),(32,33,'Peugeot','3008','Rouge','Hybride','YH - 665 - RG',5,'[]','','2025-11-17');
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

-- Dump completed on 2025-11-18 17:57:12
