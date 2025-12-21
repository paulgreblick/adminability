Warning: A partial dump from a server that has GTIDs will by default include the GTIDs of all transactions, even those that changed suppressed parts of the database. If you don't want to restore GTIDs, pass --set-gtid-purged=OFF. To make a complete dump, pass --all-databases --triggers --routines --events. 
Warning: A dump from a server that has GTIDs enabled will by default include the GTIDs of all transactions, even those that were executed during its extraction and might not be represented in the dumped data. This might result in an inconsistent data dump. 
In order to ensure a consistent backup of the database, pass --single-transaction or --lock-all-tables or --source-data. 
-- MySQL dump 10.13  Distrib 9.5.0, for macos26.1 (arm64)
--
-- Host: localhost    Database: paulgreb_adminability
-- ------------------------------------------------------
-- Server version	9.5.0

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
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '9ddb2316-d7c4-11f0-ae13-c882347cd0a2:1-525';

--
-- Dumping data for table `video_categories`
--

LOCK TABLES `video_categories` WRITE;
/*!40000 ALTER TABLE `video_categories` DISABLE KEYS */;
INSERT INTO `video_categories` VALUES (1,'Affirmations','Faceless YouTube affirmation videos',0,'2025-12-14 20:46:31');
/*!40000 ALTER TABLE `video_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `workflow_steps`
--

LOCK TABLES `workflow_steps` WRITE;
/*!40000 ALTER TABLE `workflow_steps` DISABLE KEYS */;
INSERT INTO `workflow_steps` VALUES (1,'Script Draft','writing',1,'2025-12-15 01:52:42');
INSERT INTO `workflow_steps` VALUES (2,'Script Final','writing',2,'2025-12-15 01:52:42');
INSERT INTO `workflow_steps` VALUES (3,'Base Recording','audio',3,'2025-12-15 01:52:42');
INSERT INTO `workflow_steps` VALUES (4,'Editing','audio',4,'2025-12-15 01:52:42');
INSERT INTO `workflow_steps` VALUES (5,'PowerPoint Created','video',5,'2025-12-15 01:52:42');
INSERT INTO `workflow_steps` VALUES (6,'PowerPoint Assembled','video',6,'2025-12-15 01:52:42');
INSERT INTO `workflow_steps` VALUES (7,'Title Confirmed','publish',7,'2025-12-15 01:52:42');
INSERT INTO `workflow_steps` VALUES (8,'Thumbnail Created','publish',8,'2025-12-15 01:52:42');
INSERT INTO `workflow_steps` VALUES (9,'Description Created','publish',9,'2025-12-15 01:52:42');
INSERT INTO `workflow_steps` VALUES (10,'IG Comments Created','publish',10,'2025-12-15 01:52:42');
INSERT INTO `workflow_steps` VALUES (11,'Uploaded to YouTube','final',11,'2025-12-15 01:52:42');
INSERT INTO `workflow_steps` VALUES (12,'Comments Pinned','final',12,'2025-12-15 01:52:42');
/*!40000 ALTER TABLE `workflow_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `videos`
--

LOCK TABLES `videos` WRITE;
/*!40000 ALTER TABLE `videos` DISABLE KEYS */;
INSERT INTO `videos` VALUES (65,1,'Manifestation',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (66,1,'Stress',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (67,1,'Overwhelm',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (68,1,'Anxiety',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (69,1,'Abundance',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (70,1,'Happiness',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (71,1,'Self Love',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (72,1,'Morning Positive Energy',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (73,1,'Money',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (74,1,'Healing from the Past',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (75,1,'Worry',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (76,1,'Peace and Calm',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (77,1,'Positive Life Changes',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (78,1,'Success',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (79,1,'Wealth',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
INSERT INTO `videos` VALUES (80,1,'Health',NULL,NULL,'not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started','not_started',NULL,NULL,'2025-12-14 20:46:31','2025-12-14 20:46:31');
/*!40000 ALTER TABLE `videos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `video_progress`
--

LOCK TABLES `video_progress` WRITE;
/*!40000 ALTER TABLE `video_progress` DISABLE KEYS */;
INSERT INTO `video_progress` VALUES (1,65,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (2,65,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (3,65,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (4,65,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (5,65,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (6,65,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (7,65,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (8,65,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (9,65,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (10,65,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (11,65,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (12,65,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (13,66,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (14,66,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (15,66,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (16,66,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (17,66,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (18,66,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (19,66,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (20,66,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (21,66,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (22,66,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (23,66,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (24,66,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (25,67,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (26,67,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (27,67,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (28,67,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (29,67,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (30,67,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (31,67,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (32,67,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (33,67,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (34,67,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (35,67,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (36,67,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (37,68,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (38,68,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (39,68,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (40,68,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (41,68,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (42,68,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (43,68,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (44,68,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (45,68,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (46,68,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (47,68,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (48,68,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (49,69,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (50,69,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (51,69,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (52,69,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (53,69,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (54,69,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (55,69,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (56,69,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (57,69,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (58,69,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (59,69,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (60,69,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (61,70,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (62,70,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (63,70,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (64,70,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (65,70,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (66,70,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (67,70,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (68,70,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (69,70,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (70,70,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (71,70,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (72,70,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (73,71,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (74,71,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (75,71,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (76,71,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (77,71,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (78,71,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (79,71,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (80,71,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (81,71,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (82,71,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (83,71,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (84,71,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (85,72,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (86,72,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (87,72,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (88,72,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (89,72,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (90,72,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (91,72,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (92,72,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (93,72,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (94,72,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (95,72,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (96,72,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (97,73,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (98,73,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (99,73,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (100,73,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (101,73,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (102,73,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (103,73,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (104,73,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (105,73,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (106,73,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (107,73,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (108,73,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (109,74,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (110,74,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (111,74,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (112,74,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (113,74,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (114,74,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (115,74,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (116,74,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (117,74,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (118,74,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (119,74,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (120,74,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (121,75,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (122,75,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (123,75,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (124,75,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (125,75,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (126,75,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (127,75,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (128,75,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (129,75,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (130,75,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (131,75,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (132,75,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (133,76,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (134,76,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (135,76,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (136,76,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (137,76,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (138,76,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (139,76,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (140,76,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (141,76,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (142,76,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (143,76,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (144,76,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (145,77,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (146,77,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (147,77,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (148,77,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (149,77,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (150,77,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (151,77,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (152,77,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (153,77,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (154,77,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (155,77,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (156,77,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (157,78,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (158,78,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (159,78,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (160,78,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (161,78,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (162,78,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (163,78,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (164,78,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (165,78,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (166,78,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (167,78,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (168,78,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (169,79,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (170,79,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (171,79,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (172,79,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (173,79,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (174,79,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (175,79,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (176,79,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (177,79,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (178,79,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (179,79,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (180,79,1,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (181,80,12,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (182,80,11,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (183,80,10,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (184,80,9,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (185,80,8,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (186,80,7,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (187,80,6,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (188,80,5,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (189,80,4,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (190,80,3,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (191,80,2,'not_started','2025-12-15 01:52:42');
INSERT INTO `video_progress` VALUES (192,80,1,'not_started','2025-12-15 01:52:42');
/*!40000 ALTER TABLE `video_progress` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-14 22:15:10
