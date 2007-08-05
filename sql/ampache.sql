-- MySQL dump 10.11
--
-- Host: localhost    Database: ampache
-- ------------------------------------------------------
-- Server version	5.0.45-Debian_1-log
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO,MYSQL40' */;
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
  `type` varchar(64) NOT NULL default 'interface',
  `user` int(11) NOT NULL,
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
  `prefix` enum('The','An','A','Der','Die','Das','Ein','Eine') default NULL,
  `year` int(4) unsigned NOT NULL default '1984',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `year` (`year`)
) TYPE=MyISAM;

--
-- Dumping data for table `album`
--

LOCK TABLES `album` WRITE;
/*!40000 ALTER TABLE `album` DISABLE KEYS */;
/*!40000 ALTER TABLE `album` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `album_data`
--

DROP TABLE IF EXISTS `album_data`;
CREATE TABLE `album_data` (
  `album_id` int(11) unsigned NOT NULL,
  `art` mediumblob,
  `art_mime` varchar(64) default NULL,
  `thumb` blob,
  `thumb_mime` varchar(64) default NULL,
  UNIQUE KEY `album_id` (`album_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `album_data`
--

LOCK TABLES `album_data` WRITE;
/*!40000 ALTER TABLE `album_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `album_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artist`
--

DROP TABLE IF EXISTS `artist`;
CREATE TABLE `artist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `prefix` enum('The','An','A','Der','Die','Das','Ein','Eine') default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM;

--
-- Dumping data for table `artist`
--

LOCK TABLES `artist` WRITE;
/*!40000 ALTER TABLE `artist` DISABLE KEYS */;
/*!40000 ALTER TABLE `artist` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `catalog`
--

LOCK TABLES `catalog` WRITE;
/*!40000 ALTER TABLE `catalog` DISABLE KEYS */;
/*!40000 ALTER TABLE `catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flagged`
--

DROP TABLE IF EXISTS `flagged`;
CREATE TABLE `flagged` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `object_id` int(11) unsigned NOT NULL default '0',
  `object_type` enum('artist','album','song') NOT NULL default 'song',
  `user` int(11) NOT NULL,
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
-- Dumping data for table `flagged`
--

LOCK TABLES `flagged` WRITE;
/*!40000 ALTER TABLE `flagged` DISABLE KEYS */;
/*!40000 ALTER TABLE `flagged` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `genre`
--

LOCK TABLES `genre` WRITE;
/*!40000 ALTER TABLE `genre` DISABLE KEYS */;
/*!40000 ALTER TABLE `genre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ip_history`
--

DROP TABLE IF EXISTS `ip_history`;
CREATE TABLE `ip_history` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) NOT NULL,
  `ip` int(11) unsigned NOT NULL default '0',
  `date` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `username` (`user`),
  KEY `date` (`date`),
  KEY `ip` (`ip`)
) TYPE=MyISAM;

--
-- Dumping data for table `ip_history`
--

LOCK TABLES `ip_history` WRITE;
/*!40000 ALTER TABLE `ip_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `ip_history` ENABLE KEYS */;
UNLOCK TABLES;

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
  `id` varchar(64) NOT NULL,
  `song_id` int(11) unsigned NOT NULL default '0',
  `user` int(11) NOT NULL,
  `expire` int(11) unsigned NOT NULL default '0',
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
  `user` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `object_type` (`object_type`),
  KEY `object_id` (`object_id`),
  KEY `userid` (`user`),
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
  `user` int(11) NOT NULL,
  `type` enum('private','public') NOT NULL default 'private',
  `genre` int(11) unsigned NOT NULL,
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
  `object_id` int(11) unsigned default NULL,
  `object_type` varchar(32) NOT NULL default 'song',
  `dynamic_song` text,
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
-- Table structure for table `preference`
--

DROP TABLE IF EXISTS `preference`;
CREATE TABLE `preference` (
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
) TYPE=MyISAM AUTO_INCREMENT=56;

--
-- Dumping data for table `preference`
--

