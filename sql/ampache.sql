-- MySQL dump 10.9
--
-- Host: localhost    Database: ampache
-- ------------------------------------------------------
-- Server version	4.1.9-Debian_2-log
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO,MYSQL323' */;

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
  `id` int(11) unsigned NOT NULL auto_increment,
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
  `id` int(11) unsigned NOT NULL auto_increment,
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
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `path` varchar(255) NOT NULL default '',
  `catalog_type` enum('local','remote') NOT NULL default 'local',
  `last_update` int(11) unsigned NOT NULL default '0',
  `last_add` int(11) unsigned NOT NULL default '0',
  `enabled` enum('true','false') NOT NULL default 'true',
  `private` int(1) unsigned NOT NULL default '0',
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
  `id` int(11) NOT NULL auto_increment,
  `user` int(10) unsigned NOT NULL default '0',
  `type` enum('badmp3','badid3','newid3','setid3','del','sort','ren','notify','done') NOT NULL default 'badid3',
  `song` int(11) unsigned NOT NULL default '0',
  `date` int(10) unsigned NOT NULL default '0',
  `comment` text,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `song` (`song`),
  KEY `type` (`type`)
) TYPE=MyISAM;

--
-- Dumping data for table `flagged`
--


/*!40000 ALTER TABLE `flagged` DISABLE KEYS */;
LOCK TABLES `flagged` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `flagged` ENABLE KEYS */;

--
-- Table structure for table `flagged_song`
--

DROP TABLE IF EXISTS `flagged_song`;
CREATE TABLE `flagged_song` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `song` int(10) unsigned NOT NULL default '0',
  `file` varchar(255) NOT NULL default '',
  `catalog` int(11) unsigned NOT NULL default '0',
  `album` int(11) unsigned NOT NULL default '0',
  `new_album` varchar(255) default NULL,
  `comment` varchar(255) NOT NULL default '',
  `year` mediumint(4) unsigned NOT NULL default '0',
  `artist` int(11) unsigned NOT NULL default '0',
  `new_artist` varchar(255) default NULL,
  `title` varchar(255) NOT NULL default '',
  `bitrate` mediumint(2) NOT NULL default '0',
  `rate` mediumint(2) NOT NULL default '0',
  `mode` varchar(25) default NULL,
  `size` mediumint(4) unsigned NOT NULL default '0',
  `time` mediumint(5) NOT NULL default '0',
  `track` int(11) unsigned default NULL,
  `genre` int(10) default NULL,
  `played` enum('true','false') NOT NULL default 'false',
  `enabled` enum('true','false') NOT NULL default 'true',
  `update_time` int(11) unsigned default '0',
  `addition_time` int(11) unsigned default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `song` (`song`),
  KEY `genre` (`genre`),
  KEY `album` (`album`),
  KEY `artist` (`artist`),
  KEY `id` (`id`),
  KEY `update_time` (`update_time`),
  KEY `addition_time` (`addition_time`),
  KEY `catalog` (`catalog`),
  KEY `played` (`played`),
  KEY `enabled` (`enabled`)
) TYPE=MyISAM;

--
-- Dumping data for table `flagged_song`
--


/*!40000 ALTER TABLE `flagged_song` DISABLE KEYS */;
LOCK TABLES `flagged_song` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `flagged_song` ENABLE KEYS */;

--
-- Table structure for table `flagged_types`
--

