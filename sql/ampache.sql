-- Copyright 2001 - 2020 Ampache.org
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
--
-- Host: localhost    Database: ampache_clean
-- ------------------------------------------------------
-- Server version    5.4.16-log


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


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
CREATE TABLE IF NOT EXISTS `access_list` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `start` varbinary(255) NOT NULL,
  `end` varbinary(255) NOT NULL,
  `level` smallint(3) unsigned NOT NULL DEFAULT '5',
  `type` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `user` int(11) NOT NULL,
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `start` (`start`),
  KEY `end` (`end`),
  KEY `level` (`level`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `access_list`
--

INSERT INTO `access_list` (`id`, `name`, `start`, `end`, `level`, `type`, `user`, `enabled`) VALUES
(1, 'DEFAULTv4', '\0\0\0\0', 0xffffffff, 75, 'interface', -1, 1),
(2, 'DEFAULTv4', '\0\0\0\0', 0xffffffff, 75, 'stream', -1, 1),
(3, 'DEFAULTv4', '\0\0\0\0', 0xffffffff, 75, 'rpc', -1, 1),
(4, 'DEFAULTv6', '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0', 0xffffffffffffffff, 75, 'interface', -1, 1),
(5, 'DEFAULTv6', '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0', 0xffffffffffffffff, 75, 'stream', -1, 1),
(6, 'DEFAULTv6', '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0', 0xffffffffffffffff, 75, 'rpc', -1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `album`
--

DROP TABLE IF EXISTS `album`;
CREATE TABLE IF NOT EXISTS `album` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `prefix` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `mbid` varchar(36) CHARACTER SET utf8 DEFAULT NULL,
  `year` int(4) unsigned NOT NULL DEFAULT '1984',
  `disk` smallint(5) unsigned DEFAULT NULL,
  `mbid_group` varchar(36) CHARACTER SET utf8 DEFAULT NULL,
  `release_type` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `album_artist` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `year` (`year`),
  KEY `disk` (`disk`),
  FULLTEXT KEY `name_2` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `artist`
--

DROP TABLE IF EXISTS `artist`;
CREATE TABLE IF NOT EXISTS `artist` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `prefix` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `mbid` varchar(1369) CHARACTER SET utf8 DEFAULT NULL,
  `summary` text CHARACTER SET utf8,
  `placeformed` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `yearformed` int(4) DEFAULT NULL,
  `last_update` int(11) unsigned NOT NULL DEFAULT '0',
  `user` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  FULLTEXT KEY `name_2` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `bookmark`
--

DROP TABLE IF EXISTS `bookmark`;
CREATE TABLE IF NOT EXISTS `bookmark` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `position` int(11) unsigned DEFAULT '0' NOT NULL,
  `comment` varchar(255) CHARACTER SET utf8 NOT NULL,
  `object_type` varchar(64) NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `creation_date` int(11) unsigned DEFAULT '0' NOT NULL,
  `update_date` int(11) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `broadcast`
--

DROP TABLE IF EXISTS `broadcast`;
CREATE TABLE IF NOT EXISTS `broadcast` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) unsigned NOT NULL,
  `name` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `description` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `is_private` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `song` int(11) unsigned NOT NULL DEFAULT '0',
  `started` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `listeners` int(11) unsigned NOT NULL DEFAULT '0',
  `key` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `catalog`
--

DROP TABLE IF EXISTS `catalog`;
CREATE TABLE IF NOT EXISTS `catalog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `catalog_type` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `last_update` int(11) unsigned NOT NULL DEFAULT '0',
  `last_clean` int(11) unsigned DEFAULT NULL,
  `last_add` int(11) unsigned NOT NULL DEFAULT '0',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `rename_pattern` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `sort_pattern` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `gather_types` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_local`
--

DROP TABLE IF EXISTS `catalog_local`;
CREATE TABLE IF NOT EXISTS `catalog_local` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `catalog_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_remote`
--

DROP TABLE IF EXISTS `catalog_remote`;
CREATE TABLE IF NOT EXISTS `catalog_remote` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `catalog_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `channel`
--

DROP TABLE IF EXISTS `channel`;
CREATE TABLE IF NOT EXISTS `channel` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `description` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `url` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `interface` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `port` int(11) unsigned NOT NULL DEFAULT '0',
  `fixed_endpoint` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `object_type` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `is_private` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `random` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `loop` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `admin_password` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `start_date` int(11) unsigned NOT NULL DEFAULT '0',
  `max_listeners` int(11) unsigned NOT NULL DEFAULT '0',
  `peak_listeners` int(11) unsigned NOT NULL DEFAULT '0',
  `listeners` int(11) unsigned NOT NULL DEFAULT '0',
  `connections` int(11) unsigned NOT NULL DEFAULT '0',
  `stream_type` varchar(8) CHARACTER SET utf8 DEFAULT NULL,
  `bitrate` int(11) unsigned NOT NULL DEFAULT '128',
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `clip`
--

DROP TABLE IF EXISTS `clip`;
CREATE TABLE IF NOT EXISTS `clip` (
  `id` int(11) unsigned NOT NULL,
  `artist` int(11) DEFAULT NULL,
  `song` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daap_session`
--

DROP TABLE IF EXISTS `daap_session`;
CREATE TABLE IF NOT EXISTS `daap_session` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `creationdate` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `democratic`
--

DROP TABLE IF EXISTS `democratic`;
CREATE TABLE IF NOT EXISTS `democratic` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `cooldown` tinyint(4) unsigned DEFAULT NULL,
  `level` tinyint(4) unsigned NOT NULL DEFAULT '25',
  `user` int(11) NOT NULL,
  `primary` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `base_playlist` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `level` (`level`),
  KEY `primary_2` (`primary`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
CREATE TABLE IF NOT EXISTS `image` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `image` mediumblob,
  `width` int(4) unsigned DEFAULT '0',
  `height` int(4) unsigned DEFAULT '0',
  `mime` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `size` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `object_type` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `kind` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `object_type` (`object_type`),
  KEY `object_id` (`object_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ip_history`
--

DROP TABLE IF EXISTS `ip_history`;
CREATE TABLE IF NOT EXISTS `ip_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `ip` varbinary(255) DEFAULT NULL,
  `date` int(11) unsigned NOT NULL DEFAULT '0',
  `agent` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`user`),
  KEY `date` (`date`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `license`
--

DROP TABLE IF EXISTS `license`;
CREATE TABLE IF NOT EXISTS `license` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) CHARACTER SET utf8 DEFAULT NULL,
  `description` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `external_link` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `license`
--

INSERT INTO `license` (`id`, `name`, `description`, `external_link`) VALUES
(1, '0 - default', NULL, ''),
(2, 'CC BY', NULL, 'https://creativecommons.org/licenses/by/3.0/'),
(3, 'CC BY NC', NULL, 'https://creativecommons.org/licenses/by-nc/3.0/'),
(4, 'CC BY NC ND', NULL, 'https://creativecommons.org/licenses/by-nc-nd/3.0/'),
(5, 'CC BY NC SA', NULL, 'https://creativecommons.org/licenses/by-nc-sa/3.0/'),
(6, 'CC BY ND', NULL, 'https://creativecommons.org/licenses/by-nd/3.0/'),
(7, 'CC BY SA', NULL, 'https://creativecommons.org/licenses/by-sa/3.0/'),
(8, 'Licence Art Libre', NULL, 'http://artlibre.org/licence/lal/'),
(9, 'Yellow OpenMusic', NULL, 'http://openmusic.linuxtag.org/yellow.html'),
(10, 'Green OpenMusic', NULL, 'http://openmusic.linuxtag.org/green.html'),
(11, 'Gnu GPL Art', NULL, 'http://gnuart.org/english/gnugpl.html'),
(12, 'WTFPL', NULL, 'https://en.wikipedia.org/wiki/WTFPL'),
(13, 'FMPL', NULL, 'http://www.fmpl.org/fmpl.html'),
(14, 'C Reaction', NULL, 'http://morne.free.fr/Necktar7/creaction.htm');

-- --------------------------------------------------------

--
-- Table structure for table `live_stream`
--

DROP TABLE IF EXISTS `live_stream`;
CREATE TABLE IF NOT EXISTS `live_stream` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `site_url` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `url` varchar(4096) CHARACTER SET utf8 DEFAULT NULL,
  `genre` int(11) unsigned NOT NULL DEFAULT '0',
  `catalog` int(11) unsigned NOT NULL DEFAULT '0',
  `codec` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `catalog` (`catalog`),
  KEY `genre` (`genre`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `localplay_httpq`
--

DROP TABLE IF EXISTS `localplay_httpq`;
CREATE TABLE IF NOT EXISTS `localplay_httpq` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `owner` int(11) NOT NULL,
  `host` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `port` int(11) unsigned NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `access` smallint(4) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `localplay_mpd`
--

DROP TABLE IF EXISTS `localplay_mpd`;
CREATE TABLE IF NOT EXISTS `localplay_mpd` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `owner` int(11) NOT NULL,
  `host` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `port` int(11) unsigned NOT NULL DEFAULT '6600',
  `password` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `access` smallint(4) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `movie`
--

DROP TABLE IF EXISTS `movie`;
CREATE TABLE IF NOT EXISTS `movie` (
  `id` int(11) unsigned NOT NULL,
  `original_name` varchar(80) CHARACTER SET utf8 DEFAULT NULL,
  `summary` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `year` int(11) unsigned DEFAULT NULL,
  `prefix` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `now_playing`
--

DROP TABLE IF EXISTS `now_playing`;
CREATE TABLE IF NOT EXISTS `now_playing` (
  `id` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `user` int(11) NOT NULL,
  `expire` int(11) unsigned NOT NULL DEFAULT '0',
  `insertion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expire` (`expire`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `object_count`
--

DROP TABLE IF EXISTS `object_count`;
CREATE TABLE IF NOT EXISTS `object_count` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast_episode') CHARACTER SET utf8 DEFAULT NULL,
  `object_id` int(11) unsigned NOT NULL DEFAULT '0',
  `date` int(11) unsigned NOT NULL DEFAULT '0',
  `user` int(11) NOT NULL,
  `agent` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `geo_latitude` decimal(10,6) DEFAULT NULL,
  `geo_longitude` decimal(10,6) DEFAULT NULL,
  `geo_name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `count_type` varchar(16) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `object_type` (`object_type`),
  KEY `object_id` (`object_id`),
  KEY `userid` (`user`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `personal_video`
--

DROP TABLE IF EXISTS `personal_video`;
CREATE TABLE IF NOT EXISTS `personal_video` (
  `id` int(11) unsigned NOT NULL,
  `location` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `summary` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_control`
--

DROP TABLE IF EXISTS `player_control`;
CREATE TABLE IF NOT EXISTS `player_control` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) unsigned NOT NULL,
  `cmd` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `value` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `object_type` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `send_date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `playlist`
--

DROP TABLE IF EXISTS `playlist`;
CREATE TABLE IF NOT EXISTS `playlist` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `type` enum('private','public') CHARACTER SET utf8 DEFAULT NULL,
  `date` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `playlist_data`
--

DROP TABLE IF EXISTS `playlist_data`;
CREATE TABLE IF NOT EXISTS `playlist_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `playlist` int(11) unsigned NOT NULL DEFAULT '0',
  `object_id` int(11) unsigned DEFAULT NULL,
  `object_type` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `track` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `playlist` (`playlist`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `podcast`
--

DROP TABLE IF EXISTS `podcast`;
CREATE TABLE IF NOT EXISTS `podcast` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `feed` varchar(4096) NOT NULL,
  `catalog` int(11) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 NOT NULL,
  `website` varchar(255) NULL,
  `description` varchar(4096) CHARACTER SET utf8 NULL,
  `language` varchar(5) NULL,
  `copyright` varchar(255) NULL,
  `generator` varchar(64) NULL,
  `lastbuilddate` int(11) unsigned DEFAULT '0' NOT NULL,
  `lastsync` int(11) unsigned DEFAULT '0' NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `podcast_episode`
--

DROP TABLE IF EXISTS `podcast_episode`;
CREATE TABLE IF NOT EXISTS `podcast_episode` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 NOT NULL,
  `guid` varchar(255) NOT NULL,
  `podcast` int(11) NOT NULL,
  `state` varchar(32) NOT NULL,
  `file` varchar(4096) CHARACTER SET utf8 NULL,
  `source` varchar(4096) NULL,
  `size` bigint(20) unsigned DEFAULT '0' NOT NULL,
  `time` smallint(5) unsigned DEFAULT '0' NOT NULL,
  `website` varchar(255) NULL,
  `description` varchar(4096) CHARACTER SET utf8 NULL,
  `author` varchar(64) NULL,
  `category` varchar(64) NULL,
  `played` tinyint(1) unsigned DEFAULT '0' NOT NULL,
  `pubdate` int(11) unsigned NOT NULL,
  `addition_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `preference`
--

DROP TABLE IF EXISTS `preference`;
CREATE TABLE IF NOT EXISTS `preference` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `value` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `level` int(11) unsigned NOT NULL DEFAULT '100',
  `type` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `catagory` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `subcatagory` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `catagory` (`catagory`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `preference`
--

INSERT INTO `preference` (`id`, `name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES
(1, 'download', '1', 'Allow Downloads', 100, 'boolean', 'options', 'feature'),
(4, 'popular_threshold', '10', 'Popular Threshold', 25, 'integer', 'interface', 'query'),
(19, 'transcode_bitrate', '64', 'Transcode Bitrate', 25, 'string', 'streaming', 'transcoding'),
(22, 'site_title', 'Ampache :: For the Love of Music', 'Website Title', 100, 'string', 'interface', 'custom'),
(23, 'lock_songs', '0', 'Lock Songs', 100, 'boolean', 'system', null),
(24, 'force_http_play', '0', 'Force HTTP playback regardless of port', 100, 'boolean', 'system', null),
(41, 'localplay_controller', '0', 'Localplay Type', 100, 'special', 'options', 'localplay'),
(29, 'play_type', 'web_player', 'Playback Type', 25, 'special', 'streaming', null),
(31, 'lang', 'en_US', 'Language', 100, 'special', 'interface', null),
(32, 'playlist_type', 'm3u', 'Playlist Type', 100, 'special', 'playlist', null),
(33, 'theme_name', 'reborn', 'Theme', 0, 'special', 'interface', 'theme'),
(51, 'offset_limit', '50', 'Offset Limit', 5, 'integer', 'interface', 'query'),
(40, 'localplay_level', '0', 'Localplay Access', 100, 'special', 'options', 'localplay'),
(44, 'allow_stream_playback', '1', 'Allow Streaming', 100, 'boolean', 'options', 'feature'),
(45, 'allow_democratic_playback', '0', 'Allow Democratic Play', 100, 'boolean', 'options', 'feature'),
(46, 'allow_localplay_playback', '0', 'Allow Localplay Play', 100, 'boolean', 'options', 'localplay'),
(47, 'stats_threshold', '7', 'Statistics Day Threshold', 25, 'integer', 'interface', 'query'),
(52, 'rate_limit', '8192', 'Rate Limit', 100, 'integer', 'streaming', 'transcoding'),
(53, 'playlist_method', 'default', 'Playlist Method', 5, 'string', 'playlist', null),
(55, 'transcode', 'default', 'Allow Transcoding', 25, 'string', 'streaming', 'transcoding'),
(69, 'show_lyrics', '0', 'Show lyrics', 0, 'boolean', 'interface', 'player'),
(70, 'mpd_active', '0', 'MPD Active Instance', 25, 'integer', 'internal', 'mpd'),
(71, 'httpq_active', '0', 'httpQ Active Instance', 25, 'integer', 'internal', 'httpq'),
(77, 'lastfm_grant_link', '', 'Last.FM Grant URL', 25, 'string', 'internal', 'lastfm'),
(78, 'lastfm_challenge', '', 'Last.FM Submit Challenge', 25, 'string', 'internal', 'lastfm'),
(102, 'share', '0', 'Allow Share', 100, 'boolean', 'options', 'feature'),
(123, 'ajax_load', '1', 'Ajax page load', 25, 'boolean', 'interface', null),
(82, 'now_playing_per_user', '1', 'Now Playing filtered per user', 50, 'boolean', 'interface', 'home'),
(83, 'album_sort', '0', 'Album - Default sort', 25, 'string', 'interface', 'library'),
(84, 'show_played_times', '0', 'Show # played', 25, 'string', 'interface', 'library'),
(85, 'song_page_title', '1', 'Show current song in Web Player page title', 25, 'boolean', 'interface', 'player'),
(86, 'subsonic_backend', '1', 'Use Subsonic backend', 100, 'boolean', 'system', 'backend'),
(88, 'webplayer_flash', '1', 'Authorize Flash Web Player', 25, 'boolean', 'streaming', 'player'),
(89, 'webplayer_html5', '1', 'Authorize HTML5 Web Player', 25, 'boolean', 'streaming', 'player'),
(90, 'allow_personal_info_now', '1', 'Share Now Playing information', 25, 'boolean', 'interface', 'privacy'),
(91, 'allow_personal_info_recent', '1', 'Share Recently Played information', 25, 'boolean', 'interface', 'privacy'),
(92, 'allow_personal_info_time', '1', 'Share Recently Played information - Allow access to streaming date/time', 25, 'boolean', 'interface', 'privacy'),
(93, 'allow_personal_info_agent', '1', 'Share Recently Played information - Allow access to streaming agent', 25, 'boolean', 'interface', 'privacy'),
(94, 'ui_fixed', '0', 'Fix header position on compatible themes', 25, 'boolean', 'interface', 'theme'),
(95, 'autoupdate', '1', 'Check for Ampache updates automatically', 25, 'boolean', 'system', 'update'),
(96, 'autoupdate_lastcheck', '', 'AutoUpdate last check time', 25, 'string', 'internal', 'update'),
(97, 'autoupdate_lastversion', '', 'AutoUpdate last version from last check', 25, 'string', 'internal', 'update'),
(98, 'autoupdate_lastversion_new', '', 'AutoUpdate last version from last check is newer', 25, 'boolean', 'internal', 'update'),
(99, 'webplayer_confirmclose', '0', 'Confirmation when closing current playing window', 25, 'boolean', 'interface', 'player'),
(100, 'webplayer_pausetabs', '1', 'Auto-pause between tabs', 25, 'boolean', 'interface', 'player'),
(101, 'stream_beautiful_url', '0', 'Enable URL Rewriting', 100, 'boolean', 'streaming', null),
(103, 'share_expire', '7', 'Share links default expiration days (0=never)', 100, 'integer', 'system', 'share'),
(104, 'slideshow_time', '0', 'Artist slideshow inactivity time', 25, 'integer', 'interface', 'player'),
(105, 'broadcast_by_default', '0', 'Broadcast web player by default', 25, 'boolean', 'streaming', 'player'),
(106, 'concerts_limit_future', '0', 'Limit number of future events', 25, 'integer', 'interface', 'query'),
(107, 'concerts_limit_past', '0', 'Limit number of past events', 25, 'integer', 'interface', 'query'),
(108, 'album_group', '1', 'Album - Group multiple disks', 25, 'boolean', 'interface', 'library'),
(109, 'topmenu', '0', 'Top menu', 25, 'boolean', 'interface', 'theme'),
(110, 'demo_clear_sessions', '0', 'Democratic - Clear votes for expired user sessions', 25, 'boolean', 'playlist', null),
(111, 'show_donate', '1', 'Show donate button in footer', 25, 'boolean', 'interface', null),
(112, 'upload_catalog', '-1', 'Destination catalog', 75, 'integer', 'system', 'upload'),
(113, 'allow_upload', '0', 'Allow user uploads', 75, 'boolean', 'system', 'upload'),
(114, 'upload_subdir', '1', 'Create a subdirectory per user', 75, 'boolean', 'system', 'upload'),
(115, 'upload_user_artist', '0', 'Consider the user sender as the track''s artist', 75, 'boolean', 'system', 'upload'),
(116, 'upload_script', '', 'Post-upload script (current directory = upload target directory)', 75, 'string', 'system', 'upload'),
(117, 'upload_allow_edit', '1', 'Allow users to edit uploaded songs', 75, 'boolean', 'system', 'upload'),
(118, 'daap_backend', '0', 'Use DAAP backend', 100, 'boolean', 'system', 'backend'),
(119, 'daap_pass', '', 'DAAP backend password', 100, 'string', 'system', 'backend'),
(120, 'upnp_backend', '0', 'Use UPnP backend', 100, 'boolean', 'system', 'backend'),
(121, 'allow_video', '0', 'Allow Video Features', 75, 'integer', 'options', 'feature'),
(122, 'album_release_type', '1', 'Album - Group per release type', 25, 'boolean', 'interface', 'library'),
(124, 'direct_play_limit', '0', 'Limit direct play to maximum media count', 25, 'integer', 'interface', 'player'),
(125, 'home_moment_albums', '1', 'Show Albums of the Moment', 25, 'integer', 'interface', 'home'),
(126, 'home_moment_videos', '0', 'Show Videos of the Moment', 25, 'integer', 'interface', 'home'),
(127, 'home_recently_played', '1', 'Show Recently Played', 25, 'integer', 'interface', 'home'),
(128, 'home_now_playing', '1', 'Show Now Playing', 25, 'integer', 'interface', 'home'),
(129, 'custom_logo', '', 'Custom URL - Logo', 25, 'string', 'interface', 'custom'),
(130, 'album_release_type_sort', 'album,ep,live,single', 'Album - Group per release type sort', 25, 'string', 'interface', 'library'),
(131, 'browser_notify', '1', 'Web Player browser notifications', 25, 'integer', 'interface', 'notification'),
(132, 'browser_notify_timeout', '10', 'Web Player browser notifications timeout (seconds)', 25, 'integer', 'interface', 'notification'),
(133, 'geolocation', '0', 'Allow Geolocation', 25, 'integer', 'options', 'feature'),
(134, 'webplayer_aurora', '1', 'Authorize JavaScript decoder (Aurora.js) in Web Player', 25, 'boolean', 'streaming', 'player'),
(135, 'upload_allow_remove', '1', 'Allow users to remove uploaded songs', 75, 'boolean', 'system', 'upload'),
(136, 'custom_login_logo', '', 'Custom URL - Login page logo', 75, 'string', 'interface', 'custom'),
(137, 'custom_favicon', '', 'Custom URL - Favicon', 75, 'string', 'interface', 'custom'),
(138, 'custom_text_footer', '', 'Custom text footer', 75, 'string', 'interface', 'custom'),
(139, 'webdav_backend', '0', 'Use WebDAV backend', 100, 'boolean', 'system', 'backend'),
(140, 'notify_email', '0', 'Allow E-mail notifications', 25, 'boolean', 'options', null),
(141, 'theme_color', 'dark', 'Theme color', 0, 'special', 'interface', 'theme'),
(142, 'disabled_custom_metadata_fields', '', 'Custom metadata - Disable these fields', 100, 'string', 'system', 'metadata'),
(143, 'disabled_custom_metadata_fields_input', '', 'Custom metadata - Define field list', 100, 'string', 'system', 'metadata'),
(144, 'podcast_keep', '0', '# latest episodes to keep', 100, 'integer', 'system', 'podcast'),
(145, 'podcast_new_download', '0', '# episodes to download when new episodes are available', 100, 'integer', 'system', 'podcast');

-- --------------------------------------------------------

--
-- Table structure for table `rating`
--

DROP TABLE IF EXISTS `rating`;
CREATE TABLE IF NOT EXISTS `rating` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `object_type` enum('artist','album','song','stream','video','playlist','tvshow','tvshow_season','podcast','podcast_episode') CHARACTER SET utf8 DEFAULT NULL,
  `object_id` int(11) unsigned NOT NULL DEFAULT '0',
  `rating` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rating` (`user`, `object_type`, `object_id`),
  KEY `object_id` (`object_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `recommendation`
--

DROP TABLE IF EXISTS `recommendation`;
CREATE TABLE IF NOT EXISTS `recommendation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_type` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `last_update` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `recommendation_item`
--

DROP TABLE IF EXISTS `recommendation_item`;
CREATE TABLE IF NOT EXISTS `recommendation_item` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `recommendation` int(11) unsigned NOT NULL,
  `recommendation_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `rel` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `mbid` varchar(1369) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `search`
--

DROP TABLE IF EXISTS `search`;
CREATE TABLE IF NOT EXISTS `search` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `type` enum('private','public') CHARACTER SET utf8 DEFAULT NULL,
  `rules` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `logic_operator` varchar(3) CHARACTER SET utf8 DEFAULT NULL,
  `random` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `limit` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
CREATE TABLE IF NOT EXISTS `session` (
  `id` varchar(256) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `username` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `expire` int(11) unsigned NOT NULL DEFAULT '0',
  `value` longtext COLLATE utf8_unicode_ci NOT NULL,
  `ip` varbinary(255) DEFAULT NULL,
  `type` varchar(16) CHARACTER SET utf8 DEFAULT NULL,
  `agent` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `geo_latitude` decimal(10,6) DEFAULT NULL,
  `geo_longitude` decimal(10,6) DEFAULT NULL,
  `geo_name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expire` (`expire`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `session_remember`
--

DROP TABLE IF EXISTS `session_remember`;
CREATE TABLE IF NOT EXISTS `session_remember` (
  `username` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `token` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `expire` int(11) DEFAULT NULL,
  PRIMARY KEY (`username`, `token`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `session_stream`
--

DROP TABLE IF EXISTS `session_stream`;
CREATE TABLE IF NOT EXISTS `session_stream` (
  `id` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `user` int(11) unsigned NOT NULL,
  `agent` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `expire` int(11) unsigned NOT NULL,
  `ip` varbinary(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `share`
--

DROP TABLE IF EXISTS `share`;
CREATE TABLE IF NOT EXISTS `share` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) unsigned NOT NULL,
  `object_type` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `allow_stream` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `allow_download` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `expire_days` int(4) unsigned NOT NULL DEFAULT '0',
  `max_counter` int(4) unsigned NOT NULL DEFAULT '0',
  `secret` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `counter` int(4) unsigned NOT NULL DEFAULT '0',
  `creation_date` int(11) unsigned NOT NULL DEFAULT '0',
  `lastvisit_date` int(11) unsigned NOT NULL DEFAULT '0',
  `public_url` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `song`
--

DROP TABLE IF EXISTS `song`;
CREATE TABLE IF NOT EXISTS `song` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `file` varchar(4096) CHARACTER SET utf8 DEFAULT NULL,
  `catalog` int(11) unsigned NOT NULL DEFAULT '0',
  `album` int(11) unsigned NOT NULL DEFAULT '0',
  `year` mediumint(4) unsigned NOT NULL DEFAULT '0',
  `artist` int(11) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `bitrate` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `rate` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `mode` enum('abr','vbr','cbr') CHARACTER SET utf8 DEFAULT NULL,
  `size` int(11) unsigned NOT NULL DEFAULT '0',
  `time` smallint(5) unsigned NOT NULL DEFAULT '0',
  `track` smallint(5) unsigned DEFAULT NULL,
  `mbid` varchar(36) CHARACTER SET utf8 DEFAULT NULL,
  `played` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `update_time` int(11) unsigned DEFAULT '0',
  `addition_time` int(11) unsigned DEFAULT '0',
  `user_upload` int(11) DEFAULT NULL,
  `license` int(11) DEFAULT NULL,
  `composer` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `channels` mediumint(9) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `album` (`album`),
  KEY `artist` (`artist`),
  KEY `file` (`file`(333)),
  KEY `update_time` (`update_time`),
  KEY `addition_time` (`addition_time`),
  KEY `catalog` (`catalog`),
  KEY `played` (`played`),
  KEY `enabled` (`enabled`),
  FULLTEXT KEY `title` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `song_data`
--

DROP TABLE IF EXISTS `song_data`;
CREATE TABLE IF NOT EXISTS `song_data` (
  `song_id` int(11) unsigned NOT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  `lyrics` text COLLATE utf8_unicode_ci,
  `label` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `catalog_number` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `language` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `waveform` mediumblob,
  `replaygain_track_gain` decimal(10,6) DEFAULT NULL,
  `replaygain_track_peak` decimal(10,6) DEFAULT NULL,
  `replaygain_album_gain` decimal(10,6) DEFAULT NULL,
  `replaygain_album_peak` decimal(10,6) DEFAULT NULL,
  UNIQUE KEY `song_id` (`song_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `song_preview`
--

DROP TABLE IF EXISTS `song_preview`;
CREATE TABLE IF NOT EXISTS `song_preview` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `session` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `artist` int(11) DEFAULT NULL,
  `artist_mbid` varchar(1369) CHARACTER SET utf8 DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `album_mbid` varchar(36) CHARACTER SET utf8 DEFAULT NULL,
  `mbid` varchar(36) CHARACTER SET utf8 DEFAULT NULL,
  `disk` int(11) DEFAULT NULL,
  `track` int(11) DEFAULT NULL,
  `file` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `stream_playlist`
--

DROP TABLE IF EXISTS `stream_playlist`;
CREATE TABLE IF NOT EXISTS `stream_playlist` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sid` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `url` text COLLATE utf8_unicode_ci NOT NULL,
  `info_url` text COLLATE utf8_unicode_ci,
  `image_url` text COLLATE utf8_unicode_ci,
  `title` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `author` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `album` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `time` smallint(5) DEFAULT NULL,
  `codec` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `track_num` smallint(5) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `sid` (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `name` (`name`),
  KEY `map_id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tag_map`
--

DROP TABLE IF EXISTS `tag_map`;
CREATE TABLE IF NOT EXISTS `tag_map` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) unsigned NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(16) CHARACTER SET utf8 DEFAULT NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tag_map` (`object_id`, `object_type`, `user`, `tag_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tag_merge`
--

DROP TABLE IF EXISTS `tag_merge`;
CREATE TABLE IF NOT EXISTS `tag_merge` (
  `tag_id` int(11) NOT NULL,
  `merged_to` int(11) NOT NULL,
  PRIMARY KEY (`tag_id`, `merged_to`),
  KEY `merged_to` (`merged_to`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tmp_browse`
--

DROP TABLE IF EXISTS `tmp_browse`;
CREATE TABLE IF NOT EXISTS `tmp_browse` (
  `id` int(13) NOT NULL AUTO_INCREMENT,
  `sid` varchar(128) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `data` longtext COLLATE utf8_unicode_ci NOT NULL,
  `object_data` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`sid`, `id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tmp_playlist`
--

DROP TABLE IF EXISTS `tmp_playlist`;
CREATE TABLE IF NOT EXISTS `tmp_playlist` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `session` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `type` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `object_type` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session` (`session`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tmp_playlist_data`
--

DROP TABLE IF EXISTS `tmp_playlist_data`;
CREATE TABLE IF NOT EXISTS `tmp_playlist_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tmp_playlist` int(11) unsigned NOT NULL,
  `object_type` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `track` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tmp_playlist` (`tmp_playlist`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tvshow`
--

DROP TABLE IF EXISTS `tvshow`;
CREATE TABLE IF NOT EXISTS `tvshow` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) CHARACTER SET utf8 DEFAULT NULL,
  `summary` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `year` int(11) unsigned DEFAULT NULL,
  `prefix` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tvshow_episode`
--

DROP TABLE IF EXISTS `tvshow_episode`;
CREATE TABLE IF NOT EXISTS `tvshow_episode` (
  `id` int(11) unsigned NOT NULL,
  `original_name` varchar(80) CHARACTER SET utf8 DEFAULT NULL,
  `season` int(11) unsigned NOT NULL,
  `episode_number` int(11) unsigned NOT NULL,
  `summary` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tvshow_season`
--

DROP TABLE IF EXISTS `tvshow_season`;
CREATE TABLE IF NOT EXISTS `tvshow_season` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `season_number` int(11) unsigned NOT NULL,
  `tvshow` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `update_info`
--

DROP TABLE IF EXISTS `update_info`;
CREATE TABLE IF NOT EXISTS `update_info` (
  `key` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `value` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  UNIQUE KEY `key` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `update_info`
--

INSERT INTO `update_info` (`key`, `value`) VALUES
('db_version', '380004'),
('Plugin_Last.FM', '000005');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `fullname` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `email` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `apikey` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `password` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `access` tinyint(4) unsigned NOT NULL,
  `disabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `last_seen` int(11) unsigned NOT NULL DEFAULT '0',
  `create_date` int(11) unsigned DEFAULT NULL,
  `validation` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `state` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `city` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `fullname_public` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_catalog`
--

DROP TABLE IF EXISTS `user_catalog`;
CREATE TABLE IF NOT EXISTS `user_catalog` (
  `user` int(11) unsigned NOT NULL,
  `catalog` int(11) unsigned NOT NULL,
  `level` smallint(4) unsigned NOT NULL DEFAULT '5',
  KEY `user` (`user`),
  KEY `catalog` (`catalog`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_flag`
--

DROP TABLE IF EXISTS `user_flag`;
CREATE TABLE IF NOT EXISTS `user_flag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_userflag` (`user`, `object_type`, `object_id`),
  KEY `object_id` (`object_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_preference`
--

DROP TABLE IF EXISTS `user_preference`;
CREATE TABLE IF NOT EXISTS `user_preference` (
  `user` int(11) NOT NULL,
  `preference` int(11) unsigned NOT NULL DEFAULT '0',
  `value` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  KEY `user` (`user`),
  KEY `preference` (`preference`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `user_preference`
--

INSERT INTO `user_preference` (`user`, `preference`, `value`) VALUES
(-1, 1, '1'),
(-1, 4, '10'),
(-1, 19, '32'),
(-1, 22, 'Ampache :: For the Love of Music'),
(-1, 23, '0'),
(-1, 24, '0'),
(-1, 25, '80'),
(-1, 41, 'mpd'),
(-1, 29, 'web_player'),
(-1, 31, 'en_US'),
(-1, 32, 'm3u'),
(-1, 33, 'reborn'),
(-1, 34, '27'),
(-1, 35, '27'),
(-1, 36, '27'),
(-1, 51, '50'),
(-1, 40, '100'),
(-1, 44, '1'),
(-1, 45, '1'),
(-1, 46, '1'),
(-1, 47, '7'),
(-1, 49, '1'),
(-1, 52, '8192'),
(-1, 53, 'default'),
(-1, 55, 'default'),
(-1, 57, ''),
(-1, 69, '0'),
(-1, 70, '0'),
(-1, 71, '0'),
(-1, 72, '0'),
(-1, 77, ''),
(-1, 78, ''),
(-1, 114, '1'),
(-1, 113, '0'),
(-1, 112, '-1'),
(-1, 111, '1'),
(-1, 110, '0'),
(-1, 109, '0'),
(-1, 108, '0'),
(-1, 107, '0'),
(-1, 106, '0'),
(-1, 105, '0'),
(-1, 104, '0'),
(-1, 103, '7'),
(-1, 102, '0'),
(-1, 101, '0'),
(-1, 100, '1'),
(-1, 99, '0'),
(-1, 95, '1'),
(-1, 94, '0'),
(-1, 93, '1'),
(-1, 92, '1'),
(-1, 91, '1'),
(-1, 90, '1'),
(-1, 89, '1'),
(-1, 88, '1'),
(-1, 87, '0'),
(-1, 86, '1'),
(-1, 85, '1'),
(-1, 84, '0'),
(-1, 83, '0'),
(-1, 79, '50'),
(-1, 80, '50'),
(-1, 82, '1'),
(-1, 81, '1'),
(-1, 115, '0'),
(-1, 116, ''),
(-1, 117, '1'),
(-1, 118, '0'),
(-1, 119, ''),
(-1, 120, '0'),
(-1, 121, '1'),
(-1, 122, '1'),
(-1, 123, '1'),
(-1, 124, '0'),
(-1, 125, '1'),
(-1, 126, '1'),
(-1, 127, '1'),
(-1, 128, '1'),
(-1, 129, ''),
(-1, 130, 'album,ep,live,single'),
(-1, 131, '1'),
(-1, 132, '10'),
(-1, 133, '0'),
(-1, 134, '1'),
(-1, 135, '1'),
(-1, 136, ''),
(-1, 137, ''),
(-1, 138, ''),
(-1, 139, '0'),
(-1, 140, '0'),
(-1, 141, 'dark'),
(-1, 142, ''),
(-1, 143, ''),
(-1, 96, ''),
(-1, 97, ''),
(-1, 98, ''),
(-1, 144, '10'),
(-1, 145, '1');

-- --------------------------------------------------------

--
-- Table structure for table `user_shout`
--

DROP TABLE IF EXISTS `user_shout`;
CREATE TABLE IF NOT EXISTS `user_shout` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  `date` int(11) unsigned NOT NULL,
  `sticky` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(32) CHARACTER SET utf8 DEFAULT NULL,
  `data` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sticky` (`sticky`),
  KEY `date` (`date`),
  KEY `user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_vote`
--

DROP TABLE IF EXISTS `user_vote`;
CREATE TABLE IF NOT EXISTS `user_vote` (
  `user` int(11) unsigned NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `date` int(11) unsigned NOT NULL,
  `sid` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  KEY `user` (`user`),
  KEY `object_id` (`object_id`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `video`
--

DROP TABLE IF EXISTS `video`;
CREATE TABLE IF NOT EXISTS `video` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `file` varchar(4096) CHARACTER SET utf8 DEFAULT NULL,
  `catalog` int(11) unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `video_codec` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `audio_codec` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `resolution_x` mediumint(8) unsigned NOT NULL,
  `resolution_y` mediumint(8) unsigned NOT NULL,
  `time` int(11) unsigned NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `mime` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `addition_time` int(11) unsigned NOT NULL,
  `update_time` int(11) unsigned DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `played` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `release_date` int(11) DEFAULT NULL,
  `channels` mediumint(9) DEFAULT NULL,
  `bitrate` mediumint(8) DEFAULT NULL,
  `video_bitrate` mediumint(8) DEFAULT NULL,
  `display_x` mediumint(8) DEFAULT NULL,
  `display_y` mediumint(8) DEFAULT NULL,
  `frame_rate` float DEFAULT NULL,
  `mode` enum('abr','vbr','cbr') CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file` (`file`(333)),
  KEY `enabled` (`enabled`),
  KEY `title` (`title`),
  KEY `addition_time` (`addition_time`),
  KEY `update_time` (`update_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wanted`
--

DROP TABLE IF EXISTS `wanted`;
CREATE TABLE IF NOT EXISTS `wanted` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `artist` int(11) DEFAULT NULL,
  `artist_mbid` varchar(1369) CHARACTER SET utf8 DEFAULT NULL,
  `mbid` varchar(36) CHARACTER SET utf8 DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `year` int(4) DEFAULT NULL,
  `date` int(11) unsigned NOT NULL DEFAULT '0',
  `accepted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wanted` (`user`, `artist`, `mbid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `label`
--

DROP TABLE IF EXISTS `label`;
CREATE TABLE IF NOT EXISTS `label` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `category` varchar(40) NULL,
  `summary` TEXT CHARACTER SET utf8 NULL,
  `address` varchar(256) NULL,
  `email` varchar(128) NULL,
  `website` varchar(256) NULL,
  `user` int(11) unsigned NULL,
  `creation_date` int(11) unsigned NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `label_asso`
--

DROP TABLE IF EXISTS `label_asso`;
CREATE TABLE IF NOT EXISTS `label_asso` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `label` int(11) unsigned NOT NULL,
  `artist` int(11) unsigned NOT NULL,
  `creation_date` int(11) unsigned NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_pvmsg`
--

DROP TABLE IF EXISTS `user_pvmsg`;
CREATE TABLE IF NOT EXISTS `user_pvmsg` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(80) NOT NULL,
  `message` TEXT CHARACTER SET utf8 NULL,
  `from_user` int(11) unsigned NOT NULL,
  `to_user` int(11) unsigned NOT NULL,
  `is_read` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `creation_date` int(11) unsigned NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_follower`
--

DROP TABLE IF EXISTS `user_follower`;
CREATE TABLE IF NOT EXISTS `user_follower` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) unsigned NOT NULL,
  `follow_user` int(11) unsigned NOT NULL,
  `follow_date` int(11) unsigned  NULL,
  `creation_date` int(11) unsigned NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

DROP TABLE IF EXISTS `user_activity`;
CREATE TABLE IF NOT EXISTS `user_activity` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) unsigned NOT NULL,
  `action` varchar(20) NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `object_type` varchar(32) NOT NULL,
  `activity_date` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `metadata_field`
--

DROP TABLE IF EXISTS `metadata_field`;
CREATE TABLE IF NOT EXISTS `metadata_field` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `public` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `metadata`
--

DROP TABLE IF EXISTS `metadata`;
CREATE TABLE IF NOT EXISTS `metadata` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int(11) unsigned NOT NULL,
  `field` int(11) unsigned NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  KEY `field` (`field`),
  KEY `object_id` (`object_id`),
  KEY `type` (`type`),
  KEY `objecttype` (`object_id`, `type`),
  KEY `objectfield` (`object_id`, `field`, `type`),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;


/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-01-31  09:01:17
