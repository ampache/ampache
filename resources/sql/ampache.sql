-- GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
-- Copyright Ampache.org, 2001-2024
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU Affero General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU Affero General Public License for more details.
--
-- You should have received a copy of the GNU Affero General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.
-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 192.168.1.9
-- Generation Time: Jun 10, 2025 at 10:08 AM
-- Server version: 11.8.1-MariaDB-5 from Debian
-- PHP Version: 8.4.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ampache7`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_list`
--

DROP TABLE IF EXISTS `access_list`;
CREATE TABLE IF NOT EXISTS `access_list` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `start` varbinary(255) NOT NULL,
  `end` varbinary(255) NOT NULL,
  `level` smallint(3) UNSIGNED NOT NULL DEFAULT 5,
  `type` varchar(64) DEFAULT NULL,
  `user` int(11) NOT NULL,
  `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `start` (`start`),
  KEY `end` (`end`),
  KEY `level` (`level`),
  KEY `enabled` (`enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `access_list`
--

INSERT INTO `access_list` (`id`, `name`, `start`, `end`, `level`, `type`, `user`, `enabled`) VALUES
(1, 'DEFAULTv4', 0x00000000, 0xffffffff, 75, 'interface', -1, 1),
(2, 'DEFAULTv4', 0x00000000, 0xffffffff, 75, 'stream', -1, 1),
(3, 'DEFAULTv4', 0x00000000, 0xffffffff, 75, 'rpc', -1, 1),
(4, 'DEFAULTv6', 0x00000000000000000000000000000000, 0xffffffffffffffff, 75, 'interface', -1, 1),
(5, 'DEFAULTv6', 0x00000000000000000000000000000000, 0xffffffffffffffff, 75, 'stream', -1, 1),
(6, 'DEFAULTv6', 0x00000000000000000000000000000000, 0xffffffffffffffff, 75, 'rpc', -1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `album`
--

DROP TABLE IF EXISTS `album`;
CREATE TABLE IF NOT EXISTS `album` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `prefix` varchar(32) DEFAULT NULL,
  `mbid` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `year` int(4) UNSIGNED NOT NULL DEFAULT 1984,
  `disk_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `mbid_group` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `release_type` varchar(32) DEFAULT NULL,
  `album_artist` int(11) UNSIGNED DEFAULT NULL,
  `original_year` int(4) DEFAULT NULL,
  `barcode` varchar(64) DEFAULT NULL,
  `catalog_number` varchar(64) DEFAULT NULL,
  `version` varchar(64) DEFAULT NULL,
  `time` bigint(20) UNSIGNED DEFAULT NULL,
  `release_status` varchar(32) DEFAULT NULL,
  `addition_time` int(11) UNSIGNED DEFAULT 0,
  `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `song_count` smallint(5) UNSIGNED DEFAULT 0,
  `artist_count` smallint(5) UNSIGNED DEFAULT 0,
  `song_artist_count` smallint(5) UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `year` (`year`),
  KEY `catalog_IDX` (`catalog`) USING BTREE,
  KEY `album_artist_IDX` (`album_artist`) USING BTREE,
  KEY `original_year_IDX` (`original_year`) USING BTREE,
  KEY `release_type_IDX` (`release_type`) USING BTREE,
  KEY `release_status_IDX` (`release_status`) USING BTREE,
  KEY `mbid_IDX` (`mbid`) USING BTREE,
  KEY `mbid_group_IDX` (`mbid_group`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `album_disk`
--

DROP TABLE IF EXISTS `album_disk`;
CREATE TABLE IF NOT EXISTS `album_disk` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `album_id` int(11) UNSIGNED NOT NULL,
  `disk` int(11) UNSIGNED NOT NULL,
  `disk_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `time` bigint(20) UNSIGNED DEFAULT NULL,
  `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `song_count` smallint(5) UNSIGNED DEFAULT 0,
  `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `disksubtitle` varchar(255) DEFAULT NULL,
  UNIQUE KEY `unique_album_disk` (`album_id`,`disk`,`catalog`),
  KEY `id_index` (`id`),
  KEY `album_id_type_index` (`album_id`,`disk`),
  KEY `id_disk_index` (`id`,`disk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `album_map`
--

DROP TABLE IF EXISTS `album_map`;
CREATE TABLE IF NOT EXISTS `album_map` (
  `album_id` int(11) UNSIGNED NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `object_type` varchar(16) DEFAULT NULL,
  UNIQUE KEY `unique_album_map` (`object_id`,`object_type`,`album_id`),
  KEY `object_id_index` (`object_id`),
  KEY `album_id_type_index` (`album_id`,`object_type`),
  KEY `object_id_type_index` (`object_id`,`object_type`),
  KEY `object_type_IDX` (`object_type`) USING BTREE,
  KEY `object_type_id_IDX` (`object_type`,`object_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `artist`
--

DROP TABLE IF EXISTS `artist`;
CREATE TABLE IF NOT EXISTS `artist` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `prefix` varchar(32) DEFAULT NULL,
  `mbid` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `placeformed` varchar(64) DEFAULT NULL,
  `yearformed` int(4) DEFAULT NULL,
  `last_update` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `user` int(11) DEFAULT NULL,
  `manual_update` smallint(1) DEFAULT 0,
  `time` int(11) UNSIGNED DEFAULT NULL,
  `song_count` smallint(5) UNSIGNED DEFAULT 0,
  `album_count` smallint(5) UNSIGNED DEFAULT 0,
  `album_disk_count` smallint(5) UNSIGNED DEFAULT 0,
  `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `addition_time` int(11) UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `artist_map`
--

DROP TABLE IF EXISTS `artist_map`;
CREATE TABLE IF NOT EXISTS `artist_map` (
  `artist_id` int(11) UNSIGNED NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `object_type` varchar(16) DEFAULT NULL,
  UNIQUE KEY `unique_artist_map` (`object_id`,`object_type`,`artist_id`),
  KEY `object_id_index` (`object_id`),
  KEY `artist_id_index` (`artist_id`),
  KEY `artist_id_type_index` (`artist_id`,`object_type`),
  KEY `object_id_type_index` (`object_id`,`object_type`),
  KEY `artist_id_object_type_id_IDX` (`artist_id`,`object_type`,`object_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookmark`
--

DROP TABLE IF EXISTS `bookmark`;
CREATE TABLE IF NOT EXISTS `bookmark` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `position` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `comment` varchar(255) DEFAULT NULL,
  `object_type` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `creation_date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `broadcast`
--

DROP TABLE IF EXISTS `broadcast`;
CREATE TABLE IF NOT EXISTS `broadcast` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `name` varchar(64) DEFAULT NULL,
  `description` varchar(256) DEFAULT NULL,
  `is_private` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `song` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `started` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `listeners` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `key` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_object_count`
--

DROP TABLE IF EXISTS `cache_object_count`;
CREATE TABLE IF NOT EXISTS `cache_object_count` (
  `object_id` int(11) UNSIGNED NOT NULL,
  `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `threshold` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `count_type` enum('download','stream','skip') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`object_id`,`object_type`,`threshold`,`count_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_object_count_run`
--

DROP TABLE IF EXISTS `cache_object_count_run`;
CREATE TABLE IF NOT EXISTS `cache_object_count_run` (
  `object_id` int(11) UNSIGNED NOT NULL,
  `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `threshold` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `count_type` enum('download','stream','skip') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`object_id`,`object_type`,`threshold`,`count_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catalog`
--

DROP TABLE IF EXISTS `catalog`;
CREATE TABLE IF NOT EXISTS `catalog` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `catalog_type` varchar(128) DEFAULT NULL,
  `last_update` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `last_clean` int(11) UNSIGNED DEFAULT NULL,
  `last_add` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `rename_pattern` varchar(255) DEFAULT NULL,
  `sort_pattern` varchar(255) DEFAULT NULL,
  `gather_types` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_filter_group`
--

DROP TABLE IF EXISTS `catalog_filter_group`;
CREATE TABLE IF NOT EXISTS `catalog_filter_group` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_filter_group_map`
--

DROP TABLE IF EXISTS `catalog_filter_group_map`;
CREATE TABLE IF NOT EXISTS `catalog_filter_group_map` (
  `group_id` int(11) UNSIGNED NOT NULL,
  `catalog_id` int(11) UNSIGNED NOT NULL,
  `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `group_id` (`group_id`,`catalog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_local`
--

DROP TABLE IF EXISTS `catalog_local`;
CREATE TABLE IF NOT EXISTS `catalog_local` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `path` varchar(255) DEFAULT NULL,
  `catalog_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_map`
--

DROP TABLE IF EXISTS `catalog_map`;
CREATE TABLE IF NOT EXISTS `catalog_map` (
  `catalog_id` int(11) UNSIGNED NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `object_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  UNIQUE KEY `unique_catalog_map` (`object_id`,`object_type`,`catalog_id`),
  KEY `object_type_IDX` (`object_type`) USING BTREE,
  KEY `catalog_id_object_type_IDX` (`catalog_id`,`object_type`) USING BTREE,
  KEY `catalog_id_object_id_IDX` (`catalog_id`,`object_id`) USING BTREE,
  KEY `catalog_id_object_type_id_IDX` (`catalog_id`,`object_type`,`object_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_remote`
--

DROP TABLE IF EXISTS `catalog_remote`;
CREATE TABLE IF NOT EXISTS `catalog_remote` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) DEFAULT NULL,
  `username` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `catalog_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daap_session`
--

DROP TABLE IF EXISTS `daap_session`;
CREATE TABLE IF NOT EXISTS `daap_session` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `creationdate` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_podcast_episode`
--

DROP TABLE IF EXISTS `deleted_podcast_episode`;
CREATE TABLE IF NOT EXISTS `deleted_podcast_episode` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `addition_time` int(11) UNSIGNED NOT NULL,
  `delete_time` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file` varchar(4096) DEFAULT NULL,
  `catalog` int(11) UNSIGNED NOT NULL,
  `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `podcast` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_song`
--

DROP TABLE IF EXISTS `deleted_song`;
CREATE TABLE IF NOT EXISTS `deleted_song` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `addition_time` int(11) UNSIGNED DEFAULT 0,
  `delete_time` int(11) UNSIGNED DEFAULT 0,
  `title` varchar(255) DEFAULT NULL,
  `file` varchar(4096) DEFAULT NULL,
  `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int(11) UNSIGNED DEFAULT 0,
  `album` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `artist` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_video`
--

DROP TABLE IF EXISTS `deleted_video`;
CREATE TABLE IF NOT EXISTS `deleted_video` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `addition_time` int(11) UNSIGNED NOT NULL,
  `delete_time` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file` varchar(4096) DEFAULT NULL,
  `catalog` int(11) UNSIGNED NOT NULL,
  `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `democratic`
--

DROP TABLE IF EXISTS `democratic`;
CREATE TABLE IF NOT EXISTS `democratic` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `cooldown` int(11) UNSIGNED DEFAULT NULL,
  `level` tinyint(4) UNSIGNED NOT NULL DEFAULT 25,
  `user` int(11) NOT NULL,
  `primary` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `base_playlist` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `level` (`level`),
  KEY `primary_2` (`primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
CREATE TABLE IF NOT EXISTS `image` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `image` mediumblob DEFAULT NULL,
  `width` int(4) UNSIGNED DEFAULT 0,
  `height` int(4) UNSIGNED DEFAULT 0,
  `mime` varchar(64) DEFAULT NULL,
  `size` varchar(64) DEFAULT NULL,
  `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `kind` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_image` (`width`,`height`,`mime`,`size`,`object_type`,`object_id`,`kind`),
  KEY `object_type` (`object_type`),
  KEY `object_id` (`object_id`),
  KEY `object_type_size_kind_IDX` (`object_type`,`size`,`kind`) USING BTREE,
  KEY `object_type_size_mime_IDX` (`object_type`,`size`,`mime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ip_history`
--

DROP TABLE IF EXISTS `ip_history`;
CREATE TABLE IF NOT EXISTS `ip_history` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `ip` varbinary(255) DEFAULT NULL,
  `date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `agent` varchar(255) DEFAULT NULL,
  `action` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`user`),
  KEY `date` (`date`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `label`
--

DROP TABLE IF EXISTS `label`;
CREATE TABLE IF NOT EXISTS `label` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(80) DEFAULT NULL,
  `category` varchar(40) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `address` varchar(256) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `website` varchar(256) DEFAULT NULL,
  `user` int(11) UNSIGNED DEFAULT NULL,
  `creation_date` int(11) UNSIGNED DEFAULT NULL,
  `mbid` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `country` varchar(64) DEFAULT NULL,
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `label_asso`
--

DROP TABLE IF EXISTS `label_asso`;
CREATE TABLE IF NOT EXISTS `label_asso` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` int(11) UNSIGNED NOT NULL,
  `artist` int(11) UNSIGNED NOT NULL,
  `creation_date` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `label_asso_label_IDX` (`label`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `license`
--

DROP TABLE IF EXISTS `license`;
CREATE TABLE IF NOT EXISTS `license` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(80) DEFAULT NULL,
  `description` varchar(256) DEFAULT NULL,
  `external_link` varchar(256) DEFAULT NULL,
  `order` smallint(4) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `license`
--

INSERT INTO `license` (`id`, `name`, `description`, `external_link`, `order`) VALUES
(1, '0 - default', NULL, '', 1),
(2, 'CC BY 3.0', NULL, 'https://creativecommons.org/licenses/by/3.0/', 2),
(3, 'CC BY NC 3.0', NULL, 'https://creativecommons.org/licenses/by-nc/3.0/', 3),
(4, 'CC BY NC ND 3.0', NULL, 'https://creativecommons.org/licenses/by-nc-nd/3.0/', 4),
(5, 'CC BY NC SA 3.0', NULL, 'https://creativecommons.org/licenses/by-nc-sa/3.0/', 5),
(6, 'CC BY ND 3.0', NULL, 'https://creativecommons.org/licenses/by-nd/3.0/', 6),
(7, 'CC BY SA 3.0', NULL, 'https://creativecommons.org/licenses/by-sa/3.0/', 7),
(8, 'Licence Art Libre', NULL, 'http://artlibre.org/licence/lal/', 8),
(9, 'Yellow OpenMusic', NULL, 'http://openmusic.linuxtag.org/yellow.html', 9),
(10, 'Green OpenMusic', NULL, 'http://openmusic.linuxtag.org/green.html', 10),
(11, 'Gnu GPL Art', NULL, 'http://gnuart.org/english/gnugpl.html', 11),
(12, 'WTFPL', NULL, 'https://en.wikipedia.org/wiki/WTFPL', 12),
(13, 'FMPL', NULL, 'http://www.ram.org/ramblings/philosophy/fmp/fmpl/fmpl.html', 13),
(14, 'C Reaction', NULL, 'http://morne.free.fr/Necktar7/creaction.htm', 14),
(15, 'CC BY', NULL, 'https://creativecommons.org/licenses/by/4.0/', 15),
(16, 'CC BY NC', NULL, 'https://creativecommons.org/licenses/by-nc/4.0/', 16),
(17, 'CC BY NC ND', NULL, 'https://creativecommons.org/licenses/by-nc-nd/4.0/', 17),
(18, 'CC BY NC SA', NULL, 'https://creativecommons.org/licenses/by-nc-sa/4.0/', 18),
(19, 'CC BY ND', NULL, 'https://creativecommons.org/licenses/by-nd/4.0/', 19),
(20, 'CC BY SA', NULL, 'https://creativecommons.org/licenses/by-sa/4.0/', 20);

-- --------------------------------------------------------

--
-- Table structure for table `live_stream`
--

DROP TABLE IF EXISTS `live_stream`;
CREATE TABLE IF NOT EXISTS `live_stream` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `site_url` varchar(255) DEFAULT NULL,
  `url` varchar(4096) DEFAULT NULL,
  `genre` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `codec` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `catalog` (`catalog`),
  KEY `genre` (`genre`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `localplay_httpq`
--

DROP TABLE IF EXISTS `localplay_httpq`;
CREATE TABLE IF NOT EXISTS `localplay_httpq` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `owner` int(11) NOT NULL,
  `host` varchar(255) DEFAULT NULL,
  `port` int(11) UNSIGNED NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `access` smallint(4) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `localplay_mpd`
--

DROP TABLE IF EXISTS `localplay_mpd`;
CREATE TABLE IF NOT EXISTS `localplay_mpd` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `owner` int(11) NOT NULL,
  `host` varchar(255) DEFAULT NULL,
  `port` int(11) UNSIGNED NOT NULL DEFAULT 6600,
  `password` varchar(255) DEFAULT NULL,
  `access` smallint(4) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `metadata`
--

DROP TABLE IF EXISTS `metadata`;
CREATE TABLE IF NOT EXISTS `metadata` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_id` int(11) UNSIGNED NOT NULL,
  `field` int(11) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `field` (`field`),
  KEY `object_id` (`object_id`),
  KEY `type` (`type`),
  KEY `objecttype` (`object_id`,`type`),
  KEY `objectfield` (`object_id`,`field`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `metadata_field`
--

DROP TABLE IF EXISTS `metadata_field`;
CREATE TABLE IF NOT EXISTS `metadata_field` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `public` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `now_playing`
--

DROP TABLE IF EXISTS `now_playing`;
CREATE TABLE IF NOT EXISTS `now_playing` (
  `id` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `object_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `user` int(11) NOT NULL,
  `expire` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `insertion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `object_count`
--

DROP TABLE IF EXISTS `object_count`;
CREATE TABLE IF NOT EXISTS `object_count` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `user` int(11) NOT NULL,
  `agent` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `geo_latitude` decimal(10,6) DEFAULT NULL,
  `geo_longitude` decimal(10,6) DEFAULT NULL,
  `geo_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `count_type` enum('download','stream','skip') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `object_count_UNIQUE_IDX` (`object_type`,`object_id`,`date`,`user`,`agent`,`count_type`) USING BTREE,
  KEY `object_type` (`object_type`),
  KEY `object_id` (`object_id`),
  KEY `userid` (`user`),
  KEY `date` (`date`),
  KEY `object_count_full_index` (`object_type`,`object_id`,`date`,`user`,`agent`,`count_type`) USING BTREE,
  KEY `object_count_type_IDX` (`object_type`,`object_id`) USING BTREE,
  KEY `object_count_date_IDX` (`date`,`count_type`) USING BTREE,
  KEY `object_count_user_IDX` (`object_type`,`object_id`,`user`,`count_type`) USING BTREE,
  KEY `object_type_date_IDX` (`object_type`,`date`) USING BTREE,
  KEY `object_count_idx_count_type_date_id` (`count_type`,`object_type`,`date`,`object_id`) USING BTREE,
  KEY `object_count_idx_count_type_id` (`count_type`,`object_type`,`object_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_control`
--

DROP TABLE IF EXISTS `player_control`;
CREATE TABLE IF NOT EXISTS `player_control` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `cmd` varchar(32) DEFAULT NULL,
  `value` varchar(256) DEFAULT NULL,
  `object_type` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `send_date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `playlist`
--

DROP TABLE IF EXISTS `playlist`;
CREATE TABLE IF NOT EXISTS `playlist` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `type` enum('private','public') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `date` int(11) UNSIGNED NOT NULL,
  `last_update` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `last_duration` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `username` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `last_count` int(11) DEFAULT NULL,
  `collaborate` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `playlist_data`
--

DROP TABLE IF EXISTS `playlist_data`;
CREATE TABLE IF NOT EXISTS `playlist_data` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `playlist` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `object_id` int(11) UNSIGNED DEFAULT NULL,
  `object_type` enum('broadcast','democratic','live_stream','podcast_episode','song','song_preview','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `track` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `playlist` (`playlist`),
  KEY `playlist_object_type_IDX` (`playlist`,`object_type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `podcast`
--

DROP TABLE IF EXISTS `podcast`;
CREATE TABLE IF NOT EXISTS `podcast` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `feed` varchar(4096) DEFAULT NULL,
  `catalog` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` varchar(4096) DEFAULT NULL,
  `language` varchar(5) DEFAULT NULL,
  `copyright` varchar(255) DEFAULT NULL,
  `generator` varchar(128) DEFAULT NULL,
  `lastbuilddate` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `lastsync` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `episodes` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `podcast_episode`
--

DROP TABLE IF EXISTS `podcast_episode`;
CREATE TABLE IF NOT EXISTS `podcast_episode` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `guid` varchar(255) DEFAULT NULL,
  `podcast` int(11) NOT NULL,
  `state` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `file` varchar(4096) DEFAULT NULL,
  `source` varchar(4096) DEFAULT NULL,
  `size` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `website` varchar(255) DEFAULT NULL,
  `description` varchar(4096) DEFAULT NULL,
  `author` varchar(64) DEFAULT NULL,
  `category` varchar(64) DEFAULT NULL,
  `played` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `pubdate` int(11) UNSIGNED NOT NULL,
  `addition_time` int(11) UNSIGNED NOT NULL,
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `bitrate` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
  `rate` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
  `mode` enum('abr','vbr','cbr') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `channels` mediumint(9) DEFAULT NULL,
  `waveform` mediumblob DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `preference`
--

DROP TABLE IF EXISTS `preference`;
CREATE TABLE IF NOT EXISTS `preference` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `level` int(11) UNSIGNED NOT NULL DEFAULT 100,
  `type` varchar(128) DEFAULT NULL,
  `category` varchar(128) DEFAULT NULL,
  `subcategory` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `preference_UN` (`name`),
  KEY `category` (`category`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=232 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `preference`
--

INSERT INTO `preference` (`id`, `name`, `value`, `description`, `level`, `type`, `category`, `subcategory`) VALUES
(1, 'download', '1', 'Allow Downloads', 100, 'boolean', 'options', 'feature'),
(4, 'popular_threshold', '10', 'Popular Threshold', 25, 'integer', 'interface', 'query'),
(19, 'transcode_bitrate', '128', 'Transcode Bitrate', 25, 'string', 'streaming', 'transcoding'),
(22, 'site_title', 'Ampache :: For the Love of Music', 'Website Title', 100, 'string', 'interface', 'custom'),
(23, 'lock_songs', '0', 'Lock Songs', 100, 'boolean', 'system', NULL),
(24, 'force_http_play', '0', 'Force HTTP playback regardless of port', 100, 'boolean', 'system', NULL),
(29, 'play_type', 'web_player', 'Playback Type', 25, 'special', 'streaming', NULL),
(31, 'lang', 'en_US', 'Language', 100, 'special', 'interface', NULL),
(32, 'playlist_type', 'm3u', 'Playlist Type', 100, 'special', 'playlist', NULL),
(33, 'theme_name', 'reborn', 'Theme', 0, 'special', 'interface', 'theme'),
(40, 'localplay_level', '0', 'Localplay Access', 100, 'special', 'options', 'localplay'),
(41, 'localplay_controller', '0', 'Localplay Type', 100, 'special', 'options', 'localplay'),
(44, 'allow_stream_playback', '1', 'Allow Streaming', 100, 'boolean', 'options', 'feature'),
(45, 'allow_democratic_playback', '0', 'Allow Democratic Play', 100, 'boolean', 'options', 'feature'),
(46, 'allow_localplay_playback', '0', 'Allow Localplay Play', 100, 'boolean', 'options', 'localplay'),
(47, 'stats_threshold', '7', 'Statistics Day Threshold', 75, 'integer', 'interface', 'query'),
(51, 'offset_limit', '50', 'Offset Limit', 5, 'integer', 'interface', 'query'),
(52, 'rate_limit', '8192', 'Rate Limit', 100, 'integer', 'streaming', 'transcoding'),
(53, 'playlist_method', 'default', 'Playlist Method', 5, 'string', 'playlist', NULL),
(55, 'transcode', 'default', 'Allow Transcoding', 25, 'string', 'streaming', 'transcoding'),
(69, 'show_lyrics', '0', 'Show lyrics', 0, 'boolean', 'interface', 'player'),
(70, 'mpd_active', '0', 'MPD Active Instance', 25, 'integer', 'internal', 'mpd'),
(71, 'httpq_active', '0', 'HTTPQ Active Instance', 25, 'integer', 'internal', 'httpq'),
(77, 'lastfm_grant_link', '', 'Last.FM Grant URL', 25, 'string', 'plugins', 'last.fm'),
(78, 'lastfm_challenge', '', 'Last.FM Submit Challenge', 25, 'string', 'internal', 'last.fm'),
(82, 'now_playing_per_user', '1', 'Now Playing filtered per user', 50, 'boolean', 'interface', 'home'),
(83, 'album_sort', '0', 'Album - Default sort', 25, 'string', 'interface', 'library'),
(84, 'show_played_times', '0', 'Show # played', 25, 'boolean', 'interface', 'browse'),
(85, 'song_page_title', '1', 'Show current song in Web Player page title', 25, 'boolean', 'interface', 'player'),
(86, 'subsonic_backend', '1', 'Use Subsonic backend', 100, 'boolean', 'system', 'backend'),
(88, 'webplayer_flash', '1', 'Authorize Flash Web Player', 25, 'boolean', 'streaming', 'player'),
(89, 'webplayer_html5', '1', 'Authorize HTML5 Web Player', 25, 'boolean', 'streaming', 'player'),
(90, 'allow_personal_info_now', '1', 'Share Now Playing information', 25, 'boolean', 'interface', 'privacy'),
(91, 'allow_personal_info_recent', '1', 'Share Recently Played information', 25, 'boolean', 'interface', 'privacy'),
(92, 'allow_personal_info_time', '1', 'Share Recently Played information - Allow access to streaming date/time', 25, 'boolean', 'interface', 'privacy'),
(93, 'allow_personal_info_agent', '1', 'Share Recently Played information - Allow access to streaming agent', 25, 'boolean', 'interface', 'privacy'),
(94, 'ui_fixed', '0', 'Fix header position on compatible themes', 25, 'boolean', 'interface', 'theme'),
(95, 'autoupdate', '1', 'Check for Ampache updates automatically', 100, 'boolean', 'system', 'update'),
(96, 'autoupdate_lastcheck', '', 'AutoUpdate last check time', 25, 'string', 'internal', 'update'),
(97, 'autoupdate_lastversion', '', 'AutoUpdate last version from last check', 25, 'string', 'internal', 'update'),
(98, 'autoupdate_lastversion_new', '', 'AutoUpdate last version from last check is newer', 25, 'boolean', 'internal', 'update'),
(99, 'webplayer_confirmclose', '0', 'Confirmation when closing current playing window', 25, 'boolean', 'interface', 'player'),
(100, 'webplayer_pausetabs', '1', 'Auto-pause between tabs', 25, 'boolean', 'interface', 'player'),
(101, 'stream_beautiful_url', '0', 'Enable URL Rewriting', 100, 'boolean', 'streaming', NULL),
(102, 'share', '0', 'Allow Share', 100, 'boolean', 'options', 'feature'),
(103, 'share_expire', '7', 'Share links default expiration days (0=never)', 100, 'integer', 'system', 'share'),
(104, 'slideshow_time', '0', 'Artist slideshow inactivity time', 25, 'integer', 'interface', 'player'),
(105, 'broadcast_by_default', '0', 'Broadcast web player by default', 25, 'boolean', 'streaming', 'player'),
(108, 'album_group', '1', 'Album - Group multiple disks', 25, 'boolean', 'interface', 'library'),
(109, 'topmenu', '0', 'Top menu', 25, 'boolean', 'interface', 'theme'),
(110, 'demo_clear_sessions', '0', 'Democratic - Clear votes for expired user sessions', 25, 'boolean', 'playlist', NULL),
(111, 'show_donate', '1', 'Show donate button in footer', 25, 'boolean', 'interface', NULL),
(112, 'upload_catalog', '-1', 'Destination catalog', 100, 'integer', 'options', 'upload'),
(113, 'allow_upload', '0', 'Allow user uploads', 100, 'boolean', 'system', 'upload'),
(114, 'upload_subdir', '1', 'Create a subdirectory per user', 100, 'boolean', 'system', 'upload'),
(115, 'upload_user_artist', '0', 'Consider the user sender as the track\'s artist', 100, 'boolean', 'system', 'upload'),
(116, 'upload_script', '', 'Post-upload script (current directory = upload target directory)', 100, 'string', 'system', 'upload'),
(117, 'upload_allow_edit', '1', 'Allow users to edit uploaded songs', 100, 'boolean', 'system', 'upload'),
(118, 'daap_backend', '0', 'Use DAAP backend', 100, 'boolean', 'system', 'backend'),
(119, 'daap_pass', '', 'DAAP backend password', 100, 'string', 'system', 'backend'),
(120, 'upnp_backend', '0', 'Use UPnP backend', 100, 'boolean', 'system', 'backend'),
(121, 'allow_video', '0', 'Allow Video Features', 75, 'boolean', 'options', 'feature'),
(122, 'album_release_type', '1', 'Album - Group per release type', 25, 'boolean', 'interface', 'library'),
(123, 'ajax_load', '1', 'Ajax page load', 25, 'boolean', 'interface', NULL),
(124, 'direct_play_limit', '0', 'Limit direct play to maximum media count', 25, 'integer', 'interface', 'player'),
(125, 'home_moment_albums', '1', 'Show Albums of the Moment', 25, 'boolean', 'interface', 'home'),
(126, 'home_moment_videos', '0', 'Show Videos of the Moment', 25, 'boolean', 'interface', 'home'),
(127, 'home_recently_played', '1', 'Show Recently Played', 25, 'boolean', 'interface', 'home'),
(128, 'home_now_playing', '1', 'Show Now Playing', 25, 'boolean', 'interface', 'home'),
(129, 'custom_logo', '', 'Custom URL - Logo', 25, 'string', 'interface', 'custom'),
(130, 'album_release_type_sort', 'album,ep,live,single', 'Album - Group per release type sort', 25, 'string', 'interface', 'library'),
(131, 'browser_notify', '1', 'Web Player browser notifications', 25, 'boolean', 'interface', 'notification'),
(132, 'browser_notify_timeout', '10', 'Web Player browser notifications timeout (seconds)', 25, 'integer', 'interface', 'notification'),
(133, 'geolocation', '0', 'Allow Geolocation', 25, 'boolean', 'options', 'feature'),
(134, 'webplayer_aurora', '1', 'Authorize JavaScript decoder (Aurora.js) in Web Player', 25, 'boolean', 'streaming', 'player'),
(135, 'upload_allow_remove', '1', 'Allow users to remove uploaded songs', 100, 'boolean', 'system', 'upload'),
(136, 'custom_login_logo', '', 'Custom URL - Login page logo', 75, 'string', 'interface', 'custom'),
(137, 'custom_favicon', '', 'Custom URL - Favicon', 75, 'string', 'interface', 'custom'),
(138, 'custom_text_footer', '', 'Custom text footer', 75, 'string', 'system', 'interface'),
(139, 'webdav_backend', '0', 'Use WebDAV backend', 100, 'boolean', 'system', 'backend'),
(140, 'notify_email', '0', 'Allow E-mail notifications', 25, 'boolean', 'options', NULL),
(141, 'theme_color', 'dark', 'Theme color', 0, 'special', 'interface', 'theme'),
(142, 'disabled_custom_metadata_fields', '', 'Custom metadata - Disable these fields', 100, 'string', 'system', 'metadata'),
(143, 'disabled_custom_metadata_fields_input', '', 'Custom metadata - Define field list', 100, 'string', 'system', 'metadata'),
(144, 'podcast_keep', '0', '# latest episodes to keep', 100, 'integer', 'system', 'podcast'),
(145, 'podcast_new_download', '0', '# episodes to download when new episodes are available', 100, 'integer', 'system', 'podcast'),
(146, 'libitem_contextmenu', '1', 'Library item context menu', 0, 'boolean', 'interface', 'library'),
(147, 'upload_catalog_pattern', '0', 'Rename uploaded file according to catalog pattern', 100, 'boolean', 'system', 'upload'),
(148, 'catalog_check_duplicate', '0', 'Check library item at import time and disable duplicates', 100, 'boolean', 'system', 'catalog'),
(149, 'browse_filter', '0', 'Show filter box on browse', 25, 'boolean', 'interface', 'browse'),
(150, 'sidebar_light', '0', 'Light sidebar by default', 25, 'boolean', 'interface', 'sidebar'),
(151, 'custom_blankalbum', '', 'Custom blank album default image', 75, 'string', 'interface', 'custom'),
(153, 'libitem_browse_alpha', '', 'Alphabet browsing by default for following library items (album,artist,...)', 75, 'string', 'interface', 'browse'),
(154, 'show_skipped_times', '0', 'Show # skipped', 25, 'boolean', 'interface', 'browse'),
(155, 'custom_datetime', '', 'Custom datetime', 25, 'string', 'interface', 'custom'),
(156, 'cron_cache', '0', 'Cache computed SQL data (eg. media hits stats) using a cron', 100, 'boolean', 'system', 'catalog'),
(157, 'unique_playlist', '0', 'Only add unique items to playlists', 25, 'boolean', 'playlist', NULL),
(158, 'of_the_moment', '6', 'Set the amount of items Album/Video of the Moment will display', 25, 'integer', 'interface', 'home'),
(159, 'custom_login_background', '', 'Custom URL - Login page background', 75, 'string', 'interface', 'custom'),
(160, 'show_license', '1', 'Show License', 25, 'boolean', 'interface', 'browse'),
(161, 'use_original_year', '0', 'Browse by Original Year for albums (falls back to Year)', 25, 'boolean', 'interface', 'browse'),
(162, 'hide_single_artist', '0', 'Hide the Song Artist column for Albums with one Artist', 25, 'boolean', 'interface', 'browse'),
(163, 'hide_genres', '0', 'Hide the Genre column in browse table rows', 25, 'boolean', 'interface', 'browse'),
(164, 'subsonic_always_download', '0', 'Force Subsonic streams to download. (Enable scrobble in your client to record stats)', 25, 'boolean', 'options', 'api'),
(165, 'api_enable_3', '1', 'Allow Ampache API3 responses', 25, 'boolean', 'options', 'api'),
(166, 'api_enable_4', '1', 'Allow Ampache API4 responses', 25, 'boolean', 'options', 'api'),
(167, 'api_enable_5', '1', 'Allow Ampache API5 responses', 25, 'boolean', 'options', 'api'),
(168, 'api_force_version', '0', 'Force a specific API response no matter what version you send', 25, 'special', 'options', 'api'),
(169, 'show_playlist_username', '1', 'Show playlist owner username in titles', 25, 'boolean', 'interface', 'browse'),
(170, 'api_hidden_playlists', '', 'Hide playlists in Subsonic and API clients that start with this string', 25, 'string', 'options', 'api'),
(171, 'api_hide_dupe_searches', '0', 'Hide smartlists that match playlist names in Subsonic and API clients', 25, 'boolean', 'options', 'api'),
(172, 'show_album_artist', '1', 'Show \'Album Artists\' link in the main sidebar', 25, 'boolean', 'interface', 'sidebar'),
(173, 'show_artist', '0', 'Show \'Artists\' link in the main sidebar', 25, 'boolean', 'interface', 'sidebar'),
(175, 'demo_use_search', '0', 'Democratic - Use smartlists for base playlist', 100, 'boolean', 'system', NULL),
(176, 'webplayer_removeplayed', '0', 'Remove tracks before the current playlist item in the webplayer when played', 25, 'special', 'streaming', 'player'),
(177, 'api_enable_6', '1', 'Allow Ampache API6 responses', 25, 'boolean', 'options', 'api'),
(178, 'upload_access_level', '25', 'Upload Access Level', 100, 'special', 'system', 'upload'),
(179, 'show_subtitle', '1', 'Show Album subtitle on links (if available)', 25, 'boolean', 'interface', 'browse'),
(180, 'show_original_year', '1', 'Show Album original year on links (if available)', 25, 'boolean', 'interface', 'browse'),
(181, 'show_header_login', '1', 'Show the login / registration links in the site header', 100, 'boolean', 'system', 'interface'),
(182, 'use_play2', '0', 'Use an alternative playback action for streaming if you have issues with playing music', 25, 'boolean', 'streaming', 'player'),
(183, 'custom_timezone', '', 'Custom timezone (Override PHP date.timezone)', 25, 'string', 'interface', 'custom'),
(184, 'bookmark_latest', '0', 'Only keep the latest media bookmark', 25, 'boolean', 'options', NULL),
(185, 'jp_volume', '0.8', 'Default webplayer volume', 25, 'special', 'streaming', 'player'),
(186, 'perpetual_api_session', '0', 'API sessions do not expire', 100, 'boolean', 'system', 'backend'),
(187, 'home_recently_played_all', '1', 'Show all media types in Recently Played', 25, 'boolean', 'interface', 'home'),
(188, 'show_wrapped', '1', 'Enable access to your personal \"Spotify Wrapped\" from your user page', 25, 'boolean', 'interface', 'privacy'),
(189, 'sidebar_hide_switcher', '0', 'Hide sidebar switcher arrows', 25, 'boolean', 'interface', 'sidebar'),
(190, 'sidebar_hide_browse', '0', 'Hide the Browse menu in the sidebar', 25, 'boolean', 'interface', 'sidebar'),
(191, 'sidebar_hide_dashboard', '0', 'Hide the Dashboard menu in the sidebar', 25, 'boolean', 'interface', 'sidebar'),
(192, 'sidebar_hide_video', '0', 'Hide the Video menu in the sidebar', 25, 'boolean', 'interface', 'sidebar'),
(193, 'sidebar_hide_search', '0', 'Hide the Search menu in the sidebar', 25, 'boolean', 'interface', 'sidebar'),
(194, 'sidebar_hide_playlist', '0', 'Hide the Playlist menu in the sidebar', 25, 'boolean', 'interface', 'sidebar'),
(195, 'sidebar_hide_information', '0', 'Hide the Information menu in the sidebar', 25, 'boolean', 'interface', 'sidebar'),
(197, 'custom_logo_user', '0', 'Custom URL - Use your avatar for header logo', 25, 'boolean', 'interface', 'custom'),
(198, 'index_dashboard_form', '0', 'Use Dashboard links for the index page header', 25, 'boolean', 'interface', 'home'),
(199, 'sidebar_order_browse', '10', 'Custom CSS Order - Browse', 25, 'integer', 'interface', 'sidebar'),
(200, 'sidebar_order_dashboard', '15', 'Custom CSS Order - Dashboard', 25, 'integer', 'interface', 'sidebar'),
(201, 'sidebar_order_video', '20', 'Custom CSS Order - Video', 25, 'integer', 'interface', 'sidebar'),
(202, 'sidebar_order_playlist', '30', 'Custom CSS Order - Playlist', 25, 'integer', 'interface', 'sidebar'),
(203, 'sidebar_order_search', '40', 'Custom CSS Order - Search', 25, 'integer', 'interface', 'sidebar'),
(204, 'sidebar_order_information', '60', 'Custom CSS Order - Information', 25, 'integer', 'interface', 'sidebar'),
(205, 'api_always_download', '0', 'Force API streams to download. (Enable scrobble in your client to record stats)', 25, 'boolean', 'options', 'api'),
(206, 'external_links_google', '1', 'Show Google search icon on library items', 25, 'boolean', 'interface', 'library'),
(207, 'external_links_duckduckgo', '1', 'Show DuckDuckGo search icon on library items', 25, 'boolean', 'interface', 'library'),
(208, 'external_links_wikipedia', '1', 'Show Wikipedia search icon on library items', 25, 'boolean', 'interface', 'library'),
(209, 'external_links_lastfm', '1', 'Show Last.fm search icon on library items', 25, 'boolean', 'interface', 'library'),
(210, 'external_links_bandcamp', '1', 'Show Bandcamp search icon on library items', 25, 'boolean', 'interface', 'library'),
(211, 'external_links_musicbrainz', '1', 'Show MusicBrainz search icon on library items', 25, 'boolean', 'interface', 'library'),
(212, 'homedash_max_items', '6', 'Home Dashboard max items', 25, 'integer', 'plugins', 'home dashboard'),
(213, 'homedash_random', '1', 'Random', 25, 'boolean', 'plugins', 'home dashboard'),
(214, 'homedash_newest', '0', 'Newest', 25, 'boolean', 'plugins', 'home dashboard'),
(215, 'homedash_recent', '0', 'Recent', 25, 'boolean', 'plugins', 'home dashboard'),
(216, 'homedash_trending', '1', 'Trending', 25, 'boolean', 'plugins', 'home dashboard'),
(217, 'homedash_popular', '0', 'Popular', 25, 'boolean', 'plugins', 'home dashboard'),
(218, 'homedash_order', '0', 'Plugin CSS order', 25, 'integer', 'plugins', 'home dashboard'),
(219, 'extended_playlist_links', '0', 'Show extended links for playlist media', 25, 'boolean', 'playlist', NULL),
(220, 'external_links_discogs', '1', 'Show Discogs search icon on library items', 25, 'boolean', 'interface', 'library'),
(221, 'browse_song_grid_view', '0', 'Force Grid View on Song browse', 25, 'boolean', 'interface', 'cookies'),
(222, 'browse_album_grid_view', '0', 'Force Grid View on Album browse', 25, 'boolean', 'interface', 'cookies'),
(223, 'browse_album_disk_grid_view', '0', 'Force Grid View on Album Disk browse', 25, 'boolean', 'interface', 'cookies'),
(224, 'browse_artist_grid_view', '0', 'Force Grid View on Artist browse', 25, 'boolean', 'interface', 'cookies'),
(225, 'browse_live_stream_grid_view', '0', 'Force Grid View on Radio Station browse', 25, 'boolean', 'interface', 'cookies'),
(226, 'browse_playlist_grid_view', '0', 'Force Grid View on Playlist browse', 25, 'boolean', 'interface', 'cookies'),
(227, 'browse_video_grid_view', '0', 'Force Grid View on Video browse', 25, 'boolean', 'interface', 'cookies'),
(228, 'browse_podcast_grid_view', '0', 'Force Grid View on Podcast browse', 25, 'boolean', 'interface', 'cookies'),
(229, 'browse_podcast_episode_grid_view', '0', 'Force Grid View on Podcast Episode browse', 25, 'boolean', 'interface', 'cookies'),
(230, 'show_playlist_media_parent', '0', 'Show Artist column on playlist media rows', 25, 'boolean', 'playlist', NULL),
(231, 'subsonic_legacy', '1', 'Enable legacy Subsonic API responses for compatibility issues', 25, 'boolean', 'options', 'api');

-- --------------------------------------------------------

--
-- Table structure for table `rating`
--

DROP TABLE IF EXISTS `rating`;
CREATE TABLE IF NOT EXISTS `rating` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `rating` tinyint(4) NOT NULL,
  `date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rating` (`user`,`object_type`,`object_id`),
  KEY `object_id` (`object_id`),
  KEY `user_object_type_IDX` (`user`,`object_type`) USING BTREE,
  KEY `user_object_id_IDX` (`user`,`object_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recommendation`
--

DROP TABLE IF EXISTS `recommendation`;
CREATE TABLE IF NOT EXISTS `recommendation` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_type` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `last_update` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `object_type_object_id_IDX` (`object_type`,`object_id`) USING BTREE,
  KEY `object_type_IDX` (`object_type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recommendation_item`
--

DROP TABLE IF EXISTS `recommendation_item`;
CREATE TABLE IF NOT EXISTS `recommendation_item` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `recommendation` int(11) UNSIGNED NOT NULL,
  `recommendation_id` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(256) DEFAULT NULL,
  `rel` varchar(256) DEFAULT NULL,
  `mbid` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search`
--

DROP TABLE IF EXISTS `search`;
CREATE TABLE IF NOT EXISTS `search` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `type` enum('private','public') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `last_update` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `rules` mediumtext NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `logic_operator` varchar(3) DEFAULT NULL,
  `random` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `limit` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `last_count` int(11) DEFAULT NULL,
  `last_duration` int(11) DEFAULT NULL,
  `username` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `collaborate` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `search`
--

INSERT INTO `search` (`id`, `user`, `type`, `date`, `last_update`, `rules`, `name`, `logic_operator`, `random`, `limit`, `last_count`, `last_duration`, `username`, `collaborate`) VALUES
(5, -1, 'public', 0, 0, '[[\"artistrating\",\"equal\",\"5\",null]]', 'Artist 5*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(6, -1, 'public', 0, 0, '[[\"artistrating\",\"equal\",\"4\",null]]', 'Artist 4*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(7, -1, 'public', 0, 0, '[[\"artistrating\",\"equal\",\"3\",null]]', 'Artist 3*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(8, -1, 'public', 0, 0, '[[\"artistrating\",\"equal\",\"2\",null]]', 'Artist 2*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(9, -1, 'public', 0, 0, '[[\"artistrating\",\"equal\",\"1\",null]]', 'Artist 1*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(10, -1, 'public', 0, 0, '[[\"albumrating\",\"equal\",\"5\",null]]', 'Album 5*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(11, -1, 'public', 0, 0, '[[\"albumrating\",\"equal\",\"4\",null]]', 'Album 4*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(12, -1, 'public', 0, 0, '[[\"albumrating\",\"equal\",\"3\",null]]', 'Album 3*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(13, -1, 'public', 0, 0, '[[\"albumrating\",\"equal\",\"2\",null]]', 'Album 2*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(14, -1, 'public', 0, 0, '[[\"albumrating\",\"equal\",\"1\",null]]', 'Album 1*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(15, -1, 'public', 0, 0, '[[\"myrating\",\"equal\",\"5\",null]]', 'Song 5*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(16, -1, 'public', 0, 0, '[[\"myrating\",\"equal\",\"4\",null]]', 'Song 4*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(17, -1, 'public', 0, 0, '[[\"myrating\",\"equal\",\"3\",null]]', 'Song 3*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(18, -1, 'public', 0, 0, '[[\"myrating\",\"equal\",\"2\",null]]', 'Song 2*', 'AND', 0, 0, NULL, NULL, 'System', NULL),
(19, -1, 'public', 0, 0, '[[\"myrating\",\"equal\",\"1\",null]]', 'Song 1*', 'AND', 0, 0, NULL, NULL, 'System', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
CREATE TABLE IF NOT EXISTS `session` (
  `id` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `username` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `expire` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `value` longtext NOT NULL,
  `ip` varbinary(255) DEFAULT NULL,
  `type` varchar(16) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `geo_latitude` decimal(10,6) DEFAULT NULL,
  `geo_longitude` decimal(10,6) DEFAULT NULL,
  `geo_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expire` (`expire`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `session_remember`
--

DROP TABLE IF EXISTS `session_remember`;
CREATE TABLE IF NOT EXISTS `session_remember` (
  `username` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `token` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `expire` int(11) DEFAULT NULL,
  PRIMARY KEY (`username`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `session_stream`
--

DROP TABLE IF EXISTS `session_stream`;
CREATE TABLE IF NOT EXISTS `session_stream` (
  `id` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user` int(11) NOT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `expire` int(11) UNSIGNED NOT NULL,
  `ip` varbinary(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `share`
--

DROP TABLE IF EXISTS `share`;
CREATE TABLE IF NOT EXISTS `share` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `object_type` enum('album','album_disk','artist','playlist','podcast','podcast_episode','search','song','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `allow_stream` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `allow_download` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `expire_days` int(4) UNSIGNED NOT NULL DEFAULT 0,
  `max_counter` int(4) UNSIGNED NOT NULL DEFAULT 0,
  `secret` varchar(20) DEFAULT NULL,
  `counter` int(4) UNSIGNED NOT NULL DEFAULT 0,
  `creation_date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `lastvisit_date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `public_url` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `song`
--

DROP TABLE IF EXISTS `song`;
CREATE TABLE IF NOT EXISTS `song` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `file` varchar(4096) DEFAULT NULL,
  `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `album` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `album_disk` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `disk` smallint(5) UNSIGNED DEFAULT NULL,
  `year` mediumint(4) UNSIGNED NOT NULL DEFAULT 0,
  `artist` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `title` varchar(255) DEFAULT NULL,
  `bitrate` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
  `rate` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
  `mode` enum('abr','vbr','cbr') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `size` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `track` smallint(6) DEFAULT NULL,
  `mbid` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `played` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `addition_time` int(11) UNSIGNED DEFAULT 0,
  `user_upload` int(11) DEFAULT NULL,
  `license` int(11) DEFAULT NULL,
  `composer` varchar(256) DEFAULT NULL,
  `channels` mediumint(9) DEFAULT NULL,
  `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `album` (`album`),
  KEY `artist` (`artist`),
  KEY `file` (`file`(333)),
  KEY `update_time` (`update_time`),
  KEY `addition_time` (`addition_time`),
  KEY `catalog` (`catalog`),
  KEY `played` (`played`),
  KEY `enabled` (`enabled`),
  KEY `title_enabled_IDX` (`title`,`enabled`) USING BTREE,
  KEY `album_disk_IDX` (`album_disk`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `song_data`
--

DROP TABLE IF EXISTS `song_data`;
CREATE TABLE IF NOT EXISTS `song_data` (
  `song_id` int(11) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `lyrics` text DEFAULT NULL,
  `label` varchar(128) DEFAULT NULL,
  `language` varchar(128) DEFAULT NULL,
  `waveform` mediumblob DEFAULT NULL,
  `replaygain_track_gain` decimal(10,6) DEFAULT NULL,
  `replaygain_track_peak` decimal(10,6) DEFAULT NULL,
  `replaygain_album_gain` decimal(10,6) DEFAULT NULL,
  `replaygain_album_peak` decimal(10,6) DEFAULT NULL,
  `r128_track_gain` smallint(5) DEFAULT NULL,
  `r128_album_gain` smallint(5) DEFAULT NULL,
  `disksubtitle` varchar(255) DEFAULT NULL,
  UNIQUE KEY `song_id` (`song_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `song_preview`
--

DROP TABLE IF EXISTS `song_preview`;
CREATE TABLE IF NOT EXISTS `song_preview` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `session` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `artist` int(11) DEFAULT NULL,
  `artist_mbid` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `album_mbid` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `mbid` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `disk` int(11) DEFAULT NULL,
  `track` int(11) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stream_playlist`
--

DROP TABLE IF EXISTS `stream_playlist`;
CREATE TABLE IF NOT EXISTS `stream_playlist` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sid` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `url` text NOT NULL,
  `info_url` text DEFAULT NULL,
  `image_url` text DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `album` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `time` int(11) DEFAULT NULL,
  `codec` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `track_num` smallint(5) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sid` (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `artist` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `album` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `song` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `video` int(11) UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `name` (`name`),
  KEY `map_id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tag_map`
--

DROP TABLE IF EXISTS `tag_map`;
CREATE TABLE IF NOT EXISTS `tag_map` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) UNSIGNED NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tag_map` (`object_id`,`object_type`,`user`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tag_merge`
--

DROP TABLE IF EXISTS `tag_merge`;
CREATE TABLE IF NOT EXISTS `tag_merge` (
  `tag_id` int(11) NOT NULL,
  `merged_to` int(11) NOT NULL,
  PRIMARY KEY (`tag_id`,`merged_to`),
  KEY `merged_to` (`merged_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tmp_browse`
--

DROP TABLE IF EXISTS `tmp_browse`;
CREATE TABLE IF NOT EXISTS `tmp_browse` (
  `id` int(13) NOT NULL AUTO_INCREMENT,
  `sid` varchar(128) NOT NULL,
  `data` longtext NOT NULL,
  `object_data` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tmp_browse_id_sid_IDX` (`sid`,`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tmp_playlist`
--

DROP TABLE IF EXISTS `tmp_playlist`;
CREATE TABLE IF NOT EXISTS `tmp_playlist` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `session` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `type` varchar(32) DEFAULT NULL,
  `object_type` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session` (`session`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tmp_playlist_data`
--

DROP TABLE IF EXISTS `tmp_playlist_data`;
CREATE TABLE IF NOT EXISTS `tmp_playlist_data` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tmp_playlist` int(11) UNSIGNED NOT NULL,
  `object_type` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `track` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tmp_playlist` (`tmp_playlist`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `update_info`
--

DROP TABLE IF EXISTS `update_info`;
CREATE TABLE IF NOT EXISTS `update_info` (
  `key` varchar(128) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `update_info`
--

INSERT INTO `update_info` (`key`, `value`) VALUES
('db_version', '760001'),
('Plugin_Last.FM', '000005'),
('Plugin_Home Dashboard', '2');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `apikey` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `password` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `access` tinyint(4) UNSIGNED NOT NULL,
  `disabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `last_seen` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `create_date` int(11) UNSIGNED DEFAULT NULL,
  `validation` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `state` varchar(64) DEFAULT NULL,
  `city` varchar(64) DEFAULT NULL,
  `fullname_public` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `rsstoken` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `streamtoken` varchar(255) DEFAULT NULL,
  `catalog_filter_group` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

DROP TABLE IF EXISTS `user_activity`;
CREATE TABLE IF NOT EXISTS `user_activity` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `action` varchar(20) DEFAULT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `activity_date` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_object_type_IDX` (`user`,`object_type`) USING BTREE,
  KEY `user_object_id_IDX` (`user`,`object_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_data`
--

DROP TABLE IF EXISTS `user_data`;
CREATE TABLE IF NOT EXISTS `user_data` (
  `user` int(11) DEFAULT NULL,
  `key` varchar(128) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  UNIQUE KEY `unique_data` (`user`,`key`),
  KEY `user` (`user`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_flag`
--

DROP TABLE IF EXISTS `user_flag`;
CREATE TABLE IF NOT EXISTS `user_flag` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `object_type` enum('album','album_disk','artist','catalog','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_userflag` (`user`,`object_type`,`object_id`),
  KEY `object_id` (`object_id`),
  KEY `user_object_type_IDX` (`user`,`object_type`) USING BTREE,
  KEY `user_object_id_IDX` (`user`,`object_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_follower`
--

DROP TABLE IF EXISTS `user_follower`;
CREATE TABLE IF NOT EXISTS `user_follower` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `follow_user` int(11) UNSIGNED NOT NULL,
  `follow_date` int(11) UNSIGNED DEFAULT NULL,
  `creation_date` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_playlist`
--

DROP TABLE IF EXISTS `user_playlist`;
CREATE TABLE IF NOT EXISTS `user_playlist` (
  `playqueue_time` int(11) UNSIGNED NOT NULL,
  `playqueue_client` varchar(255) NOT NULL,
  `user` int(11) NOT NULL DEFAULT 0,
  `object_type` enum('song','live_stream','video','podcast_episode') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `track` smallint(6) UNSIGNED NOT NULL DEFAULT 0,
  `current_track` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `current_time` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`playqueue_time`,`playqueue_client`,`user`,`track`),
  KEY `user` (`user`),
  KEY `object_type` (`object_type`),
  KEY `object_id` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_playlist_map`
--

DROP TABLE IF EXISTS `user_playlist_map`;
CREATE TABLE IF NOT EXISTS `user_playlist_map` (
  `playlist_id` varchar(16) DEFAULT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  UNIQUE KEY `playlist_user` (`playlist_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preference`
--

DROP TABLE IF EXISTS `user_preference`;
CREATE TABLE IF NOT EXISTS `user_preference` (
  `user` int(11) NOT NULL,
  `preference` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  UNIQUE KEY `unique_name` (`user`,`name`),
  KEY `user` (`user`),
  KEY `preference` (`preference`),
  KEY `user_name_IDX` (`user`,`name`) USING BTREE,
  KEY `user_preference_IDX` (`user`,`preference`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_preference`
--

INSERT INTO `user_preference` (`user`, `preference`, `name`, `value`) VALUES
(-1, 1, 'download', '1'),
(-1, 4, 'popular_threshold', '10'),
(-1, 19, 'transcode_bitrate', '32'),
(-1, 22, 'site_title', 'Ampache :: For the Love of Music'),
(-1, 23, 'lock_songs', '0'),
(-1, 24, 'force_http_play', '0'),
(-1, 29, 'play_type', 'web_player'),
(-1, 31, 'lang', 'en_US'),
(-1, 32, 'playlist_type', 'm3u'),
(-1, 33, 'theme_name', 'reborn'),
(-1, 40, 'localplay_level', '100'),
(-1, 41, 'localplay_controller', 'mpd'),
(-1, 44, 'allow_stream_playback', '1'),
(-1, 45, 'allow_democratic_playback', '1'),
(-1, 46, 'allow_localplay_playback', '1'),
(-1, 47, 'stats_threshold', '7'),
(-1, 51, 'offset_limit', '50'),
(-1, 52, 'rate_limit', '8192'),
(-1, 53, 'playlist_method', 'default'),
(-1, 55, 'transcode', 'default'),
(-1, 69, 'show_lyrics', '0'),
(-1, 70, 'mpd_active', '0'),
(-1, 71, 'httpq_active', '0'),
(-1, 77, 'lastfm_grant_link', ''),
(-1, 78, 'lastfm_challenge', ''),
(-1, 82, 'now_playing_per_user', '1'),
(-1, 83, 'album_sort', '0'),
(-1, 84, 'show_played_times', '0'),
(-1, 85, 'song_page_title', '1'),
(-1, 86, 'subsonic_backend', '1'),
(-1, 88, 'webplayer_flash', '1'),
(-1, 89, 'webplayer_html5', '1'),
(-1, 90, 'allow_personal_info_now', '1'),
(-1, 91, 'allow_personal_info_recent', '1'),
(-1, 92, 'allow_personal_info_time', '1'),
(-1, 93, 'allow_personal_info_agent', '1'),
(-1, 94, 'ui_fixed', '0'),
(-1, 95, 'autoupdate', '1'),
(-1, 96, 'autoupdate_lastcheck', ''),
(-1, 97, 'autoupdate_lastversion', ''),
(-1, 98, 'autoupdate_lastversion_new', ''),
(-1, 99, 'webplayer_confirmclose', '0'),
(-1, 100, 'webplayer_pausetabs', '1'),
(-1, 101, 'stream_beautiful_url', '0'),
(-1, 102, 'share', '0'),
(-1, 103, 'share_expire', '7'),
(-1, 104, 'slideshow_time', '0'),
(-1, 105, 'broadcast_by_default', '0'),
(-1, 108, 'album_group', '1'),
(-1, 109, 'topmenu', '0'),
(-1, 110, 'demo_clear_sessions', '0'),
(-1, 111, 'show_donate', '1'),
(-1, 112, 'upload_catalog', '-1'),
(-1, 113, 'allow_upload', '0'),
(-1, 114, 'upload_subdir', '1'),
(-1, 115, 'upload_user_artist', '0'),
(-1, 116, 'upload_script', ''),
(-1, 117, 'upload_allow_edit', '1'),
(-1, 118, 'daap_backend', '0'),
(-1, 119, 'daap_pass', ''),
(-1, 120, 'upnp_backend', '0'),
(-1, 121, 'allow_video', '0'),
(-1, 122, 'album_release_type', '1'),
(-1, 123, 'ajax_load', '1'),
(-1, 124, 'direct_play_limit', '0'),
(-1, 125, 'home_moment_albums', '0'),
(-1, 126, 'home_moment_videos', '0'),
(-1, 127, 'home_recently_played', '1'),
(-1, 128, 'home_now_playing', '1'),
(-1, 129, 'custom_logo', ''),
(-1, 130, 'album_release_type_sort', 'album,ep,live,single'),
(-1, 131, 'browser_notify', '1'),
(-1, 132, 'browser_notify_timeout', '10'),
(-1, 133, 'geolocation', '0'),
(-1, 134, 'webplayer_aurora', '1'),
(-1, 135, 'upload_allow_remove', '1'),
(-1, 136, 'custom_login_logo', ''),
(-1, 137, 'custom_favicon', ''),
(-1, 138, 'custom_text_footer', ''),
(-1, 139, 'webdav_backend', '0'),
(-1, 140, 'notify_email', '0'),
(-1, 141, 'theme_color', 'dark'),
(-1, 142, 'disabled_custom_metadata_fields', ''),
(-1, 143, 'disabled_custom_metadata_fields_input', ''),
(-1, 144, 'podcast_keep', '10'),
(-1, 145, 'podcast_new_download', '1'),
(-1, 146, 'libitem_contextmenu', '1'),
(-1, 147, 'upload_catalog_pattern', '0'),
(-1, 148, 'catalog_check_duplicate', '0'),
(-1, 149, 'browse_filter', '0'),
(-1, 150, 'sidebar_light', '0'),
(-1, 151, 'custom_blankalbum', ''),
(-1, 153, 'libitem_browse_alpha', ''),
(-1, 154, 'show_skipped_times', '0'),
(-1, 155, 'custom_datetime', ''),
(-1, 156, 'cron_cache', '0'),
(-1, 157, 'unique_playlist', ''),
(-1, 158, 'of_the_moment', '6'),
(-1, 159, 'custom_login_background', ''),
(-1, 160, 'show_license', '1'),
(-1, 161, 'use_original_year', '0'),
(-1, 162, 'hide_single_artist', '0'),
(-1, 163, 'hide_genres', '0'),
(-1, 164, 'subsonic_always_download', '0'),
(-1, 165, 'api_enable_3', '1'),
(-1, 166, 'api_enable_4', '1'),
(-1, 167, 'api_enable_5', '1'),
(-1, 168, 'api_force_version', '0'),
(-1, 169, 'show_playlist_username', '0'),
(-1, 170, 'api_hidden_playlists', ''),
(-1, 171, 'api_hide_dupe_searches', '0'),
(-1, 172, 'show_album_artist', '0'),
(-1, 173, 'show_artist', '1'),
(-1, 175, 'demo_use_search', '0'),
(-1, 176, 'webplayer_removeplayed', '0'),
(-1, 177, 'api_enable_6', '1'),
(-1, 178, 'upload_access_level', '25'),
(-1, 179, 'show_subtitle', '1'),
(-1, 180, 'show_original_year', '1'),
(-1, 181, 'show_header_login', '1'),
(-1, 182, 'use_play2', '0'),
(-1, 183, 'custom_timezone', ''),
(-1, 184, 'bookmark_latest', '0'),
(-1, 185, 'jp_volume', '0.8'),
(-1, 186, 'perpetual_api_session', '0'),
(-1, 187, 'home_recently_played_all', '1'),
(-1, 188, 'show_wrapped', '1'),
(-1, 189, 'sidebar_hide_switcher', '0'),
(-1, 190, 'sidebar_hide_browse', '0'),
(-1, 191, 'sidebar_hide_dashboard', '0'),
(-1, 192, 'sidebar_hide_video', '0'),
(-1, 193, 'sidebar_hide_search', '0'),
(-1, 194, 'sidebar_hide_playlist', '0'),
(-1, 195, 'sidebar_hide_information', '0'),
(-1, 197, 'custom_logo_user', '0'),
(-1, 198, 'index_dashboard_form', '0'),
(-1, 199, 'sidebar_order_browse', '10'),
(-1, 200, 'sidebar_order_dashboard', '15'),
(-1, 201, 'sidebar_order_video', '20'),
(-1, 202, 'sidebar_order_playlist', '30'),
(-1, 203, 'sidebar_order_search', '40'),
(-1, 204, 'sidebar_order_information', '60'),
(-1, 205, 'api_always_download', '0'),
(-1, 206, 'external_links_google', '1'),
(-1, 207, 'external_links_duckduckgo', '1'),
(-1, 208, 'external_links_wikipedia', '1'),
(-1, 209, 'external_links_lastfm', '1'),
(-1, 210, 'external_links_bandcamp', '1'),
(-1, 211, 'external_links_musicbrainz', '1'),
(-1, 212, 'homedash_max_items', '6'),
(-1, 213, 'homedash_random', '1'),
(-1, 214, 'homedash_newest', '0'),
(-1, 215, 'homedash_recent', '0'),
(-1, 216, 'homedash_trending', '1'),
(-1, 217, 'homedash_popular', '0'),
(-1, 218, 'homedash_order', '0'),
(-1, 219, 'extended_playlist_links', '0'),
(-1, 220, 'external_links_discogs', '1'),
(-1, 221, 'browse_song_grid_view', '0'),
(-1, 222, 'browse_album_grid_view', '0'),
(-1, 223, 'browse_album_disk_grid_view', '0'),
(-1, 224, 'browse_artist_grid_view', '0'),
(-1, 225, 'browse_live_stream_grid_view', '0'),
(-1, 226, 'browse_playlist_grid_view', '0'),
(-1, 227, 'browse_video_grid_view', '0'),
(-1, 228, 'browse_podcast_grid_view', '0'),
(-1, 229, 'browse_podcast_episode_grid_view', '0'),
(-1, 230, 'show_playlist_media_parent', '0');

-- --------------------------------------------------------

--
-- Table structure for table `user_pvmsg`
--

DROP TABLE IF EXISTS `user_pvmsg`;
CREATE TABLE IF NOT EXISTS `user_pvmsg` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject` varchar(80) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `from_user` int(11) UNSIGNED NOT NULL,
  `to_user` int(11) UNSIGNED NOT NULL,
  `is_read` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `creation_date` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_shout`
--

DROP TABLE IF EXISTS `user_shout`;
CREATE TABLE IF NOT EXISTS `user_shout` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `text` text NOT NULL,
  `date` int(11) UNSIGNED NOT NULL,
  `sticky` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `object_id` int(11) UNSIGNED NOT NULL,
  `object_type` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `data` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sticky` (`sticky`),
  KEY `date` (`date`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_vote`
--

DROP TABLE IF EXISTS `user_vote`;
CREATE TABLE IF NOT EXISTS `user_vote` (
  `user` int(11) NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `date` int(11) UNSIGNED NOT NULL,
  `sid` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  KEY `user` (`user`),
  KEY `object_id` (`object_id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `video`
--

DROP TABLE IF EXISTS `video`;
CREATE TABLE IF NOT EXISTS `video` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `file` varchar(4096) DEFAULT NULL,
  `catalog` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `video_codec` varchar(255) DEFAULT NULL,
  `audio_codec` varchar(255) DEFAULT NULL,
  `resolution_x` mediumint(8) UNSIGNED NOT NULL,
  `resolution_y` mediumint(8) UNSIGNED NOT NULL,
  `time` int(11) UNSIGNED NOT NULL,
  `size` bigint(20) UNSIGNED NOT NULL,
  `mime` varchar(255) DEFAULT NULL,
  `addition_time` int(11) UNSIGNED NOT NULL,
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `played` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `release_date` int(11) DEFAULT NULL,
  `channels` mediumint(9) DEFAULT NULL,
  `bitrate` mediumint(8) DEFAULT NULL,
  `video_bitrate` int(11) UNSIGNED DEFAULT NULL,
  `display_x` mediumint(8) DEFAULT NULL,
  `display_y` mediumint(8) DEFAULT NULL,
  `frame_rate` float DEFAULT NULL,
  `mode` enum('abr','vbr','cbr') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `file` (`file`(333)),
  KEY `enabled` (`enabled`),
  KEY `title` (`title`),
  KEY `addition_time` (`addition_time`),
  KEY `update_time` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wanted`
--

DROP TABLE IF EXISTS `wanted`;
CREATE TABLE IF NOT EXISTS `wanted` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `artist` int(11) DEFAULT NULL,
  `artist_mbid` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `mbid` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `year` int(4) DEFAULT NULL,
  `date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `accepted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wanted` (`user`,`artist`,`mbid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `album`
--
ALTER TABLE `album` ADD FULLTEXT KEY `name_2` (`name`);

--
-- Indexes for table `artist`
--
ALTER TABLE `artist` ADD FULLTEXT KEY `name_2` (`name`);

--
-- Indexes for table `song`
--
ALTER TABLE `song` ADD FULLTEXT KEY `title` (`title`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
