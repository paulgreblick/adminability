-- MySQL dump 10.19  Distrib 10.3.39-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: paulgreb_adminability
-- ------------------------------------------------------
-- Server version	10.3.39-MariaDB

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
-- Table structure for table `doc_categories`
--

DROP TABLE IF EXISTS `doc_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'folder',
  `color` varchar(20) DEFAULT 'gray',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_categories`
--

LOCK TABLES `doc_categories` WRITE;
/*!40000 ALTER TABLE `doc_categories` DISABLE KEYS */;
INSERT INTO `doc_categories` VALUES (1,'Reference','reference',NULL,'book','blue',1,'2025-12-25 03:01:26'),(2,'Processes','processes',NULL,'list','green',2,'2025-12-25 03:01:26'),(3,'Workflows','workflows',NULL,'flow','purple',3,'2025-12-25 03:01:26'),(4,'Guides','guides',NULL,'map','orange',4,'2025-12-25 03:01:26');
/*!40000 ALTER TABLE `doc_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_tag_map`
--

DROP TABLE IF EXISTS `doc_tag_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_tag_map` (
  `doc_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`doc_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `doc_tag_map_ibfk_1` FOREIGN KEY (`doc_id`) REFERENCES `docs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doc_tag_map_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `doc_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_tag_map`
--

LOCK TABLES `doc_tag_map` WRITE;
/*!40000 ALTER TABLE `doc_tag_map` DISABLE KEYS */;
INSERT INTO `doc_tag_map` VALUES (1,1);
/*!40000 ALTER TABLE `doc_tag_map` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_tags`
--

DROP TABLE IF EXISTS `doc_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `color` varchar(20) DEFAULT 'gray',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_tags`
--

LOCK TABLES `doc_tags` WRITE;
/*!40000 ALTER TABLE `doc_tags` DISABLE KEYS */;
INSERT INTO `doc_tags` VALUES (1,'Reference','reference','blue','2026-01-14 01:59:21'),(2,'Process','process','green','2026-01-14 01:59:21'),(3,'Guide','guide','purple','2026-01-14 01:59:21'),(4,'Processes','processes','green','2026-01-14 02:32:12'),(5,'Workflows','workflows','purple','2026-01-14 02:32:12'),(6,'Guides','guides','orange','2026-01-14 02:32:12');
/*!40000 ALTER TABLE `doc_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docs`
--

DROP TABLE IF EXISTS `docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `doc_type` enum('reference','process','workflow','guide') DEFAULT 'reference',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `sort_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `parent_id` (`parent_id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `docs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `doc_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `docs_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `docs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `docs_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `docs_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docs`
--

LOCK TABLES `docs` WRITE;
/*!40000 ALTER TABLE `docs` DISABLE KEYS */;
INSERT INTO `docs` VALUES (1,1,NULL,'Test','test','<p>Test from Paul</p>','reference','draft',0,1,1,'2025-12-25 03:05:28','2025-12-25 03:05:38');
/*!40000 ALTER TABLE `docs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ip_lockouts`
--

DROP TABLE IF EXISTS `ip_lockouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_lockouts` (
  `ip_address` varchar(45) NOT NULL,
  `locked_until` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attempt_count` int(11) DEFAULT 0,
  PRIMARY KEY (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ip_lockouts`
--

LOCK TABLES `ip_lockouts` WRITE;
/*!40000 ALTER TABLE `ip_lockouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `ip_lockouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `note_projects`
--

DROP TABLE IF EXISTS `note_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT 'gray',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `note_projects`
--

LOCK TABLES `note_projects` WRITE;
/*!40000 ALTER TABLE `note_projects` DISABLE KEYS */;
INSERT INTO `note_projects` VALUES (1,'General','gray',1,'2025-12-15 21:57:41'),(2,'Affirmations Project','purple',2,'2025-12-15 21:57:41'),(3,'Website','blue',3,'2025-12-15 21:57:41'),(4,'Ideas','yellow',4,'2025-12-15 21:57:41');
/*!40000 ALTER TABLE `note_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT 1,
  `parent_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `type` enum('note','idea','task','question') DEFAULT 'note',
  `status` enum('idea','in_progress','done') DEFAULT 'idea',
  `priority` enum('low','normal','high') DEFAULT 'normal',
  `is_pinned` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_notes_project` (`project_id`),
  KEY `idx_notes_parent` (`parent_id`),
  CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `note_projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
INSERT INTO `notes` VALUES (2,1,NULL,NULL,'Need to make UTM generator','task','idea','normal',0,2,NULL,'2025-12-16 16:54:24','2025-12-16 16:54:24'),(6,1,NULL,'Email Dashboard System','For Monday and Thursday emails, check drive to see where work is at.','note','idea','normal',0,2,NULL,'2025-12-17 01:54:19','2025-12-17 01:54:19'),(7,1,NULL,'Social Media Systems','Note that Metricool upload sheet for Instagram Carly Style posts is the basis for all other Instagram posts whether they are about articles or other things.\r\n\r\nOther article based posts - exactly the same setup\r\nnon-article based posts\r\n- either \r\n- 1 - caption with DM word to prompt link to lead magnet\r\n- 2 - awareness post of some kind with no prompt to DM anything','note','idea','normal',0,2,NULL,'2025-12-17 20:24:27','2025-12-17 20:24:27'),(9,1,NULL,'Upload scripts','For A&I and Anita namesite, we have to have all upload batch files created.','note','idea','normal',0,2,NULL,'2025-12-18 17:35:09','2025-12-18 17:35:09'),(14,1,NULL,'Additional Social Media Work','- prep more Pinterest images to keep cycle going\r\n- do same for square images\r\n\r\n- curate title pins\r\n- make square article title pins\r\n\r\n- step lesson Angel Quotes further\r\n- create Metricool sheets for different platforms for Angel Quotes','note','idea','normal',0,2,NULL,'2025-12-25 03:18:57','2025-12-25 03:18:57'),(15,1,NULL,'Subjects - # coding','Need to continue assigning #s to the subjects.','task','idea','normal',0,2,NULL,'2025-12-25 03:20:17','2025-12-25 03:20:17');
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'users.view','View users','2025-12-14 19:00:38'),(2,'users.create','Create users','2025-12-14 19:00:38'),(3,'users.edit','Edit users','2025-12-14 19:00:38'),(4,'users.delete','Delete users','2025-12-14 19:00:38'),(5,'roles.view','View roles','2025-12-14 19:00:38'),(6,'roles.manage','Manage roles and permissions','2025-12-14 19:00:38'),(7,'dashboard.view','View dashboard','2025-12-14 19:00:38'),(8,'notes.view','View notes','2025-12-14 19:00:38'),(9,'notes.create','Create notes','2025-12-14 19:00:38'),(10,'notes.edit','Edit notes','2025-12-14 19:00:38'),(11,'notes.delete','Delete notes','2025-12-14 19:00:38'),(12,'videos.view','View video tracker','2025-12-15 02:30:39'),(13,'videos.create','Create videos and categories','2025-12-15 02:30:39'),(14,'videos.edit','Edit video progress','2025-12-15 02:30:39'),(15,'videos.delete','Delete videos and categories','2025-12-15 02:30:39'),(20,'docs.view','View documents','2025-12-25 03:01:26'),(21,'docs.create','Create documents','2025-12-25 03:01:26'),(22,'docs.edit','Edit documents','2025-12-25 03:01:26'),(23,'docs.delete','Delete documents','2025-12-25 03:01:26');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,20),(1,21),(1,22),(1,23),(2,1),(2,2),(2,3),(2,5),(2,7),(2,8),(2,9),(2,10),(2,11),(2,12),(2,13),(2,14),(2,15),(3,7),(3,8),(3,9),(3,10),(3,12),(3,13),(3,14),(3,15),(4,7),(4,8),(4,12),(4,13),(4,14),(4,15);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super_admin','Full access to all features','2025-12-14 19:00:38'),(2,'admin','Administrative access','2025-12-14 19:00:38'),(3,'editor','Can view and edit content','2025-12-14 19:00:38'),(4,'viewer','Read-only access','2025-12-14 19:00:38');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'paul@paulgreblick.com','$2y$12$F/mgVio/tX9BEPzjcbqPJe1yjX9tHJfoL1Epu9YHgN1VWvBEZpzgC','Paul Greblick','Paul',1,1,'2025-12-14 19:00:38','2025-12-25 21:03:04','2025-12-25 03:04:28'),(2,'anita@angelsandinsights.com','$2y$10$gt/Pb8vm1lnbrq/mNlARBeBcgyEjfX7BBnbOlykwBZ71wWEXwpXmO','Anita Colussi-Zanon','Anita',1,1,'2025-12-14 19:07:11','2026-01-09 15:29:14','2026-01-09 15:29:14');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_categories`
--

DROP TABLE IF EXISTS `video_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_categories`
--

LOCK TABLES `video_categories` WRITE;
/*!40000 ALTER TABLE `video_categories` DISABLE KEYS */;
INSERT INTO `video_categories` VALUES (1,'Affirmations','Faceless YouTube affirmation videos',0,'2025-12-15 03:28:54');
/*!40000 ALTER TABLE `video_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_progress`
--

DROP TABLE IF EXISTS `video_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `video_id` int(11) NOT NULL,
  `step_id` int(11) NOT NULL,
  `status` enum('not_started','in_progress','complete') DEFAULT 'not_started',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_video_step` (`video_id`,`step_id`),
  KEY `step_id` (`step_id`),
  CONSTRAINT `video_progress_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_progress_ibfk_2` FOREIGN KEY (`step_id`) REFERENCES `workflow_steps` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_progress`
--

LOCK TABLES `video_progress` WRITE;
/*!40000 ALTER TABLE `video_progress` DISABLE KEYS */;
INSERT INTO `video_progress` VALUES (1,1,1,'not_started','2025-12-15 03:28:54'),(2,1,2,'not_started','2025-12-15 03:28:54'),(3,1,3,'not_started','2025-12-15 03:28:54'),(4,1,4,'not_started','2025-12-15 03:28:54'),(5,1,5,'not_started','2025-12-15 03:28:54'),(6,1,6,'not_started','2025-12-15 03:28:54'),(7,1,7,'not_started','2025-12-15 03:28:54'),(8,1,8,'not_started','2025-12-15 03:28:54'),(9,1,9,'not_started','2025-12-15 03:28:54'),(10,1,10,'not_started','2025-12-15 03:28:54'),(11,1,11,'not_started','2025-12-15 03:28:54'),(12,1,12,'not_started','2025-12-15 03:28:54'),(13,2,1,'not_started','2025-12-15 03:28:54'),(14,2,2,'not_started','2025-12-15 03:28:54'),(15,2,3,'not_started','2025-12-15 03:28:54'),(16,2,4,'not_started','2025-12-15 03:28:54'),(17,2,5,'not_started','2025-12-15 03:28:54'),(18,2,6,'not_started','2025-12-15 03:28:54'),(19,2,7,'not_started','2025-12-15 03:28:54'),(20,2,8,'not_started','2025-12-15 03:28:54'),(21,2,9,'not_started','2025-12-15 03:28:54'),(22,2,10,'not_started','2025-12-15 03:28:54'),(23,2,11,'not_started','2025-12-15 03:28:54'),(24,2,12,'not_started','2025-12-15 03:28:54'),(25,3,1,'not_started','2025-12-15 03:28:54'),(26,3,2,'not_started','2025-12-15 03:28:54'),(27,3,3,'not_started','2025-12-15 03:28:54'),(28,3,4,'not_started','2025-12-15 03:28:54'),(29,3,5,'not_started','2025-12-15 03:28:54'),(30,3,6,'not_started','2025-12-15 03:28:54'),(31,3,7,'not_started','2025-12-15 03:28:54'),(32,3,8,'not_started','2025-12-15 03:28:54'),(33,3,9,'not_started','2025-12-15 03:28:54'),(34,3,10,'not_started','2025-12-15 03:28:54'),(35,3,11,'not_started','2025-12-15 03:28:54'),(36,3,12,'not_started','2025-12-15 03:28:54'),(37,4,1,'not_started','2025-12-15 03:28:54'),(38,4,2,'not_started','2025-12-15 03:28:54'),(39,4,3,'not_started','2025-12-15 03:28:54'),(40,4,4,'not_started','2025-12-15 03:28:54'),(41,4,5,'not_started','2025-12-15 03:28:54'),(42,4,6,'not_started','2025-12-15 03:28:54'),(43,4,7,'not_started','2025-12-15 03:28:54'),(44,4,8,'not_started','2025-12-15 03:28:54'),(45,4,9,'not_started','2025-12-15 03:28:54'),(46,4,10,'not_started','2025-12-15 03:28:54'),(47,4,11,'not_started','2025-12-15 03:28:54'),(48,4,12,'not_started','2025-12-15 03:28:54'),(49,5,1,'not_started','2025-12-15 03:28:54'),(50,5,2,'not_started','2025-12-15 03:28:54'),(51,5,3,'in_progress','2025-12-16 00:16:45'),(52,5,4,'not_started','2025-12-15 03:28:54'),(53,5,5,'not_started','2025-12-15 03:28:54'),(54,5,6,'not_started','2025-12-15 03:28:54'),(55,5,7,'not_started','2025-12-15 03:52:03'),(56,5,8,'not_started','2025-12-15 03:28:54'),(57,5,9,'not_started','2025-12-15 03:28:54'),(58,5,10,'not_started','2025-12-15 03:28:54'),(59,5,11,'not_started','2025-12-15 03:28:54'),(60,5,12,'not_started','2025-12-15 03:28:54'),(61,6,1,'not_started','2025-12-15 03:28:54'),(62,6,2,'not_started','2025-12-15 03:28:54'),(63,6,3,'not_started','2025-12-15 03:28:54'),(64,6,4,'not_started','2025-12-15 03:28:54'),(65,6,5,'not_started','2025-12-15 03:28:54'),(66,6,6,'not_started','2025-12-15 03:28:54'),(67,6,7,'not_started','2025-12-15 03:28:54'),(68,6,8,'not_started','2025-12-15 03:28:54'),(69,6,9,'not_started','2025-12-15 03:28:54'),(70,6,10,'not_started','2025-12-15 03:28:54'),(71,6,11,'not_started','2025-12-15 03:28:54'),(72,6,12,'not_started','2025-12-15 03:28:54'),(73,7,1,'not_started','2025-12-15 03:28:54'),(74,7,2,'not_started','2025-12-15 03:28:54'),(75,7,3,'not_started','2025-12-15 03:28:54'),(76,7,4,'not_started','2025-12-15 03:28:54'),(77,7,5,'not_started','2025-12-15 03:28:54'),(78,7,6,'not_started','2025-12-15 03:28:54'),(79,7,7,'not_started','2025-12-15 03:28:54'),(80,7,8,'not_started','2025-12-15 03:28:54'),(81,7,9,'not_started','2025-12-15 03:28:54'),(82,7,10,'not_started','2025-12-15 03:28:54'),(83,7,11,'not_started','2025-12-15 03:28:54'),(84,7,12,'not_started','2025-12-15 03:28:54'),(85,8,1,'not_started','2025-12-15 03:28:54'),(86,8,2,'not_started','2025-12-15 03:28:54'),(87,8,3,'not_started','2025-12-15 03:28:54'),(88,8,4,'not_started','2025-12-15 03:28:54'),(89,8,5,'not_started','2025-12-15 03:28:54'),(90,8,6,'not_started','2025-12-15 03:28:54'),(91,8,7,'not_started','2025-12-15 03:28:54'),(92,8,8,'not_started','2025-12-15 03:28:54'),(93,8,9,'not_started','2025-12-15 03:28:54'),(94,8,10,'not_started','2025-12-15 03:28:54'),(95,8,11,'not_started','2025-12-15 03:28:54'),(96,8,12,'not_started','2025-12-15 03:28:54'),(97,9,1,'not_started','2025-12-15 03:28:54'),(98,9,2,'not_started','2025-12-15 03:28:54'),(99,9,3,'not_started','2025-12-15 03:28:54'),(100,9,4,'not_started','2025-12-15 03:28:54'),(101,9,5,'not_started','2025-12-15 03:28:54'),(102,9,6,'not_started','2025-12-15 03:28:54'),(103,9,7,'not_started','2025-12-15 03:28:54'),(104,9,8,'not_started','2025-12-15 03:28:54'),(105,9,9,'not_started','2025-12-15 03:28:54'),(106,9,10,'not_started','2025-12-15 03:28:54'),(107,9,11,'not_started','2025-12-15 03:28:54'),(108,9,12,'not_started','2025-12-15 03:28:54'),(109,10,1,'not_started','2025-12-15 03:28:54'),(110,10,2,'not_started','2025-12-15 03:28:54'),(111,10,3,'not_started','2025-12-15 03:28:54'),(112,10,4,'not_started','2025-12-15 03:28:54'),(113,10,5,'not_started','2025-12-15 03:28:54'),(114,10,6,'not_started','2025-12-15 03:28:54'),(115,10,7,'not_started','2025-12-15 03:28:54'),(116,10,8,'not_started','2025-12-15 03:28:54'),(117,10,9,'not_started','2025-12-15 03:28:54'),(118,10,10,'not_started','2025-12-15 03:28:54'),(119,10,11,'not_started','2025-12-15 03:28:54'),(120,10,12,'not_started','2025-12-15 03:28:54'),(121,11,1,'not_started','2025-12-15 03:28:54'),(122,11,2,'not_started','2025-12-15 03:28:54'),(123,11,3,'not_started','2025-12-15 03:28:54'),(124,11,4,'not_started','2025-12-15 03:28:54'),(125,11,5,'not_started','2025-12-15 03:28:54'),(126,11,6,'not_started','2025-12-15 03:28:54'),(127,11,7,'not_started','2025-12-15 03:28:54'),(128,11,8,'not_started','2025-12-15 03:28:54'),(129,11,9,'not_started','2025-12-15 03:28:54'),(130,11,10,'not_started','2025-12-15 03:28:54'),(131,11,11,'not_started','2025-12-15 03:28:54'),(132,11,12,'not_started','2025-12-15 03:28:54'),(133,12,1,'not_started','2025-12-15 03:28:54'),(134,12,2,'not_started','2025-12-15 03:28:54'),(135,12,3,'not_started','2025-12-15 03:28:54'),(136,12,4,'not_started','2025-12-15 03:28:54'),(137,12,5,'not_started','2025-12-15 03:28:54'),(138,12,6,'not_started','2025-12-15 03:28:54'),(139,12,7,'not_started','2025-12-15 03:28:54'),(140,12,8,'not_started','2025-12-15 03:28:54'),(141,12,9,'not_started','2025-12-15 03:28:54'),(142,12,10,'not_started','2025-12-15 03:28:54'),(143,12,11,'not_started','2025-12-15 03:28:54'),(144,12,12,'not_started','2025-12-15 03:28:54'),(145,13,1,'not_started','2025-12-15 03:28:54'),(146,13,2,'not_started','2025-12-15 03:28:54'),(147,13,3,'not_started','2025-12-15 03:28:54'),(148,13,4,'not_started','2025-12-15 03:28:54'),(149,13,5,'not_started','2025-12-15 03:28:54'),(150,13,6,'not_started','2025-12-15 03:28:54'),(151,13,7,'not_started','2025-12-15 03:28:54'),(152,13,8,'not_started','2025-12-15 03:28:54'),(153,13,9,'not_started','2025-12-15 03:28:54'),(154,13,10,'not_started','2025-12-15 03:28:54'),(155,13,11,'not_started','2025-12-15 03:28:54'),(156,13,12,'not_started','2025-12-15 03:28:54'),(157,14,1,'not_started','2025-12-15 03:28:54'),(158,14,2,'not_started','2025-12-15 03:28:54'),(159,14,3,'not_started','2025-12-15 03:28:54'),(160,14,4,'not_started','2025-12-15 03:28:54'),(161,14,5,'not_started','2025-12-15 03:28:54'),(162,14,6,'not_started','2025-12-15 03:28:54'),(163,14,7,'not_started','2025-12-15 03:28:54'),(164,14,8,'not_started','2025-12-15 03:28:54'),(165,14,9,'not_started','2025-12-15 03:28:54'),(166,14,10,'not_started','2025-12-15 03:28:54'),(167,14,11,'not_started','2025-12-15 03:28:54'),(168,14,12,'not_started','2025-12-15 03:28:54'),(169,15,1,'not_started','2025-12-15 03:28:54'),(170,15,2,'not_started','2025-12-15 03:28:54'),(171,15,3,'not_started','2025-12-15 03:28:54'),(172,15,4,'not_started','2025-12-15 03:28:54'),(173,15,5,'not_started','2025-12-15 03:28:54'),(174,15,6,'not_started','2025-12-15 03:28:54'),(175,15,7,'not_started','2025-12-15 03:28:54'),(176,15,8,'not_started','2025-12-15 03:28:54'),(177,15,9,'not_started','2025-12-15 03:28:54'),(178,15,10,'not_started','2025-12-15 03:28:54'),(179,15,11,'not_started','2025-12-15 03:28:54'),(180,15,12,'not_started','2025-12-15 03:28:54'),(181,16,1,'not_started','2025-12-15 03:49:55'),(182,16,2,'not_started','2025-12-15 03:28:54'),(183,16,3,'not_started','2025-12-15 03:28:54'),(184,16,4,'not_started','2025-12-15 03:28:54'),(185,16,5,'not_started','2025-12-15 03:28:54'),(186,16,6,'not_started','2025-12-15 03:28:54'),(187,16,7,'not_started','2025-12-15 03:28:54'),(188,16,8,'not_started','2025-12-15 03:28:54'),(189,16,9,'not_started','2025-12-15 03:28:54'),(190,16,10,'not_started','2025-12-15 03:28:54'),(191,16,11,'not_started','2025-12-15 03:28:54'),(192,16,12,'not_started','2025-12-15 03:28:54');
/*!40000 ALTER TABLE `video_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `videos`
--

DROP TABLE IF EXISTS `videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `folder_link` varchar(500) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `video_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `videos`
--

LOCK TABLES `videos` WRITE;
/*!40000 ALTER TABLE `videos` DISABLE KEYS */;
INSERT INTO `videos` VALUES (1,1,'Manifestation',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(2,1,'Stress',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(3,1,'Overwhelm',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(4,1,'Anxiety',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(5,1,'Abundance',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(6,1,'Happiness',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(7,1,'Self Love',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(8,1,'Morning Positive Energy',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(9,1,'Money',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(10,1,'Healing from the Past',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(11,1,'Worry',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(12,1,'Peace and Calm',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(13,1,'Positive Life Changes',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(14,1,'Success',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(15,1,'Wealth',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54'),(16,1,'Health',NULL,NULL,NULL,NULL,'2025-12-15 03:28:54','2025-12-15 03:28:54');
/*!40000 ALTER TABLE `videos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_steps`
--

DROP TABLE IF EXISTS `workflow_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workflow_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phase` enum('writing','audio','video','publish','final') NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_steps`
--

LOCK TABLES `workflow_steps` WRITE;
/*!40000 ALTER TABLE `workflow_steps` DISABLE KEYS */;
INSERT INTO `workflow_steps` VALUES (1,'Script Draft','writing',1,'2025-12-15 03:28:54'),(2,'Script Final','writing',2,'2025-12-15 03:28:54'),(3,'Base Recording','audio',3,'2025-12-15 03:28:54'),(4,'Editing','audio',4,'2025-12-15 03:28:54'),(5,'PowerPoint Created','video',5,'2025-12-15 03:28:54'),(6,'PowerPoint Assembled','video',6,'2025-12-15 03:28:54'),(7,'Title Confirmed','publish',7,'2025-12-15 03:28:54'),(8,'Thumbnail Created','publish',8,'2025-12-15 03:28:54'),(9,'Description Created','publish',9,'2025-12-15 03:28:54'),(10,'IG Comments Created','publish',10,'2025-12-15 03:28:54'),(11,'Uploaded to YouTube','final',11,'2025-12-15 03:28:54'),(12,'Comments Pinned','final',12,'2025-12-15 03:28:54');
/*!40000 ALTER TABLE `workflow_steps` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-13 20:32:46
