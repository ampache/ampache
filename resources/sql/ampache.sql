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
-- Host: 192.168.1.20
-- Generation Time: Jul 31, 2026 at 01:39 PM
-- Server version: 11.8.6-MariaDB-0+deb13u1 from Debian
-- PHP Version: 8.5.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ampache8`
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
  `subtitle` varchar(64) DEFAULT NULL,
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
  `weight` int(11) NOT NULL DEFAULT 0,
  `last_played` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `year` (`year`),
  KEY `catalog_IDX` (`catalog`) USING BTREE,
  KEY `album_artist_IDX` (`album_artist`) USING BTREE,
  KEY `original_year_IDX` (`original_year`) USING BTREE,
  KEY `release_type_IDX` (`release_type`) USING BTREE,
  KEY `release_status_IDX` (`release_status`) USING BTREE,
  KEY `mbid_IDX` (`mbid`) USING BTREE,
  KEY `mbid_group_IDX` (`mbid_group`) USING BTREE,
  KEY `album_last_played_IDX` (`last_played`)
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
  `weight` int(11) NOT NULL DEFAULT 0,
  `last_played` int(11) UNSIGNED DEFAULT NULL,
  UNIQUE KEY `unique_album_disk` (`album_id`,`disk`,`catalog`),
  KEY `id_index` (`id`),
  KEY `album_id_type_index` (`album_id`,`disk`),
  KEY `id_disk_index` (`id`,`disk`),
  KEY `album_disk_last_played_IDX` (`last_played`)
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
  `weight` int(11) NOT NULL DEFAULT 0,
  `last_played` int(11) UNSIGNED DEFAULT NULL,
  `lastfm_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `artist_last_played_IDX` (`last_played`)
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
  `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
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
  `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
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
-- Table structure for table `collection`
--

DROP TABLE IF EXISTS `collection`;
CREATE TABLE IF NOT EXISTS `collection` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `username` varchar(128) DEFAULT NULL,
  `type` enum('private','public') DEFAULT 'private',
  `object_type` varchar(16) DEFAULT NULL,
  `date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `last_update` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `last_count` int(11) DEFAULT NULL,
  `collaborate` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collection_map`
--

DROP TABLE IF EXISTS `collection_map`;
CREATE TABLE IF NOT EXISTS `collection_map` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `collection` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `object_type` varchar(16) NOT NULL,
  `track` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `collection_track_IDX` (`collection`,`track`),
  KEY `object_type_id_IDX` (`object_type`,`object_id`),
  KEY `collection_object_IDX` (`collection`,`object_type`,`object_id`)
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
-- Table structure for table `folder`
--