LOCK TABLES `preference` WRITE;
/*!40000 ALTER TABLE `preference` DISABLE KEYS */;
INSERT INTO `preference` VALUES (1,'download','0','Allow Downloads',100,'boolean','options'),(4,'popular_threshold','10','Popular Threshold',25,'integer','interface'),(19,'sample_rate','32','Transcode Bitrate',25,'string','streaming'),(22,'site_title','Ampache :: Pour l\'Amour de la Musique','Website Title',100,'string','system'),(23,'lock_songs','0','Lock Songs',100,'boolean','system'),(24,'force_http_play','1','Forces Http play regardless of port',100,'boolean','system'),(25,'http_port','80','Non-Standard Http Port',100,'integer','system'),(26,'catalog_echo_count','100','Catalog Echo Interval',100,'integer','system'),(41,'localplay_controller','0','Localplay Type',100,'special','streaming'),(29,'play_type','stream','Type of Playback',25,'special','streaming'),(30,'direct_link','1','Allow Direct Links',100,'boolean','options'),(31,'lang','en_US','Language',100,'special','interface'),(32,'playlist_type','m3u','Playlist Type',100,'special','playlist'),(33,'theme_name','classic','Theme',0,'special','interface'),(34,'ellipse_threshold_album','27','Album Ellipse Threshold',0,'integer','interface'),(35,'ellipse_threshold_artist','27','Artist Ellipse Threshold',0,'integer','interface'),(36,'ellipse_threshold_title','27','Title Ellipse Threshold',0,'integer','interface'),(51,'offset_limit','50','Offset Limit',5,'integer','interface'),(40,'localplay_level','0','Localplay Access Level',100,'special','streaming'),(44,'allow_stream_playback','1','Allow Streaming',100,'boolean','system'),(45,'allow_democratic_playback','0','Allow Democratic Play',100,'boolean','system'),(46,'allow_localplay_playback','0','Allow Localplay Play',100,'boolean','system'),(47,'stats_threshold','7','Statistics Day Threshold',25,'integer','interface'),(49,'min_object_count','1','Min Element Count',5,'integer','interface'),(52,'rate_limit','8192','Rate Limit',100,'integer','streaming'),(53,'playlist_method','normal','Playlist Method',5,'string','playlist'),(54,'playlist_add','append','Add Behavior',5,'string','playlist'),(55,'transcode','default','Transcoding',25,'string','streaming');
/*!40000 ALTER TABLE `preference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rating`
--

DROP TABLE IF EXISTS `rating`;
CREATE TABLE `rating` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) NOT NULL,
  `object_type` enum('artist','album','song','steam','video') NOT NULL default 'artist',
  `object_id` int(11) unsigned NOT NULL default '0',
  `rating` enum('-1','0','1','2','3','4','5') NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `object_id` (`object_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `rating`
--

LOCK TABLES `rating` WRITE;
/*!40000 ALTER TABLE `rating` DISABLE KEYS */;
/*!40000 ALTER TABLE `rating` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
  `id` varchar(64) NOT NULL,
  `username` varchar(16) NOT NULL default '',
  `expire` int(11) unsigned NOT NULL default '0',
  `value` longtext NOT NULL,
  `ip` int(11) unsigned default NULL,
  `type` enum('sso','mysql','ldap','http') NOT NULL default 'mysql',
  PRIMARY KEY  (`id`),
  KEY `expire` (`expire`)
) TYPE=MyISAM;

--
-- Dumping data for table `session`
--

LOCK TABLES `session` WRITE;
/*!40000 ALTER TABLE `session` DISABLE KEYS */;
/*!40000 ALTER TABLE `session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session_stream`
--

DROP TABLE IF EXISTS `session_stream`;
CREATE TABLE `session_stream` (
  `id` varchar(64) NOT NULL,
  `user` int(11) unsigned NOT NULL,
  `agent` varchar(255) default NULL,
  `expire` int(11) unsigned NOT NULL,
  `ip` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `session_stream`
--

LOCK TABLES `session_stream` WRITE;
/*!40000 ALTER TABLE `session_stream` DISABLE KEYS */;
/*!40000 ALTER TABLE `session_stream` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `song`
--

DROP TABLE IF EXISTS `song`;
CREATE TABLE `song` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `file` varchar(255) NOT NULL default '',
  `catalog` int(11) unsigned NOT NULL default '0',
  `album` int(11) unsigned NOT NULL default '0',
  `year` mediumint(4) unsigned NOT NULL default '0',
  `artist` int(11) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `bitrate` mediumint(8) unsigned NOT NULL default '0',
  `rate` mediumint(8) unsigned NOT NULL default '0',
  `mode` enum('abr','vbr','cbr') default 'cbr',
  `size` int(11) unsigned NOT NULL default '0',
  `time` smallint(5) unsigned NOT NULL default '0',
  `track` smallint(5) unsigned default NULL,
  `genre` int(11) unsigned default NULL,
  `played` tinyint(1) unsigned NOT NULL default '0',
  `enabled` tinyint(1) unsigned NOT NULL default '1',
  `update_time` int(11) unsigned default '0',
  `addition_time` int(11) unsigned default '0',
  `hash` varchar(64) default NULL,
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
-- Dumping data for table `song`
--

LOCK TABLES `song` WRITE;
/*!40000 ALTER TABLE `song` DISABLE KEYS */;
/*!40000 ALTER TABLE `song` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `song_data`
--

DROP TABLE IF EXISTS `song_data`;
CREATE TABLE `song_data` (
  `song_id` int(11) unsigned NOT NULL,
  `comment` text,
  `lyrics` text,
  `label` varchar(128) default NULL,
  `catalog_number` varchar(128) default NULL,
  `language` varchar(128) default NULL,
  UNIQUE KEY `song_id` (`song_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `song_data`
--

LOCK TABLES `song_data` WRITE;
/*!40000 ALTER TABLE `song_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `song_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tag_map`
--

DROP TABLE IF EXISTS `tag_map`;
CREATE TABLE `tag_map` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(16) NOT NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `object_id` (`object_id`),
  KEY `object_type` (`object_type`),
  KEY `user_id` (`user`)
) TYPE=MyISAM;

--
-- Dumping data for table `tag_map`
--

LOCK TABLES `tag_map` WRITE;
/*!40000 ALTER TABLE `tag_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag_map` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
  `map_id` int(11) unsigned NOT NULL,
  `name` varchar(32) NOT NULL,
  `order` tinyint(2) NOT NULL,
  KEY `order` (`order`),
  KEY `map_id` (`map_id`)
) TYPE=MyISAM;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tmp_playlist`
--

DROP TABLE IF EXISTS `tmp_playlist`;
CREATE TABLE `tmp_playlist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `session` varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL,
  `object_type` varchar(32) NOT NULL,
  `base_playlist` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `session` (`session`),
  KEY `type` (`type`)
) TYPE=MyISAM;

--
-- Dumping data for table `tmp_playlist`
--

LOCK TABLES `tmp_playlist` WRITE;
/*!40000 ALTER TABLE `tmp_playlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `tmp_playlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tmp_playlist_data`
--

DROP TABLE IF EXISTS `tmp_playlist_data`;
CREATE TABLE `tmp_playlist_data` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `tmp_playlist` int(11) unsigned NOT NULL,
  `object_type` varchar(32) default NULL,
  `object_id` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `tmp_playlist` (`tmp_playlist`)
) TYPE=MyISAM;

--
-- Dumping data for table `tmp_playlist_data`
--

LOCK TABLES `tmp_playlist_data` WRITE;
/*!40000 ALTER TABLE `tmp_playlist_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `tmp_playlist_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `update_info`
--

DROP TABLE IF EXISTS `update_info`;
CREATE TABLE `update_info` (
  `key` varchar(128) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  UNIQUE KEY `key_2` (`key`),
  KEY `key` (`key`)
) TYPE=MyISAM;

--
-- Dumping data for table `update_info`
--

LOCK TABLES `update_info` WRITE;
/*!40000 ALTER TABLE `update_info` DISABLE KEYS */;
INSERT INTO `update_info` VALUES ('db_version','340008');
/*!40000 ALTER TABLE `update_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(128) NOT NULL default '',
  `fullname` varchar(128) NOT NULL default '',
  `email` varchar(128) default NULL,
  `password` varchar(64) NOT NULL default '',
  `access` tinyint(4) unsigned NOT NULL,
  `disabled` tinyint(1) unsigned NOT NULL default '0',
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
-- Table structure for table `user_preference`
--

DROP TABLE IF EXISTS `user_preference`;
CREATE TABLE `user_preference` (
  `user` int(11) NOT NULL,
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
/*!40000 ALTER TABLE `user_preference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_shout`
--

DROP TABLE IF EXISTS `user_shout`;
CREATE TABLE `user_shout` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) NOT NULL,
  `text` text NOT NULL,
  `date` int(11) unsigned NOT NULL,
  `sticky` tinyint(1) unsigned NOT NULL default '0',
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `sticky` (`sticky`),
  KEY `date` (`date`),
  KEY `user` (`user`)
) TYPE=MyISAM;

--
-- Dumping data for table `user_shout`
--

LOCK TABLES `user_shout` WRITE;
/*!40000 ALTER TABLE `user_shout` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_shout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_vote`
--

DROP TABLE IF EXISTS `user_vote`;
CREATE TABLE `user_vote` (
  `user` int(11) unsigned NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `date` int(11) unsigned NOT NULL,
  KEY `user` (`user`),
  KEY `object_id` (`object_id`),
  KEY `date` (`date`)
) TYPE=MyISAM;

--
-- Dumping data for table `user_vote`
--

LOCK TABLES `user_vote` WRITE;
/*!40000 ALTER TABLE `user_vote` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_vote` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2007-08-05 22:13:37
