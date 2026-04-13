-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: bizinote_db
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
-- Table structure for table `app_analytics`
--

DROP TABLE IF EXISTS `app_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_analytics`
--

LOCK TABLES `app_analytics` WRITE;
/*!40000 ALTER TABLE `app_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=343 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_settings`
--

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
INSERT INTO `app_settings` VALUES (70,'support_email','admin@biswamart.com','2026-02-02 06:26:58'),(71,'support_phone','+91 8080808080','2026-02-03 04:50:42'),(72,'app_version','1.0.0','2026-02-02 06:36:07');
/*!40000 ALTER TABLE `app_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `backup_name` varchar(255) NOT NULL,
  `backup_data` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups`
--

LOCK TABLES `backups` WRITE;
/*!40000 ALTER TABLE `backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bills`
--

DROP TABLE IF EXISTS `bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_mobile` varchar(20) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items`)),
  `subtotal` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT NULL,
  `grand_total` decimal(10,2) DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bills`
--

LOCK TABLES `bills` WRITE;
/*!40000 ALTER TABLE `bills` DISABLE KEYS */;
INSERT INTO `bills` VALUES (25,4,'03022026001',5,'Cutie ','1234567890','ffprince761@gmail.comf','Admin 678','[{\"productId\":7,\"name\":\"Samsung\",\"price\":\"19000.00\",\"quantity\":10,\"unit\":\"Nos\",\"stock\":50}]',190000.00,90000.00,0.00,100000.00,100000.00,'Card','2026-02-03','2026-02-03 05:53:53','2026-02-03 05:53:53'),(26,4,'05022026002',5,'Cutie ','1234567890','ffprince761@gmail.comf','Admin 678','[{\"productId\":\"manual_1770268440664\",\"name\":\"Potato \",\"price\":50,\"quantity\":10,\"unit\":\"Kg\",\"stock\":999,\"isManual\":true}]',500.00,0.00,0.00,500.00,500.00,'UPI','2026-02-05','2026-02-05 05:14:08','2026-02-05 05:14:08');
/*!40000 ALTER TABLE `bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocked_ips`
--

DROP TABLE IF EXISTS `blocked_ips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `blocked_until` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `idx_ip_blocked` (`ip_address`,`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocked_ips`
--

LOCK TABLES `blocked_ips` WRITE;
/*!40000 ALTER TABLE `blocked_ips` DISABLE KEYS */;
/*!40000 ALTER TABLE `blocked_ips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (5,4,'Cutie ','1234567890','ffprince761@gmail.comf','Admin 678','2026-02-03 05:52:35','2026-02-03 05:52:35');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_otps`
--

DROP TABLE IF EXISTS `email_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `purpose` enum('registration','password_reset') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_otp` (`otp`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_otps`
--

LOCK TABLES `email_otps` WRITE;
/*!40000 ALTER TABLE `email_otps` DISABLE KEYS */;
INSERT INTO `email_otps` VALUES (1,'ffprince761@gmail.comf','222261','password_reset','2026-02-06 05:02:17','2026-02-06 06:12:17',0),(3,'yourmadman03@gmail.com','397636','registration','2026-02-06 05:08:58','2026-02-06 06:18:58',1),(5,'yourmadman03@gmail.com','407764','password_reset','2026-02-06 05:12:10','2026-02-06 06:22:10',1);
/*!40000 ALTER TABLE `email_otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (5,4,'Rent','Every month ',10000.00,'2026-02-03','2026-02-03 05:55:00','2026-02-03 05:55:00');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_login_attempts`
--

DROP TABLE IF EXISTS `failed_login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_time` (`email`,`attempt_time`),
  KEY `idx_ip_time` (`ip_address`,`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_login_attempts`
--

LOCK TABLES `failed_login_attempts` WRITE;
/*!40000 ALTER TABLE `failed_login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `owner_activity_logs`
--

DROP TABLE IF EXISTS `owner_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `owner_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `owner_activity_logs_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `owner_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `owner_activity_logs`
--

LOCK TABLES `owner_activity_logs` WRITE;
/*!40000 ALTER TABLE `owner_activity_logs` DISABLE KEYS */;
INSERT INTO `owner_activity_logs` VALUES (1,1,'block_user','Blocked user ID: 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 05:12:26'),(2,1,'unblock_user','Unblocked user ID: 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 05:12:36'),(3,1,'delete_user','Deleted user: Test User (test@bizinote.com)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 05:12:47'),(4,1,'delete_user','Deleted user: Admin  (ffprince761@gmail.com)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 05:12:57'),(5,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:02:44'),(6,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:05:52'),(7,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:10:29'),(8,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:10:32'),(9,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:15:53'),(10,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:16:28'),(11,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:19:28'),(12,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:22:57'),(13,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:27:32'),(14,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:36:07'),(15,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:36:52'),(16,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-02 06:41:37'),(17,1,'login','Successful login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 04:47:45'),(18,1,'update_account','Updated account details','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 04:48:33'),(19,1,'change_password','Changed password','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 04:48:59'),(20,1,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 04:49:02'),(21,1,'login','Successful login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 04:49:38'),(22,1,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 04:49:41'),(23,1,'login','Successful login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 04:50:08'),(24,1,'update_settings','Updated app settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 04:50:42'),(25,1,'block_user','Blocked user ID: 3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 04:52:07'),(26,1,'unblock_user','Unblocked user ID: 3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 05:09:06'),(27,1,'block_user','Blocked user ID: 3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 05:09:12'),(28,1,'unblock_user','Unblocked user ID: 3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 05:19:24'),(29,1,'block_user','Blocked user ID: 3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 05:19:32'),(30,1,'unblock_user','Unblocked user ID: 3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 05:37:39'),(31,1,'delete_user','Deleted user: Bizinote@gmail.comb (admin@gmail.com)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 05:51:31'),(32,1,'block_user','Blocked user ID: 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 05:55:50'),(33,1,'unblock_user','Unblocked user ID: 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 05:56:17'),(34,1,'block_user','Blocked user ID: 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 06:05:15'),(35,1,'unblock_user','Unblocked user ID: 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 06:05:33'),(36,1,'block_user','Blocked user ID: 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 06:05:58'),(37,1,'unblock_user','Unblocked user ID: 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 06:06:33'),(38,1,'block_user','Blocked user ID: 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 06:07:57'),(39,1,'unblock_user','Unblocked user ID: 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 06:33:11'),(40,1,'block_user','Blocked user ID: 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 06:38:34'),(41,1,'unblock_user','Unblocked user ID: 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-03 06:38:59'),(42,1,'login','Successful login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-05 05:07:48'),(43,1,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-05 05:09:01'),(44,1,'login','Successful login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-05 05:10:18'),(45,1,'login','Successful login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-06 04:43:37'),(46,1,'block_user','Blocked user ID: 5','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-06 04:59:46'),(47,1,'delete_user','Deleted user: Prince  (yourmadman03@gmail.com)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-06 05:01:15'),(48,1,'delete_user','Deleted user: Prince  (yourmadman03@gmail.com)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0','2026-02-06 05:07:29');
/*!40000 ALTER TABLE `owner_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `owner_daily_activity`
--

DROP TABLE IF EXISTS `owner_daily_activity`;
/*!50001 DROP VIEW IF EXISTS `owner_daily_activity`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `owner_daily_activity` AS SELECT
 1 AS `activity_date`,
  1 AS `active_users`,
  1 AS `total_actions` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `owner_revenue_summary`
--

DROP TABLE IF EXISTS `owner_revenue_summary`;
/*!50001 DROP VIEW IF EXISTS `owner_revenue_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `owner_revenue_summary` AS SELECT
 1 AS `revenue_date`,
  1 AS `bill_count`,
  1 AS `total_revenue`,
  1 AS `avg_bill_value`,
  1 AS `active_businesses` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `owner_user_stats`
--

DROP TABLE IF EXISTS `owner_user_stats`;
/*!50001 DROP VIEW IF EXISTS `owner_user_stats`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `owner_user_stats` AS SELECT
 1 AS `id`,
  1 AS `name`,
  1 AS `email`,
  1 AS `business_name`,
  1 AS `registered_at`,
  1 AS `last_active`,
  1 AS `product_count`,
  1 AS `customer_count`,
  1 AS `bill_count`,
  1 AS `total_revenue`,
  1 AS `expense_count`,
  1 AS `total_expenses` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `owner_users`
--

DROP TABLE IF EXISTS `owner_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `owner_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `owner_users`
--

LOCK TABLES `owner_users` WRITE;
/*!40000 ALTER TABLE `owner_users` DISABLE KEYS */;
INSERT INTO `owner_users` VALUES (1,'Madman','owner@bizinote.com','$2y$10$gD.55U45CuKOjFxU.zCGv.OJ3xdqCo8IOjNdQTleTPZTZC9pDz5He','Bizinote Owner',1,'2026-02-06 04:43:37','2026-02-02 04:17:38','2026-02-06 04:43:37');
/*!40000 ALTER TABLE `owner_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `unit` varchar(20) DEFAULT 'Nos',
  `low_stock_threshold` int(11) DEFAULT 10,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (7,4,'Samsung','Mobile Accessories',19000.00,40,'Nos',10,'A17','2026-02-03 05:53:07','2026-02-03 05:53:53');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_logs`
--

DROP TABLE IF EXISTS `security_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip_address`,`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_logs`
--

LOCK TABLES `security_logs` WRITE;
/*!40000 ALTER TABLE `security_logs` DISABLE KEYS */;
INSERT INTO `security_logs` VALUES (25,'10.209.43.59','login',0,NULL,'okhttp/4.12.0','2026-02-05 06:05:55'),(26,'10.209.43.59','login',0,NULL,'okhttp/4.12.0','2026-02-05 06:05:59'),(27,'10.209.43.59','login',0,NULL,'okhttp/4.12.0','2026-02-06 04:57:22'),(28,'10.209.43.59','login',0,NULL,'okhttp/4.12.0','2026-02-06 04:57:23'),(29,'10.209.43.59','login_blocked_user',0,NULL,'okhttp/4.12.0','2026-02-06 05:00:33'),(30,'10.209.43.59','login',1,4,'okhttp/4.12.0','2026-02-06 05:02:44'),(31,'10.209.43.59','login',1,6,'okhttp/4.12.0','2026-02-06 05:07:18'),(32,'10.209.43.59','login',1,4,'okhttp/4.12.0','2026-02-06 05:13:37');
/*!40000 ALTER TABLE `security_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `date` date NOT NULL,
  `note` varchar(255) DEFAULT 'Stock Added',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustments`
--

LOCK TABLES `stock_adjustments` WRITE;
/*!40000 ALTER TABLE `stock_adjustments` DISABLE KEYS */;
INSERT INTO `stock_adjustments` VALUES (1,4,3,10,'2026-01-31','Stock Added','2026-01-31 13:37:44'),(2,4,3,10,'2026-01-31','Stock Added','2026-01-31 13:39:09'),(3,4,3,10,'2026-01-31','Stock Added','2026-01-31 13:44:41'),(4,4,3,20,'2026-01-31','Stock Added','2026-01-31 13:50:16'),(5,4,3,20,'2026-01-31','Stock Added','2026-01-31 13:58:58');
/*!40000 ALTER TABLE `stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_blocked` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (4,'Bizinote ','ffprince761@gmail.comf','$2y$10$EyzADNSYJkxg3bANv864be1iNG2C01Qj3s1/IiYcp.H1B2HUc21Dm','Bizinote ','7608081767','','2026-02-03 05:52:02','2026-02-03 06:38:59',0),(7,'Prince ','yourmadman03@gmail.com','$2y$10$KPfWcUHoqdk6dqQFD7MAuemGC/qRpAaFF9QVoRsdmhrCVZ89ggdUi','Prince store ','6369969797','','2026-02-06 05:09:23','2026-02-06 05:09:23',0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'bizinote_db'
--

--
-- Dumping routines for database 'bizinote_db'
--

--
-- Final view structure for view `owner_daily_activity`
--

/*!50001 DROP VIEW IF EXISTS `owner_daily_activity`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `owner_daily_activity` AS select cast(`all_activity`.`created_at` as date) AS `activity_date`,count(distinct `all_activity`.`user_id`) AS `active_users`,count(0) AS `total_actions` from (select `bills`.`user_id` AS `user_id`,`bills`.`created_at` AS `created_at` from `bills` union all select `products`.`user_id` AS `user_id`,`products`.`created_at` AS `created_at` from `products` union all select `customers`.`user_id` AS `user_id`,`customers`.`created_at` AS `created_at` from `customers` union all select `expenses`.`user_id` AS `user_id`,`expenses`.`created_at` AS `created_at` from `expenses`) `all_activity` group by cast(`all_activity`.`created_at` as date) order by cast(`all_activity`.`created_at` as date) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `owner_revenue_summary`
--

/*!50001 DROP VIEW IF EXISTS `owner_revenue_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `owner_revenue_summary` AS select cast(`bills`.`date` as date) AS `revenue_date`,count(0) AS `bill_count`,sum(`bills`.`grand_total`) AS `total_revenue`,avg(`bills`.`grand_total`) AS `avg_bill_value`,count(distinct `bills`.`user_id`) AS `active_businesses` from `bills` group by cast(`bills`.`date` as date) order by cast(`bills`.`date` as date) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `owner_user_stats`
--

/*!50001 DROP VIEW IF EXISTS `owner_user_stats`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013  SQL SECURITY DEFINER */
/*!50001 VIEW `owner_user_stats` AS select `u`.`id` AS `id`,`u`.`name` AS `name`,`u`.`email` AS `email`,`u`.`business_name` AS `business_name`,`u`.`created_at` AS `registered_at`,`u`.`updated_at` AS `last_active`,count(distinct `p`.`id`) AS `product_count`,count(distinct `c`.`id`) AS `customer_count`,count(distinct `b`.`id`) AS `bill_count`,coalesce(sum(`b`.`grand_total`),0) AS `total_revenue`,count(distinct `e`.`id`) AS `expense_count`,coalesce(sum(`e`.`amount`),0) AS `total_expenses` from ((((`users` `u` left join `products` `p` on(`u`.`id` = `p`.`user_id`)) left join `customers` `c` on(`u`.`id` = `c`.`user_id`)) left join `bills` `b` on(`u`.`id` = `b`.`user_id`)) left join `expenses` `e` on(`u`.`id` = `e`.`user_id`)) group by `u`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-06 12:15:01
