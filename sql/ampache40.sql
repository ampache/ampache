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
  `name` varchar(255) default NULL,
  `start` varbinary(255) NOT NULL,
  `end` varbinary(255) NOT NULL,
  `level` smallint(3) unsigned NOT NULL default '5',
  `type` varchar(64) default NULL,
  `user` int(11) NOT NULL,
  `key` varchar(255) default NULL,
  `enabled` tinyint(1) unsigned NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `start` (`start`),
  KEY `end` (`end`),
  KEY `level` (`level`),
  KEY `enabled` (`enabled`)
) TYPE=MyISAM AUTO_INCREMENT=7;

--
-- Dumping data for table `access_list`
--

LOCK TABLES `access_list` WRITE;
/*!40000 ALTER TABLE `access_list` DISABLE KEYS */;
INSERT INTO `access_list` VALUES (1,'DEFAULTv4','\0\0\0\0','ÿÿÿÿ',75,'interface',-1,NULL,1),(2,'DEFAULTv4','\0\0\0\0','ÿÿÿÿ',75,'stream',-1,NULL,1),(3,'DEFAULTv6','\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ',75,'interface',-1,NULL,1),(4,'DEFAULTv6','\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ',75,'stream',-1,NULL,1),(5,'DEFAULTv4','\0\0\0\0','ÿÿÿÿ',75,'rpc',-1,'',0),(6,'DEFAULTv6','\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','ÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿÿ',75,'rpc',-1,'',0);
/*!40000 ALTER TABLE `access_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `album`
--

DROP TABLE IF EXISTS `album`;
CREATE TABLE `album` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `prefix` varchar(32) default NULL,
  `year` int(4) unsigned NOT NULL default '1984',
  `disk` smallint(5) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `year` (`year`),
  KEY `disk` (`disk`)
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
  `thumb` mediumblob,
  `thumb_mime` varchar(64) default NULL,
  UNIQUE KEY `album_id` (`album_id`),
  KEY `art_mime` (`art_mime`)
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
  `name` varchar(255) default NULL,
  `prefix` varchar(32) default NULL,
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
-- Table structure for table `artist_data`
--

DROP TABLE IF EXISTS `artist_data`;
CREATE TABLE `artist_data` (
  `artist_id` int(11) unsigned NOT NULL,
  `art` mediumblob NOT NULL,
  `art_mime` varchar(32) default NULL,
  `thumb` mediumblob,
  `thumb_mime` varchar(32) default NULL,
  `bio` text NOT NULL,
  UNIQUE KEY `artist_id` (`artist_id`),
  KEY `art_mime` (`art_mime`)
) TYPE=MyISAM;

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
CREATE TABLE `catalog` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) default NULL,
  `path` varchar(255) default NULL,
  `catalog_type` enum('local','remote') default NULL,
  `last_update` int(11) unsigned NOT NULL default '0',
  `last_clean` int(11) unsigned default NULL,
  `last_add` int(11) unsigned NOT NULL default '0',
  `enabled` tinyint(1) unsigned NOT NULL default '1',
  `rename_pattern` varchar(255) default NULL,
  `sort_pattern` varchar(255) default NULL,
  `gather_types` varchar(255) default NULL,
  `key` varchar(255) default NULL,
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
-- Table structure for table `democratic`
--

DROP TABLE IF EXISTS `democratic`;
CREATE TABLE `democratic` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(64) default NULL,
  `cooldown` tinyint(4) unsigned default NULL,
  `level` tinyint(4) unsigned NOT NULL default '25',
  `user` int(11) NOT NULL,
  `primary` tinyint(1) unsigned NOT NULL default '0',
  `base_playlist` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `level` (`level`),
  KEY `primary_2` (`primary`)
) TYPE=MyISAM;

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
CREATE TABLE `dynamic_playlist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `user` int(11) NOT NULL,
  `date` int(11) unsigned NOT NULL,
  `type` varchar(128) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

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
CREATE TABLE `dynamic_playlist_data` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `dynamic_id` int(11) unsigned NOT NULL,
  `field` varchar(255) default NULL,
  `internal_operator` varchar(64) default NULL,
  `external_operator` varchar(64) default NULL,
  `value` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

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
CREATE TABLE `flagged` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `object_id` int(11) unsigned NOT NULL default '0',
  `object_type` enum('artist','album','song') default NULL,
  `user` int(11) NOT NULL,
  `flag` enum('delete','retag','reencode','other') default NULL,
  `date` int(11) unsigned NOT NULL default '0',
  `approved` tinyint(1) unsigned NOT NULL default '0',
  `comment` varchar(255) default NULL,
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
-- Table structure for table `ip_history`
--

DROP TABLE IF EXISTS `ip_history`;
CREATE TABLE `ip_history` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) NOT NULL,
  `ip` varbinary(255) default NULL,
  `date` int(11) unsigned NOT NULL default '0',
  `agent` varchar(255) default NULL,
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
  `name` varchar(128) default NULL,
  `site_url` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `genre` int(11) unsigned NOT NULL default '0',
  `catalog` int(11) unsigned NOT NULL default '0',
  `frequency` varchar(32) default NULL,
  `call_sign` varchar(32) default NULL,
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
-- Table structure for table `localplay_httpq`
--

DROP TABLE IF EXISTS `localplay_httpq`;
CREATE TABLE `localplay_httpq` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) default NULL,
  `owner` int(11) NOT NULL,
  `host` varchar(255) default NULL,
  `port` int(11) unsigned NOT NULL,
  `password` varchar(255) default NULL,
  `access` smallint(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `localplay_httpq`
--

LOCK TABLES `localplay_httpq` WRITE;
/*!40000 ALTER TABLE `localplay_httpq` DISABLE KEYS */;
/*!40000 ALTER TABLE `localplay_httpq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `localplay_mpd`
--

DROP TABLE IF EXISTS `localplay_mpd`;
CREATE TABLE `localplay_mpd` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) default NULL,
  `owner` int(11) NOT NULL,
  `host` varchar(255) default NULL,
  `port` int(11) unsigned NOT NULL default '6600',
  `password` varchar(255) default NULL,
  `access` smallint(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `localplay_mpd`
--

LOCK TABLES `localplay_mpd` WRITE;
/*!40000 ALTER TABLE `localplay_mpd` DISABLE KEYS */;
/*!40000 ALTER TABLE `localplay_mpd` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `localplay_shoutcast`
--

DROP TABLE IF EXISTS `localplay_shoutcast`;
CREATE TABLE `localplay_shoutcast` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) default NULL,
  `owner` int(11) NOT NULL,
  `pid` varchar(255) default NULL,
  `playlist` varchar(255) default NULL,
  `local_root` varchar(255) default NULL,
  `access` smallint(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `localplay_shoutcast`
--

LOCK TABLES `localplay_shoutcast` WRITE;
/*!40000 ALTER TABLE `localplay_shoutcast` DISABLE KEYS */;
/*!40000 ALTER TABLE `localplay_shoutcast` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `now_playing`
--

DROP TABLE IF EXISTS `now_playing`;
CREATE TABLE `now_playing` (
  `id` varchar(64) NOT NULL default '',
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(255) default NULL,
  `user` int(11) NOT NULL,
  `expire` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `expire` (`expire`)
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
  `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video') default NULL,
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
  `name` varchar(128) default NULL,
  `user` int(11) NOT NULL,
  `type` enum('private','public') default NULL,
  `date` int(11) unsigned NOT NULL,
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
  `object_type` varchar(32) default NULL,
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
  `name` varchar(128) default NULL,
  `value` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `level` int(11) unsigned NOT NULL default '100',
  `type` varchar(128) default NULL,
  `catagory` varchar(128) default NULL,
  PRIMARY KEY  (`id`),
  KEY `catagory` (`catagory`),
  KEY `name` (`name`)
) TYPE=MyISAM AUTO_INCREMENT=79;

--
-- Dumping data for table `preference`
--

LOCK TABLES `preference` WRITE;
/*!40000 ALTER TABLE `preference` DISABLE KEYS */;
INSERT INTO `preference` VALUES (1,'download','0','Allow Downloads',100,'boolean','options'),(4,'popular_threshold','10','Popular Threshold',25,'integer','interface'),(19,'sample_rate','32','Transcode Bitrate',25,'string','streaming'),(22,'site_title','Ampache :: Pour l\'Amour de la Musique','Website Title',100,'string','system'),(23,'lock_songs','0','Lock Songs',100,'boolean','system'),(24,'force_http_play','1','Forces Http play regardless of port',100,'boolean','system'),(25,'http_port','80','Non-Standard Http Port',100,'integer','system'),(41,'localplay_controller','0','Localplay Type',100,'special','options'),(29,'play_type','stream','Type of Playback',25,'special','streaming'),(31,'lang','fr_FR','Language',100,'special','interface'),(32,'playlist_type','m3u','Playlist Type',100,'special','playlist'),(33,'theme_name','classic','Theme',0,'special','interface'),(34,'ellipse_threshold_album','27','Album Ellipse Threshold',0,'integer','interface'),(35,'ellipse_threshold_artist','27','Artist Ellipse Threshold',0,'integer','interface'),(36,'ellipse_threshold_title','27','Title Ellipse Threshold',0,'integer','interface'),(51,'offset_limit','50','Offset Limit',5,'integer','interface'),(40,'localplay_level','0','Localplay Access',100,'special','options'),(44,'allow_stream_playback','1','Allow Streaming',100,'boolean','system'),(45,'allow_democratic_playback','0','Allow Democratic Play',100,'boolean','system'),(46,'allow_localplay_playback','0','Allow Localplay Play',100,'boolean','system'),(47,'stats_threshold','7','Statistics Day Threshold',25,'integer','interface'),(49,'min_object_count','1','Min Element Count',5,'integer','interface'),(52,'rate_limit','8192','Rate Limit',100,'integer','streaming'),(53,'playlist_method','default','Playlist Method',5,'string','playlist'),(55,'transcode','default','Transcoding',25,'string','streaming'),(57,'tags_userlist','','User to track',0,'string','tags'),(69,'show_lyrics','0','Show Lyrics',0,'boolean','interface'),(70,'mpd_active','0','MPD Active Instance',25,'integer','internal'),(71,'httpq_active','0','HTTPQ Active Instance',25,'integer','internal'),(72,'shoutcast_active','0','Shoutcast Active Instance',25,'integer','internal'),(73,'lastfm_user','','Last.FM Username',25,'string','plugins'),(74,'lastfm_pass','','Last.FM Password',25,'string','plugins'),(75,'lastfm_port','','Last.FM Submit Port',25,'string','internal'),(76,'lastfm_host','','Last.FM Submit Host',25,'string','internal'),(77,'lastfm_url','','Last.FM Submit URL',25,'string','internal'),(78,'lastfm_challenge','','Last.FM Submit Challenge',25,'string','internal');
/*!40000 ALTER TABLE `preference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rating`
--

DROP TABLE IF EXISTS `rating`;
CREATE TABLE `rating` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) NOT NULL,
  `object_type` enum('artist','album','song','steam','video') default NULL,
  `object_id` int(11) unsigned NOT NULL default '0',
  `rating` tinyint(4) NOT NULL,
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
  `id` varchar(64) NOT NULL default '',
  `username` varchar(16) default NULL,
  `expire` int(11) unsigned NOT NULL default '0',
  `value` longtext NOT NULL,
  `ip` varbinary(255) default NULL,
  `type` enum('mysql','ldap','http','api','xml-rpc') default NULL,
  `agent` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `expire` (`expire`),
  KEY `type` (`type`)
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
  `id` varchar(64) NOT NULL default '',
  `user` int(11) unsigned NOT NULL,
  `agent` varchar(255) default NULL,
  `expire` int(11) unsigned NOT NULL,
  `ip` varbinary(255) default NULL,
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
  `file` varchar(255) default NULL,
  `catalog` int(11) unsigned NOT NULL default '0',
  `album` int(11) unsigned NOT NULL default '0',
  `year` mediumint(4) unsigned NOT NULL default '0',
  `artist` int(11) unsigned NOT NULL default '0',
  `title` varchar(255) default NULL,
  `bitrate` mediumint(8) unsigned NOT NULL default '0',
  `rate` mediumint(8) unsigned NOT NULL default '0',
  `mode` enum('abr','vbr','cbr') default NULL,
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
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
CREATE TABLE `tag` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  UNIQUE KEY `name` (`name`),
  KEY `map_id` (`id`)
) TYPE=MyISAM;

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
CREATE TABLE `tag_map` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `tag_id` int(11) unsigned NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(16) default NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `object_id` (`object_id`),
  KEY `object_type` (`object_type`),
  KEY `user_id` (`user`),
  KEY `tag_id` (`tag_id`)
) TYPE=MyISAM;

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
CREATE TABLE `tmp_browse` (
  `sid` varchar(128) default NULL,
  `type` varchar(255) default NULL,
  `data` longtext NOT NULL,
  UNIQUE KEY `sid` (`sid`)
) TYPE=MyISAM;

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
CREATE TABLE `tmp_playlist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `session` varchar(32) default NULL,
  `type` varchar(32) default NULL,
  `object_type` varchar(32) default NULL,
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
  `track` int(11) unsigned default NULL,
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
  `key` varchar(128) default NULL,
  `value` varchar(255) default NULL,
  UNIQUE KEY `key` (`key`)
) TYPE=MyISAM;

--
-- Dumping data for table `update_info`
--

LOCK TABLES `update_info` WRITE;
/*!40000 ALTER TABLE `update_info` DISABLE KEYS */;
INSERT INTO `update_info` VALUES ('db_version','350008'),('Plugin_Last.FM','000003');
/*!40000 ALTER TABLE `update_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(128) default NULL,
  `fullname` varchar(128) default NULL,
  `email` varchar(128) default NULL,
  `password` varchar(64) default NULL,
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
-- Table structure for table `user_catalog`
--

DROP TABLE IF EXISTS `user_catalog`;
CREATE TABLE `user_catalog` (
  `user` int(11) unsigned NOT NULL,
  `catalog` int(11) unsigned NOT NULL,
  `level` smallint(4) unsigned NOT NULL default '5',
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
  `user` int(11) NOT NULL,
  `preference` int(11) unsigned NOT NULL default '0',
  `value` varchar(255) default NULL,
  KEY `user` (`user`),
  KEY `preference` (`preference`)
) TYPE=MyISAM;

--
-- Dumping data for table `user_preference`
--

LOCK TABLES `user_preference` WRITE;
/*!40000 ALTER TABLE `user_preference` DISABLE KEYS */;
INSERT INTO `user_preference` VALUES (-1,1,'1'),(-1,4,'10'),(-1,19,'32'),(-1,22,'Ampache :: Pour l\'Amour de la Musique'),(-1,23,'0'),(-1,24,'1'),(-1,25,'80'),(-1,41,'mpd'),(-1,29,'stream'),(-1,31,'en_US'),(-1,32,'m3u'),(-1,33,'classic'),(-1,34,'27'),(-1,35,'27'),(-1,36,'27'),(-1,51,'50'),(-1,40,'100'),(-1,44,'1'),(-1,45,'1'),(-1,46,'1'),(-1,47,'7'),(-1,49,'1'),(-1,52,'8192'),(-1,53,'default'),(-1,55,'default'),(-1,57,''),(-1,69,'0'),(-1,70,'0'),(-1,71,'0'),(-1,72,'0'),(-1,73,''),(-1,74,''),(-1,75,''),(-1,76,''),(-1,77,''),(-1,78,'');
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
  `object_type` varchar(32) default NULL,
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

--
-- Table structure for table `video`
--

DROP TABLE IF EXISTS `video`;
CREATE TABLE `video` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `file` varchar(255) default NULL,
  `catalog` int(11) unsigned NOT NULL,
  `title` varchar(255) default NULL,
  `video_codec` varchar(255) default NULL,
  `audio_codec` varchar(255) default NULL,
  `resolution_x` mediumint(8) unsigned NOT NULL,
  `resolution_y` mediumint(8) unsigned NOT NULL,
  `time` int(11) unsigned NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `mime` varchar(255) default NULL,
  `addition_time` int(11) unsigned NOT NULL,
  `update_time` int(11) unsigned default NULL,
  `enabled` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `file` (`file`),
  KEY `enabled` (`enabled`),
  KEY `title` (`title`),
  KEY `addition_time` (`addition_time`),
  KEY `update_time` (`update_time`)
) TYPE=MyISAM;

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
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2010-02-03  2:17:25
