-- MySQL dump 10.10
--
-- Host: localhost    Database: ampache
-- ------------------------------------------------------
-- Server version	5.0.18-Debian_7
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO,MYSQL323' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `access_list`
--

DROP TABLE IF EXISTS `access_list`;
CREATE TABLE `access_list` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL default '',
  `start` int(11) unsigned NOT NULL default '0',
  `end` int(11) unsigned NOT NULL default '0',
  `level` smallint(3) unsigned NOT NULL default '5',
  `user` varchar(128) default NULL,
  `key` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `ip` (`start`)
) TYPE=MyISAM;

--
-- Dumping data for table `access_list`
--


/*!40000 ALTER TABLE `access_list` DISABLE KEYS */;
LOCK TABLES `access_list` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `access_list` ENABLE KEYS */;

--
-- Table structure for table `album`
--

DROP TABLE IF EXISTS `album`;
CREATE TABLE `album` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL default '',
  `prefix` enum('The','An','A') default NULL,
  `year` int(4) unsigned NOT NULL default '1984',
  `art` mediumblob,
  `art_mime` varchar(128) default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `id` (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `album`
--


/*!40000 ALTER TABLE `album` DISABLE KEYS */;
LOCK TABLES `album` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `album` ENABLE KEYS */;

--
-- Table structure for table `artist`
--

DROP TABLE IF EXISTS `artist`;
CREATE TABLE `artist` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL default '',
  `prefix` enum('The','An','A') default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `id` (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `artist`
--


/*!40000 ALTER TABLE `artist` DISABLE KEYS */;
LOCK TABLES `artist` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `artist` ENABLE KEYS */;

--
-- Table structure for table `catalog`
--

DROP TABLE IF EXISTS `catalog`;
CREATE TABLE `catalog` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(128) NOT NULL default '',
  `path` varchar(255) NOT NULL default '',
  `catalog_type` enum('local','remote') NOT NULL default 'local',
  `last_update` int(11) unsigned NOT NULL default '0',
  `last_add` int(11) unsigned NOT NULL default '0',
  `enabled` tinyint(1) unsigned NOT NULL default '1',
  `id3_set_command` varchar(255) NOT NULL default '/usr/bin/id3v2 -a "%a" -A "%A" -t "%t" -g %g -y %y -T %T -c "%c" "%filename"',
  `rename_pattern` varchar(255) NOT NULL default '%a - %T - %t.mp3',
  `sort_pattern` varchar(255) NOT NULL default '%C/%a/%A',
  `gather_types` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `enabled` (`enabled`)
) TYPE=MyISAM;

--
-- Dumping data for table `catalog`
--


/*!40000 ALTER TABLE `catalog` DISABLE KEYS */;
LOCK TABLES `catalog` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `catalog` ENABLE KEYS */;

--
-- Table structure for table `flagged`
--

DROP TABLE IF EXISTS `flagged`;
CREATE TABLE `flagged` (
  `id` int(11) unsigned NOT NULL,
  `object_id` int(11) unsigned NOT NULL default '0',
  `object_type` enum('artist','album','song') NOT NULL default 'song',
  `user` varchar(128) NOT NULL default '',
  `flag` enum('delete','retag','reencode','other') NOT NULL default 'other',
  `date` int(11) unsigned NOT NULL,
  `approved` tinyint(1) unsigned NOT NULL default '0',
  `comment` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `date` (`date`,`approved`)
) TYPE=MyISAM;

--
-- Dumping data for table `flagged`
--


/*!40000 ALTER TABLE `flagged` DISABLE KEYS */;
LOCK TABLES `flagged` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `flagged` ENABLE KEYS */;

--
-- Table structure for table `genre`
--

DROP TABLE IF EXISTS `genre`;
CREATE TABLE `genre` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `genre`
--


/*!40000 ALTER TABLE `genre` DISABLE KEYS */;
LOCK TABLES `genre` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `genre` ENABLE KEYS */;

--
-- Table structure for table `ip_history`
--

DROP TABLE IF EXISTS `ip_history`;
CREATE TABLE `ip_history` (
  `username` varchar(128) default NULL,
  `ip` int(11) unsigned NOT NULL default '0',
  `connections` int(11) unsigned NOT NULL default '1',
  `date` int(11) unsigned NOT NULL default '0',
  KEY `username` (`username`),
  KEY `date` (`date`)
) TYPE=MyISAM;

--
-- Dumping data for table `ip_history`
--


/*!40000 ALTER TABLE `ip_history` DISABLE KEYS */;
LOCK TABLES `ip_history` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `ip_history` ENABLE KEYS */;

--
-- Table structure for table `now_playing`
--

DROP TABLE IF EXISTS `now_playing`;
CREATE TABLE `now_playing` (
  `id` int(11) unsigned NOT NULL,
  `song_id` int(11) unsigned NOT NULL default '0',
  `user` varchar(128) default NULL,
  `start_time` int(11) unsigned NOT NULL default '0',
  `session` varchar(64) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `now_playing`
--


/*!40000 ALTER TABLE `now_playing` DISABLE KEYS */;
LOCK TABLES `now_playing` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `now_playing` ENABLE KEYS */;

--
-- Table structure for table `object_count`
--

DROP TABLE IF EXISTS `object_count`;
CREATE TABLE `object_count` (
  `id` int(11) unsigned NOT NULL,
  `object_type` enum('album','artist','song','playlist','genre','catalog') NOT NULL default 'song',
  `object_id` int(11) unsigned NOT NULL default '0',
  `date` int(11) unsigned NOT NULL default '0',
  `count` int(11) unsigned NOT NULL default '0',
  `userid` varchar(128) default NULL,
  PRIMARY KEY  (`id`),
  KEY `object_type` (`object_type`),
  KEY `object_id` (`object_id`),
  KEY `userid` (`userid`),
  KEY `date` (`date`)
) TYPE=MyISAM;

--
-- Dumping data for table `object_count`
--


/*!40000 ALTER TABLE `object_count` DISABLE KEYS */;
LOCK TABLES `object_count` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `object_count` ENABLE KEYS */;

--
-- Table structure for table `playlist`
--

DROP TABLE IF EXISTS `playlist`;
CREATE TABLE `playlist` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(128) NOT NULL default '',
  `user` varchar(128) NOT NULL default '',
  `type` enum('private','public') NOT NULL default 'private',
  `date` timestamp NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `id` (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `playlist`
--


/*!40000 ALTER TABLE `playlist` DISABLE KEYS */;
LOCK TABLES `playlist` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `playlist` ENABLE KEYS */;

--
-- Table structure for table `playlist_data`
--

DROP TABLE IF EXISTS `playlist_data`;
CREATE TABLE `playlist_data` (
  `id` int(11) unsigned NOT NULL,
  `playlist` int(11) unsigned NOT NULL default '0',
  `song` int(11) unsigned default NULL,
  `dyn_song` text,
  `track` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `playlist` (`playlist`)
) TYPE=MyISAM;

--
-- Dumping data for table `playlist_data`
--


/*!40000 ALTER TABLE `playlist_data` DISABLE KEYS */;
LOCK TABLES `playlist_data` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `playlist_data` ENABLE KEYS */;

--
-- Table structure for table `playlist_permission`
--

DROP TABLE IF EXISTS `playlist_permission`;
CREATE TABLE `playlist_permission` (
  `id` int(11) unsigned NOT NULL,
  `userid` varchar(128) NOT NULL default '',
  `playlist` int(11) unsigned NOT NULL default '0',
  `level` smallint(3) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`),
  KEY `playlist` (`playlist`)
) TYPE=MyISAM;

--
-- Dumping data for table `playlist_permission`
--


/*!40000 ALTER TABLE `playlist_permission` DISABLE KEYS */;
LOCK TABLES `playlist_permission` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `playlist_permission` ENABLE KEYS */;

--
-- Table structure for table `preferences`
--

DROP TABLE IF EXISTS `preferences`;
CREATE TABLE `preferences` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(128) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `level` int(11) unsigned NOT NULL default '100',
  `type` varchar(128) NOT NULL default '',
  `catagory` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `preferences`
--


/*!40000 ALTER TABLE `preferences` DISABLE KEYS */;
LOCK TABLES `preferences` WRITE;
INSERT INTO `preferences` VALUES (1,'download','0','Allow Downloads',100,'boolean','options'),(2,'upload','0','Allow Uploads',100,'boolean','options'),(3,'quarantine','1','Quarantine All Uploads',100,'boolean','options'),(4,'popular_threshold','10','Popular Threshold',25,'integer','interface'),(5,'font','Verdana, Helvetica, sans-serif','Interface Font',25,'string','theme'),(6,'bg_color1','#ffffff','Background Color 1',25,'string','theme'),(7,'bg_color2','#000000','Background Color 2',25,'string','theme'),(8,'base_color1','#bbbbbb','Base Color 1',25,'string','theme'),(9,'base_color2','#dddddd','Base Color 2',25,'string','theme'),(10,'font_color1','#222222','Font Color 1',25,'string','theme'),(11,'font_color2','#000000','Font Color 2',25,'string','theme'),(12,'font_color3','#ffffff','Font Color 3',25,'string','theme'),(13,'row_color1','#cccccc','Row Color 1',25,'string','theme'),(14,'row_color2','#bbbbbb','Row Color 2',25,'string','theme'),(15,'row_color3','#dddddd','Row Color 3',25,'string','theme'),(16,'error_color','#990033','Error Color',25,'string','theme'),(17,'font_size','10','Font Size',25,'integer','theme'),(18,'upload_dir','/tmp','Upload Directory',25,'string','options'),(19,'sample_rate','32','Downsample Bitrate',25,'string','streaming'),(22,'site_title','For The Love of Music','Website Title',100,'string','system'),(23,'lock_songs','0','Lock Songs',100,'boolean','system'),(24,'force_http_play','1','Forces Http play regardless of port',100,'boolean','system'),(25,'http_port','80','Non-Standard Http Port',100,'integer','system'),(26,'catalog_echo_count','100','Catalog Echo Interval',100,'integer','system'),(41,'localplay_controller','0','Localplay Type',100,'special','streaming'),(29,'play_type','stream','Type of Playback',25,'special','streaming'),(30,'direct_link','1','Allow Direct Links',100,'boolean','options'),(31,'lang','en_US','Language',100,'special','interface'),(32,'playlist_type','m3u','Playlist Type',100,'special','streaming'),(33,'theme_name','classic','Theme',0,'special','theme'),(34,'ellipse_threshold_album','27','Album Ellipse Threshold',0,'integer','interface'),(35,'ellipse_threshold_artist','27','Artist Ellipse Threshold',0,'integer','interface'),(36,'ellipse_threshold_title','27','Title Ellipse Threshold',0,'integer','interface'),(39,'quarantine_dir','/tmp','Quarantine Directory',100,'string','system'),(40,'localplay_level','0','Localplay Access Level',100,'special','streaming');
UNLOCK TABLES;
/*!40000 ALTER TABLE `preferences` ENABLE KEYS */;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
CREATE TABLE `ratings` (
  `id` int(11) unsigned NOT NULL,
  `user` varchar(128) NOT NULL default '',
  `object_type` enum('artist','album','song') NOT NULL default 'artist',
  `object_id` int(11) unsigned NOT NULL default '0',
  `user_rating` enum('00','0','1','2','3','4','5') NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `object_id` (`object_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `ratings`
--


/*!40000 ALTER TABLE `ratings` DISABLE KEYS */;
LOCK TABLES `ratings` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `ratings` ENABLE KEYS */;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
  `id` varchar(32) NOT NULL default '',
  `username` varchar(16) NOT NULL default '',
  `expire` int(11) unsigned NOT NULL default '0',
  `value` text NOT NULL,
  `ip` int(11) unsigned default NULL,
  `type` enum('sso','mysql','ldap','http') NOT NULL default 'mysql',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `session`
--


/*!40000 ALTER TABLE `session` DISABLE KEYS */;
LOCK TABLES `session` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `session` ENABLE KEYS */;

--
-- Table structure for table `song`
--

DROP TABLE IF EXISTS `song`;
CREATE TABLE `song` (
  `id` int(11) unsigned NOT NULL,
  `file` varchar(255) NOT NULL default '',
  `catalog` int(11) unsigned NOT NULL default '0',
  `album` int(11) unsigned NOT NULL default '0',
  `comment` text NOT NULL,
  `year` mediumint(4) unsigned NOT NULL default '0',
  `artist` int(11) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `bitrate` mediumint(2) NOT NULL default '0',
  `rate` mediumint(2) NOT NULL default '0',
  `mode` varchar(25) default NULL,
  `size` int(11) unsigned NOT NULL default '0',
  `time` mediumint(5) NOT NULL default '0',
  `track` int(11) unsigned default NULL,
  `genre` int(11) unsigned default NULL,
  `played` tinyint(1) unsigned NOT NULL default '0',
  `enabled` tinyint(1) unsigned NOT NULL default '1',
  `update_time` int(11) unsigned default '0',
  `addition_time` int(11) unsigned default '0',
  PRIMARY KEY  (`id`),
  KEY `genre` (`genre`),
  KEY `album` (`album`),
  KEY `artist` (`artist`),
  KEY `id` (`id`),
  KEY `file` (`file`),
  KEY `update_time` (`update_time`),
  KEY `addition_time` (`addition_time`),
  KEY `catalog` (`catalog`),
  KEY `played` (`played`),
  KEY `enabled` (`enabled`)
) TYPE=MyISAM;

--
-- Dumping data for table `song`
--


/*!40000 ALTER TABLE `song` DISABLE KEYS */;
LOCK TABLES `song` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `song` ENABLE KEYS */;

--
-- Table structure for table `update_info`
--

DROP TABLE IF EXISTS `update_info`;
CREATE TABLE `update_info` (
  `key` varchar(128) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  KEY `key` (`key`)
) TYPE=MyISAM;

--
-- Dumping data for table `update_info`
--


/*!40000 ALTER TABLE `update_info` DISABLE KEYS */;
LOCK TABLES `update_info` WRITE;
INSERT INTO `update_info` VALUES ('db_version','332010');
UNLOCK TABLES;
/*!40000 ALTER TABLE `update_info` ENABLE KEYS */;

--
-- Table structure for table `upload`
--

DROP TABLE IF EXISTS `upload`;
CREATE TABLE `upload` (
  `id` int(11) unsigned NOT NULL,
  `user` varchar(128) NOT NULL default '',
  `file` varchar(255) NOT NULL default '',
  `action` enum('add','delete','quarantine') NOT NULL default 'add',
  `addition_time` int(11) unsigned default '0',
  PRIMARY KEY  (`id`),
  KEY `action` (`action`),
  KEY `user` (`user`)
) TYPE=MyISAM;

--
-- Dumping data for table `upload`
--


/*!40000 ALTER TABLE `upload` DISABLE KEYS */;
LOCK TABLES `upload` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `upload` ENABLE KEYS */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL,
  `username` varchar(128) NOT NULL default '',
  `fullname` varchar(128) NOT NULL default '',
  `email` varchar(128) default NULL,
  `password` varchar(64) NOT NULL default '',
  `access` varchar(64) NOT NULL default '',
  `disabled` tinyint(1) NOT NULL default '0',
  `offset_limit` int(5) unsigned NOT NULL default '50',
  `last_seen` int(11) unsigned NOT NULL default '0',
  `create_date` int(11) unsigned default NULL,
  `validation` varchar(128) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) TYPE=MyISAM;

--
-- Dumping data for table `user`
--


/*!40000 ALTER TABLE `user` DISABLE KEYS */;
LOCK TABLES `user` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;

--
-- Table structure for table `user_catalog`
--

DROP TABLE IF EXISTS `user_catalog`;
CREATE TABLE `user_catalog` (
  `user` int(11) unsigned NOT NULL default '0',
  `catalog` int(11) unsigned NOT NULL default '0',
  `level` smallint(3) NOT NULL default '25'
) TYPE=MyISAM;

--
-- Dumping data for table `user_catalog`
--


/*!40000 ALTER TABLE `user_catalog` DISABLE KEYS */;
LOCK TABLES `user_catalog` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `user_catalog` ENABLE KEYS */;

--
-- Table structure for table `user_preference`
--

DROP TABLE IF EXISTS `user_preference`;
CREATE TABLE `user_preference` (
  `user` varchar(128) NOT NULL default '',
  `preference` int(11) unsigned NOT NULL default '0',
  `value` varchar(255) NOT NULL default '',
  KEY `user` (`user`),
  KEY `preference` (`preference`),
  KEY `user_2` (`user`),
  KEY `preference_2` (`preference`)
) TYPE=MyISAM;

--
-- Dumping data for table `user_preference`
--


/*!40000 ALTER TABLE `user_preference` DISABLE KEYS */;
LOCK TABLES `user_preference` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `user_preference` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

