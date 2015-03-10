CREATE DATABASE  IF NOT EXISTS `mjs` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_bin */;
USE `mjs`;
-- MySQL dump 10.13  Distrib 5.6.17, for osx10.6 (i386)
--
-- Host: localhost    Database: mjs
-- ------------------------------------------------------
-- Server version	5.6.20

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `acctstatus`
--

DROP TABLE IF EXISTS `acctstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acctstatus` (
  `inx` int(11) NOT NULL,
  `desc` varchar(255) NOT NULL,
  PRIMARY KEY (`inx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acctstatus`
--

LOCK TABLES `acctstatus` WRITE;
/*!40000 ALTER TABLE `acctstatus` DISABLE KEYS */;
INSERT INTO `acctstatus` VALUES (0,'suspend'),(1,'active');
/*!40000 ALTER TABLE `acctstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calls`
--

DROP TABLE IF EXISTS `calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calls` (
  `inx` int(11) NOT NULL,
  `party1Inx` int(11) NOT NULL COMMENT 'Inx to the record of the 1st party called, this would be the instructor for tutoring session ',
  `party1CallTime` datetime DEFAULT NULL,
  `party1CallRes` int(11) NOT NULL COMMENT 'Inx to call states',
  `party2Inx` int(11) NOT NULL COMMENT 'Inx to the record of the 2nd party called, this would be the student for tutoring session',
  `party2CallTime` datetime DEFAULT NULL,
  `party2CallRes` int(11) DEFAULT NULL COMMENT 'Inx to call states',
  `party3Inx` int(11) DEFAULT NULL COMMENT 'Inx to the record of the 3rd party called, this would be the translator for tutoring session',
  `party3CallTime` datetime DEFAULT NULL,
  `party3CallRes` int(11) DEFAULT NULL,
  `grpCallStartTime` datetime DEFAULT NULL COMMENT 'Start date time when all parties are connected on call',
  `grpCallEndTime` datetime DEFAULT NULL COMMENT 'End date time when ANY of the 3  parties hang up the call',
  `party1SessionId` varchar(32) DEFAULT NULL,
  `party2SessionId` varchar(32) DEFAULT NULL,
  `party3SessionId` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`inx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calls`
--

LOCK TABLES `calls` WRITE;
/*!40000 ALTER TABLE `calls` DISABLE KEYS */;
/*!40000 ALTER TABLE `calls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `callstates`
--

DROP TABLE IF EXISTS `callstates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `callstates` (
  `inx` int(11) NOT NULL,
  `desc` varchar(255) NOT NULL,
  PRIMARY KEY (`inx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `callstates`
--

LOCK TABLES `callstates` WRITE;
/*!40000 ALTER TABLE `callstates` DISABLE KEYS */;
INSERT INTO `callstates` VALUES (0,'answered'),(1,'notAnswer');
/*!40000 ALTER TABLE `callstates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configue_default`
--

DROP TABLE IF EXISTS `configue_default`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configue_default` (
  `d_key` varchar(45) COLLATE utf8_bin NOT NULL,
  `d_value` varchar(45) COLLATE utf8_bin DEFAULT NULL COMMENT 'tmm  total monthly minutes',
  PRIMARY KEY (`d_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configue_default`
--

LOCK TABLES `configue_default` WRITE;
/*!40000 ALTER TABLE `configue_default` DISABLE KEYS */;
INSERT INTO `configue_default` VALUES ('tmm','120');
/*!40000 ALTER TABLE `configue_default` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `instructors`
--

DROP TABLE IF EXISTS `instructors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instructors` (
  `inx` int(11) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `email` varchar(512) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `acctCreateDate` datetime NOT NULL COMMENT 'Date time this account was created',
  PRIMARY KEY (`inx`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instructors`
--

LOCK TABLES `instructors` WRITE;
/*!40000 ALTER TABLE `instructors` DISABLE KEYS */;
INSERT INTO `instructors` VALUES (18,'11','11','11@qq','+12176507163','2015-02-13 22:43:44');
/*!40000 ALTER TABLE `instructors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `inx` int(11) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(255) CHARACTER SET utf8 NOT NULL,
  `lastName` varchar(255) CHARACTER SET utf8 NOT NULL,
  `email` varchar(512) CHARACTER SET utf8 NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8 NOT NULL,
  `acctCreateDate` datetime NOT NULL COMMENT 'Date time this account was created',
  `acctStatus` int(11) NOT NULL,
  `membershipStartDate` datetime DEFAULT '0000-00-00 00:00:00' COMMENT 'Start date of when student starts paying for tutoring sessions',
  `membershipDur` int(11) DEFAULT NULL COMMENT 'Total # of months the student has pay for tutoring',
  `totalMonthlyMins` int(11) DEFAULT NULL COMMENT 'Total number of tutoring mins allotted to student for each month',
  `minsRemaining` int(11) DEFAULT NULL COMMENT 'Total # of tutoring mins remaining in student''s account',
  PRIMARY KEY (`inx`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'weiming','xu','1274263@qq.com','+18986245088','2014-10-10 00:00:00',1,'2015-02-04 00:00:00',3,11,45),(7,'必須で','必須で','qq@qq','+12176507163','2015-02-23 21:04:50',1,'2015-02-24 00:00:00',1,120,120);
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translators`
--

DROP TABLE IF EXISTS `translators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translators` (
  `inx` int(11) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `email` varchar(512) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `acctCreateDate` datetime NOT NULL COMMENT 'Date time this account was created',
  PRIMARY KEY (`inx`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translators`
--

LOCK TABLES `translators` WRITE;
/*!40000 ALTER TABLE `translators` DISABLE KEYS */;
INSERT INTO `translators` VALUES (1,'qq','qq','qq@qq','123','2015-01-27 19:07:03');
/*!40000 ALTER TABLE `translators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tutorsessions`
--

DROP TABLE IF EXISTS `tutorsessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tutorsessions` (
  `inx` int(11) NOT NULL AUTO_INCREMENT,
  `studentInx` int(11) NOT NULL,
  `instructorInx` int(11) NOT NULL,
  `translatorInx` int(11) DEFAULT NULL,
  `scheduleStartTime` datetime NOT NULL,
  `scheduleEndTime` datetime NOT NULL,
  `actualEndTime` datetime DEFAULT NULL,
  `timezone` varchar(255) NOT NULL,
  `iscancelled` int(11) DEFAULT '0' COMMENT '0 not cancel 1 cancel',
  PRIMARY KEY (`inx`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tutorsessions`
--

LOCK TABLES `tutorsessions` WRITE;
/*!40000 ALTER TABLE `tutorsessions` DISABLE KEYS */;
INSERT INTO `tutorsessions` VALUES (50,1,18,NULL,'2015-02-17 02:00:00','2015-02-17 02:11:00',NULL,'PRC',0),(52,1,18,NULL,'2015-02-18 21:15:00','2015-02-18 21:26:00',NULL,'PRC',1),(54,1,18,NULL,'2015-02-19 15:30:00','2015-02-19 15:41:00',NULL,'PRC',0),(55,1,18,NULL,'2015-02-27 12:15:00','2015-02-27 12:26:00',NULL,'PRC',0);
/*!40000 ALTER TABLE `tutorsessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `inx` int(11) NOT NULL,
  `password` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  `username` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`inx`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'123','ysago@unisrv.jp');
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

-- Dump completed on 2015-03-01 15:25:37