DROP TABLE IF EXISTS `flagged_types`;
CREATE TABLE `flagged_types` (
  `id` int(11) NOT NULL auto_increment,
  `type` varchar(32) NOT NULL default '',
  `value` varchar(128) NOT NULL default '',
  `access` enum('user','admin') NOT NULL default 'user',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `flagged_types`
--


/*!40000 ALTER TABLE `flagged_types` DISABLE KEYS */;
LOCK TABLES `flagged_types` WRITE;
INSERT INTO `flagged_types` VALUES (1,'badmp3','Corrupt or low-quality mp3','user'),(2,'badid3','Incomplete or incorrect song information','user'),(3,'newid3','Updated id3 information is available','admin'),(4,'del','Remove this file','admin'),(5,'sort','Put this file in a directory matching the conventions of its catalog','admin'),(6,'ren','Rename this file from id3 info','admin'),(7,'notify','Notify the user who flagged this song that it has been updated.','admin'),(8,'done','Take no action on this song.','admin'),(9,'setid3','Schedule file for id3 update','admin'),(10,'disabled','Disabled this song','admin');
UNLOCK TABLES;
/*!40000 ALTER TABLE `flagged_types` ENABLE KEYS */;

--
-- Table structure for table `genre`
--

DROP TABLE IF EXISTS `genre`;
CREATE TABLE `genre` (
  `id` int(11) unsigned NOT NULL auto_increment,
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
-- Table structure for table `now_playing`
--

DROP TABLE IF EXISTS `now_playing`;
CREATE TABLE `now_playing` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `song_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) unsigned default NULL,
  `start_time` int(11) unsigned NOT NULL default '0',
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
  `id` int(11) unsigned NOT NULL auto_increment,
  `object_type` enum('album','artist','song','playlist') NOT NULL default 'song',
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
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `owner` int(10) unsigned NOT NULL default '0',
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
  `playlist` int(11) unsigned NOT NULL default '0',
  `song` int(11) unsigned NOT NULL default '0',
  `track` int(11) unsigned NOT NULL default '0',
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


/*!40000 ALTER TABLE `playlist_permission` DISABLE KEYS */;
LOCK TABLES `playlist_permission` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `playlist_permission` ENABLE KEYS */;

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
  `locked` smallint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `preferences`
--


/*!40000 ALTER TABLE `preferences` DISABLE KEYS */;
LOCK TABLES `preferences` WRITE;
INSERT INTO `preferences` VALUES (1,'download','0','Allow Downloads',100,'user',0),(2,'upload','0','Allow Uploads',100,'user',0),(3,'quarantine','1','Quarantine All Uploads',100,'user',0),(4,'popular_threshold','10','Popular Threshold',25,'user',0),(5,'font','Verdana, Helvetica, sans-serif','Interface Font',25,'user',0),(6,'bg_color1','#ffffff','Background Color 1',25,'user',0),(7,'bg_color2','#000000','Background Color 2',25,'user',0),(8,'base_color1','#bbbbbb','Base Color 1',25,'user',0),(9,'base_color2','#dddddd','Base Color 2',25,'user',0),(10,'font_color1','#222222','Font Color 1',25,'user',0),(11,'font_color2','#000000','Font Color 2',25,'user',0),(12,'font_color3','#ffffff','Font Color 3',25,'user',0),(13,'row_color1','#cccccc','Row Color 1',25,'user',0),(14,'row_color2','#bbbbbb','Row Color 2',25,'user',0),(15,'row_color3','#dddddd','Row Color 3',25,'user',0),(16,'error_color','#990033','Error Color',25,'user',0),(17,'font_size','10','Font Size',25,'user',0),(18,'upload_dir','/tmp','Upload Directory',25,'user',0),(19,'sample_rate','32','Downsample Bitrate',25,'user',0),(20,'refresh_limit','0','Refresh Rate for Homepage',100,'system',0),(21,'local_length','900','Session Expire in Seconds',100,'system',0),(22,'site_title','For The Love of Music','Website Title',100,'system',0),(23,'lock_songs','0','Lock Songs',100,'system',1),(24,'force_http_play','1','Forces Http play regardless of port',100,'system',1),(25,'http_port','80','Non-Standard Http Port',100,'system',1),(26,'catalog_echo_count','100','Catalog Echo Interval',100,'system',0),(27,'album_cache_limit','25','Album Cache Limit',100,'system',0),(28,'artist_cache_limit','50','Artist Cache Limit',100,'system',0),(29,'play_type','stream','Type of Playback',25,'user',0),(30,'direct_link','1','Allow Direct Links',100,'user',0),(31,'lang','en_US','Language',100,'user',0),(32,'playlist_type','m3u','Playlist Type',100,'user',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `preferences` ENABLE KEYS */;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
  `id` varchar(32) NOT NULL default '',
  `username` varchar(16) NOT NULL default '',
  `expire` int(11) unsigned NOT NULL default '0',
  `value` text NOT NULL,
  `type` enum('sso','mysql','ldap') NOT NULL default 'mysql',
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
  `genre` int(10) default NULL,
  `played` enum('true','false') NOT NULL default 'false',
  `status` enum('disabled','enabled') NOT NULL default 'enabled',
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
  KEY `enabled` (`status`)
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
INSERT INTO `update_info` VALUES ('db_version','330004');
UNLOCK TABLES;
/*!40000 ALTER TABLE `update_info` ENABLE KEYS */;

--
-- Table structure for table `upload`
--

DROP TABLE IF EXISTS `upload`;
CREATE TABLE `upload` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) unsigned NOT NULL default '0',
  `file` varchar(255) NOT NULL default '',
  `comment` varchar(255) NOT NULL default '',
  `action` enum('add','quarantine','delete') NOT NULL default 'quarantine',
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
  `id` int(11) unsigned NOT NULL auto_increment,
  `username` varchar(128) NOT NULL default '',
  `fullname` varchar(128) NOT NULL default '',
  `email` varchar(128) default NULL,
  `password` varchar(64) NOT NULL default '',
  `access` varchar(64) NOT NULL default '',
  `offset_limit` int(5) unsigned NOT NULL default '50',
  `last_seen` int(11) unsigned NOT NULL default '0',
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
  `user` int(11) unsigned NOT NULL default '0',
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

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;