DROP TABLE IF EXISTS `folder`;
CREATE TABLE IF NOT EXISTS `folder` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `catalog` int(11) NOT NULL DEFAULT 0,
  `parent` int(11) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `update_time` int(11) UNSIGNED DEFAULT 0,
  `addition_time` int(11) UNSIGNED DEFAULT 0,
  `playable` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `object_count` int(11) UNSIGNED DEFAULT 0,
  `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `path` varchar(255) DEFAULT NULL,
  `path_name` varchar(512) DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `folder_catalog_IDX` (`catalog`,`path_name`),
  KEY `catalog` (`catalog`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `folder_map`
--

DROP TABLE IF EXISTS `folder_map`;
CREATE TABLE IF NOT EXISTS `folder_map` (
  `folder_id` int(11) UNSIGNED DEFAULT NULL,
  `object_id` int(11) UNSIGNED NOT NULL,
  `object_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `catalog` int(11) NOT NULL DEFAULT 0,
  `path_name` varchar(512) DEFAULT NULL,
  UNIQUE KEY `unique_folder_map` (`object_id`,`object_type`,`folder_id`),
  KEY `folder_catalog_IDX` (`catalog`,`path_name`),
  KEY `object_id_index` (`object_id`),
  KEY `folder_id_type_index` (`folder_id`,`object_type`),
  KEY `object_id_type_index` (`object_id`,`object_type`),
  KEY `object_type_IDX` (`object_type`) USING BTREE,
  KEY `object_type_id_IDX` (`object_type`,`object_id`) USING BTREE
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
  `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
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
  `artist` int(11) UNSIGNED DEFAULT NULL,
  `creation_date` int(11) UNSIGNED DEFAULT NULL,
  `album` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `label_asso_label_IDX` (`label`) USING BTREE,
  KEY `label_asso_album_IDX` (`album`)
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
  `position_ms` int(11) UNSIGNED DEFAULT NULL,
  `playback_rate` float DEFAULT NULL,
  `state` varchar(16) DEFAULT NULL,
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
  `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
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
  KEY `object_id` (`object_id`),
  KEY `userid` (`user`),
  KEY `object_count_date_IDX` (`date`,`count_type`) USING BTREE,
  KEY `object_count_user_IDX` (`object_type`,`object_id`,`user`,`count_type`) USING BTREE,
  KEY `object_type_date_IDX` (`object_type`,`date`) USING BTREE,
  KEY `object_count_idx_count_type_date_id` (`count_type`,`object_type`,`date`,`object_id`) USING BTREE,
  KEY `object_count_idx_count_type_id` (`count_type`,`object_type`,`object_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `object_count_archive`
--

DROP TABLE IF EXISTS `object_count_archive`;
CREATE TABLE IF NOT EXISTS `object_count_archive` (
  `object_type` enum('album','album_disk','artist','catalog','collection','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `date` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `user` int(11) NOT NULL,
  `agent` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `geo_latitude` decimal(10,6) DEFAULT NULL,
  `geo_longitude` decimal(10,6) DEFAULT NULL,
  `geo_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `count_type` enum('download','stream','skip') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  KEY `object_count_archive_IDX` (`object_type`,`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED;

-- --------------------------------------------------------

--
-- Table structure for table `object_count_summary`
--

DROP TABLE IF EXISTS `object_count_summary`;
CREATE TABLE IF NOT EXISTS `object_count_summary` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_type` enum('album','album_disk','artist','catalog','collection','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') NOT NULL,
  `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `user` int(11) NOT NULL,
  `count_type` enum('download','stream','skip') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `date_from` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `date_to` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `object_count_summary_UNIQUE_IDX` (`object_type`,`object_id`,`user`,`count_type`),
  KEY `object_count_summary_type_IDX` (`object_type`,`count_type`)
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
  `weight` int(11) NOT NULL DEFAULT 0,
  `last_played` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `podcast_last_played_IDX` (`last_played`)
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
  `weight` int(11) NOT NULL DEFAULT 0,
  `last_played` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `podcast_episode_last_played_IDX` (`last_played`)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Preference defaults are applied by the installer, not dumped here.
-- InstallationHelper::install_insert_db() calls Preference::set_defaults() and
-- Preference::translate_db(), which populate the `preference` and `user_preference`
-- (-1 system default) rows from the application code (the single source of truth).
--

-- --------------------------------------------------------

--
-- Table structure for table `rating`
--

DROP TABLE IF EXISTS `rating`;
CREATE TABLE IF NOT EXISTS `rating` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
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
  `weight` int(11) NOT NULL DEFAULT 0,
  `last_played` int(11) UNSIGNED DEFAULT NULL,
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
  KEY `album_disk_IDX` (`album_disk`) USING BTREE,
  KEY `song_last_played_IDX` (`last_played`)
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
  `bpm` decimal(6,2) DEFAULT NULL,
  UNIQUE KEY `song_id` (`song_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `song_map`
--

DROP TABLE IF EXISTS `song_map`;
CREATE TABLE IF NOT EXISTS `song_map` (
  `song_id` int(11) UNSIGNED NOT NULL,
  `object_id` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `object_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  UNIQUE KEY `unique_song_map` (`object_id`,`object_type`,`song_id`),
  KEY `object_id_index` (`object_id`),
  KEY `song_id_type_index` (`song_id`,`object_type`),
  KEY `object_id_type_index` (`object_id`,`object_type`)
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
-- `db_version` is the migration version THIS FILE was dumped at, not the current one. The installer reads it and runs
-- every later migration, so the dump may lag the code and only has to be refreshed at release (`bin/cli
-- admin:exportSchema`). Never raise it by hand: a value above what the schema below actually contains makes the
-- updater see nothing pending and skip those migrations forever.
-- Installed plugins set their own `Plugin_*` version rows via Plugin::set_plugin_version().
--

INSERT INTO `update_info` (`key`, `value`) VALUES
('db_version', '800036');

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
  `subsonic_secret` varchar(255) DEFAULT NULL,
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
  `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
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
-- user_preference (-1) default rows are applied by the installer (see the note above).
--

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
  `weight` int(11) NOT NULL DEFAULT 0,
  `last_played` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file` (`file`(333)),
  KEY `enabled` (`enabled`),
  KEY `title` (`title`),
  KEY `addition_time` (`addition_time`),
  KEY `update_time` (`update_time`),
  KEY `video_last_played_IDX` (`last_played`)
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
