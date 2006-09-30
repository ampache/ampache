-- MySQL dump 10.9
--
-- Host: localhost    Database: ampache
-- ------------------------------------------------------
-- Server version	4.1.21-log
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO,MYSQL323' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `access_list`
--

DROP TABLE IF EXISTS `access_list`;
CREATE TABLE `access_list` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `start` int(11) unsigned NOT NULL default '0',
  `end` int(11) unsigned NOT NULL default '0',
  `level` smallint(3) unsigned NOT NULL default '5',
  `type` varchar(64) NOT NULL default '',
  `user` varchar(128) default NULL,
  `key` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `start` (`start`),
  KEY `end` (`end`),
  KEY `level` (`level`)
) TYPE=MyISAM;

--
-- Dumping data for table `access_list`
--

LOCK TABLES `access_list` WRITE;
/*!40000 ALTER TABLE `access_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `access_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `album`
--

DROP TABLE IF EXISTS `album`;
CREATE TABLE `album` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `prefix` enum('The','An','A') default NULL,
  `year` int(4) unsigned NOT NULL default '1984',
  `art` mediumblob,
  `art_mime` varchar(128) default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `year` (`year`)
) TYPE=MyISAM;

--
-- Table structure for table `artist`
--

DROP TABLE IF EXISTS `artist`;
CREATE TABLE `artist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `prefix` enum('The','An','A') default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM;

--
-- Table structure for table `catalog`
--

DROP TABLE IF EXISTS `catalog`;
CREATE TABLE `catalog` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `path` varchar(255) NOT NULL default '',
  `catalog_type` enum('local','remote') NOT NULL default 'local',
  `last_update` int(11) unsigned NOT NULL default '0',
  `last_add` int(11) unsigned NOT NULL default '0',
  `enabled` tinyint(1) unsigned NOT NULL default '1',
  `rename_pattern` varchar(255) NOT NULL default '%a - %T - %t.mp3',
  `sort_pattern` varchar(255) NOT NULL default '%C/%a/%A',
  `gather_types` varchar(255) NOT NULL default '',
  `key` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `enabled` (`enabled`)
) TYPE=MyISAM;

--
-- Table structure for table `flagged`
--

DROP TABLE IF EXISTS `flagged`;
CREATE TABLE `flagged` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `object_id` int(11) unsigned NOT NULL default '0',
  `object_type` enum('artist','album','song') NOT NULL default 'song',
  `user` varchar(128) NOT NULL default '',
  `flag` enum('delete','retag','reencode','other') NOT NULL default 'other',
  `date` int(11) unsigned NOT NULL default '0',
  `approved` tinyint(1) unsigned NOT NULL default '0',
  `comment` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `date` (`date`,`approved`),
  KEY `object_id` (`object_id`),
  KEY `object_type` (`object_type`),
  KEY `user` (`user`)
) TYPE=MyISAM;

--
-- Table structure for table `genre`
--

DROP TABLE IF EXISTS `genre`;
CREATE TABLE `genre` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM;

--
-- Table structure for table `ip_history`
--

DROP TABLE IF EXISTS `ip_history`;
CREATE TABLE `ip_history` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` varchar(128) default NULL,
  `ip` int(11) unsigned NOT NULL default '0',
  `date` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `username` (`user`),
  KEY `date` (`date`),
  KEY `ip` (`ip`)
) TYPE=MyISAM;

--
-- Table structure for table `live_stream`
--

DROP TABLE IF EXISTS `live_stream`;
CREATE TABLE `live_stream` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `site_url` varchar(255) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `genre` int(11) unsigned NOT NULL default '0',
  `catalog` int(11) unsigned NOT NULL default '0',
  `frequency` varchar(32) NOT NULL default '',
  `call_sign` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `catalog` (`catalog`),
  KEY `genre` (`genre`),
  KEY `name` (`name`)
) TYPE=MyISAM;

--
-- Dumping data for table `live_stream`
--

LOCK TABLES `live_stream` WRITE;
/*!40000 ALTER TABLE `live_stream` DISABLE KEYS */;
/*!40000 ALTER TABLE `live_stream` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `now_playing`
--

DROP TABLE IF EXISTS `now_playing`;
CREATE TABLE `now_playing` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `song_id` int(11) unsigned NOT NULL default '0',
  `user` varchar(128) default NULL,
  `start_time` int(11) unsigned NOT NULL default '0',
  `session` varchar(64) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `now_playing`
--

LOCK TABLES `now_playing` WRITE;
/*!40000 ALTER TABLE `now_playing` DISABLE KEYS */;
/*!40000 ALTER TABLE `now_playing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `object_count`
--

DROP TABLE IF EXISTS `object_count`;
CREATE TABLE `object_count` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video') NOT NULL default 'song',
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

LOCK TABLES `object_count` WRITE;
/*!40000 ALTER TABLE `object_count` DISABLE KEYS */;
/*!40000 ALTER TABLE `object_count` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `playlist`
--

DROP TABLE IF EXISTS `playlist`;
CREATE TABLE `playlist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `user` varchar(128) NOT NULL default '',
  `type` enum('private','public') NOT NULL default 'private',
  `date` timestamp NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `type` (`type`)
) TYPE=MyISAM;

--
-- Dumping data for table `playlist`
--

LOCK TABLES `playlist` WRITE;
/*!40000 ALTER TABLE `playlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `playlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `playlist_data`
--

DROP TABLE IF EXISTS `playlist_data`;
CREATE TABLE `playlist_data` (
  `id` int(11) unsigned NOT NULL auto_increment,
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

LOCK TABLES `playlist_data` WRITE;
/*!40000 ALTER TABLE `playlist_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `playlist_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `playlist_permission`
--

DROP TABLE IF EXISTS `playlist_permission`;
CREATE TABLE `playlist_permission` (
  `id` int(11) unsigned NOT NULL auto_increment,
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

LOCK TABLES `playlist_permission` WRITE;
/*!40000 ALTER TABLE `playlist_permission` DISABLE KEYS */;
/*!40000 ALTER TABLE `playlist_permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preferences`
--

DROP TABLE IF EXISTS `preferences`;
CREATE TABLE `preferences` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `level` int(11) unsigned NOT NULL default '100',
  `type` varchar(128) NOT NULL default '',
  `catagory` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `catagory` (`catagory`),
  KEY `name` (`name`)
) TYPE=MyISAM AUTO_INCREMENT=43;

--
-- Dumping data for table `preferences`
--

LOCK TABLES `preferences` WRITE;
/*!40000 ALTER TABLE `preferences` DISABLE KEYS */;
INSERT INTO `preferences` VALUES (1,'download','0','Allow Downloads',100,'boolean','options'),(2,'upload','0','Allow Uploads',100,'boolean','options'),(3,'quarantine','1','Quarantine All Uploads',100,'boolean','options'),(4,'popular_threshold','10','Popular Threshold',25,'integer','interface'),(18,'upload_dir','','Upload Directory',25,'string','options'),(19,'sample_rate','32','Downsample Bitrate',25,'string','streaming'),(22,'site_title','Ampache :: Pour l\'Amour de la Musique','Website Title',100,'string','system'),(23,'lock_songs','0','Lock Songs',100,'boolean','system'),(24,'force_http_play','1','Forces Http play regardless of port',100,'boolean','system'),(25,'http_port','80','Non-Standard Http Port',100,'integer','system'),(26,'catalog_echo_count','100','Catalog Echo Interval',100,'integer','system'),(41,'localplay_controller','0','Localplay Type',100,'special','streaming'),(29,'play_type','stream','Type of Playback',25,'special','streaming'),(30,'direct_link','1','Allow Direct Links',100,'boolean','options'),(31,'lang','en_US','Language',100,'special','interface'),(32,'playlist_type','m3u','Playlist Type',100,'special','streaming'),(33,'theme_name','classic','Theme',0,'special','interface'),(34,'ellipse_threshold_album','27','Album Ellipse Threshold',0,'integer','interface'),(35,'ellipse_threshold_artist','27','Artist Ellipse Threshold',0,'integer','interface'),(36,'ellipse_threshold_title','27','Title Ellipse Threshold',0,'integer','interface'),(39,'quarantine_dir','','Quarantine Directory',100,'string','system'),(42,'min_album_size','0','Min Album Size',0,'integer','interface'),(40,'localplay_level','0','Localplay Access Level',100,'special','streaming');
/*!40000 ALTER TABLE `preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
CREATE TABLE `ratings` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` varchar(128) NOT NULL default '',
  `object_type` enum('artist','album','song','steam','video') NOT NULL default 'artist',
  `object_id` int(11) unsigned NOT NULL default '0',
  `user_rating` enum('00','0','1','2','3','4','5') NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `object_id` (`object_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `ratings`
--

LOCK TABLES `ratings` WRITE;
/*!40000 ALTER TABLE `ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `ratings` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY  (`id`),
  KEY `expire` (`expire`)
) TYPE=MyISAM;

--
-- Table structure for table `song`
--

DROP TABLE IF EXISTS `song`;
CREATE TABLE `song` (
  `id` int(11) unsigned NOT NULL auto_increment,
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
  KEY `file` (`file`),
  KEY `update_time` (`update_time`),
  KEY `addition_time` (`addition_time`),
  KEY `catalog` (`catalog`),
  KEY `played` (`played`),
  KEY `enabled` (`enabled`)
) TYPE=MyISAM;

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

LOCK TABLES `update_info` WRITE;
/*!40000 ALTER TABLE `update_info` DISABLE KEYS */;
INSERT INTO `update_info` VALUES ('db_version','332013');
/*!40000 ALTER TABLE `update_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `upload`
--

DROP TABLE IF EXISTS `upload`;
CREATE TABLE `upload` (
  `id` int(11) unsigned NOT NULL auto_increment,
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

LOCK TABLES `upload` WRITE;
/*!40000 ALTER TABLE `upload` DISABLE KEYS */;
/*!40000 ALTER TABLE `upload` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL auto_increment,
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

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_catalog`
--

DROP TABLE IF EXISTS `user_catalog`;
CREATE TABLE `user_catalog` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) unsigned NOT NULL default '0',
  `catalog` int(11) unsigned NOT NULL default '0',
  `level` smallint(3) NOT NULL default '25',
  PRIMARY KEY  (`id`),
  KEY `user` (`user`),
  KEY `catalog` (`catalog`)
) TYPE=MyISAM;

--
-- Dumping data for table `user_catalog`
--

LOCK TABLES `user_catalog` WRITE;
/*!40000 ALTER TABLE `user_catalog` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_preference`
--

DROP TABLE IF EXISTS `user_preference`;
CREATE TABLE `user_preference` (
  `user` varchar(128) NOT NULL default '',
  `preference` int(11) unsigned NOT NULL default '0',
  `value` varchar(255) NOT NULL default '',
  KEY `user` (`user`),
  KEY `preference` (`preference`)
) TYPE=MyISAM;

--
-- Dumping data for table `user_preference`
--

LOCK TABLES `user_preference` WRITE;
/*!40000 ALTER TABLE `user_preference` DISABLE KEYS */;
INSERT INTO `user_preference` VALUES ('-1',1,'0'),('-1',2,'0'),('-1',3,'1'),('-1',4,'10'),('-1',17,'10'),('-1',18,''),('-1',19,'32'),('-1',22,'Ampache :: Pour l\'Amour de la Musique'),('-1',23,'0'),('-1',24,'1'),('-1',25,'80'),('-1',26,'100'),('-1',41,'0'),('-1',29,'stream'),('-1',30,'1'),('-1',31,'en_US'),('-1',32,'m3u'),('-1',33,'classic'),('-1',34,'27'),('-1',35,'27'),('-1',36,'27'),('-1',39,''),('-1',40,'0'),('-1',42,'0');
/*!40000 ALTER TABLE `user_preference` ENABLE KEYS */;
UNLOCK TABLES;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

