-- MySQL dump 9.11
--
-- Host: localhost    Database: ampache-dev
-- ------------------------------------------------------
-- Server version	4.0.24_Debian-10sarge1-log

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
  `user` varchar(128) default NULL,
  `key` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `ip` (`start`)
) TYPE=MyISAM;

--
-- Dumping data for table `access_list`
--


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
  KEY `date` (`date`,`approved`)
) TYPE=MyISAM;

--
-- Dumping data for table `flagged`
--


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


--
-- Table structure for table `object_count`
--

DROP TABLE IF EXISTS `object_count`;
CREATE TABLE `object_count` (
  `id` int(11) unsigned NOT NULL auto_increment,
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


--
-- Table structure for table `playlist`
--

DROP TABLE IF EXISTS `playlist`;
CREATE TABLE `playlist` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `user` varchar(128) NOT NULL default '',
  `type` enum('private','public') NOT NULL default 'private',
  `date` timestamp(14) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `id` (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `playlist`
--


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
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `preferences`
--

INSERT INTO `preferences` VALUES (1,'download','0','Allow Downloads',100,'boolean','options');
INSERT INTO `preferences` VALUES (2,'upload','0','Allow Uploads',100,'boolean','options');
INSERT INTO `preferences` VALUES (3,'quarantine','1','Quarantine All Uploads',100,'boolean','options');
INSERT INTO `preferences` VALUES (4,'popular_threshold','10','Popular Threshold',25,'integer','interface');
INSERT INTO `preferences` VALUES (5,'font','Verdana, Helvetica, sans-serif','Interface Font',25,'string','theme');
INSERT INTO `preferences` VALUES (6,'bg_color1','#ffffff','Background Color 1',25,'string','theme');
INSERT INTO `preferences` VALUES (7,'bg_color2','#000000','Background Color 2',25,'string','theme');
INSERT INTO `preferences` VALUES (8,'base_color1','#bbbbbb','Base Color 1',25,'string','theme');
INSERT INTO `preferences` VALUES (9,'base_color2','#dddddd','Base Color 2',25,'string','theme');
INSERT INTO `preferences` VALUES (10,'font_color1','#222222','Font Color 1',25,'string','theme');
INSERT INTO `preferences` VALUES (11,'font_color2','#000000','Font Color 2',25,'string','theme');
INSERT INTO `preferences` VALUES (12,'font_color3','#ffffff','Font Color 3',25,'string','theme');
INSERT INTO `preferences` VALUES (13,'row_color1','#cccccc','Row Color 1',25,'string','theme');
INSERT INTO `preferences` VALUES (14,'row_color2','#bbbbbb','Row Color 2',25,'string','theme');
INSERT INTO `preferences` VALUES (15,'row_color3','#dddddd','Row Color 3',25,'string','theme');
INSERT INTO `preferences` VALUES (16,'error_color','#990033','Error Color',25,'string','theme');
INSERT INTO `preferences` VALUES (17,'font_size','10','Font Size',25,'integer','theme');
INSERT INTO `preferences` VALUES (18,'upload_dir','','Upload Directory',25,'string','options');
INSERT INTO `preferences` VALUES (19,'sample_rate','32','Downsample Bitrate',25,'string','streaming');
INSERT INTO `preferences` VALUES (22,'site_title','For The Love of Music','Website Title',100,'string','system');
INSERT INTO `preferences` VALUES (23,'lock_songs','0','Lock Songs',100,'boolean','system');
INSERT INTO `preferences` VALUES (24,'force_http_play','1','Forces Http play regardless of port',100,'boolean','system');
INSERT INTO `preferences` VALUES (25,'http_port','80','Non-Standard Http Port',100,'integer','system');
INSERT INTO `preferences` VALUES (26,'catalog_echo_count','100','Catalog Echo Interval',100,'integer','system');
INSERT INTO `preferences` VALUES (41,'localplay_controller','0','Localplay Type',100,'special','streaming');
INSERT INTO `preferences` VALUES (29,'play_type','stream','Type of Playback',25,'special','streaming');
INSERT INTO `preferences` VALUES (30,'direct_link','1','Allow Direct Links',100,'boolean','options');
INSERT INTO `preferences` VALUES (31,'lang','en_US','Language',100,'special','interface');
INSERT INTO `preferences` VALUES (32,'playlist_type','m3u','Playlist Type',100,'special','streaming');
INSERT INTO `preferences` VALUES (33,'theme_name','classic','Theme',0,'special','theme');
INSERT INTO `preferences` VALUES (34,'ellipse_threshold_album','27','Album Ellipse Threshold',0,'integer','interface');
INSERT INTO `preferences` VALUES (35,'ellipse_threshold_artist','27','Artist Ellipse Threshold',0,'integer','interface');
INSERT INTO `preferences` VALUES (36,'ellipse_threshold_title','27','Title Ellipse Threshold',0,'integer','interface');
INSERT INTO `preferences` VALUES (39,'quarantine_dir','','Quarantine Directory',100,'string','system');
INSERT INTO `preferences` VALUES (42,'min_album_size','0','Min Album Size',0,'integer','interface');
INSERT INTO `preferences` VALUES (40,'localplay_level','0','Localplay Access Level',100,'special','streaming');

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
CREATE TABLE `ratings` (
  `id` int(11) unsigned NOT NULL auto_increment,
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

INSERT INTO `update_info` VALUES ('db_version','332011');

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

INSERT INTO `user_preference` VALUES ('-1',1,'0');
INSERT INTO `user_preference` VALUES ('-1',2,'0');
INSERT INTO `user_preference` VALUES ('-1',3,'1');
INSERT INTO `user_preference` VALUES ('-1',4,'10');
INSERT INTO `user_preference` VALUES ('-1',5,'Verdana, Helvetica, sans-serif');
INSERT INTO `user_preference` VALUES ('-1',6,'#ffffff');
INSERT INTO `user_preference` VALUES ('-1',7,'#000000');
INSERT INTO `user_preference` VALUES ('-1',8,'#bbbbbb');
INSERT INTO `user_preference` VALUES ('-1',9,'#dddddd');
INSERT INTO `user_preference` VALUES ('-1',10,'#222222');
INSERT INTO `user_preference` VALUES ('-1',11,'#000000');
INSERT INTO `user_preference` VALUES ('-1',12,'#ffffff');
INSERT INTO `user_preference` VALUES ('-1',13,'#cccccc');
INSERT INTO `user_preference` VALUES ('-1',14,'#bbbbbb');
INSERT INTO `user_preference` VALUES ('-1',15,'#dddddd');
INSERT INTO `user_preference` VALUES ('-1',16,'#990033');
INSERT INTO `user_preference` VALUES ('-1',17,'10');
INSERT INTO `user_preference` VALUES ('-1',18,'');
INSERT INTO `user_preference` VALUES ('-1',19,'32');
INSERT INTO `user_preference` VALUES ('-1',22,'For The Love of Music');
INSERT INTO `user_preference` VALUES ('-1',23,'0');
INSERT INTO `user_preference` VALUES ('-1',24,'1');
INSERT INTO `user_preference` VALUES ('-1',25,'80');
INSERT INTO `user_preference` VALUES ('-1',26,'100');
INSERT INTO `user_preference` VALUES ('-1',41,'0');
INSERT INTO `user_preference` VALUES ('-1',29,'stream');
INSERT INTO `user_preference` VALUES ('-1',30,'1');
INSERT INTO `user_preference` VALUES ('-1',31,'en_US');
INSERT INTO `user_preference` VALUES ('-1',32,'m3u');
INSERT INTO `user_preference` VALUES ('-1',33,'classic');
INSERT INTO `user_preference` VALUES ('-1',34,'27');
INSERT INTO `user_preference` VALUES ('-1',35,'27');
INSERT INTO `user_preference` VALUES ('-1',36,'27');
INSERT INTO `user_preference` VALUES ('-1',39,'');
INSERT INTO `user_preference` VALUES ('-1',40,'0');
INSERT INTO `user_preference` VALUES ('-1',42,'0');

