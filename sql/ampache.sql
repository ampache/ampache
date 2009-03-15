-- Copyright (c) Ampache.org
-- All rights reserved.
--
-- This program is free software; you can redistribute it and/or
-- modify it under the terms of the GNU General Public License v2
-- as published by the Free Software Foundation.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
-- MySQL dump 10.11
--
-- Host: localhost    Database: ampache
-- ------------------------------------------------------
-- Server version	5.0.77-1-log

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
-- Table structure for table `access_list`
--

DROP TABLE IF EXISTS `access_list`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `access_list` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) character set utf8 default NULL,
  `start` varbinary(255) NOT NULL,
  `end` varbinary(255) NOT NULL,
  `level` smallint(3) unsigned NOT NULL default '5',
  `type` varchar(64) character set utf8 default NULL,
  `user` int(11) NOT NULL,
  `key` varchar(255) character set utf8 default NULL,
  `enabled` tinyint(1) unsigned NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `start` (`start`),
  KEY `end` (`end`),
  KEY `level` (`level`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `access_list`
--

LOCK TABLES `access_list` WRITE;
/*!40000 ALTER TABLE `access_list` DISABLE KEYS */;
INSERT INTO `access_list` VALUES (1,'DEFAULTv4','\0\0\0\0','ÿÿÿÿ',75,'interface',-1,NULL,1),(2,'DEFAULTv4','\0\0\0\0','ÿÿÿÿ',75,'stream',-1,NULL,1),(3,'DEFAULTv6','\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ',75,'interface',-1,NULL,1),(4,'DEFAULTv6','\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ',75,'stream',-1,NULL,1);
/*!40000 ALTER TABLE `access_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `album`
--

DROP TABLE IF EXISTS `album`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `album` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) character set utf8 default NULL,
  `prefix` varchar(32) collate utf8_unicode_ci default NULL,
  `year` int(4) unsigned NOT NULL default '1984',
  `disk` smallint(5) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `year` (`year`),
  KEY `disk` (`disk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `album_data` (
  `album_id` int(11) unsigned NOT NULL,
  `art` mediumblob,
  `art_mime` varchar(64) character set utf8 default NULL,
  `thumb` mediumblob,
  `thumb_mime` varchar(64) character set utf8 default NULL,
  UNIQUE KEY `album_id` (`album_id`),
  KEY `art_mime` (`art_mime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `artist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) character set utf8 default NULL,
  `prefix` varchar(32) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `artist`
--

LOCK TABLES `artist` WRITE;
/*!40000 ALTER TABLE `artist` DISABLE KEYS */;
/*!40000 ALTER TABLE `artist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artist_data`
--

DROP TABLE IF EXISTS `artist_data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `artist_data` (
  `artist_id` int(11) unsigned NOT NULL,
  `art` mediumblob NOT NULL,
  `art_mime` varchar(32) character set utf8 default NULL,
  `thumb` mediumblob,
  `thumb_mime` varchar(32) character set utf8 default NULL,
  `bio` text collate utf8_unicode_ci NOT NULL,
  UNIQUE KEY `artist_id` (`artist_id`),
  KEY `art_mime` (`art_mime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `artist_data`
--

LOCK TABLES `artist_data` WRITE;
/*!40000 ALTER TABLE `artist_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `artist_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catalog`
--

DROP TABLE IF EXISTS `catalog`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `catalog` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) character set utf8 default NULL,
  `path` varchar(255) character set utf8 default NULL,
  `catalog_type` enum('local','remote') character set utf8 default NULL,
  `last_update` int(11) unsigned NOT NULL default '0',
  `last_clean` int(11) unsigned default NULL,
  `last_add` int(11) unsigned NOT NULL default '0',
  `enabled` tinyint(1) unsigned NOT NULL default '1',
  `rename_pattern` varchar(255) character set utf8 default NULL,
  `sort_pattern` varchar(255) character set utf8 default NULL,
  `gather_types` varchar(255) character set utf8 default NULL,
  `key` varchar(255) character set utf8 default NULL,
  PRIMARY KEY  (`id`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `catalog`
--

LOCK TABLES `catalog` WRITE;
/*!40000 ALTER TABLE `catalog` DISABLE KEYS */;
/*!40000 ALTER TABLE `catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `democratic`
--

DROP TABLE IF EXISTS `democratic`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `democratic` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(64) character set utf8 default NULL,
  `cooldown` tinyint(4) unsigned default NULL,
  `level` tinyint(4) unsigned NOT NULL default '25',
  `user` int(11) NOT NULL,
  `primary` tinyint(1) unsigned NOT NULL default '0',
  `base_playlist` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `level` (`level`),
  KEY `primary_2` (`primary`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `democratic`
--

LOCK TABLES `democratic` WRITE;
/*!40000 ALTER TABLE `democratic` DISABLE KEYS */;
/*!40000 ALTER TABLE `democratic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dynamic_playlist`
--

DROP TABLE IF EXISTS `dynamic_playlist`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `dynamic_playlist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `user` int(11) NOT NULL,
  `date` int(11) unsigned NOT NULL,
  `type` varchar(128) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `dynamic_playlist`
--

LOCK TABLES `dynamic_playlist` WRITE;
/*!40000 ALTER TABLE `dynamic_playlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `dynamic_playlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dynamic_playlist_data`
--

DROP TABLE IF EXISTS `dynamic_playlist_data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `dynamic_playlist_data` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `dynamic_id` int(11) unsigned NOT NULL,
  `field` varchar(255) collate utf8_unicode_ci NOT NULL,
  `internal_operator` varchar(64) collate utf8_unicode_ci NOT NULL,
  `external_operator` varchar(64) collate utf8_unicode_ci NOT NULL,
  `value` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `dynamic_playlist_data`
--

LOCK TABLES `dynamic_playlist_data` WRITE;
/*!40000 ALTER TABLE `dynamic_playlist_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `dynamic_playlist_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flagged`
--

DROP TABLE IF EXISTS `flagged`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `flagged` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `object_id` int(11) unsigned NOT NULL default '0',
  `object_type` enum('artist','album','song') character set utf8 default NULL,
  `user` int(11) NOT NULL,
  `flag` enum('delete','retag','reencode','other') character set utf8 default NULL,
  `date` int(11) unsigned NOT NULL default '0',
  `approved` tinyint(1) unsigned NOT NULL default '0',
  `comment` varchar(255) character set utf8 default NULL,
  PRIMARY KEY  (`id`),
  KEY `date` (`date`,`approved`),
  KEY `object_id` (`object_id`),
  KEY `object_type` (`object_type`),
  KEY `user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `flagged`
--

LOCK TABLES `flagged` WRITE;
/*!40000 ALTER TABLE `flagged` DISABLE KEYS */;
/*!40000 ALTER TABLE `flagged` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ip_history`
--

DROP TABLE IF EXISTS `ip_history`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ip_history` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) NOT NULL,
  `ip` varbinary(255) default NULL,
  `date` int(11) unsigned NOT NULL default '0',
  `agent` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`user`),
  KEY `date` (`date`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `live_stream` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) character set utf8 default NULL,
  `site_url` varchar(255) character set utf8 default NULL,
  `url` varchar(255) character set utf8 default NULL,
  `genre` int(11) unsigned NOT NULL default '0',
  `catalog` int(11) unsigned NOT NULL default '0',
  `frequency` varchar(32) character set utf8 default NULL,
  `call_sign` varchar(32) character set utf8 default NULL,
  PRIMARY KEY  (`id`),
  KEY `catalog` (`catalog`),
  KEY `genre` (`genre`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `now_playing` (
  `id` varchar(64) character set utf8 NOT NULL default '',
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(255) collate utf8_unicode_ci NOT NULL,
  `user` int(11) NOT NULL,
  `expire` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `expire` (`expire`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `object_count` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video') character set utf8 default NULL,
  `object_id` int(11) unsigned NOT NULL default '0',
  `date` int(11) unsigned NOT NULL default '0',
  `user` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `object_type` (`object_type`),
  KEY `object_id` (`object_id`),
  KEY `userid` (`user`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `playlist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) character set utf8 default NULL,
  `user` int(11) NOT NULL,
  `type` enum('private','public') character set utf8 default NULL,
  `date` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `playlist_data` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `playlist` int(11) unsigned NOT NULL default '0',
  `object_id` int(11) unsigned default NULL,
  `object_type` varchar(32) character set utf8 default NULL,
  `track` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `playlist` (`playlist`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `preference` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) character set utf8 default NULL,
  `value` varchar(255) character set utf8 default NULL,
  `description` varchar(255) character set utf8 default NULL,
  `level` int(11) unsigned NOT NULL default '100',
  `type` varchar(128) character set utf8 default NULL,
  `catagory` varchar(128) character set utf8 default NULL,
  PRIMARY KEY  (`id`),
  KEY `catagory` (`catagory`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=70 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `preference`
--

LOCK TABLES `preference` WRITE;
/*!40000 ALTER TABLE `preference` DISABLE KEYS */;
INSERT INTO `preference` VALUES (1,'download','0','Allow Downloads',100,'boolean','options'),(4,'popular_threshold','10','Popular Threshold',25,'integer','interface'),(19,'sample_rate','32','Transcode Bitrate',25,'string','streaming'),(22,'site_title','Ampache :: Pour l\'Amour de la Musique','Website Title',100,'string','system'),(23,'lock_songs','0','Lock Songs',100,'boolean','system'),(24,'force_http_play','1','Forces Http play regardless of port',100,'boolean','system'),(25,'http_port','80','Non-Standard Http Port',100,'integer','system'),(41,'localplay_controller','0','Localplay Type',100,'special','options'),(29,'play_type','stream','Type of Playback',25,'special','streaming'),(31,'lang','en_US','Language',100,'special','interface'),(32,'playlist_type','m3u','Playlist Type',100,'special','playlist'),(33,'theme_name','classic','Theme',0,'special','interface'),(34,'ellipse_threshold_album','27','Album Ellipse Threshold',0,'integer','interface'),(35,'ellipse_threshold_artist','27','Artist Ellipse Threshold',0,'integer','interface'),(36,'ellipse_threshold_title','27','Title Ellipse Threshold',0,'integer','interface'),(51,'offset_limit','50','Offset Limit',5,'integer','interface'),(40,'localplay_level','0','Localplay Access',100,'special','options'),(44,'allow_stream_playback','1','Allow Streaming',100,'boolean','system'),(45,'allow_democratic_playback','0','Allow Democratic Play',100,'boolean','system'),(46,'allow_localplay_playback','0','Allow Localplay Play',100,'boolean','system'),(47,'stats_threshold','7','Statistics Day Threshold',25,'integer','interface'),(49,'min_object_count','1','Min Element Count',5,'integer','interface'),(52,'rate_limit','8192','Rate Limit',100,'integer','streaming'),(53,'playlist_method','default','Playlist Method',5,'string','playlist'),(55,'transcode','default','Transcoding',25,'string','streaming'),(57,'tags_userlist','','User to track',0,'string','tags'),(69,'show_lyrics','0','Show Lyrics',0,'boolean','interface');
/*!40000 ALTER TABLE `preference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rating`
--

DROP TABLE IF EXISTS `rating`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `rating` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) NOT NULL,
  `object_type` enum('artist','album','song','steam','video') character set utf8 default NULL,
  `object_id` int(11) unsigned NOT NULL default '0',
  `rating` tinyint(4) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `object_id` (`object_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session` (
  `id` varchar(64) character set utf8 NOT NULL default '',
  `username` varchar(16) character set utf8 default NULL,
  `expire` int(11) unsigned NOT NULL default '0',
  `value` longtext collate utf8_unicode_ci NOT NULL,
  `ip` varbinary(255) default NULL,
  `type` enum('mysql','ldap','http','api','xml-rpc') character set utf8 default NULL,
  `agent` varchar(255) character set utf8 default NULL,
  PRIMARY KEY  (`id`),
  KEY `expire` (`expire`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_stream` (
  `id` varchar(64) character set utf8 NOT NULL default '',
  `user` int(11) unsigned NOT NULL,
  `agent` varchar(255) character set utf8 default NULL,
  `expire` int(11) unsigned NOT NULL,
  `ip` varbinary(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `song` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `file` varchar(255) character set utf8 default NULL,
  `catalog` int(11) unsigned NOT NULL default '0',
  `album` int(11) unsigned NOT NULL default '0',
  `year` mediumint(4) unsigned NOT NULL default '0',
  `artist` int(11) unsigned NOT NULL default '0',
  `title` varchar(255) character set utf8 default NULL,
  `bitrate` mediumint(8) unsigned NOT NULL default '0',
  `rate` mediumint(8) unsigned NOT NULL default '0',
  `mode` enum('abr','vbr','cbr') character set utf8 default NULL,
  `size` int(11) unsigned NOT NULL default '0',
  `time` smallint(5) unsigned NOT NULL default '0',
  `track` smallint(5) unsigned default NULL,
  `played` tinyint(1) unsigned NOT NULL default '0',
  `enabled` tinyint(1) unsigned NOT NULL default '1',
  `update_time` int(11) unsigned default '0',
  `addition_time` int(11) unsigned default '0',
  PRIMARY KEY  (`id`),
  KEY `album` (`album`),
  KEY `artist` (`artist`),
  KEY `file` (`file`),
  KEY `update_time` (`update_time`),
  KEY `addition_time` (`addition_time`),
  KEY `catalog` (`catalog`),
  KEY `played` (`played`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `song_data` (
  `song_id` int(11) unsigned NOT NULL,
  `comment` text collate utf8_unicode_ci,
  `lyrics` text collate utf8_unicode_ci,
  `label` varchar(128) character set utf8 default NULL,
  `catalog_number` varchar(128) character set utf8 default NULL,
  `language` varchar(128) character set utf8 default NULL,
  UNIQUE KEY `song_id` (`song_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `song_data`
--

LOCK TABLES `song_data` WRITE;
/*!40000 ALTER TABLE `song_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `song_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `tag` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  UNIQUE KEY `name` (`name`),
  KEY `map_id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `tag`
--

LOCK TABLES `tag` WRITE;
/*!40000 ALTER TABLE `tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tag_map`
--

DROP TABLE IF EXISTS `tag_map`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `tag_map` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `tag_id` int(11) unsigned NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(16) character set utf8 default NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `object_id` (`object_id`),
  KEY `object_type` (`object_type`),
  KEY `user_id` (`user`),
  KEY `tag_id` (`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `tag_map`
--

LOCK TABLES `tag_map` WRITE;
/*!40000 ALTER TABLE `tag_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag_map` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tmp_browse`
--

DROP TABLE IF EXISTS `tmp_browse`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `tmp_browse` (
  `sid` varchar(128) collate utf8_unicode_ci NOT NULL,
  `type` varchar(255) collate utf8_unicode_ci NOT NULL,
  `data` longtext collate utf8_unicode_ci NOT NULL,
  UNIQUE KEY `sid` (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `tmp_browse`
--

LOCK TABLES `tmp_browse` WRITE;
/*!40000 ALTER TABLE `tmp_browse` DISABLE KEYS */;
/*!40000 ALTER TABLE `tmp_browse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tmp_playlist`
--

DROP TABLE IF EXISTS `tmp_playlist`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `tmp_playlist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `session` varchar(32) character set utf8 default NULL,
  `type` varchar(32) character set utf8 default NULL,
  `object_type` varchar(32) character set utf8 default NULL,
  PRIMARY KEY  (`id`),
  KEY `session` (`session`),
  KEY `type` (`type`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `tmp_playlist_data` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `tmp_playlist` int(11) unsigned NOT NULL,
  `object_type` varchar(32) character set utf8 default NULL,
  `object_id` int(11) unsigned NOT NULL,
  `track` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `tmp_playlist` (`tmp_playlist`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `update_info` (
  `key` varchar(128) character set utf8 default NULL,
  `value` varchar(255) character set utf8 default NULL,
  UNIQUE KEY `key` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `update_info`
--

LOCK TABLES `update_info` WRITE;
/*!40000 ALTER TABLE `update_info` DISABLE KEYS */;
INSERT INTO `update_info` VALUES ('db_version','350008');
/*!40000 ALTER TABLE `update_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(128) character set utf8 default NULL,
  `fullname` varchar(128) character set utf8 default NULL,
  `email` varchar(128) character set utf8 default NULL,
  `password` varchar(64) character set utf8 default NULL,
  `access` tinyint(4) unsigned NOT NULL,
  `disabled` tinyint(1) unsigned NOT NULL default '0',
  `last_seen` int(11) unsigned NOT NULL default '0',
  `create_date` int(11) unsigned default NULL,
  `validation` varchar(128) character set utf8 default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_catalog` (
  `user` int(11) unsigned NOT NULL,
  `catalog` int(11) unsigned NOT NULL,
  `level` smallint(4) unsigned NOT NULL default '5',
  KEY `user` (`user`),
  KEY `catalog` (`catalog`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_preference` (
  `user` int(11) NOT NULL,
  `preference` int(11) unsigned NOT NULL default '0',
  `value` varchar(255) character set utf8 default NULL,
  KEY `user` (`user`),
  KEY `preference` (`preference`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `user_preference`
--

LOCK TABLES `user_preference` WRITE;
/*!40000 ALTER TABLE `user_preference` DISABLE KEYS */;
INSERT INTO `user_preference` VALUES (-1,1,'0'),(-1,4,'10'),(-1,19,'32'),(-1,22,'Ampache :: Pour l\'Amour de la Musique'),(-1,23,'0'),(-1,24,'1'),(-1,25,'80'),(-1,41,'0'),(-1,29,'stream'),(-1,31,'en_US'),(-1,32,'m3u'),(-1,33,'classic'),(-1,34,'27'),(-1,35,'27'),(-1,36,'27'),(-1,51,'50'),(-1,40,'0'),(-1,44,'1'),(-1,45,'0'),(-1,46,'0'),(-1,47,'7'),(-1,49,'1'),(-1,52,'8192'),(-1,53,'default'),(-1,55,'default'),(-1,57,''),(-1,69,'0');
/*!40000 ALTER TABLE `user_preference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_shout`
--

DROP TABLE IF EXISTS `user_shout`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_shout` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) NOT NULL,
  `text` text collate utf8_unicode_ci NOT NULL,
  `date` int(11) unsigned NOT NULL,
  `sticky` tinyint(1) unsigned NOT NULL default '0',
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(32) character set utf8 default NULL,
  PRIMARY KEY  (`id`),
  KEY `sticky` (`sticky`),
  KEY `date` (`date`),
  KEY `user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

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
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_vote` (
  `user` int(11) unsigned NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `date` int(11) unsigned NOT NULL,
  KEY `user` (`user`),
  KEY `object_id` (`object_id`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `user_vote`
--

LOCK TABLES `user_vote` WRITE;
/*!40000 ALTER TABLE `user_vote` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_vote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video`
--

DROP TABLE IF EXISTS `video`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `video` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `file` varchar(255) collate utf8_unicode_ci NOT NULL,
  `catalog` int(11) unsigned NOT NULL,
  `title` varchar(255) collate utf8_unicode_ci NOT NULL,
  `video_codec` varchar(255) collate utf8_unicode_ci NOT NULL,
  `audio_codec` varchar(255) collate utf8_unicode_ci NOT NULL,
  `resolution_x` mediumint(8) unsigned NOT NULL,
  `resolution_y` mediumint(8) unsigned NOT NULL,
  `time` int(11) unsigned NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `mime` varchar(255) collate utf8_unicode_ci NOT NULL,
  `addition_time` int(11) unsigned NOT NULL,
  `update_time` int(11) unsigned default NULL,
  `enabled` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `file` (`file`),
  KEY `enabled` (`enabled`),
  KEY `title` (`title`),
  KEY `addition_time` (`addition_time`),
  KEY `update_time` (`update_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `video`
--

LOCK TABLES `video` WRITE;
/*!40000 ALTER TABLE `video` DISABLE KEYS */;
/*!40000 ALTER TABLE `video` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2009-03-15 23:52:10
