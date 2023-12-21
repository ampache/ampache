<?php

/** @noinspection PhpUnusedPrivateMethodInspection */
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=0);

namespace Ampache\Module\System;

use Ampache\Config\AmpConfig;
use Ampache\Config\Init\Exception\EnvironmentNotSuitableException;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ahc\Cli\IO\Interactor;

/**
 * Update Class
 *
 * This class mainly handles schema updates for the database.
 * Versions are a monotonically increasing integer: First column(s) are the
 * major version, followed by a single column for the minor version and four
 * columns for the build number. 3.6 build 1 is 360000; 10.9 build 17 is
 * 1090017.
 */
class Update
{
    public $key;
    public $value;
    public static $versions; // array containing version information

    /**
     * get_version
     *
     * This checks to see what version you are currently running.
     * Because we may not have the update_info table we have to check
     * for its existence first.
     * @return string
     * @throws EnvironmentNotSuitableException
     */
    private static function _get_db_version(): string
    {
        /* Make sure that update_info exits */
        $sql        = "SHOW TABLES LIKE 'update_info'";
        $db_results = Dba::read($sql);
        if (!Dba::dbh()) {
            throw new EnvironmentNotSuitableException();
        }

        // If no table
        if (!Dba::num_rows($db_results)) {
            // They can't upgrade, they are too old
            throw new EnvironmentNotSuitableException();
        } else {
            // If we've found the update_info table, let's get the version from it
            $sql        = "SELECT `key`, `value` FROM `update_info` WHERE `key`='db_version'";
            $db_results = Dba::read($sql);
            if ($results = Dba::fetch_assoc($db_results)) {
                return $results['value'];
            }
        }
        // now it's really got problems
        throw new EnvironmentNotSuitableException();
    }

    /**
     * check_tables
     *
     * is something missing? why is it missing!?
     * @param bool $execute
     * @return array
     * @throws EnvironmentNotSuitableException
     */
    public static function check_tables(bool $execute = false)
    {
        $db_version = (int)self::_get_db_version();
        $missing    = array();
        $collation  = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset    = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine     = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        $tables     = array(
            'image' => "CREATE TABLE IF NOT EXISTS `image` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `image` mediumblob DEFAULT NULL, `width` int(4) UNSIGNED DEFAULT 0, `height` int(4) UNSIGNED DEFAULT 0, `mime` varchar(64) COLLATE $collation DEFAULT NULL, `size` varchar(64) COLLATE $collation DEFAULT NULL, `object_type` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `kind` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`), KEY `object_type` (`object_type`), KEY `object_id` (`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'tmp_browse' => "CREATE TABLE IF NOT EXISTS `tmp_browse` (`id` int(13) NOT NULL AUTO_INCREMENT, `sid` varchar(128) COLLATE utf8_unicode_ci NOT NULL, `data` longtext COLLATE utf8_unicode_ci NOT NULL, `object_data` longtext COLLATE utf8_unicode_ci DEFAULT NULL, PRIMARY KEY (`sid`, `id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            'search' => "CREATE TABLE IF NOT EXISTS `search` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) NOT NULL, `type` enum('private','public') CHARACTER SET utf8 DEFAULT NULL, `rules` mediumtext NOT NULL, `name` varchar(255) CHARACTER SET $charset DEFAULT NULL, `logic_operator` varchar(3) CHARACTER SET $charset DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine AUTO_INCREMENT=4 DEFAULT CHARSET=$charset;",
            'stream_playlist' => "CREATE TABLE IF NOT EXISTS `stream_playlist` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `sid` varchar(64) NOT NULL, `url` text NOT NULL, `info_url` text DEFAULT NULL, `image_url` text DEFAULT NULL, `title` varchar(255) DEFAULT NULL, `author` varchar(255) DEFAULT NULL, `album` varchar(255) DEFAULT NULL, `type` varchar(255) DEFAULT NULL, `time` smallint(5) DEFAULT NULL, PRIMARY KEY (`id`), KEY `sid` (`sid`));",
            'user_flag' => "CREATE TABLE IF NOT EXISTS `user_flag` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) NOT NULL, `object_id` int(11) unsigned NOT NULL, `object_type` varchar(32) CHARACTER SET $charset DEFAULT NULL, `date` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`), UNIQUE KEY `unique_userflag` (`user`, `object_type`, `object_id`), KEY `object_id` (`object_id`)) ENGINE=$engine;",
            'catalog_local' => "CREATE TABLE IF NOT EXISTS `catalog_local` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `path` varchar(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'catalog_remote' => "CREATE TABLE IF NOT EXISTS `catalog_remote` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `uri` varchar(255) COLLATE $collation NOT NULL, `username` varchar(255) COLLATE $collation NOT NULL, `password` varchar(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'wanted' => "CREATE TABLE IF NOT EXISTS `wanted` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) NOT NULL, `artist` int(11) NOT NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL, `name` varchar(255) CHARACTER SET $charset NOT NULL, `year` int(4) NULL, `date` int(11) unsigned NOT NULL DEFAULT '0', `accepted` tinyint(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), UNIQUE KEY `unique_wanted` (`user`, `artist`, `mbid`)) ENGINE=$engine;",
            'song_preview' => "CREATE TABLE IF NOT EXISTS `song_preview` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `session` varchar(256) CHARACTER SET $charset NOT NULL, `artist` int(11) NOT NULL, `title` varchar(255) CHARACTER SET $charset NOT NULL, `album_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL, `disk` int(11) NULL, `track` int(11) NULL, `file` varchar(255) CHARACTER SET $charset NULL, PRIMARY KEY (`id`)) ENGINE=$engine;",
            'share' => "CREATE TABLE IF NOT EXISTS `share` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `allow_stream` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `allow_download` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `expire_days` int(4) UNSIGNED NOT NULL DEFAULT 0, `max_counter` int(4) UNSIGNED NOT NULL DEFAULT 0, `secret` varchar(20) COLLATE $collation DEFAULT NULL, `counter` int(4) UNSIGNED NOT NULL DEFAULT 0, `creation_date` int(11) UNSIGNED NOT NULL DEFAULT 0, `lastvisit_date` int(11) UNSIGNED NOT NULL DEFAULT 0, `public_url` varchar(255) COLLATE $collation DEFAULT NULL, `description` varchar(255) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'broadcast' => "CREATE TABLE IF NOT EXISTS `broadcast` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `name` varchar(64) COLLATE $collation DEFAULT NULL, `description` varchar(256) COLLATE $collation DEFAULT NULL, `is_private` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `song` int(11) UNSIGNED NOT NULL DEFAULT 0, `started` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `listeners` int(11) UNSIGNED NOT NULL DEFAULT 0, `key` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'player_control' => "CREATE TABLE IF NOT EXISTS `player_control` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `cmd` varchar(32) COLLATE $collation DEFAULT NULL, `value` varchar(256) COLLATE $collation DEFAULT NULL, `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `send_date` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'recommendation' => "CREATE TABLE IF NOT EXISTS `recommendation` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `last_update` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'recommendation_item' => "CREATE TABLE IF NOT EXISTS `recommendation_item` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `recommendation` int(11) UNSIGNED NOT NULL, `recommendation_id` int(11) UNSIGNED DEFAULT NULL, `name` varchar(256) COLLATE $collation DEFAULT NULL, `rel` varchar(256) COLLATE $collation DEFAULT NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'license' => "CREATE TABLE IF NOT EXISTS `license` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(80) COLLATE $collation DEFAULT NULL, `description` varchar(256) COLLATE $collation DEFAULT NULL, `external_link` varchar(256) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine AUTO_INCREMENT=15 DEFAULT CHARSET=$charset COLLATE=$collation;",
            'daap_session' => "CREATE TABLE IF NOT EXISTS `daap_session` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `creationdate` int(11) UNSIGNED NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'tvshow' => "CREATE TABLE IF NOT EXISTS `tvshow` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(80) COLLATE $collation DEFAULT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, `year` int(11) UNSIGNED DEFAULT NULL, `prefix` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'tvshow_season' => "CREATE TABLE IF NOT EXISTS `tvshow_season` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `season_number` int(11) UNSIGNED NOT NULL, `tvshow` int(11) UNSIGNED NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'tvshow_episode' => "CREATE TABLE IF NOT EXISTS `tvshow_episode` (`id` int(11) UNSIGNED NOT NULL, `original_name` varchar(80) COLLATE $collation DEFAULT NULL, `season` int(11) UNSIGNED NOT NULL, `episode_number` int(11) UNSIGNED NOT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'movie' => "CREATE TABLE IF NOT EXISTS `movie` (`id` int(11) UNSIGNED NOT NULL, `original_name` varchar(80) COLLATE $collation DEFAULT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, `year` int(11) UNSIGNED DEFAULT NULL, `prefix` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'personal_video' => "CREATE TABLE IF NOT EXISTS `personal_video` (`id` int(11) UNSIGNED NOT NULL, `location` varchar(256) COLLATE $collation DEFAULT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'clip' => "CREATE TABLE IF NOT EXISTS `clip` (`id` int(11) UNSIGNED NOT NULL, `artist` int(11) DEFAULT NULL, `song` int(11) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'session_remember' => "CREATE TABLE IF NOT EXISTS `session_remember` (`username` varchar(16) NOT NULL, `token` varchar(32) NOT NULL, `expire` int(11) NULL, PRIMARY KEY (`username`, `token`)) ENGINE=$engine;",
            'tag_merge' => "CREATE TABLE IF NOT EXISTS `tag_merge` (`tag_id` int(11) NOT NULL, `merged_to` int(11) NOT NULL, PRIMARY KEY (`tag_id`, `merged_to`), KEY `merged_to` (`merged_to`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'label' => "CREATE TABLE IF NOT EXISTS `label` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(80) COLLATE $collation DEFAULT NULL, `category` varchar(40) COLLATE $collation DEFAULT NULL, `summary` text COLLATE $collation DEFAULT NULL, `address` varchar(256) COLLATE $collation DEFAULT NULL, `email` varchar(128) COLLATE $collation DEFAULT NULL, `website` varchar(256) COLLATE $collation DEFAULT NULL, `user` int(11) UNSIGNED DEFAULT NULL, `creation_date` int(11) UNSIGNED DEFAULT NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `country` varchar(64) COLLATE $collation DEFAULT NULL, `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'label_asso' => "CREATE TABLE IF NOT EXISTS `label_asso` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `label` int(11) UNSIGNED NOT NULL, `artist` int(11) UNSIGNED NOT NULL, `creation_date` int(11) UNSIGNED DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'user_pvmsg' => "CREATE TABLE IF NOT EXISTS `user_pvmsg` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `subject` varchar(80) COLLATE $collation DEFAULT NULL, `message` text COLLATE $collation DEFAULT NULL, `from_user` int(11) UNSIGNED NOT NULL, `to_user` int(11) UNSIGNED NOT NULL, `is_read` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `creation_date` int(11) UNSIGNED DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'user_follower' => "CREATE TABLE IF NOT EXISTS `user_follower` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `follow_user` int(11) UNSIGNED NOT NULL, `follow_date` int(11) UNSIGNED DEFAULT NULL, `creation_date` int(11) UNSIGNED DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'user_activity' => "CREATE TABLE IF NOT EXISTS `user_activity` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `user` INT(11) NOT NULL, `action` varchar(20) NOT NULL, `object_id` INT(11) UNSIGNED NOT NULL, `object_type` varchar(32) NOT NULL, `activity_date` INT(11) UNSIGNED NOT NULL) ENGINE=$engine;",
            'metadata_field' => "CREATE TABLE IF NOT EXISTS `metadata_field` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(255) COLLATE $collation DEFAULT NULL, `public` tinyint(1) NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'metadata' => "CREATE TABLE IF NOT EXISTS `metadata` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `object_id` int(11) UNSIGNED NOT NULL, `field` int(11) UNSIGNED NOT NULL, `data` text COLLATE $collation NOT NULL, `type` varchar(50) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`), KEY `field` (`field`), KEY `object_id` (`object_id`), KEY `type` (`type`), KEY `objecttype` (`object_id`, `type`), KEY `objectfield` (`object_id`, `field`, `type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'podcast' => "CREATE TABLE IF NOT EXISTS `podcast` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `feed` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) NOT NULL, `title` varchar(255) COLLATE $collation DEFAULT NULL, `website` varchar(255) COLLATE $collation DEFAULT NULL, `description` varchar(4096) COLLATE $collation DEFAULT NULL, `language` varchar(5) COLLATE $collation DEFAULT NULL, `copyright` varchar(255) COLLATE $collation DEFAULT NULL, `generator` varchar(64) COLLATE $collation DEFAULT NULL, `lastbuilddate` int(11) UNSIGNED NOT NULL DEFAULT 0, `lastsync` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `episodes` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'podcast_episode' => "CREATE TABLE IF NOT EXISTS `podcast_episode` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `title` varchar(255) COLLATE $collation DEFAULT NULL, `guid` varchar(255) COLLATE $collation DEFAULT NULL, `podcast` int(11) NOT NULL, `state` varchar(32) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `source` varchar(4096) COLLATE $collation DEFAULT NULL, `size` bigint(20) UNSIGNED NOT NULL DEFAULT 0, `time` int(11) UNSIGNED NOT NULL DEFAULT 0, `website` varchar(255) COLLATE $collation DEFAULT NULL, `description` varchar(4096) COLLATE $collation DEFAULT NULL, `author` varchar(64) COLLATE $collation DEFAULT NULL, `category` varchar(64) COLLATE $collation DEFAULT NULL, `played` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `pubdate` int(11) UNSIGNED NOT NULL, `addition_time` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0, `waveform` mediumblob DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'bookmark' => "CREATE TABLE IF NOT EXISTS `bookmark` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `user` int(11) UNSIGNED NOT NULL, `position` int(11) UNSIGNED DEFAULT '0' NOT NULL, `comment` varchar(255) CHARACTER SET $charset NOT NULL, `object_type` varchar(64) NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `creation_date` int(11) UNSIGNED DEFAULT '0' NOT NULL, `update_date` int(11) UNSIGNED DEFAULT '0' NOT NULL) ENGINE=$engine;",
            'cache_object_count' => "CREATE TABLE IF NOT EXISTS `cache_object_count` (`object_id` int(11) UNSIGNED NOT NULL, `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `count` int(11) UNSIGNED NOT NULL DEFAULT 0, `threshold` int(11) UNSIGNED NOT NULL DEFAULT 0, `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, PRIMARY KEY (`object_id`, `object_type`, `threshold`, `count_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'cache_object_count_run' => "CREATE TABLE IF NOT EXISTS `cache_object_count_run` (`object_id` int(11) UNSIGNED NOT NULL, `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `count` int(11) UNSIGNED NOT NULL DEFAULT 0, `threshold` int(11) UNSIGNED NOT NULL DEFAULT 0, `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, PRIMARY KEY (`object_id`, `object_type`, `threshold`, `count_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'catalog_map' => "CREATE TABLE IF NOT EXISTS `catalog_map` (`catalog_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_catalog_map` (`object_id`, `object_type`, `catalog_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'user_data' => "CREATE TABLE IF NOT EXISTS `user_data` (`user` int(11) DEFAULT NULL, `key` varchar(128) COLLATE $collation DEFAULT NULL, `value` varchar(255) COLLATE $collation DEFAULT NULL, UNIQUE KEY `unique_data` (`user`, `key`), KEY `user` (`user`), KEY `key` (`key`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'deleted_song' => "CREATE TABLE IF NOT EXISTS `deleted_song` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED DEFAULT 0, `delete_time` int(11) UNSIGNED DEFAULT 0, `title` varchar(255) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, `update_time` int(11) UNSIGNED DEFAULT 0, `album` int(11) UNSIGNED NOT NULL DEFAULT 0, `artist` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'deleted_video' => "CREATE TABLE IF NOT EXISTS `deleted_video` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'deleted_podcast_episode' => "CREATE TABLE IF NOT EXISTS `deleted_podcast_episode` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, `podcast` int(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'artist_map' => "CREATE TABLE IF NOT EXISTS `artist_map` (`artist_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_artist_map` (`object_id`, `object_type`, `artist_id`), KEY `object_id_index` (`object_id`), KEY `artist_id_index` (`artist_id`), KEY `artist_id_type_index` (`artist_id`, `object_type`), KEY `object_id_type_index` (`object_id`, `object_type`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            'album_map' => "CREATE TABLE IF NOT EXISTS `album_map` (`album_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_album_map` (`object_id`, `object_type`, `album_id`), KEY `object_id_index` (`object_id`), KEY `album_id_type_index` (`album_id`, `object_type`), KEY `object_id_type_index` (`object_id`, `object_type`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            'catalog_filter_group' => "CREATE TABLE IF NOT EXISTS `catalog_filter_group` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('DEFAULT'); UPDATE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT'; ALTER TABLE `catalog_filter_group` AUTO_INCREMENT = 1;",
            'catalog_filter_group_map' => "CREATE TABLE IF NOT EXISTS `catalog_filter_group_map` (`group_id` int(11) UNSIGNED NOT NULL, `catalog_id` int(11) UNSIGNED NOT NULL, `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, UNIQUE KEY (group_id,catalog_id)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'album_disk' => "CREATE TABLE IF NOT EXISTS `album_disk` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `album_id` int(11) UNSIGNED NOT NULL, `disk` int(11) UNSIGNED NOT NULL, `disk_count` int(11) unsigned DEFAULT 0 NOT NULL, `time` bigint(20) UNSIGNED DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0, `song_count` smallint(5) UNSIGNED DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, UNIQUE KEY `unique_album_disk` (`album_id`, `disk`, `catalog`), INDEX `id_index` (`id`), INDEX `album_id_type_index` (`album_id`, `disk`), INDEX `id_disk_index` (`id`, `disk`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'user_playlist' => "CREATE TABLE IF NOT EXISTS `user_playlist` (`playqueue_time` int(11) UNSIGNED NOT NULL, `playqueue_client` varchar(255) CHARACTER SET $charset COLLATE $collation, user int(11) DEFAULT 0, `object_type` enum('song','live_stream','video','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci, `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0, `track` smallint(6) UNSIGNED NOT NULL DEFAULT 0, `current_track` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `current_time` smallint(5) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`playqueue_time`, `playqueue_client`, `user`, `track`), KEY `user` (`user`), KEY `object_type` (`object_type`), KEY `object_id` (`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;"
        );
        $versions = array(
            'image' => 360003,
            'tmp_browse' => 360005,
            'search' => 360006,
            'stream_playlist' => 360011,
            'user_flag' => 360017,
            'catalog_local' => 360020,
            'catalog_remote' => 360020,
            'wanted' => 360029,
            'song_preview' => 360030,
            'share' => 360037,
            'broadcast' => 360042,
            'player_control' => 360042,
            'recommendation' => 360044,
            'recommendation_item' => 360044,
            'license' => 370004,
            'daap_session' => 370007,
            'tvshow' => 370009,
            'tvshow_season' => 370009,
            'tvshow_episode' => 370009,
            'movie' => 370009,
            'personal_video' => 370009,
            'clip' => 370009,
            'session_remember' => 370015,
            'tag_merge' => 370018,
            'label' => 370033,
            'label_asso' => 370033,
            'user_pvmsg' => 370034,
            'user_follower' => 370034,
            'user_activity' => 370040,
            'metadata_field' => 370041,
            'metadata' => 370041,
            'podcast' => 380001,
            'podcast_episode' => 380001,
            'bookmark' => 380002,
            'cache_object_count' => 400008,
            'cache_object_count_run' => 400008,
            'catalog_map' => 500004,
            'user_data' => 500006,
            'deleted_song' => 500013,
            'deleted_video' => 500013,
            'deleted_podcast_episode' => 500013,
            'artist_map' => 530000,
            'album_map' => 530001,
            'catalog_filter_group' => 550001,
            'catalog_filter_group_map' => 550001,
            'album_disk' => 600004,
            'user_playlist' => 600018
        );
        foreach ($tables as $table_name => $table_sql) {
            $sql        = "DESCRIBE `$table_name`;";
            $db_results = Dba::read($sql);
            // you might not be at the version required for this table so make sure it actually is missing.
            if (!$db_results && $db_version >= $versions[$table_name]) {
                $missing[] = $table_name;
                if (!$execute) {
                    debug_event(__CLASS__, 'MISSING TABLE: ' . $table_name, 1);
                    continue;
                }
                if (Dba::write($table_sql)) {
                    debug_event(__CLASS__, 'CREATED MISSING TABLE: ' . $table_name, 1);
                }
            }
        }

        return $missing;
    }

    /**
     * format_version
     *
     * Make the version number pretty. (600028 => 6.0.0 Build: 028)
     * @param string $version
     */
    public static function format_version($version = ''): string
    {
        if (empty($version)) {
            $version = self::_get_db_version();
        }

        return $version[0] . '.' . $version[1] . '.' . $version[2] . ' Build: ' . substr($version, strlen((string)$version) - 3, strlen((string)$version));
    }

    /**
     * need_update
     *
     * Checks to see if we need to update ampache at all.
     */
    public static function need_update(): bool
    {
        $current_version = self::_get_db_version();

        if (!is_array(self::$versions)) {
            self::$versions = self::_set_versions();
        }

        // Iterate through the versions and see if we need to apply any updates
        foreach (self::$versions as $update) {
            if ($update['version'] > $current_version) {
                return true;
            }
        }

        return false;
    }

    /**
     * _set_versions
     * Set the list of database updates used by self::$versions
     */
    private static function _set_versions(): array
    {
        /* Define the array */
        $version = array();

        $update_string = "* Add MBID (MusicBrainz ID) fields<br />* Remove useless preferences<br />";
        $version[]     = array('version' => '360001', 'description' => $update_string);

        $update_string = "* Add Bandwidth and Feature preferences to simplify how interface is presented<br />* Change Tables to FULLTEXT() for improved searching<br />* Increase Filename lengths to 4096<br />* Remove useless KEY reference from ACL and Catalog tables<br />* Add new Remote User / Remote Password fields to Catalog<br />";
        $version[]     = array('version' => '360002', 'description' => $update_string);

        $update_string = "* Add image table to store images.<br />* Drop album_data and artist_data.<br />";
        $version[]     = array('version' => '360003', 'description' => $update_string);

        $update_string = "* Add uniqueness constraint to ratings.<br />";
        $version[]     = array('version' => '360004', 'description' => $update_string);

        $update_string = "* Modify tmp_browse to allow caching of multiple browses per session.<br />";
        $version[]     = array('version' => '360005', 'description' => $update_string);

        $update_string = "* Add table for dynamic playlists.<br />";
        $version[]     = array('version' => '360006', 'description' => $update_string);

        $update_string = "* Verify remote_username and remote_password were added correctly to catalog table.<br />";
        $version[]     = array('version' => '360008', 'description' => $update_string);

        $update_string = "* Allow long sessionids in tmp_playlist table.<br />";
        $version[]     = array('version' => '360009', 'description' => $update_string);

        $update_string = "* Allow compound MBIDs in the artist table.<br />";
        $version[]     = array('version' => '360010', 'description' => $update_string);

        $update_string = "* Add table to store stream session playlist.<br />";
        $version[]     = array('version' => '360011', 'description' => $update_string);

        $update_string = "* Drop enum for the type field in session.<br />";
        $version[]     = array('version' => '360012', 'description' => $update_string);

        $update_string = "* Update stream_playlist table to address performance issues.<br />";
        $version[]     = array('version' => '360013', 'description' => $update_string);

        $update_string = "* Increase the length of sessionids again.<br />";
        $version[]     = array('version' => '360014', 'description' => $update_string);

        $update_string = "* Add iframes parameter to preferences.<br />";
        $version[]     = array('version' => '360015', 'description' => $update_string);

        $update_string = "* Optionally filter Now Playing to return only the last song per user.<br />";
        $version[]     = array('version' => '360016', 'description' => $update_string);

        $update_string = "* Add user flags on objects.<br />";
        $version[]     = array('version' => '360017', 'description' => $update_string);

        $update_string = "* Add album default sort value to preferences.<br />";
        $version[]     = array('version' => '360018', 'description' => $update_string);

        $update_string = "* Add option to show number of times a song was played.<br />";
        $version[]     = array('version' => '360019', 'description' => $update_string);

        $update_string = "* Catalog types are plugins now.<br />";
        $version[]     = array('version' => '360020', 'description' => $update_string);

        $update_string = "* Add insertion date on Now Playing and option to show the current song in page title for Web player.<br />";
        $version[]     = array('version' => '360021', 'description' => $update_string);

        $update_string = "* Remove unused live_stream fields and add codec field.<br />";
        $version[]     = array('version' => '360022', 'description' => $update_string);

        $update_string = "* Enable/Disable SubSonic and Plex backend.<br />";
        $version[]     = array('version' => '360023', 'description' => $update_string);

        $update_string = "* Drop flagged table.<br />";
        $version[]     = array('version' => '360024', 'description' => $update_string);

        $update_string = "* Add options to enable HTML5 / Flash on web players.<br />";
        $version[]     = array('version' => '360025', 'description' => $update_string);

        $update_string = "* Added agent to `object_count` table.<br />";
        $version[]     = array('version' => '360026', 'description' => $update_string);

        $update_string = "* Add option to allow/disallow to show personnal information to other users (now playing and recently played).<br />";
        $version[]     = array('version' => '360027', 'description' => $update_string);

        $update_string = "* Personnal information: allow/disallow to show in now playing.<br />* Personnal information: allow/disallow to show in recently played.<br />* Personnal information: allow/disallow to show time and/or agent in recently played.<br />";
        $version[]     = array('version' => '360028', 'description' => $update_string);

        $update_string = "* Add new table to store wanted releases.<br />";
        $version[]     = array('version' => '360029', 'description' => $update_string);

        $update_string = "* New table to store song previews.<br />";
        $version[]     = array('version' => '360030', 'description' => $update_string);

        $update_string = "* Add option to fix header position on compatible themes.<br />";
        $version[]     = array('version' => '360031', 'description' => $update_string);

        $update_string = "* Add check update automatically option.<br />";
        $version[]     = array('version' => '360032', 'description' => $update_string);

        $update_string = "* Add song waveform as song data.<br />";
        $version[]     = array('version' => '360033', 'description' => $update_string);

        $update_string = "* Add settings for confirmation when closing window and auto-pause between tabs.<br />";
        $version[]     = array('version' => '360034', 'description' => $update_string);

        $update_string = "* Add beautiful stream url setting.<br />";
        $version[]     = array('version' => '360035', 'description' => $update_string);

        $update_string = "* Remove unused parameters.<br />";
        $version[]     = array('version' => '360036', 'description' => $update_string);

        $update_string = "* Add sharing features.<br />";
        $version[]     = array('version' => '360037', 'description' => $update_string);

        $update_string = "* Add missing albums browse on missing artists.<br />";
        $version[]     = array('version' => '360038', 'description' => $update_string);

        $update_string = "* Add website field on users.<br />";
        $version[]     = array('version' => '360039', 'description' => $update_string);

        $update_string = "* Add channels.<br />";
        $version[]     = array('version' => '360041', 'description' => $update_string);

        $update_string = "* Add broadcasts and player control.<br />";
        $version[]     = array('version' => '360042', 'description' => $update_string);

        $update_string = "* Add slideshow on currently played artist preference.<br />";
        $version[]     = array('version' => '360043', 'description' => $update_string);

        $update_string = "* Add artist description/recommendation external service data cache.<br />";
        $version[]     = array('version' => '360044', 'description' => $update_string);

        $update_string = "* Set user field on playlists as optional.<br />";
        $version[]     = array('version' => '360045', 'description' => $update_string);

        $update_string = "* Add broadcast web player by default preference.<br />";
        $version[]     = array('version' => '360046', 'description' => $update_string);

        $update_string = "* Add apikey field on users.<br />";
        $version[]     = array('version' => '360047', 'description' => $update_string);

        $update_string = "* Add concerts options.<br />";
        $version[]     = array('version' => '360048', 'description' => $update_string);

        $update_string = "* Add album group multiple disks setting.<br />";
        $version[]     = array('version' => '360049', 'description' => $update_string);

        $update_string = "* Add top menu setting.<br />";
        $version[]     = array('version' => '360050', 'description' => $update_string);

        $update_string = "* Drop unused dynamic_playlist tables and add session id to votes.<br />";
        $version[]     = array('version' => '370001', 'description' => $update_string);

        $update_string = "* Add tag persistent merge reference.<br />";
        $version[]     = array('version' => '370002', 'description' => $update_string);

        $update_string = "* Add show/hide donate button preference.<br />";
        $version[]     = array('version' => '370003', 'description' => $update_string);

        $update_string = "* Add license information and user's artist association.<br />";
        $version[]     = array('version' => '370004', 'description' => $update_string);

        $update_string = "* Add new column album_artist into table song.<br />";
        $version[]     = array('version' => '370005', 'description' => $update_string);

        $update_string = "* Add random and limit options to smart playlists.<br />";
        $version[]     = array('version' => '370006', 'description' => $update_string);

        $update_string = "* Add DAAP backend preference.<br />";
        $version[]     = array('version' => '370007', 'description' => $update_string);

        $update_string = "* Add UPnP backend preference.<br />";
        $version[]     = array('version' => '370008', 'description' => $update_string);

        $update_string = "* Enhance video support with TVShows and Movies.<br />";
        $version[]     = array('version' => '370009', 'description' => $update_string);

        $update_string = "* Add MusicBrainz Album Release Group identifier.<br />";
        $version[]     = array('version' => '370010', 'description' => $update_string);

        $update_string = "* Add Prefix to TVShows and Movies.<br />";
        $version[]     = array('version' => '370011', 'description' => $update_string);

        $update_string = "* Add metadata information to albums / songs / videos.<br />";
        $version[]     = array('version' => '370012', 'description' => $update_string);

        $update_string = "* Replace iframe with ajax page load.<br />";
        $version[]     = array('version' => '370013', 'description' => $update_string);

        $update_string = "* Modified release_date in video table to signed int.<br />";
        $version[]     = array('version' => '370014', 'description' => $update_string);

        $update_string = "* Add session_remember table to store remember tokens.<br />";
        $version[]     = array('version' => '370015', 'description' => $update_string);

        $update_string = "* Add limit of media count for direct play preference.<br />";
        $version[]     = array('version' => '370016', 'description' => $update_string);

        $update_string = "* Add home display settings.<br />";
        $version[]     = array('version' => '370017', 'description' => $update_string);

        $update_string = "* Enhance tag persistent merge reference.<br />";
        $version[]     = array('version' => '370018', 'description' => $update_string);

        $update_string = "* Add album group order setting.<br />";
        $version[]     = array('version' => '370019', 'description' => $update_string);

        $update_string = "* Add webplayer browser notification settings.<br />";
        $version[]     = array('version' => '370020', 'description' => $update_string);

        $update_string = "* Add rating to playlists, tvshows and tvshows seasons.<br />";
        $version[]     = array('version' => '370021', 'description' => $update_string);

        $update_string = "* Add users geolocation.<br />";
        $version[]     = array('version' => '370022', 'description' => $update_string);

        $update_string = "* Add Aurora.js webplayer option.<br />";
        $version[]     = array('version' => '370023', 'description' => $update_string);

        $update_string = "* Add count_type column to object_count table.<br />";
        $version[]     = array('version' => '370024', 'description' => $update_string);

        $update_string = "* Add state and city fields to user table.<br />";
        $version[]     = array('version' => '370025', 'description' => $update_string);

        $update_string = "* Add replay gain fields to song_data table.<br />";
        $version[]     = array('version' => '370026', 'description' => $update_string);

        $update_string = "* Move column album_artist from table song to table album.<br />";
        $version[]     = array('version' => '370027', 'description' => $update_string);

        $update_string = "* Add width and height in table image.<br />";
        $version[]     = array('version' => '370028', 'description' => $update_string);

        $update_string = "* Set image column from image table as nullable.<br />";
        $version[]     = array('version' => '370029', 'description' => $update_string);

        $update_string = "* Add an option to allow users to remove uploaded songs.<br />";
        $version[]     = array('version' => '370030', 'description' => $update_string);

        $update_string = "* Add an option to customize login art, favicon and text footer.<br />";
        $version[]     = array('version' => '370031', 'description' => $update_string);

        $update_string = "* Add WebDAV backend preference.<br />";
        $version[]     = array('version' => '370032', 'description' => $update_string);

        $update_string = "* Add Label tables.<br />";
        $version[]     = array('version' => '370033', 'description' => $update_string);

        $update_string = "* Add User messages and user follow tables.<br />";
        $version[]     = array('version' => '370034', 'description' => $update_string);

        $update_string = "* Add option on user fullname to show/hide it publicly.<br />";
        $version[]     = array('version' => '370035', 'description' => $update_string);

        $update_string = "* Add track number field to stream_playlist table.<br />";
        $version[]     = array('version' => '370036', 'description' => $update_string);

        $update_string = "* Delete http_port preference (use ampache.cfg.php configuration instead).<br />";
        $version[]     = array('version' => '370037', 'description' => $update_string);

        $update_string = "* Add theme color option.<br />";
        $version[]     = array('version' => '370038', 'description' => $update_string);

        $update_string = "* Renamed false named sample_rate option name in preference table.<br />";
        $version[]     = array('version' => '370039', 'description' => $update_string);

        $update_string = "* Add user_activity table.<br />";
        $version[]     = array('version' => '370040', 'description' => $update_string);

        $update_string = "* Add basic metadata tables.<br />";
        $version[]     = array('version' => '370041', 'description' => $update_string);

        $update_string = "* Add podcasts.<br />";
        $version[]     = array('version' => '380001', 'description' => $update_string);

        $update_string = "* Add bookmarks.<br />";
        $version[]     = array('version' => '380002', 'description' => $update_string);

        $update_string = "* Add unique constraint on tag_map table.<br />";
        $version[]     = array('version' => '380003', 'description' => $update_string);

        $update_string = "* Add preference subcategory.<br />";
        $version[]     = array('version' => '380004', 'description' => $update_string);

        $update_string = "* Add manual update flag on artist.<br />";
        $version[]     = array('version' => '380005', 'description' => $update_string);

        $update_string = "* Add library item context menu option.<br />";
        $version[]     = array('version' => '380006', 'description' => $update_string);

        $update_string = "* Add upload rename pattern and ignore duplicate options.<br />";
        $version[]     = array('version' => '380007', 'description' => $update_string);

        $update_string = "* Add browse filter and light sidebar options.<br />";
        $version[]     = array('version' => '380008', 'description' => $update_string);

        $update_string = "* Add update date to playlist.<br />";
        $version[]     = array('version' => '380009', 'description' => $update_string);

        $update_string = "* Add custom blank album/video default image and alphabet browsing options.<br />";
        $version[]     = array('version' => '380010', 'description' => $update_string);

        $update_string = "* Fix username max size to be the same one across all tables.<br />";
        $version[]     = array('version' => '380011', 'description' => $update_string);

        $update_string = "* Fix change in <a href='https://github.com/ampache/ampache/commit/0c26c336269624d75985e46d324e2bc8108576ee'>this commit</a>, that left the userbase with an inconsistent database, if users updated or installed Ampache before 28 Apr 2015<br />";
        $version[]     = array('version' => '380012', 'description' => $update_string);

        $update_string = "* Enable better podcast defaults<br />* Increase copyright column size to fix issue #1861<br />* Add name_track, name_artist, name_album to user_activity<br />* Add mbid_track, mbid_artist, mbid_album to user_activity<br />* Insert some decent SmartLists for a better default experience<br />* Delete plex preferences from the server<br />";
        $version[]     = array('version' => '400000', 'description' => $update_string);

        $update_string = "* Update preferences for older users to match current subcategory items<br /> (~3.6 introduced subcategories but didn't include updates for existing users.<br /> This is a cosmetic update and does not affect any operation)<br />";
        $version[]     = array('version' => '400001', 'description' => $update_string);

        $update_string = "**IMPORTANT UPDATE NOTES**<br /><br /> This is part of a major update to how Ampache handles Albums, Artists and data migration during tag updates.<br /><br />* Update album disk support to allow 1 instead of 0 by default.<br />* Add barcode catalog_number and original_year to albums.<br />* Drop catalog_number from song_data and use album instead.<br />";
        $version[]     = array('version' => '400002', 'description' => $update_string);

        $update_string = "* Make sure preference names are updated to current strings<br />";
        $version[]     = array('version' => '400003', 'description' => $update_string);

        $update_string = "* Delete upload_user_artist database settings<br />";
        $version[]     = array('version' => '400004', 'description' => $update_string);

        $update_string = "* Add a last_count to search table to speed up access requests<br />";
        $version[]     = array('version' => '400005', 'description' => $update_string);

        $update_string = "* Drop shoutcast_active preferences. (Feature has not existed for years)<br />* Drop localplay_shoutcast table if present.<br />";
        $version[]     = array('version' => '400006', 'description' => $update_string);

        $update_string = "* Add ui option for skip_count display.<br />* Add ui option for displaying dates in a custom format.<br />";
        $version[]     = array('version' => '400007', 'description' => $update_string);

        $update_string = "* Add system option for cron based cache and create related tables.<br />";
        $version[]     = array('version' => '400008', 'description' => $update_string);

        $update_string = "* Add ui option for forcing unique items to playlists.<br />";
        $version[]     = array('version' => '400009', 'description' => $update_string);

        $update_string = "* Add a last_duration to search table to speed up access requests<br />";
        $version[]     = array('version' => '400010', 'description' => $update_string);

        $update_string = "**IMPORTANT UPDATE NOTES**<br /><br /> To allow negatives the maximum value of `song`.`track` has been reduced. This shouldn't affect anyone due to the large size allowed.<br /><br />* Allow negative track numbers for albums. (-32,767 -> 32,767)<br />* Truncate database tracks to 0 when greater than 32,767<br />";
        $version[]     = array('version' => '400011', 'description' => $update_string);

        $update_string = "* Add a rss token to allow the use of RSS unauthenticated feeds<br/ >";
        $version[]     = array('version' => '400012', 'description' => $update_string);

        $update_string = "* Extend Democratic cooldown beyond 255.<br/ >";
        $version[]     = array('version' => '400013', 'description' => $update_string);

        $update_string = "* Add last_duration to playlist<br/ > * Add time to artist and album<br/ >";
        $version[]     = array('version' => '400014', 'description' => $update_string);

        $update_string = "* Extend artist time. smallint was too small<br/ > ";
        $version[]     = array('version' => '400015', 'description' => $update_string);

        $update_string = "* Extend album and make artist even bigger. This should cover everyone.<br/ > ";
        $version[]     = array('version' => '400016', 'description' => $update_string);

        $update_string = "* Extend video bitrate to unsigned. There's no reason for a negative bitrate.<br/ > ";
        $version[]     = array('version' => '400018', 'description' => $update_string);

        $update_string = "* Put 'of_the_moment' into a per user preference.<br/ > ";
        $version[]     = array('version' => '400019', 'description' => $update_string);

        $update_string = "* Customizable login page background.<br/ > ";
        $version[]     = array('version' => '400020', 'description' => $update_string);

        $update_string = "* Add r128 gain columns to song_data.<br/ > ";
        $version[]     = array('version' => '400021', 'description' => $update_string);

        $update_string = "* Extend allowed time for podcast_episodes.<br/ > ";
        $version[]     = array('version' => '400022', 'description' => $update_string);

        $update_string = "* Delete 'concerts_limit_past' and 'concerts_limit_future' database settings.<br/ > ";
        $version[]     = array('version' => '400023', 'description' => $update_string);

        $update_string = "**IMPORTANT UPDATE NOTES**<br />These columns will fill dynamically in the web UI but you should do a catalog 'add' as soon as possible to fill them.<br />It will take a while for large libraries but will help API and SubSonic clients.<br /><br />* Add 'song_count', 'album_count' and 'album_group_count' to artist. <br />";
        $version[]     = array('version' => '400024', 'description' => $update_string);

        $update_string = "* Delete duplicate files in the song table<br />";
        $version[]     = array('version' => '500000', 'description' => $update_string);

        $update_string = "* Add `release_status`, `addition_time`, `catalog` to album table<br />* Add `mbid`, `country` and `active` to label table<br />* Fill the album `catalog` value using the song table<br />* Fill the artist `album_count`, `album_group_count` and `song_count` values";
        $version[]     = array('version' => '500001', 'description' => $update_string);

        $update_string = "* Create `total_count` and `total_skip` to album, artist, song, video and podcast_episode tables<br />* Fill counts into the columns";
        $version[]     = array('version' => '500002', 'description' => $update_string);

        $update_string = "* Add `catalog` to podcast_episode table";
        $version[]     = array('version' => '500003', 'description' => $update_string);

        $update_string = "<b>WARNING</b> For large catalogs this will be slow!<br />* Create catalog_map table and fill it with data";
        $version[]     = array('version' => '500004', 'description' => $update_string);

        $update_string = "* Add song_count, artist_count to album";
        $version[]     = array('version' => '500005', 'description' => $update_string);

        $update_string = "* Add user_playlist and user_data table";
        $version[]     = array('version' => '500006', 'description' => $update_string);

        $update_string = "* Add a 'Browse' category to interface preferences<br />* Add option ('show_license') for hiding license column in song rows";
        $version[]     = array('version' => '500007', 'description' => $update_string);

        $update_string = "* Add filter_user to catalog table<br />* Set a unique key on user_data";
        $version[]     = array('version' => '500008', 'description' => $update_string);

        $update_string = "* Add ui option ('use_original_year') Browse by Original Year for albums (falls back to Year)";
        $version[]     = array('version' => '500009', 'description' => $update_string);

        $update_string = "* Add ui option ('hide_single_artist') Hide the Song Artist column for Albums with one Artist";
        $version[]     = array('version' => '500010', 'description' => $update_string);

        $update_string = "* Add `total_count` to podcast table and fill counts into the column";
        $version[]     = array('version' => '500011', 'description' => $update_string);

        $update_string = "* Move user bandwidth calculations out of the user format function into the user_data table";
        $version[]     = array('version' => '500012', 'description' => $update_string);

        $update_string = "* Add tables for tracking deleted files. (deleted_song, deleted_video, deleted_podcast_episode)<br />* Add username to the playlist table to stop pulling user all the time";
        $version[]     = array('version' => '500013', 'description' => $update_string);

        $update_string = "* Add `episodes` to podcast table to track episode count";
        $version[]     = array('version' => '500014', 'description' => $update_string);

        $update_string = "* Add ui option ('hide_genres') Hide the Genre column in browse table rows";
        $version[]     = array('version' => '500015', 'description' => $update_string);

        $update_string = "* Add podcast to the object_count table";
        $version[]     = array('version' => '510000', 'description' => $update_string);

        $update_string = "* Add podcast to the cache_object_count tables";
        $version[]     = array('version' => '510001', 'description' => $update_string);

        $update_string = "* Add live_stream to the rating table";
        $version[]     = array('version' => '510003', 'description' => $update_string);

        $update_string = "* Add waveform column to podcast_episode table";
        $version[]     = array('version' => '510004', 'description' => $update_string);

        $update_string = "* Add ui option ('subsonic_always_download') Force Subsonic streams to download. (Enable scrobble in your client to record stats)";
        $version[]     = array('version' => '510005', 'description' => $update_string);

        $update_string = "* Add ui options ('api_enable_3', 'api_enable_4', 'api_enable_5') to enable/disable specific API versions<br />* Add ui option ('api_force_version') to force a specific API response (even if that version is disabled)";
        $version[]     = array('version' => '520000', 'description' => $update_string);

        $update_string = "* Make sure preference names are always unique";
        $version[]     = array('version' => '520001', 'description' => $update_string);

        $update_string = "* Add ui option ('show_playlist_username') Show playlist owner username in titles";
        $version[]     = array('version' => '520002', 'description' => $update_string);

        $update_string = "* Add ui option ('api_hidden_playlists') Hide playlists in Subsonic and API clients that start with this string";
        $version[]     = array('version' => '520003', 'description' => $update_string);

        $update_string = "* Set 'plugins' category to lastfm_challenge preference";
        $version[]     = array('version' => '520004', 'description' => $update_string);

        $update_string = "* Add ui option ('api_hide_dupe_searches') Hide smartlists that match playlist names in Subsonic and API clients";
        $version[]     = array('version' => '520005', 'description' => $update_string);

        $update_string = "<b>" . T_("WARNING") . "</b> For large catalogs this will be slow!<br />* Create artist_map table and fill it with data";
        $version[]     = array('version' => '530000', 'description' => $update_string);

        $update_string = "* Create album_map table and fill it with data";
        $version[]     = array('version' => '530001', 'description' => $update_string);

        $update_string = "* Use song_count & artist_count with album_map";
        $version[]     = array('version' => '530002', 'description' => $update_string);

        $update_string = "* Drop id column from catalog_map table<br />* Alter `catalog_map` object_type charset and collation";
        $version[]     = array('version' => '530003', 'description' => $update_string);

        $update_string = "* Alter `album_map` table charset and engine to MyISAM if engine set";
        $version[]     = array('version' => '530004', 'description' => $update_string);

        $update_string = "* Alter `artist_map` table charset and engine to MyISAM if engine set";
        $version[]     = array('version' => '530005', 'description' => $update_string);

        $update_string = "* Make sure `object_count` table has all the correct primary artist/album rows";
        $version[]     = array('version' => '530006', 'description' => $update_string);

        $update_string = "* Convert basic text columns into utf8 to reduce index sizes";
        $version[]     = array('version' => '530007', 'description' => $update_string);

        $update_string = "* Remove `user_activity` columns that are useless";
        $version[]     = array('version' => '530008', 'description' => $update_string);

        $update_string = "* Compact `object_count` columns";
        $version[]     = array('version' => '530009', 'description' => $update_string);

        $update_string = "* Compact mbid columns back to 36 characters";
        $version[]     = array('version' => '530010', 'description' => $update_string);

        $update_string = "* Compact some `user` columns<br />* enum `object_count`.`count_type`";
        $version[]     = array('version' => '530011', 'description' => $update_string);

        $update_string = "* Index data on object_count";
        $version[]     = array('version' => '530012', 'description' => $update_string);

        $update_string = "* Compact `cache_object_count`, `cache_object_count_run` columns";
        $version[]     = array('version' => '530013', 'description' => $update_string);

        $update_string = "* Delete `object_count` duplicates<br />* Use a smaller unique index on `object_count`";
        $version[]     = array('version' => '530014', 'description' => $update_string);

        $update_string = "* Add `show_album_artist` and `show_artist` preferences to show/hide Sidebar Browse menu links<br />* Fallback to Album Artist if both disabled";
        $version[]     = array('version' => '530015', 'description' => $update_string);

        $update_string = "* Add missing rating item back in the type enum";
        $version[]     = array('version' => '530016', 'description' => $update_string);

        $update_string = "* Index `title` with `enabled` on `song` table to speed up searching";
        $version[]     = array('version' => '540000', 'description' => $update_string);

        $update_string = "* Index `album` table columns.<br />* `catalog`, `album_artist`, `original_year`, `release_type`, `release_status`, `mbid`, `mbid_group`";
        $version[]     = array('version' => '540001', 'description' => $update_string);

        $update_string = "* Index `object_type` with `date` in `object_count` table";
        $version[]     = array('version' => '540002', 'description' => $update_string);

        $update_string = "* Add tables `catalog_filter_group` and `catalog_filter_group_map` for catalog filtering by groups<br />* Add column `catalog_filter_group` to `user` table to assign a filter group";
        $version[]     = array('version' => '550001', 'description' => $update_string);

        $update_string = "* Migrate catalog `filter_user` settings to the `catalog_filter_group` table<br>* Assign all public catalogs to the DEFAULT group<br>* Drop table `user_catalog`<br>* Remove `filter_user` from the `catalog` table<br><br><br>**IMPORTANT UPDATE NOTES** Any user that has a private catalog will have their own filter group created which includes all public catalogs";
        $version[]     = array('version' => '550002', 'description' => $update_string);

        $update_string = "* Add system preference `demo_use_search`, Use smartlists for base playlist in Democratic play";
        $version[]     = array('version' => '550003', 'description' => $update_string);

        $update_string = "* Make `demo_use_search`a system preference correctly";
        $version[]     = array('version' => '550004', 'description' => $update_string);

        $update_string = "* Add `song_artist` and `album_artist` maps to catalog_map";
        $version[]     = array('version' => '550005', 'description' => $update_string);

        $update_string = "* Add user preference `webplayer_removeplayed`, Remove tracks before the current playlist item in the webplayer when played";
        $version[]     = array('version' => '600001', 'description' => $update_string);

        $update_string = "* Drop channel table";
        $version[]     = array('version' => '600002', 'description' => $update_string);

        $update_string = "* Add `total_skip` to podcast table";
        $version[]     = array('version' => '600003', 'description' => $update_string);

        $update_string = "* Add `disk` to song table<br />* Create album_disk table and migrate user ratings & flags";
        $version[]     = array('version' => '600004', 'description' => $update_string);

        $update_string = "<b>" . T_("WARNING") . "</b> Please consider using the CLI for this update (php bin/cli admin:updateDatabase -e) <a href='https://github.com/ampache/ampache/wiki/ampache6-details'>Ampache Wiki</a><br />* Migrate multi-disk albums to single album id's";
        $version[]     = array('version' => '600005', 'description' => $update_string);

        $update_string = "* Add `disk_count` to album table";
        $version[]     = array('version' => '600006', 'description' => $update_string);

        $update_string = "* Fill album_disk table update count tables";
        $version[]     = array('version' => '600007', 'description' => $update_string);

        $update_string = "* Rename `artist`.`album_group_count` => `album_disk_count`";
        $version[]     = array('version' => '600008', 'description' => $update_string);

        $update_string = "* Drop `disk` from the `album` table";
        $version[]     = array('version' => '600009', 'description' => $update_string);

        $update_string = "* Rename `user_data` album keys";
        $version[]     = array('version' => '600010', 'description' => $update_string);

        $update_string = "* Add `album_disk` to enum types for `object_count`, `rating` and `cache_object_count` tables";
        $version[]     = array('version' => '600011', 'description' => $update_string);

        $update_string = "* Add `song_artist` and `album_artist` maps to catalog_map<br />* This is a duplicate of `update_550004` But may have been skipped depending on your site's version history";
        $version[]     = array('version' => '600012', 'description' => $update_string);

        $update_string = "* Add ui option 'api_enable_6' to enable/disable API6";
        $version[]     = array('version' => '600013', 'description' => $update_string);

        $update_string = "* Add `subtitle` to the album table";
        $version[]     = array('version' => '600014', 'description' => $update_string);

        $update_string = "* Add `streamtoken` to user table allowing permalink music stream access";
        $version[]     = array('version' => '600015', 'description' => $update_string);

        $update_string = "* Add `object_type_IDX` to artist_map table<br />* Add `object_type_IDX` to catalog_map table";
        $version[]     = array('version' => '600016', 'description' => $update_string);

        $update_string = "* Drop `user_playlist` table and recreate it";
        $version[]     = array('version' => '600018', 'description' => $update_string);

        $update_string = "* During migration some album_disk data may be missing it's object type";
        $version[]     = array('version' => '600019', 'description' => $update_string);

        $update_string = "* Set system preferences to Admin (100)<br />* These options are only available to Admin users anyway";
        $version[]     = array('version' => '600020', 'description' => $update_string);

        $update_string = "* Extend `time` column for the song table";
        $version[]     = array('version' => '600021', 'description' => $update_string);

        $update_string = "* Extend `time` column for the stream_playlist table";
        $version[]     = array('version' => '600022', 'description' => $update_string);

        $update_string = "* Add upload_access_level to restrict uploads to certain user groups";
        $version[]     = array('version' => '600023', 'description' => $update_string);

        $update_string = "* Add ui option ('show_subtitle') Show Album subtitle on links (if available)<br />* Add ui option ('show_original_year') Show Album original year on links (if available)";
        $version[]     = array('version' => '600024', 'description' => $update_string);

        $update_string = "* Add ui option ('show_header_login') Show the login / registration links in the site header";
        $version[]     = array('version' => '600025', 'description' => $update_string);

        $update_string = "* Add user preference `use_play2`, Use an alternative playback action for streaming if you have issues with playing music";
        $version[]     = array('version' => '600026', 'description' => $update_string);

        $update_string = "* Rename `subtitle` to `version` in the `album` table";
        $version[]     = array('version' => '600027', 'description' => $update_string);

        $update_string = "* Add `bitrate`, `rate`, `mode` and `channels` to the `podcast_episode` table";
        $version[]     = array('version' => '600028', 'description' => $update_string);

        $update_string = "* Extend `object_type` enum list on `rating` table";
        $version[]     = array('version' => '600032', 'description' => $update_string);

        $update_string = "* Convert `object_type` to an enum on `user_flag` table";
        $version[]     = array('version' => '600033', 'description' => $update_string);

        $update_string = "* Convert `object_type` to an enum on `image` table";
        $version[]     = array('version' => '600034', 'description' => $update_string);

        $update_string = "* Add `enabled` to `podcast_episode` table";
        $version[]     = array('version' => '600035', 'description' => $update_string);

        $update_string = "* Update user `play_size` and catalog `size` fields to megabytes (Stop large catalogs overflowing 32bit ints)";
        $version[]     = array('version' => '600036', 'description' => $update_string);

        $update_string = "* Update user server and user counts now that the scaling has changed";
        $version[]     = array('version' => '600037', 'description' => $update_string);

        $update_string = "* Update `access_list` in case you have a bad `user` column";
        $version[]     = array('version' => '600038', 'description' => $update_string);

        $update_string = "* Add user preference `custom_timezone`, Display dates using a different timezone to the server timezone";
        $version[]     = array('version' => '600039', 'description' => $update_string);

        $update_string = "* Add `disksubtitle` to `song_data` and `album_disk` table";
        $version[]     = array('version' => '600040', 'description' => $update_string);

        $update_string = "* Index `label` column on the `label_asso` table";
        $version[]     = array('version' => '600041', 'description' => $update_string);

        $update_string = "* Add user preference `bookmark_latest`, Only keep the latest media bookmark";
        $version[]     = array('version' => '600042', 'description' => $update_string);

        $update_string = "* Set correct preference type for `use_play2`<br />* Add user preference `jp_volume`, Default webplayer volume";
        $version[]     = array('version' => '600043', 'description' => $update_string);

        $update_string = "* Add system preference `perpetual_api_session`, API sessions do not expire";
        $version[]     = array('version' => '600044', 'description' => $update_string);

        $update_string = "* Add column `last_update` and `date`to search table";
        $version[]     = array('version' => '600045', 'description' => $update_string);

        $update_string = "* Add user preference `home_recently_played_all`, Show all media types in Recently Played";
        $version[]     = array('version' => '600046', 'description' => $update_string);

        $update_string = "* Add user preference `show_wrapped`, Enable access to your personal \"Spotify Wrapped\" from your user page";
        $version[]     = array('version' => '600047', 'description' => $update_string);

        $update_string = "* Add `date` column to rating table";
        $version[]     = array('version' => '600048', 'description' => $update_string);

        return $version;
    }

    /**
     * display_update
     * This displays a list of the needed
     * updates to the database. This will actually
     * echo out the list...
     */
    public static function display_update(): array
    {
        $result          = [];
        $current_version = self::_get_db_version();
        if (!is_array(self::$versions)) {
            self::$versions = self::_set_versions();
        }

        foreach (self::$versions as $update) {
            if ($update['version'] > $current_version) {
                $result[] = [
                    'version' => T_('Version') . ': ' . self::format_version($update['version']),
                    'description' => $update['description']
                ];
            }
        }

        return $result;
    }

    /**
     * run_update
     * This function actually updates the db. It goes through versions and finds the ones that need to be run. Checking to make sure the function exists first.
     * @param Interactor|null $interactor
     */
    public static function run_update(Interactor $interactor = null): bool
    {
        debug_event(self::class, 'run_update: starting', 4);
        /* Nuke All Active session before we start the mojo */
        $sql = "TRUNCATE session";
        Dba::write($sql);

        // Prevent the script from timing out, which could be bad
        set_time_limit(0);

        $current_version = self::_get_db_version();

        // Run a check to make sure that they don't try to upgrade from a version that won't work.
        if ($current_version < '350008') {
            echo '<p class="database-update">Database version too old, please upgrade to <a href="https://github.com/ampache/ampache/releases/download/3.8.2/ampache-3.8.2_all.zip">Ampache-3.8.2</a> first</p>';

            return false;
        }

        $methods = get_class_methods(Update::class);

        if (!is_array((self::$versions))) {
            self::$versions = self::_set_versions();
        }

        debug_event(self::class, 'run_update: checking versions', 4);
        foreach (self::$versions as $version) {
            // If it's newer than our current version let's see if a function
            // exists and run the bugger.
            if ($version['version'] > $current_version) {
                $update_function = "_update_" . $version['version'];
                if (in_array($update_function, $methods)) {
                    if ($interactor) {
                        $interactor->info($update_function, true);
                    }
                    debug_event(self::class, 'run_update: START ' . $version['version'], 5);
                    $success = call_user_func(array('Ampache\Module\System\Update', $update_function), $interactor);

                    // If the update fails drop out
                    if ($success) {
                        debug_event(self::class, 'run_update: SUCCESS ' . $version['version'], 3);
                        self::_set_db_version($version['version']);
                    } else {
                        echo AmpError::display('update');

                        return false;
                    }
                }
            }
        } // end foreach version

        // Let's also clean up the preferences unconditionally
        debug_event(self::class, 'run_update: starting rebuild_all_preferences', 5);
        User::rebuild_all_preferences();
        // translate preferences on DB update
        Preference::translate_db();

        debug_event(self::class, 'run_update: Upgrade complete', 4);

        return true;
    }

    /**
     * _write
     *
     * This updates the 'update_info' which is used by the updater and plugins
     * @param Interactor|null $interactor
     * @param string $sql
     * @param array $params
     */
    private static function _write($interactor, $sql, $params = array()): bool
    {
        if (Dba::write($sql, $params) === false) {
            if ($interactor) {
                $interactor->info(
                    $sql,
                    true
                );
            }

            return false;
        }

        return true;
    }

    /**
     * _write_preference
     *
     * Add preferences and print update errors for preference inserts on failure
     * @param Interactor|null $interactor
     * @param string $name
     * @param string $description
     * @param string|int|float $default
     * @param int $level
     * @param string $type
     * @param string $category
     * @param null|string $subcategory
     */
    private static function _write_preference($interactor, $name, $description, $default, $level, $type, $category, $subcategory = null): bool
    {
        if (Preference::insert($name, $description, $default, $level, $type, $category, $subcategory, true) === false) {
            if ($interactor) {
                $interactor->info(
                    /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                    sprintf(T_('Bad Request: %s'), $name),
                    true
                );
            }

            return false;
        }

        return true;
    }

    /**
     * _set_db_version
     *
     * This updates the 'update_info' which is used by the updater.
     * @param string $value
     */
    private static function _set_db_version($value)
    {
        $sql = "UPDATE `update_info` SET `value` = ? WHERE `key` = 'db_version'";
        Dba::write($sql, array($value));
    }

    /**
     * _update_360001
     *
     * This adds the MB UUIDs to the different tables as well as some additional cleanup.
     */
    private static function _update_360001(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `album` ADD `mbid` CHAR (36) AFTER `prefix`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `artist` ADD `mbid` CHAR (36) AFTER `prefix`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `song` ADD `mbid` CHAR (36) AFTER `track`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        // Remove any RIO related information from the database as the plugin has been removed
        $sql = "DELETE FROM `update_info` WHERE `key` LIKE 'Plugin_Ri%'";
        Dba::write($sql);
        $sql = "DELETE FROM `preference` WHERE `name` LIKE 'rio_%'";
        Dba::write($sql);

        return true;
    }

    /**
     * _update_360002
     *
     * This update makes changes to the cataloging to accomodate the new method
     * for syncing between Ampache instances.
     */
    private static function _update_360002(Interactor $interactor = null): bool
    {
        // Drop the key from catalog and ACL
        $sql_array = array(
            "ALTER TABLE `catalog` DROP `key`",
            "ALTER TABLE `access_list` DROP `key`",
            "ALTER TABLE `catalog` ADD `remote_username` VARCHAR (255) AFTER `catalog_type`",
            "ALTER TABLE `catalog` ADD `remote_password` VARCHAR (255) AFTER `remote_username`",
            "ALTER TABLE `song` CHANGE `file` `file` VARCHAR (4096)",
            "ALTER TABLE `video` CHANGE `file` `file` VARCHAR (4096)",
            "ALTER TABLE `live_stream` CHANGE `url` `url` VARCHAR (4096)",
            "ALTER TABLE `artist` ADD FULLTEXT(`name`)",
            "ALTER TABLE `album` ADD FULLTEXT(`name`)",
            "ALTER TABLE `song` ADD FULLTEXT(`title`)"
        );
        foreach ($sql_array as $sql) {
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        // Now add in the min_object_count preference and the random_method
        self::_write_preference($interactor, 'bandwidth', 'Bandwidth', '50', 5, 'integer', 'interface');
        self::_write_preference($interactor, 'features', 'Features', '50', 5, 'integer', 'interface');

        return true;
    }

    /**
     * _update_360003
     *
     * This update moves the image data to its own table.
     */
    private static function _update_360003(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        $sql       = "CREATE TABLE IF NOT EXISTS `image` (`id` int(11) unsigned NOT NULL auto_increment, `image` mediumblob NOT NULL, `mime` varchar(64) NOT NULL, `size` varchar(64) NOT NULL, `object_type` varchar(64) NOT NULL, `object_id` int(11) unsigned NOT NULL, PRIMARY KEY (`id`), KEY `object_type` (`object_type`), KEY `object_id` (`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        foreach (array('album', 'artist') as $type) {
            $sql        = "SELECT `" . $type . "_id` AS `object_id`, `art`, `art_mime` FROM `" . $type . "_data` WHERE `art` IS NOT NULL";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `object_type`, `object_id`) VALUES('" . Dba::escape($row['art']) . "', '" . $row['art_mime'] . "', 'original', '" . $type . "', '" . $row['object_id'] . "')";
                Dba::write($sql);
            }
            $sql = "DROP TABLE IF EXISTS `" . $type . "_data`";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * _update_360004
     *
     * This update creates an index on the rating table.
     */
    private static function _update_360004(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "CREATE UNIQUE INDEX `unique_rating` ON `rating` (`user`, `object_type`, `object_id`);") !== false);
    }

    /**
     * _update_360005
     *
     * This changes the tmp_browse table around.
     */
    private static function _update_360005(Interactor $interactor = null): bool
    {
        $sql = "DROP TABLE IF EXISTS `tmp_browse`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `tmp_browse` (`id` int(13) NOT NULL auto_increment, `sid` varchar(128) CHARACTER SET utf8 NOT NULL, `data` longtext NOT NULL, `object_data` longtext, PRIMARY KEY (`sid`, `id`)) ENGINE=MYISAM DEFAULT CHARSET=utf8;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_360006
     *
     * This adds the table for newsearch/dynamic playlists
     */
    private static function _update_360006(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (self::_write($interactor, "CREATE TABLE IF NOT EXISTS `search` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) NOT NULL, `type` enum('private','public') CHARACTER SET utf8 DEFAULT NULL, `rules` mediumtext NOT NULL, `name` varchar(255) CHARACTER SET $charset DEFAULT NULL, `logic_operator` varchar(3) CHARACTER SET $charset DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine AUTO_INCREMENT=4 DEFAULT CHARSET=$charset;") !== false);
    }

    /**
     * _update_360008
     *
     * Fix bug that caused the remote_username/password fields to not be created.
     * FIXME: Huh?
     */
    private static function _update_360008(Interactor $interactor = null): bool
    {
        $remote_username = false;
        $remote_password = false;

        $sql        = "DESCRIBE `catalog`";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['Field'] == 'remote_username') {
                $remote_username = true;
            }
            if ($row['Field'] == 'remote_password') {
                $remote_password = true;
            }
        } // end while

        if (!$remote_username) {
            // Add in Username / Password for catalog - to be used for remote catalogs
            $sql = "ALTER TABLE `catalog` ADD `remote_username` VARCHAR (255) AFTER `catalog_type`";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }
        if (!$remote_password) {
            $sql = "ALTER TABLE `catalog` ADD `remote_password` VARCHAR (255) AFTER `remote_username`";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * _update_360009
     *
     * The main session table was already updated to use varchar(64) for the ID,
     * tmp_playlist needs the same change
     */
    private static function _update_360009(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `tmp_playlist` CHANGE `session` `session` varchar(64);") !== false);
    }

    /**
     * _update_360010
     *
     * MBz NGS means collaborations have more than one MBID
     * (the ones belonging to the underlying artists).  We need a bigger column.
     */
    private static function _update_360010(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `artist` CHANGE `mbid` `mbid` varchar(36);") !== false);
    }

    /**
     * _update_360011
     *
     * We need a place to store actual playlist data for downloadable
     * playlist files.
     */
    private static function _update_360011(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "CREATE TABLE IF NOT EXISTS `stream_playlist` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `sid` varchar(64) NOT NULL, `url` text NOT NULL, `info_url` text DEFAULT NULL, `image_url` text DEFAULT NULL, `title` varchar(255) DEFAULT NULL, `author` varchar(255) DEFAULT NULL, `album` varchar(255) DEFAULT NULL, `type` varchar(255) DEFAULT NULL, `time` smallint(5) DEFAULT NULL, PRIMARY KEY (`id`), KEY `sid` (`sid`));") !== false);
    }

    /**
     * _update_360012
     *
     * Drop the enum on session.type
     */
    private static function _update_360012(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `session` CHANGE `type` `type` varchar(16) DEFAULT NULL;") !== false);
    }

    /**
     * _update_360013
     *
     * MyISAM works better out of the box for the stream_playlist table
     */
    private static function _update_360013(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (self::_write($interactor, "ALTER TABLE `stream_playlist` ENGINE=$engine;") !== false);
    }

    /**
     * _update_360014
     *
     * PHP session IDs are an ever-growing beast.
     */
    private static function _update_360014(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `stream_playlist` CHANGE `sid` `sid` varchar(256);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `tmp_playlist` CHANGE `session` `session` varchar(256);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `session` CHANGE `id` `id` varchar(256) NOT NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_360015
     *
     * This inserts the Iframes preference...
     */
    private static function _update_360015(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'iframes', 'Iframes', '1', 25, 'boolean', 'interface');
    }

    /*
     * _update_360016
     *
     * Add Now Playing filtered per user preference option
     */
    private static function _update_360016(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'now_playing_per_user', 'Now playing filtered per user', '1', 50, 'boolean', 'interface');
    }

    /**
     * _update_360017
     *
     * New table to store user flags.
     */
    private static function _update_360017(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (self::_write($interactor, "CREATE TABLE IF NOT EXISTS `user_flag` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) NOT NULL, `object_id` int(11) unsigned NOT NULL, `object_type` varchar(32) CHARACTER SET $charset DEFAULT NULL, `date` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`), UNIQUE KEY `unique_userflag` (`user`, `object_type`, `object_id`), KEY `object_id` (`object_id`)) ENGINE=$engine;") !== false);
    }

    /**
     * _update_360018
     *
     * Add Album default sort preference...
     */
    private static function _update_360018(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'album_sort', 'Album Default Sort', '0', 25, 'string', 'interface');
    }

    /**
     * _update_360019
     *
     * Add Show number of times a song was played preference
     */
    private static function _update_360019(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'show_played_times', 'Show # played', '0', 25, 'string', 'interface');
    }

    /**
     * _update_360020
     *
     * Catalog types are plugins now
     */
    private static function _update_360020(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE IF NOT EXISTS `catalog_local` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `path` varchar(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        Dba::write($sql);
        $sql = "CREATE TABLE IF NOT EXISTS `catalog_remote` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `uri` varchar(255) COLLATE $collation NOT NULL, `username` varchar(255) COLLATE $collation NOT NULL, `password` varchar(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        Dba::write($sql);

        $sql        = "SELECT `id`, `catalog_type`, `path`, `remote_username`, `remote_password` FROM `catalog`";
        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            if ($results['catalog_type'] == 'local') {
                $sql = "INSERT INTO `catalog_local` (`path`, `catalog_id`) VALUES (?, ?)";
                if (self::_write($interactor, $sql, array($results['path'], $results['id'])) === false) {
                    return false;
                }
            } elseif ($results['catalog_type'] == 'remote') {
                $sql = "INSERT INTO `catalog_remote` (`uri`, `username`, `password`, `catalog_id`) VALUES (?, ?, ?, ?)";
                if (self::_write($interactor, $sql, array($results['path'], $results['remote_username'], $results['remote_password'], $results['id'])) === false) {
                    return false;
                }
            }
        }

        $sql_array = array(
            "ALTER TABLE `catalog` DROP `path`, DROP `remote_username`, DROP `remote_password`",
            "ALTER TABLE `catalog` MODIFY COLUMN `catalog_type` varchar(128)",
            "UPDATE `artist` SET `mbid` = NULL WHERE `mbid` = ''",
            "UPDATE `album` SET `mbid` = NULL WHERE `mbid` = ''",
            "UPDATE `song` SET `mbid` = NULL WHERE `mbid` = ''"
        );
        foreach ($sql_array as $sql) {
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * _update_360021
     *
     * Add insertion date on Now Playing and option to show the current song in page title for Web player
     */
    private static function _update_360021(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `now_playing` ADD `insertion` INT (11) AFTER `expire`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        return self::_write_preference($interactor, 'song_page_title', 'Show current song in Web player page title', '1', 25, 'boolean', 'interface');
    }

    /**
     * _update_360022
     *
     * Remove unused live_stream fields and add codec field
     */
    private static function _update_360022(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `live_stream` ADD `codec` varchar(32) NULL AFTER `catalog`, DROP `frequency`, DROP `call_sign`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `stream_playlist` ADD `codec` varchar(32) NULL AFTER `time`";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_360023
     *
     * Enable/Disable SubSonic and Plex backend
     */
    private static function _update_360023(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'subsonic_backend', 'Use SubSonic backend', '1', 100, 'boolean', 'system') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'plex_backend', 'Use Plex backend', '0', 100, 'boolean', 'system') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_360024
     *
     * Drop unused flagged table
     */
    private static function _update_360024(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "DROP TABLE IF EXISTS `flagged`;") !== false);
    }

    /**
     * _update_360025
     *
     * Add options to enable HTML5 / Flash on web players
     */
    private static function _update_360025(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'webplayer_flash', 'Authorize Flash Web Player(s)', '1', 25, 'boolean', 'streaming') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'webplayer_html5', 'Authorize HTML5 Web Player(s)', '1', 25, 'boolean', 'streaming') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_360026
     *
     * Add agent field in `object_count` table
     */
    private static function _update_360026(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `object_count` ADD `agent` varchar(255) NULL AFTER `user`;") !== false);
    }

    /**
     * _update_360027
     *
     * Personal information: allow/disallow to show my personal information into now playing and recently played lists.
     */
    private static function _update_360027(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'allow_personal_info', 'Allow to show my personal info to other users (now playing, recently played)', '1', 25, 'boolean', 'interface');
    }

    /**
     * _update_360028
     *
     * Personal information: allow/disallow to show in now playing.
     * Personal information: allow/disallow to show in recently played.
     * Personal information: allow/disallow to show time and/or agent in recently played.
     */
    private static function _update_360028(Interactor $interactor = null): bool
    {
        // Update previous update preference
        $sql = "UPDATE `preference` SET `name`='allow_personal_info_now', `description`='Personal information visibility - Now playing' WHERE `name`='allow_personal_info'";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        // Insert new recently played preference
        if (self::_write_preference($interactor, 'allow_personal_info_recent', 'Personal information visibility - Recently played / actions', '1', 25, 'boolean', 'interface') === false) {
            return false;
        }
        // Insert streaming time preference
        if (self::_write_preference($interactor, 'allow_personal_info_time', 'Personal information visibility - Recently played - Allow to show streaming date/time', '1', 25, 'boolean', 'interface') === false) {
            return false;
        }
        // Insert streaming agent preference
        if (self::_write_preference($interactor, 'allow_personal_info_agent', 'Personal information visibility - Recently played - Allow to show streaming agent', '1', 25, 'boolean', 'interface') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_360029
     *
     * New table to store wanted releases
     */
    private static function _update_360029(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (self::_write($interactor, "CREATE TABLE IF NOT EXISTS `wanted` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) NOT NULL, `artist` int(11) NOT NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL, `name` varchar(255) CHARACTER SET $charset NOT NULL, `year` int(4) NULL, `date` int(11) unsigned NOT NULL DEFAULT '0', `accepted` tinyint(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), UNIQUE KEY `unique_wanted` (`user`, `artist`, `mbid`)) ENGINE=$engine;") !== false);
    }

    /**
     * _update_360030
     *
     * New table to store song previews
     */
    private static function _update_360030(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (self::_write($interactor, "CREATE TABLE IF NOT EXISTS `song_preview` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `session` varchar(256) CHARACTER SET $charset NOT NULL, `artist` int(11) NOT NULL, `title` varchar(255) CHARACTER SET $charset NOT NULL, `album_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL, `disk` int(11) NULL, `track` int(11) NULL, `file` varchar(255) CHARACTER SET $charset NULL, PRIMARY KEY (`id`)) ENGINE=$engine;") !== false);
    }

    /**
     * _update_360031
     *
     * Add option to fix header/sidebars position on compatible themes
     */
    private static function _update_360031(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'ui_fixed', 'Fix header position on compatible themes', '0', 25, 'boolean', 'interface');
    }

    /**
     * _update_360032
     *
     * Add check update automatically option
     */
    private static function _update_360032(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'autoupdate', 'Check for Ampache updates automatically', '1', 100, 'boolean', 'system') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'autoupdate_lastcheck', 'AutoUpdate last check time', '', 25, 'string', 'internal') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'autoupdate_lastversion', 'AutoUpdate last version from last check', '', 25, 'string', 'internal') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'autoupdate_lastversion_new', 'AutoUpdate last version from last check is newer', '', 25, 'boolean', 'internal') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_360033
     *
     * Add song waveform as song data
     */
    private static function _update_360033(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `song_data` ADD `waveform` MEDIUMBLOB NULL AFTER `language`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user_shout` ADD `data` varchar(256) NULL AFTER `object_type`";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_360034
     *
     * Add settings for confirmation when closing window and auto-pause between tabs
     */
    private static function _update_360034(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'webplayer_confirmclose', 'Confirmation when closing current playing window', '0', 25, 'boolean', 'interface') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'webplayer_pausetabs', 'Auto-pause betweens tabs', '1', 25, 'boolean', 'interface') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_360035
     *
     * Add beautiful stream url setting
     * Reverted https://github.com/ampache/ampache/commit/0c26c336269624d75985e46d324e2bc8108576ee
     * with adding update_380012.
     * Because it was changed after many systems have already performed this update.
     * Fix for this is update_380012 that actually readds the preference string.
     * So all users have a consistent database.
     */
    private static function _update_360035(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'stream_beautiful_url', 'Use beautiful stream url', '0', 100, 'boolean', 'streaming');
    }

    /**
     * _update_360036
     *
     * Remove some unused parameters
     */
    private static function _update_360036(Interactor $interactor = null): bool
    {
        $sql = "DELETE FROM `preference` WHERE `name` LIKE 'ellipse_threshold_%'";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "DELETE FROM `preference` WHERE `name` = 'min_object_count'";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "DELETE FROM `preference` WHERE `name` = 'bandwidth'";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "DELETE FROM `preference` WHERE `name` = 'features'";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "DELETE FROM `preference` WHERE `name` = 'tags_userlist'";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_360037
     *
     * Add sharing features
     */
    private static function _update_360037(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE IF NOT EXISTS `share` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `allow_stream` tinyint(1) unsigned NOT NULL DEFAULT '0', `allow_download` tinyint(1) unsigned NOT NULL DEFAULT '0', `expire_days` int(4) unsigned NOT NULL DEFAULT '0', `max_counter` int(4) unsigned NOT NULL DEFAULT '0', `secret` varchar(20) CHARACTER SET $charset NULL, `counter` int(4) unsigned NOT NULL DEFAULT '0', `creation_date` int(11) unsigned NOT NULL DEFAULT '0', `lastvisit_date` int(11) unsigned NOT NULL DEFAULT '0', `public_url` varchar(255) CHARACTER SET $charset NULL, `description` varchar(255) CHARACTER SET $charset NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'share', 'Allow Share', '0', 100, 'boolean', 'options') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'share_expire', 'Share links default expiration days (0=never)', '7', 100, 'integer', 'system') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_360038
     *
     * Add missing albums browse on missing artists
     */
    private static function _update_360038(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `wanted` ADD `artist_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL AFTER `artist`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `wanted` MODIFY `artist` int(11) NULL";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `song_preview` ADD `artist_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL AFTER `artist`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `song_preview` MODIFY `artist` int(11) NULL";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_360039
     *
     * Add website field on users
     */
    private static function _update_360039(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        return (self::_write($interactor, "ALTER TABLE `user` ADD `website` varchar(255) CHARACTER SET $charset NULL AFTER `email`;") !== false);
    }

    /**
     * _update_360041
     *
     * Add channels
     */
    private static function _update_360041(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (self::_write($interactor, "CREATE TABLE IF NOT EXISTS `channel` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(64) CHARACTER SET $charset NULL, `description` varchar(256) CHARACTER SET $charset NULL, `url` varchar(256) CHARACTER SET $charset NULL, `interface` varchar(64) CHARACTER SET $charset NULL, `port` int(11) unsigned NOT NULL DEFAULT '0', `fixed_endpoint` tinyint(1) unsigned NOT NULL DEFAULT '0', `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `is_private` tinyint(1) unsigned NOT NULL DEFAULT '0', `random` tinyint(1) unsigned NOT NULL DEFAULT '0', `loop` tinyint(1) unsigned NOT NULL DEFAULT '0', `admin_password` varchar(20) CHARACTER SET $charset NULL, `start_date` int(11) unsigned NOT NULL DEFAULT '0', `max_listeners` int(11) unsigned NOT NULL DEFAULT '0', `peak_listeners` int(11) unsigned NOT NULL DEFAULT '0', `listeners` int(11) unsigned NOT NULL DEFAULT '0', `connections` int(11) unsigned NOT NULL DEFAULT '0', `stream_type` varchar(8) CHARACTER SET $charset NOT NULL DEFAULT 'mp3', `bitrate` int(11) unsigned NOT NULL DEFAULT '128', `pid` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine;") !== false);
    }

    /**
     * _update_360042
     *
     * Add broadcasts and player control
     */
    private static function _update_360042(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE IF NOT EXISTS `broadcast` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `name` varchar(64) CHARACTER SET $charset NULL, `description` varchar(256) CHARACTER SET $charset NULL, `is_private` tinyint(1) unsigned NOT NULL DEFAULT '0', `song` int(11) unsigned NOT NULL DEFAULT '0', `started` tinyint(1) unsigned NOT NULL DEFAULT '0', `listeners` int(11) unsigned NOT NULL DEFAULT '0', `key` varchar(32) CHARACTER SET $charset NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `player_control` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `cmd` varchar(32) CHARACTER SET $charset NOT NULL, `value` varchar(256) CHARACTER SET $charset NULL, `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `send_date` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_360043
     *
     * Add slideshow on currently played artist preference
     */
    private static function _update_360043(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'slideshow_time', 'Artist slideshow inactivity time', '0', 25, 'integer', 'interface');
    }

    /**
     * _update_360044
     *
     * Add artist description/recommendation external service data cache
     */
    private static function _update_360044(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "ALTER TABLE `artist` ADD `summary` TEXT CHARACTER SET $charset NULL, ADD `placeformed` varchar(64) NULL, ADD `yearformed` int(4) NULL, ADD `last_update` int(11) unsigned NOT NULL DEFAULT '0'";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `recommendation` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `last_update` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `recommendation_item` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `recommendation` int(11) unsigned NOT NULL, `recommendation_id` int(11) unsigned NULL, `name` varchar(256) NULL, `rel` varchar(256) NULL, `mbid` varchar(36) NULL, PRIMARY KEY (`id`)) ENGINE=$engine";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_360045
     *
     * Set user field on playlists as optional
     */
    private static function _update_360045(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `playlist` MODIFY `user` int(11) NULL;") !== false);
    }

    /**
     * _update_360046
     *
     * Add broadcast web player by default preference
     */
    private static function _update_360046(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'broadcast_by_default', 'Broadcast web player by default', '0', 25, 'boolean', 'streaming');
    }

    /**
     * _update_360047
     *
     * Add apikey field on users
     */
    private static function _update_360047(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        return (self::_write($interactor, "ALTER TABLE `user` ADD `apikey` varchar(255) CHARACTER SET $charset NULL AFTER `website`;") !== false);
    }

    /**
     * _update_360048
     *
     * Add concerts options
     */
    private static function _update_360048(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'concerts_limit_future', 'Limit number of future events', '0', 25, 'integer', 'interface') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'concerts_limit_past', 'Limit number of past events', '0', 25, 'integer', 'interface') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_360049
     *
     * Add album group multiple disks setting
     */
    private static function _update_360049(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'album_group', 'Album - Group multiple disks', '0', 25, 'boolean', 'interface');
    }

    /**
     * _update_360050
     *
     * Add top menu setting
     */
    private static function _update_360050(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'topmenu', 'Top menu', '0', 25, 'boolean', 'interface');
    }

    /**
     * _update_370001
     *
     * Drop unused dynamic_playlist tables and add session id to votes
     */
    private static function _update_370001(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        $sql = "DROP TABLE IF EXISTS `dynamic_playlist`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "DROP TABLE IF EXISTS `dynamic_playlist_data`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user_vote` ADD `sid` varchar(256) CHARACTER SET $charset NULL AFTER `date`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        return self::_write_preference($interactor, 'demo_clear_sessions', 'Clear democratic votes of expired user sessions', '0', 25, 'boolean', 'playlist');
    }

    /**
     * _update_370002
     *
     * Add tag persistent merge reference
     */
    private static function _update_370002(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `tag` ADD `merged_to` int(11) NULL AFTER `name`;") !== false);
    }

    /**
     * _update_370003
     *
     * Add show/hide donate button preference
     */
    private static function _update_370003(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'show_donate', 'Show donate button in footer', '1', 25, 'boolean', 'interface');
    }

    /**
     * _update_370004
     *
     * Add system upload preferences
     * Add license information and user's artist association
     */
    private static function _update_370004(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        if (self::_write_preference($interactor, 'upload_catalog', 'Uploads catalog destination', '-1', 100, 'integer', 'system') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'allow_upload', 'Allow users to upload media', '0', 75, 'boolean', 'options') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'upload_subdir', 'Upload: create a subdirectory per user (recommended)', '1', 100, 'boolean', 'system') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'upload_user_artist', 'Upload: consider the user sender as the track\'s artist', '0', 100, 'boolean', 'system') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'upload_script', 'Upload: run the following script after upload (current directory = upload target directory)', '', 100, 'string', 'system') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'upload_allow_edit', 'Upload: allow users to edit uploaded songs', '1', 100, 'boolean', 'system') === false) {
            return false;
        }
        $sql_array = array(
            "ALTER TABLE `artist` ADD `user` int(11) NULL AFTER `last_update`",
            "CREATE TABLE IF NOT EXISTS `license` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(80) NOT NULL, `description` varchar(256) NULL, `external_link` varchar(256) NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('0 - default', '')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY', 'https://creativecommons.org/licenses/by/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY NC', 'https://creativecommons.org/licenses/by-nc/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY NC ND', 'https://creativecommons.org/licenses/by-nc-nd/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY NC SA', 'https://creativecommons.org/licenses/by-nc-sa/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY ND', 'https://creativecommons.org/licenses/by-nd/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('CC BY SA', 'https://creativecommons.org/licenses/by-sa/3.0/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('Licence Art Libre', 'http://artlibre.org/licence/lal/')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('Yellow OpenMusic', 'http://openmusic.linuxtag.org/yellow.html')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('Green OpenMusic', 'http://openmusic.linuxtag.org/green.html')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('Gnu GPL Art', 'http://gnuart.org/english/gnugpl.html')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('WTFPL', 'https://en.wikipedia.org/wiki/WTFPL')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('FMPL', 'http://www.fmpl.org/fmpl.html')",
            "INSERT INTO `license` (`name`, `external_link`) VALUES ('C Reaction', 'http://morne.free.fr/Necktar7/creaction.htm')",
            "ALTER TABLE `song` ADD `user_upload` int(11) NULL AFTER `addition_time`, ADD `license` int(11) NULL AFTER `user_upload`"
        );
        foreach ($sql_array as $sql) {
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * _update_370005
     *
     * Add new column album_artist into table album
     */
    private static function _update_370005(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `song` ADD `album_artist` int(11) unsigned DEFAULT NULL AFTER `artist`;") !== false);
    }

    /**
     * _update_370006
     *
     * Add random and limit options to smart playlists
     */
    private static function _update_370006(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `search` ADD `random` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `logic_operator`, ADD `limit` int(11) unsigned NOT NULL DEFAULT '0' AFTER `random`;") !== false);
    }

    /**
     * _update_370007
     *
     * Add DAAP backend preference
     */
    private static function _update_370007(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        if (self::_write_preference($interactor, 'daap_backend', 'Use DAAP backend', '0', 100, 'boolean', 'system') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'daap_pass', 'DAAP backend password', '', 100, 'string', 'system') === false) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `daap_session` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `creationdate` int(11) unsigned NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_370008
     *
     * Add UPnP backend preference
     */
    private static function _update_370008(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'upnp_backend', 'Use UPnP backend', '0', 100, 'boolean', 'system');
    }

    /**
     * _update_370009
     *
     * Enhance video support with TVShows and Movies
     */
    private static function _update_370009(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql_array = array(
            "ALTER TABLE `video` ADD `release_date` date NULL AFTER `enabled`, ADD `played` tinyint(1) unsigned DEFAULT '0' NOT NULL AFTER `enabled`",
            "CREATE TABLE IF NOT EXISTS `tvshow` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(80) NOT NULL, `summary` varchar(256) NULL, `year` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "CREATE TABLE IF NOT EXISTS `tvshow_season` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `season_number` int(11) unsigned NOT NULL, `tvshow` int(11) unsigned NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "CREATE TABLE IF NOT EXISTS `tvshow_episode` (`id` int(11) unsigned NOT NULL, `original_name` varchar(80) NULL, `season` int(11) unsigned NOT NULL, `episode_number` int(11) unsigned NOT NULL, `summary` varchar(256) NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "CREATE TABLE IF NOT EXISTS `movie` (`id` int(11) unsigned NOT NULL, `original_name` varchar(80) NULL, `summary` varchar(256) NULL, `year` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "CREATE TABLE IF NOT EXISTS `personal_video` (`id` int(11) unsigned NOT NULL, `location` varchar(256) NULL, `summary` varchar(256) NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "CREATE TABLE IF NOT EXISTS `clip` (`id` int(11) unsigned NOT NULL, `artist` int(11) NULL, `song` int(11) NULL, PRIMARY KEY (`id`)) ENGINE=$engine"
        );
        foreach ($sql_array as $sql) {
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        return self::_write_preference($interactor, 'allow_video', 'Allow video features', '1', 75, 'integer', 'options');
    }

    /**
     * _update_370010
     *
     * Add MusicBrainz Album Release Group identifier
     */
    private static function _update_370010(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        return (self::_write($interactor, "ALTER TABLE `album` ADD `mbid_group` varchar(36) CHARACTER SET $charset NULL;") !== false);
    }

    /**
     * _update_370011
     *
     * Add Prefix to TVShows and Movies
     */
    private static function _update_370011(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        $sql = "ALTER TABLE `tvshow` ADD `prefix` varchar(32) CHARACTER SET $charset NULL";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `movie` ADD `prefix` varchar(32) CHARACTER SET $charset NULL";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_370012
     *
     * Add metadata information to albums / songs / videos
     */
    private static function _update_370012(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        $sql = "ALTER TABLE `album` ADD `release_type` varchar(32) CHARACTER SET $charset NULL";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `song` ADD `composer` varchar(256) CHARACTER SET $charset NULL, ADD `channels` MEDIUMINT NULL";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `video` ADD `channels` MEDIUMINT NULL, ADD `bitrate` MEDIUMINT(8) NULL, ADD `video_bitrate` MEDIUMINT(8) NULL, ADD `display_x` MEDIUMINT(8) NULL, ADD `display_y` MEDIUMINT(8) NULL, ADD `frame_rate` FLOAT NULL, ADD `mode` enum('abr','vbr','cbr') NULL DEFAULT 'cbr'";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        return self::_write_preference($interactor, 'allow_video', 'Allow video features', '1', 75, 'integer', 'options');
    }

    /**
     * _update_370013
     *
     * Replace iframe with ajax page load
     */
    private static function _update_370013(Interactor $interactor = null): bool
    {
        $sql = "DELETE FROM `preference` WHERE `name` = 'iframes'";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        return self::_write_preference($interactor, 'ajax_load', 'Ajax page load', '1', 25, 'boolean', 'interface');
    }

    /**
     * update 370014
     *
     * Modified release_date of table video to signed int(11)
     */
    private static function _update_370014(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `video` CHANGE COLUMN `release_date` `release_date` INT NULL DEFAULT NULL;") !== false);
    }

    /**
     * update 370015
     *
     * Add session_remember table to store remember tokens
     */
    private static function _update_370015(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (self::_write($interactor, "CREATE TABLE IF NOT EXISTS `session_remember` (`username` varchar(16) NOT NULL, `token` varchar(32) NOT NULL, `expire` int(11) NULL, PRIMARY KEY (`username`, `token`)) ENGINE=$engine;") !== false);
    }

    /**
     * update 370016
     *
     * Add limit of media count for direct play preference
     */
    private static function _update_370016(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'direct_play_limit', 'Limit direct play to maximum media count', '0', 25, 'integer', 'interface');
    }

    /**
     * update 370017
     *
     * Add home display settings
     */
    private static function _update_370017(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'home_moment_albums', 'Show Albums of the moment at home page', '1', 25, 'integer', 'interface') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'home_moment_videos', 'Show Videos of the moment at home page', '1', 25, 'integer', 'interface') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'home_now_playing', 'Show Now Playing at home page', '1', 25, 'integer', 'interface') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'custom_logo', 'Custom logo url', '', 25, 'string', 'interface') === false) {
            return false;
        }

        return true;
    }

    /*
     * update 370018
     *
     * Enhance tag persistent merge reference.
     */
    private static function _update_370018(Interactor $interactor = null): bool
    {
        $sql = "DROP TABLE IF EXISTS `tag_merge`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `tag_merge` (`tag_id` int(11) NOT NULL, `merged_to` int(11) NOT NULL, PRIMARY KEY (`tag_id`,`merged_to`), KEY `merged_to` (`merged_to`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql        = "DESCRIBE `tag`";
        $db_results = Dba::read($sql);
        $is_hidden  = false;
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['Field'] == 'merged_to') {
                $sql = "INSERT INTO `tag_merge` (`tag_id`, `merged_to`) SELECT `tag`.`id`, `tag`.`merged_to` FROM `tag` WHERE `merged_to` IS NOT NULL";
                if (self::_write($interactor, $sql) === false) {
                    return false;
                }
                // don't drop until you've confirmed a merge
                $sql = "ALTER TABLE `tag` DROP COLUMN `merged_to`";
                if (self::_write($interactor, $sql) === false) {
                    return false;
                }
            }
            if ($row['Field'] == 'is_hidden') {
                $is_hidden = true;
            }
        }
        if (!$is_hidden) {
            $sql = "ALTER TABLE `tag` ADD COLUMN `is_hidden` TINYINT(1) NOT NULL DEFAULT 0";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * update 370019
     *
     * Add album group order setting
     */
    private static function _update_370019(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'album_release_type_sort', 'Album - Group per release type Sort', 'album,ep,live,single', 25, 'string', 'interface');
    }

    /**
     * update 370020
     *
     * Add webplayer browser notification settings
     */
    private static function _update_370020(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'browser_notify', 'WebPlayer browser notifications', '1', 25, 'integer', 'interface') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'browser_notify_timeout', 'WebPlayer browser notifications timeout (seconds)', '10', 25, 'integer', 'interface') === false) {
            return false;
        }

        return true;
    }

    /**
     * update 370021
     *
     * Add rating to playlists, tvshows and tvshows seasons
     */
    private static function _update_370021(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `rating` CHANGE `object_type` `object_type` enum('artist','album','song','stream','video','playlist','tvshow','tvshow_season') NULL;") !== false);
    }

    /**
     * update 370022
     *
     * Add users geolocation
     */
    private static function _update_370022(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `session` ADD COLUMN `geo_latitude` DECIMAL(10,6) NULL, ADD COLUMN `geo_longitude` DECIMAL(10,6) NULL, ADD COLUMN `geo_name` varchar(255) NULL";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `object_count` ADD COLUMN `geo_latitude` DECIMAL(10,6) NULL, ADD COLUMN `geo_longitude` DECIMAL(10,6) NULL, ADD COLUMN `geo_name` varchar(255) NULL";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        return self::_write_preference($interactor, 'geolocation', 'Allow geolocation', '0', 25, 'integer', 'options');
    }

    /**
     * update 370023
     *
     * Add Aurora.js webplayer option
     */
    private static function _update_370023(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'webplayer_aurora', 'Authorize JavaScript decoder (Aurora.js) in Web Player(s)', '1', 25, 'boolean', 'streaming');
    }

    /**
     * update 370024
     *
     * Add count_type column to object_count table
     */
    private static function _update_370024(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `object_count` ADD COLUMN `count_type` varchar(16) NOT NULL DEFAULT 'stream';") !== false);
    }

    /**
     * update 370025
     *
     * Add state and city fields to user table
     */
    private static function _update_370025(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `user` ADD COLUMN `state` varchar(64) NULL, ADD COLUMN `city` varchar(64) NULL;") !== false);
    }

    /**
     * update 370026
     *
     * Add replay gain fields to song_data table
     */
    private static function _update_370026(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `song_data` ADD COLUMN `replaygain_track_gain` DECIMAL(10,6) NULL, ADD COLUMN `replaygain_track_peak` DECIMAL(10,6) NULL, ADD COLUMN `replaygain_album_gain` DECIMAL(10,6) NULL, ADD COLUMN `replaygain_album_peak` DECIMAL(10,6) NULL;") !== false);
    }

    /**
     * _update_370027
     *
     * Move column album_artist from table song to table album
     */
    private static function _update_370027(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `album` ADD `album_artist` int(11) unsigned DEFAULT NULL AFTER `release_type`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "UPDATE `album` INNER JOIN `song` ON `album`.`id` = `song`.`album` SET `album`.`album_artist` = `song`.`album_artist`";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `song` DROP COLUMN `album_artist`";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_370028
     *
     * Add width and height in table image
     */
    private static function _update_370028(Interactor $interactor = null): bool
    {
        $sql        = "SELECT `width` FROM `image`";
        $db_results = Dba::read($sql);
        if (!$db_results) {
            $sql = "ALTER TABLE `image` ADD `width` int(4) unsigned DEFAULT 0 AFTER `image`";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }
        $sql        = "SELECT `height` FROM `image`";
        $db_results = Dba::read($sql);
        if (!$db_results) {
            $sql = "ALTER TABLE `image` ADD `height` int(4) unsigned DEFAULT 0 AFTER `width`";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * _update_370029
     *
     * Set image column from image table as nullable.
     */
    private static function _update_370029(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `image` CHANGE COLUMN `image` `image` MEDIUMBLOB NULL DEFAULT NULL;") !== false);
    }

    /**
     * _update_370030
     *
     * Add an option to allow users to remove uploaded songs.
     */
    private static function _update_370030(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'upload_allow_remove', 'Upload: allow users to remove uploaded songs', '1', 100, 'boolean', 'system');
    }

    /**
     * _update_370031
     *
     * Add an option to customize login art, favicon and text footer.
     */
    private static function _update_370031(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'custom_login_logo', 'Custom login page logo url', '', 75, 'string', 'interface') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'custom_favicon', 'Custom favicon url', '', 75, 'string', 'interface') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'custom_text_footer', 'Custom text footer', '', 75, 'string', 'interface') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_370032
     *
     * Add WebDAV backend preference.
     */
    private static function _update_370032(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'webdav_backend', 'Use WebDAV backend', '0', 100, 'boolean', 'system');
    }

    /**
     * _update_370033
     *
     * Add Label tables.
     */
    private static function _update_370033(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE IF NOT EXISTS `label` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(80) NOT NULL, `category` varchar(40) NULL, `summary` TEXT CHARACTER SET $charset NULL, `address` varchar(256) NULL, `email` varchar(128) NULL, `website` varchar(256) NULL, `user` int(11) unsigned NULL, `creation_date` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `label_asso` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `label` int(11) unsigned NOT NULL, `artist` int(11) unsigned NOT NULL, `creation_date` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_370034
     *
     * Add User messages and user follow tables.
     */
    private static function _update_370034(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE IF NOT EXISTS `user_pvmsg` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `subject` varchar(80) NOT NULL, `message` TEXT CHARACTER SET $charset NULL, `from_user` int(11) unsigned NOT NULL, `to_user` int(11) unsigned NOT NULL, `is_read` tinyint(1) unsigned NOT NULL DEFAULT '0', `creation_date` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `user_follower` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `follow_user` int(11) unsigned NOT NULL, `follow_date` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        return self::_write_preference($interactor, 'notify_email', 'Receive notifications by email (shouts, private messages, ...)', '0', 25, 'boolean', 'options');
    }

    /**
     * _update_370035
     *
     * Add option on user fullname to show/hide it publicly
     */
    private static function _update_370035(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `user` ADD COLUMN `fullname_public` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';") !== false);
    }

    /**
     * _update_370036
     *
     * Add field for track number when generating streaming playlists
     */
    private static function _update_370036(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `stream_playlist` ADD COLUMN `track_num` SMALLINT(5) DEFAULT '0';") !== false);
    }

    /**
     * _update_370037
     *
     * Delete http_port preference (use ampache.cfg.php configuration instead)
     */
    private static function _update_370037(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "DELETE FROM `preference` WHERE `name` = 'http_port';") !== false);
    }

    /**
     * _update_370038
     *
     * Add theme color option
     */
    private static function _update_370038(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'theme_color', 'Theme color', 'dark', 0, 'special', 'interface');
    }

    /**
     * _update_370039
     *
     * Renamed false named sample_rate option name in preference table
     */
    private static function _update_370039(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "UPDATE `preference` SET `name` = 'transcode_bitrate' WHERE `preference`.`name` = 'sample_rate';") !== false);
    }

    /**
     * _update_370040
     *
     * Add user_activity table
     */
    private static function _update_370040(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (self::_write($interactor, "CREATE TABLE IF NOT EXISTS `user_activity` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `user` INT(11) NOT NULL, `action` varchar(20) NOT NULL, `object_id` INT(11) UNSIGNED NOT NULL, `object_type` varchar(32) NOT NULL, `activity_date` INT(11) UNSIGNED NOT NULL) ENGINE=$engine;") !== false);
    }

    /**
     * _update_370041
     *
     * Add Metadata tables and preferences
     */
    private static function _update_370041(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE IF NOT EXISTS `metadata_field` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) NOT NULL, `public` tinyint(1) NOT NULL, UNIQUE KEY `name` (`name`) ) ENGINE=$engine";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `metadata` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `object_id` INT(11) UNSIGNED NOT NULL, `field` INT(11) UNSIGNED NOT NULL, `data` text COLLATE $collation NOT NULL, `type` varchar(50) CHARACTER SET $charset DEFAULT NULL, KEY `field` (`field`), KEY `object_id` (`object_id`), KEY `type` (`type`), KEY `objecttype` (`object_id`, `type`), KEY `objectfield` (`object_id`, `field`, `type`) ) ENGINE=$engine";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'disabled_custom_metadata_fields', 'Disable custom metadata fields (ctrl / shift click to select multiple)', '', 100, 'string', 'system') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'disabled_custom_metadata_fields_input', 'Disable custom metadata fields. Insert them in a comma separated list. They will add to the fields selected above.', '', 100, 'string', 'system') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_380001
     *
     * Add podcasts
     */
    private static function _update_380001(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE IF NOT EXISTS `podcast` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `feed` varchar(4096) NOT NULL, `catalog` int(11) NOT NULL, `title` varchar(255) CHARACTER SET $charset NOT NULL, `website` varchar(255) NULL, `description` varchar(4096) CHARACTER SET $charset NULL, `language` varchar(5) NULL, `copyright` varchar(64) NULL, `generator` varchar(64) NULL, `lastbuilddate` int(11) UNSIGNED DEFAULT '0' NOT NULL, `lastsync` int(11) UNSIGNED DEFAULT '0' NOT NULL) ENGINE=$engine";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `podcast_episode` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) CHARACTER SET $charset NOT NULL, `guid` varchar(255) NOT NULL, `podcast` int(11) NOT NULL, `state` varchar(32) NOT NULL, `file` varchar(4096) CHARACTER SET $charset NULL, `source` varchar(4096) NULL, `size` bigint(20) UNSIGNED DEFAULT '0' NOT NULL, `time` smallint(5) UNSIGNED DEFAULT '0' NOT NULL, `website` varchar(255) NULL, `description` varchar(4096) CHARACTER SET $charset NULL, `author` varchar(64) NULL, `category` varchar(64) NULL, `played` tinyint(1) UNSIGNED DEFAULT '0' NOT NULL, `pubdate` int(11) UNSIGNED NOT NULL, `addition_time` int(11) UNSIGNED NOT NULL) ENGINE=$engine";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'podcast_keep', 'Podcast: # latest episodes to keep', '10', 100, 'integer', 'system') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'podcast_new_download', 'Podcast: # episodes to download when new episodes are available', '1', 100, 'integer', 'system') === false) {
            return false;
        }
        $sql = "ALTER TABLE `rating` CHANGE `object_type` `object_type` enum('artist','album','song','stream','video','playlist','tvshow','tvshow_season','podcast','podcast_episode') NULL";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_380002
     *
     * Add bookmarks
     */
    private static function _update_380002(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (self::_write($interactor, "CREATE TABLE IF NOT EXISTS `bookmark` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `user` int(11) UNSIGNED NOT NULL, `position` int(11) UNSIGNED DEFAULT '0' NOT NULL, `comment` varchar(255) CHARACTER SET $charset NOT NULL, `object_type` varchar(64) NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `creation_date` int(11) UNSIGNED DEFAULT '0' NOT NULL, `update_date` int(11) UNSIGNED DEFAULT '0' NOT NULL) ENGINE=$engine;") !== false);
    }

    /**
     * _update_380003
     *
     * Add unique constraint on tag_map table
     */
    private static function _update_380003(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER IGNORE TABLE `tag_map` ADD UNIQUE INDEX `UNIQUE_TAG_MAP` (`object_id`, `object_type`, `user`, `tag_id`);") !== false);
    }

    /**
     * _update_380004
     *
     * Add preference subcategory
     */
    private static function _update_380004(Interactor $interactor = null): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        return (self::_write($interactor, "ALTER TABLE `preference` ADD `subcatagory` varchar(128) CHARACTER SET $charset DEFAULT NULL AFTER `catagory`;") !== false);
    }

    /**
     * _update_380005
     *
     * Add manual update flag on artist
     */
    private static function _update_380005(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `artist` ADD COLUMN `manual_update` SMALLINT(1) DEFAULT '0';") !== false);
    }

    /**
     * _update_380006
     *
     * Add library item context menu option
     */
    private static function _update_380006(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'libitem_contextmenu', 'Library item context menu', '1', 0, 'boolean', 'interface', 'library');
    }

    /**
     * _update_380007
     *
     * Add upload rename pattern and ignore duplicate options
     */
    private static function _update_380007(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'upload_catalog_pattern', 'Rename uploaded file according to catalog pattern', '0', 100, 'boolean', 'system', 'upload') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'catalog_check_duplicate', 'Check library item at import time and disable duplicates', '0', 100, 'boolean', 'system', 'catalog') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_380008
     *
     * Add browse filter and light sidebar options
     */
    private static function _update_380008(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'browse_filter', 'Show filter box on browse', '0', 25, 'boolean', 'interface', 'library') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'sidebar_light', 'Light sidebar by default', '0', 25, 'boolean', 'interface', 'theme') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_380009
     *
     * Add update date to playlist
     */
    private static function _update_380009(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `playlist` ADD COLUMN `last_update` int(11) unsigned NOT NULL DEFAULT '0';") !== false);
    }

    /**
     * _update_380010
     *
     * Add custom blank album/video default image and alphabet browsing options
     */
    private static function _update_380010(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'custom_blankalbum', 'Custom blank album default image', '', 75, 'string', 'interface', 'custom') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'custom_blankmovie', 'Custom blank video default image', '', 75, 'string', 'interface', 'custom') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'libitem_browse_alpha', 'Alphabet browsing by default for following library items (album,artist,...)', '', 75, 'string', 'interface', 'library') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_380011
     *
     * Fix username max size to be the same one across all tables.
     */
    private static function _update_380011(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE session MODIFY username varchar(255)";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "ALTER TABLE session_remember MODIFY username varchar(255)";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "ALTER TABLE user MODIFY username varchar(255)";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "ALTER TABLE user MODIFY fullname varchar(255)";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_380012
     *
     * Fix change in https://github.com/ampache/ampache/commit/0c26c336269624d75985e46d324e2bc8108576ee
     * That left the user base with an inconsistent database.
     * For more information, please look at update_360035.
     */
    private static function _update_380012(Interactor $interactor = null): bool
    {
        $sql = "UPDATE `preference` SET `description`='Enable url rewriting' WHERE `preference`.`name`='stream_beautiful_url'";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400000
     *
     * Increase copyright column size to fix issue #1861
     * Add name_track, name_artist, name_album to user_activity
     * Add mbid_track, mbid_artist, mbid_album to user_activity
     * Insert some decent SmartLists for a better default experience
     * Delete the following plex preferences from the server
     *   plex_backend
     *   myplex_username
     *   myplex_authtoken
     *   myplex_published
     *   plex_uniqid
     *   plex_servername
     *   plex_public_address
     *   plex_public_port
     *   plex_local_auth
     *   plex_match_email
     * Add preference for master/develop branch selection
     */
    private static function _update_400000(Interactor $interactor = null): bool
    {
        $sql_array = array(
            "ALTER TABLE `podcast` MODIFY `copyright` varchar(255)",
            "ALTER TABLE `user_activity` ADD COLUMN `name_track` varchar(255) NULL DEFAULT NULL, ADD COLUMN `name_artist` varchar(255) NULL DEFAULT NULL, ADD COLUMN `name_album` varchar(255) NULL DEFAULT NULL;",
            "ALTER TABLE `user_activity` ADD COLUMN `mbid_track` varchar(255) NULL DEFAULT NULL, ADD COLUMN `mbid_artist` varchar(255) NULL DEFAULT NULL, ADD COLUMN `mbid_album` varchar(255) NULL DEFAULT NULL;",
            "INSERT IGNORE INTO `search` (`user`, `type`, `rules`, `name`, `logic_operator`, `random`, `limit`) VALUES (-1, 'public', '[[\"artistrating\",\"equal\",\"5\",null]]', 'Artist 5*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"4\",null]]', 'Artist 4*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"3\",null]]', 'Artist 3*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"2\",null]]', 'Artist 2*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"1\",null]]', 'Artist 1*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"5\",null]]', 'Album 5*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"4\",null]]', 'Album 4*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"3\",null]]', 'Album 3*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"2\",null]]', 'Album 2*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"1\",null]]', 'Album 1*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"5\",null]]', 'Song 5*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"4\",null]]', 'Song 4*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"3\",null]]', 'Song 3*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"2\",null]]', 'Song 2*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"1\",null]]', 'Song 1*', 'AND', 0, 0);",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_backend');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'myplex_username');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'myplex_authtoken');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'myplex_published');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_uniqid');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_servername');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_public_address');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_public_port');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_local_auth');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_match_email');",
            "DELETE FROM `preference` WHERE `preference`.`name` IN ('plex_backend', 'myplex_username', 'myplex_authtoken', 'myplex_published', 'plex_uniqid', 'plex_servername', 'plex_public_address', 'plex_public_port ', 'plex_local_auth', 'plex_match_email');"
        );
        foreach ($sql_array as $sql) {
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * _update_400001
     *
     * Make sure people on older databases have the same preference categories
     */
    private static function _update_400001(Interactor $interactor = null): bool
    {
        $sql_array = array(
            "UPDATE `preference` SET `preference`.`subcatagory` = 'library' WHERE `preference`.`name` in ('album_sort', 'show_played_times', 'album_group', 'album_release_type', 'album_release_type_sort', 'libitem_contextmenu', 'browse_filter', 'libitem_browse_alpha') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'backend' WHERE `preference`.`name` in ('subsonic_backend', 'daap_backend', 'daap_pass', 'upnp_backend', 'webdav_backend') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'catalog' WHERE `preference`.`name` = 'catalog_check_duplicate' AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'custom' WHERE `preference`.`name` in ('site_title', 'custom_logo', 'custom_login_logo', 'custom_favicon', 'custom_text_footer', 'custom_blankalbum', 'custom_blankmovie') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'feature' WHERE `preference`.`name` in ('download', 'allow_stream_playback', 'allow_democratic_playback', 'share', 'allow_video', 'geolocation') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'home' WHERE `preference`.`name` in ('now_playing_per_user', 'home_moment_albums', 'home_moment_videos', 'home_recently_played', 'home_now_playing') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'httpq' WHERE `preference`.`name` = 'httpq_active' AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'lastfm' WHERE `preference`.`name` in ('lastfm_grant_link', 'lastfm_challenge') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'localplay' WHERE `preference`.`name` in ('localplay_controller', 'localplay_level', 'allow_localplay_playback') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'metadata' WHERE `preference`.`name` in ('disabled_custom_metadata_fields', 'disabled_custom_metadata_fields_input') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'mpd' WHERE `preference`.`name` = 'mpd_active' AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'notification' WHERE `preference`.`name` in ('browser_notify', 'browser_notify_timeout') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'player' WHERE `preference`.`name` in ('show_lyrics', 'song_page_title', 'webplayer_flash', 'webplayer_html5', 'webplayer_confirmclose', 'webplayer_pausetabs', 'slideshow_time', 'broadcast_by_default', 'direct_play_limit', 'webplayer_aurora') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'podcast' WHERE `preference`.`name` in ('podcast_keep', 'podcast_new_download') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'privacy' WHERE `preference`.`name` in ('allow_personal_info_now', 'allow_personal_info_recent', 'allow_personal_info_time', 'allow_personal_info_agent') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'query' WHERE `preference`.`name` in ('popular_threshold', 'offset_limit', 'stats_threshold', 'concerts_limit_future', 'concerts_limit_past') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'share' WHERE `preference`.`name` = 'share_expire' AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'shoutcast' WHERE `preference`.`name` = 'shoutcast_active' AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'theme' WHERE `preference`.`name` in ('theme_name', 'ui_fixed', 'topmenu', 'theme_color', 'sidebar_light') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'transcoding' WHERE `preference`.`name` in ('transcode_bitrate', 'rate_limit', 'transcode') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'update' WHERE `preference`.`name` in ('autoupdate', 'autoupdate_lastcheck', 'autoupdate_lastversion', 'autoupdate_lastversion_new') AND `preference`.`subcatagory` IS NULL;",
            "UPDATE `preference` SET `preference`.`subcatagory` = 'upload' WHERE `preference`.`name` in ('upload_catalog', 'allow_upload', 'upload_subdir', 'upload_user_artist', 'upload_script', 'upload_allow_edit', 'upload_allow_remove', 'upload_catalog_pattern') AND `preference`.`subcatagory` IS NULL;"
        );
        foreach ($sql_array as $sql) {
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * _update_400002
     *
     * Update disk to allow 1 instead of making it 0 by default
     * Add barcode catalog_number and original_year
     * Drop catalog_number from song_data
     */
    private static function _update_400002(Interactor $interactor = null): bool
    {
        $sql = "UPDATE `album` SET `album`.`disk` = 1 WHERE `album`.`disk` = 0;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "ALTER TABLE `album` ADD `original_year` INT(4) NULL, ADD `barcode` varchar(64) NULL, ADD `catalog_number` varchar(64) NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "ALTER TABLE `song_data` DROP `catalog_number`";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400003
     *
     * Make sure preference names are updated to current strings
     */
    private static function _update_400003(Interactor $interactor = null): bool
    {
        $sql_array = array(
            "UPDATE `preference` SET `preference`.`description` = 'Force HTTP playback regardless of port' WHERE `preference`.`name` = 'force_http_play';",
            "UPDATE `preference` SET `preference`.`description` = 'Playback Type' WHERE `preference`.`name` = 'play_type';",
            "UPDATE `preference` SET `preference`.`description` = 'httpQ Active Instance' WHERE `preference`.`name` = 'httpq_active';",
            "UPDATE `preference` SET `preference`.`description` = 'Now Playing filtered per user' WHERE `preference`.`name` = 'now_playing_per_user';",
            "UPDATE `preference` SET `preference`.`description` = 'Use Subsonic backend' WHERE `preference`.`name` = 'subsonic_backend';",
            "UPDATE `preference` SET `preference`.`description` = 'Share Now Playing information' WHERE `preference`.`name` = 'allow_personal_info_now';",
            "UPDATE `preference` SET `preference`.`description` = 'Share Recently Played information' WHERE `preference`.`name` = 'allow_personal_info_recent';",
            "UPDATE `preference` SET `preference`.`description` = 'Share Recently Played information - Allow access to streaming date/time' WHERE `preference`.`name` = 'allow_personal_info_time';",
            "UPDATE `preference` SET `preference`.`description` = 'Share Recently Played information - Allow access to streaming agent' WHERE `preference`.`name` = 'allow_personal_info_agent';",
            "UPDATE `preference` SET `preference`.`description` = 'Enable URL Rewriting' WHERE `preference`.`name` = 'stream_beautiful_url';",
            "UPDATE `preference` SET `preference`.`description` = 'Destination catalog' WHERE `preference`.`name` = 'upload_catalog';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow user uploads' WHERE `preference`.`name` = 'allow_upload';",
            "UPDATE `preference` SET `preference`.`description` = 'Create a subdirectory per user' WHERE `preference`.`name` = 'upload_subdir';",
            "UPDATE `preference` SET `preference`.`description` = 'Consider the user sender as the track''s artist' WHERE `preference`.`name` = 'upload_user_artist';",
            "UPDATE `preference` SET `preference`.`description` = 'Post-upload script (current directory = upload target directory)' WHERE `preference`.`name` = 'upload_script';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow users to edit uploaded songs' WHERE `preference`.`name` = 'upload_allow_edit';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow users to remove uploaded songs' WHERE `preference`.`name` = 'upload_allow_remove';",
            "UPDATE `preference` SET `preference`.`description` = 'Show Albums of the Moment' WHERE `preference`.`name` = 'home_moment_albums';",
            "UPDATE `preference` SET `preference`.`description` = 'Show Videos of the Moment' WHERE `preference`.`name` = 'home_moment_videos';",
            "UPDATE `preference` SET `preference`.`description` = 'Custom URL - Logo' WHERE `preference`.`name` = 'custom_logo';",
            "UPDATE `preference` SET `preference`.`description` = 'Custom URL - Login page logo' WHERE `preference`.`name` = 'custom_login_logo';",
            "UPDATE `preference` SET `preference`.`description` = 'Custom URL - Favicon' WHERE `preference`.`name` = 'custom_favicon';",
            "UPDATE `preference` SET `preference`.`description` = 'Album - Default sort' WHERE `preference`.`name` = 'album_sort';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow Geolocation' WHERE `preference`.`name` = 'Geolocation';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow Video Features' WHERE `preference`.`name` = 'allow_video';",
            "UPDATE `preference` SET `preference`.`description` = 'Democratic - Clear votes for expired user sessions' WHERE `preference`.`name` = 'demo_clear_sessions';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow Transcoding' WHERE `preference`.`name` = 'transcoding';",
            "UPDATE `preference` SET `preference`.`description` = 'Authorize Flash Web Player' WHERE `preference`.`name` = 'webplayer_flash';",
            "UPDATE `preference` SET `preference`.`description` = 'Authorize HTML5 Web Player' WHERE `preference`.`name` = 'webplayer_html5';",
            "UPDATE `preference` SET `preference`.`description` = 'Web Player browser notifications' WHERE `preference`.`name` = 'browser_notify';",
            "UPDATE `preference` SET `preference`.`description` = 'Web Player browser notifications timeout (seconds)' WHERE `preference`.`name` = 'browser_notify_timeout';",
            "UPDATE `preference` SET `preference`.`description` = 'Authorize JavaScript decoder (Aurora.js) in Web Player' WHERE `preference`.`name` = 'webplayer_aurora';",
            "UPDATE `preference` SET `preference`.`description` = 'Show Now Playing' WHERE `preference`.`name` = 'home_now_playing';",
            "UPDATE `preference` SET `preference`.`description` = 'Show Recently Played' WHERE `preference`.`name` = 'home_recently_played';",
            "UPDATE `preference` SET `preference`.`description` = '# latest episodes to keep' WHERE `preference`.`name` = 'podcast_keep';",
            "UPDATE `preference` SET `preference`.`description` = '# episodes to download when new episodes are available' WHERE `preference`.`name` = 'podcast_new_download';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow Transcoding' WHERE `preference`.`name` = 'transcode';",
            "UPDATE `preference` SET `preference`.`description` = 'Allow E-mail notifications' WHERE `preference`.`name` = 'notify_email';",
            "UPDATE `preference` SET `preference`.`description` = 'Custom metadata - Disable these fields' WHERE `preference`.`name` = 'disabled_custom_metadata_fields';",
            "UPDATE `preference` SET `preference`.`description` = 'Custom metadata - Define field list' WHERE `preference`.`name` = 'disabled_custom_metadata_fields_input';",
            "UPDATE `preference` SET `preference`.`description` = 'Auto-pause between tabs' WHERE `preference`.`name` = 'webplayer_pausetabs';"
        );
        foreach ($sql_array as $sql) {
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * _update_400004
     *
     * delete upload_user_artist database settings
     */
    private static function _update_400004(Interactor $interactor = null): bool
    {
        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'upload_user_artist');";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "DELETE FROM `preference` WHERE `preference`.`name` = 'upload_user_artist';";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400005
     *
     * Add a last_count to searches to speed up access requests
     */
    private static function _update_400005(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `search` ADD `last_count` INT(11) NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400006
     *
     * drop shoutcast_active preferences and localplay_shoutcast table
     */
    private static function _update_400006(Interactor $interactor = null): bool
    {
        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'shoutcast_active');";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "DELETE FROM `preference` WHERE `preference`.`name` = 'shoutcast_active';";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "DROP TABLE IF EXISTS `localplay_shoutcast`";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400007
     *
     * Add ui option for skip_count display
     * Add ui option for displaying dates in a custom format
     */
    private static function _update_400007(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'show_skipped_times', 'Show # skipped', '0', 25, 'boolean', 'interface', 'library') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'custom_datetime', 'Custom datetime', '', 25, 'string', 'interface', 'custom') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_400008
     *
     * Add system option for cron based cache and create related tables
     */
    private static function _update_400008(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'cron_cache', 'Cache computed SQL data (eg. media hits stats) using a cron', '0', 100, 'boolean', 'system', 'catalog') === false) {
            return false;
        }

        $tables    = ['cache_object_count', 'cache_object_count_run'];
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        foreach ($tables as $table) {
            $sql = "CREATE TABLE IF NOT EXISTS `" . $table . "` (`object_id` int(11) unsigned NOT NULL, `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast_episode') CHARACTER SET utf8 NOT NULL, `count` int(11) unsigned NOT NULL DEFAULT '0', `threshold` int(11) unsigned NOT NULL DEFAULT '0', `count_type` varchar(16) NOT NULL, PRIMARY KEY (`object_id`, `object_type`, `threshold`, `count_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }

        $sql = "UPDATE `preference` SET `level`=75 WHERE `preference`.`name`='stats_threshold'";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400009
     *
     * Add ui option for forcing unique items to playlists
     */
    private static function _update_400009(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'unique_playlist', 'Only add unique items to playlists', '0', 25, 'boolean', 'playlist');
    }

    /**
     * _update_400010
     *
     * Add a last_duration to searches to speed up access requests
     */
    private static function _update_400010(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `search` ADD `last_duration` INT(11) NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400011
     *
     * Allow negative track numbers for albums
     * Truncate database tracks to 0 when greater than 32767
     */
    private static function _update_400011(Interactor $interactor = null): bool
    {
        $sql = "UPDATE `song` SET `track` = 0 WHERE `track` > 32767;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "ALTER TABLE `song` MODIFY COLUMN `track` SMALLINT DEFAULT NULL NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400012
     *
     * Add a rss token to use an RSS unauthenticated feed.
     */
    private static function _update_400012(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `user` ADD `rsstoken` varchar(255) NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400013
     *
     * Extend Democratic cooldown beyond 255.
     */
    private static function _update_400013(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `democratic` MODIFY COLUMN `cooldown` int(11) unsigned DEFAULT NULL NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400014
     *
     * Add last_duration to playlist
     * Add time to artist and album
     */
    private static function _update_400014(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `playlist` ADD COLUMN `last_duration` int(11) unsigned NOT NULL DEFAULT '0'";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "ALTER TABLE `album` ADD COLUMN `time` smallint(5) unsigned NOT NULL DEFAULT '0'";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "ALTER TABLE `artist` ADD COLUMN `time` smallint(5) unsigned NOT NULL DEFAULT '0'";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400015
     *
     * Extend artist time. smallint was too small
     */
    private static function _update_400015(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `artist` MODIFY COLUMN `time` int(11) unsigned DEFAULT NULL NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400016
     *
     * Extend album and make artist even bigger. This should cover everyone.
     */
    private static function _update_400016(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `album` MODIFY COLUMN `time` bigint(20) unsigned DEFAULT NULL NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "ALTER TABLE `artist` MODIFY COLUMN `time` int(11) unsigned DEFAULT NULL NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400018
     *
     * Extend video bitrate to unsigned. There's no reason for a negative bitrate.
     */
    private static function _update_400018(Interactor $interactor = null): bool
    {
        $sql = "UPDATE `video` SET `video_bitrate` = 0 WHERE `video_bitrate` < 0;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "ALTER TABLE `video` MODIFY COLUMN `video_bitrate` int(11) unsigned DEFAULT NULL NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400019
     *
     * Put of_the_moment into a per user preference
     */
    private static function _update_400019(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'of_the_moment', 'Set the amount of items Album/Video of the Moment will display', '6', 25, 'integer', 'interface', 'home');
    }

    /**
     * _update_400020
     *
     * Customizable login background image
     */
    private static function _update_400020(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'custom_login_background', 'Custom URL - Login page background', '', 75, 'string', 'interface', 'custom');
    }

    /**
     * _update_400021
     *
     * Add r128 gain columns to song_data
     */
    private static function _update_400021(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `song_data` ADD `r128_track_gain` smallint(5) DEFAULT NULL, ADD `r128_album_gain` smallint(5) DEFAULT NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400022
     *
     * Extend allowed time for podcast_episodes
     */
    private static function _update_400022(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `podcast_episode` MODIFY COLUMN `time` int(11) unsigned DEFAULT 0 NOT NULL; ";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400023
     *
     * delete concerts_limit_past and concerts_limit_future database settings
     */
    private static function _update_400023(Interactor $interactor = null): bool
    {
        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` IN ('concerts_limit_past', 'concerts_limit_future'));";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $sql = "DELETE FROM `preference` WHERE `preference`.`name` IN ('concerts_limit_past', 'concerts_limit_future');";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_400024
     *
     * Add song_count, album_count and album_group_count to artist
     */
    private static function _update_400024(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `artist` ADD `song_count` smallint(5) unsigned DEFAULT 0 NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `artist` ADD `album_count` smallint(5) unsigned DEFAULT 0 NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `artist` ADD `album_group_count` smallint(5) unsigned DEFAULT 0 NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_500000
     *
     * Delete duplicate files in the song table
     */
    private static function _update_500000(Interactor $interactor = null): bool
    {
        $sql = "DELETE `dupe` FROM `song` AS `dupe`, `song` AS `orig` WHERE `dupe`.`id` > `orig`.`id` AND `dupe`.`file` <=> `orig`.`file`;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_500001
     *
     * Add `release_status`, `addition_time`, `catalog` to album table
     * Add `mbid`, `country`, `active` to label table
     * Fill the album `catalog` and `time` values using the song table
     * Fill the artist `album_count`, `album_group_count` and `song_count` values
     */
    private static function _update_500001(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `album` ADD `release_status` varchar(32) DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album` ADD `addition_time` int(11) UNSIGNED DEFAULT 0 NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album` ADD `catalog` int(11) UNSIGNED NOT NULL DEFAULT '0';";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `label` ADD `mbid` varchar(36) DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `label` ADD `country` varchar(64) DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `label` ADD `active` tinyint(1) UNSIGNED NOT NULL DEFAULT '1';";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "UPDATE `album`, (SELECT min(`song`.`catalog`) AS `catalog`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`catalog` = `song`.`catalog` WHERE `album`.`catalog` != `song`.`catalog` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);
        $sql = "UPDATE `album`, (SELECT SUM(`song`.`time`) AS `time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`time` = `song`.`time` WHERE `album`.`time` != `song`.`time` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);
        $sql = "UPDATE `album`, (SELECT MIN(`song`.`addition_time`) AS `addition_time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`addition_time` = `song`.`addition_time` WHERE `album`.`addition_time` != `song`.`addition_time` AND `song`.`album` = `album`.`id`;";
        Dba::write($sql);

        return true;
    }

    /**
     * _update_500002
     *
     * Create `total_count` and `total_skip` to album, artist, song, video and podcast_episode tables
     * Fill counts into the columns
     */
    private static function _update_500002(Interactor $interactor = null): bool
    {
        // tables which usually calculate a count
        $tables = ['album', 'artist', 'song', 'video', 'podcast_episode'];
        foreach ($tables as $type) {
            $sql = "ALTER TABLE `$type` ADD `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0';";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
            $sql = "UPDATE `$type`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = '$type' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `$type`.`total_count` = `object_count`.`total_count` WHERE `$type`.`total_count` != `object_count`.`total_count` AND `$type`.`id` = `object_count`.`object_id`;";
            Dba::write($sql);
        }
        // tables that also have a skip count
        $tables = ['song', 'video', 'podcast_episode'];
        foreach ($tables as $type) {
            $sql = "ALTER TABLE `$type` ADD `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0';";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
            $sql = "UPDATE `$type`, (SELECT COUNT(`object_count`.`object_id`) AS `total_skip`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = '$type' AND `object_count`.`count_type` = 'skip' GROUP BY `object_count`.`object_id`) AS `object_count` SET `$type`.`total_skip` = `object_count`.`total_skip` WHERE `$type`.`total_skip` != `object_count`.`total_skip` AND `$type`.`id` = `object_count`.`object_id`;";
            Dba::write($sql);
        }

        return true;
    }

    /**
     * _update_500003
     *
     * add `catalog` to podcast_episode table
     */
    private static function _update_500003(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `podcast_episode` ADD `catalog` int(11) UNSIGNED NOT NULL DEFAULT '0';";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "UPDATE `podcast_episode`, (SELECT min(`podcast`.`catalog`) AS `catalog`, `podcast`.`id` FROM `podcast` GROUP BY `podcast`.`id`) AS `podcast` SET `podcast_episode`.`catalog` = `podcast`.`catalog` WHERE `podcast_episode`.`catalog` != `podcast`.`catalog` AND `podcast_episode`.`podcast` = `podcast`.`id`;";
        Dba::write($sql);

        return true;
    }

    /**
     * _update_500004
     *
     * Create catalog_map table and fill it with data
     */
    private static function _update_500004(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        $tables    = ['song', 'album', 'video', 'podcast_episode'];
        $catalogs  = Catalog::get_catalogs();
        // Make sure your files have a catalog
        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog !== null) {
                $rootdir = realpath($catalog->get_path());
                foreach ($tables as $type) {
                    $sql = ($type === 'album')
                        ? "UPDATE `album` LEFT JOIN `song` ON `song`.`album` = `album`.`id` SET `album`.`catalog` = ? WHERE `song`.`file` LIKE '$rootdir%' AND (`$type`.`catalog` IS NULL OR `$type`.`catalog` != ?);"
                        : "UPDATE `$type` SET `catalog` = ? WHERE `$type`.`file` LIKE '$rootdir%' AND (`$type`.`catalog` IS NULL OR `$type`.`catalog` != ?);";
                    Dba::write($sql, array($catalog->id, $catalog->id));
                }
            }
        }
        // create the table
        $sql = "CREATE TABLE IF NOT EXISTS `catalog_map` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `catalog_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `unique_catalog_map` (`object_id`, `object_type`, `catalog_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        // fill the data
        foreach ($tables as $type) {
            $sql = "REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `$type`.`catalog`, '$type', `$type`.`id` FROM `$type` WHERE `$type`.`catalog` > 0;";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }
        // artist is a special one as it can be across multiple tables
        $sql = "REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `song`.`catalog`, 'artist', `artist`.`id` FROM `artist` LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` WHERE `song`.`catalog` > 0;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `album`.`catalog`, 'artist', `artist`.`id` FROM `artist` LEFT JOIN `album` ON `album`.`album_artist` = `artist`.`id` WHERE `album`.`catalog` > 0;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_500005
     *
     * Add song_count, artist_count to album
     */
    private static function _update_500005(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `album` ADD `song_count` smallint(5) unsigned DEFAULT 0 NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album` ADD `artist_count` smallint(5) unsigned DEFAULT 0 NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "REPLACE INTO `update_info` SET `key`= 'album_group', `value`= (SELECT COUNT(DISTINCT(`album`.`id`)) AS `count` FROM `album` WHERE `id` in (SELECT MIN(`id`) FROM `album` GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`, `album`.`mbid_group`));";
        Dba::write($sql);
        $sql = "UPDATE `album`, (SELECT COUNT(`song`.`id`) AS `song_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`song_count` = `song`.`song_count` WHERE `album`.`song_count` != `song`.`song_count` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT(`song`.`artist`)) AS `artist_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`artist_count` = `song`.`artist_count` WHERE `album`.`artist_count` != `song`.`artist_count` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);

        return true;
    }

    /**
     * _update_500006
     *
     * Add user_playlist table
     */
    private static function _update_500006(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        $sql       = "CREATE TABLE IF NOT EXISTS `user_playlist` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) DEFAULT NULL, `object_type` enum('song','live_stream','video','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL DEFAULT '0', `track` smallint(6) DEFAULT NULL, `current_track` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `current_time` smallint(5) UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY (`id`),KEY `user` (`user`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `user_data` (`user` int(11) DEFAULT NULL, `key` varchar(128) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `value` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, KEY `user` (`user`), KEY `key` (`key`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_500007
     *
     * Add a 'Browse' category to interface preferences
     * Add ui option ('show_license') for hiding license column in song rows
     */
    private static function _update_500007(Interactor $interactor = null): bool
    {
        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'browse' WHERE `preference`.`name` IN ('show_played_times', 'browse_filter', 'libitem_browse_alpha', 'show_skipped_times')";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        return self::_write_preference($interactor, 'show_license', 'Show License', '1', 25, 'boolean', 'interface', 'browse');
    }

    /**
     * _update_500008
     *
     * Add filter_user to catalog table, set unique on user_data
     */
    private static function _update_500008(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `catalog` DROP COLUMN `filter_user`;";
        Dba::write($sql);
        $sql = "ALTER TABLE `catalog` ADD `filter_user` int(11) unsigned DEFAULT 0 NOT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        $tables = ['podcast', 'live_stream'];
        foreach ($tables as $type) {
            $sql = "REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `$type`.`catalog`, '$type', `$type`.`id` FROM `$type`;";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }
        $sql = "ALTER TABLE `user_data` DROP KEY `unique_data`;";
        Dba::write($sql);
        $sql = "ALTER TABLE `user_data` ADD UNIQUE `unique_data` (`user`, `key`);";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_500009
     *
     * Add ui option ('use_original_year') Browse by Original Year for albums (falls back to Year)
     */
    private static function _update_500009(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'use_original_year', 'Browse by Original Year for albums (falls back to Year)', '0', 25, 'boolean', 'interface', 'browse');
    }

    /**
     * _update_500010
     *
     * Add ui option ('hide_single_artist') Hide the Song Artist column for Albums with one Artist
     */
    private static function _update_500010(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'hide_single_artist', 'Hide the Song Artist column for Albums with one Artist', '0', 25, 'boolean', 'interface', 'browse');
    }

    /**
     * _update_500011
     *
     * Add `total_count` to podcast table and fill counts into the column
     */
    private static function _update_500011(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `podcast` ADD `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0';";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "UPDATE `podcast`, (SELECT SUM(`podcast_episode`.`total_count`) AS `total_count`, `podcast` FROM `podcast_episode` GROUP BY `podcast_episode`.`podcast`) AS `object_count` SET `podcast`.`total_count` = `object_count`.`total_count` WHERE `podcast`.`total_count` != `object_count`.`total_count` AND `podcast`.`id` = `object_count`.`podcast`;";
        Dba::write($sql);

        return true;
    }

    /**
     * _update_500012
     *
     * Move user bandwidth calculations out of the user format function into the user_data table
     */
    private static function _update_500012(Interactor $interactor = null): bool
    {
        $sql       = "SELECT `id` FROM `user`";
        $db_users  = Dba::read($sql);
        $user_list = array();
        while ($results = Dba::fetch_assoc($db_users)) {
            $user_list[] = (int)$results['id'];
        }
        // Calculate their total Bandwidth Usage
        foreach ($user_list as $user_id) {
            $params = array($user_id);
            $total  = 0;
            $sql_s  = "SELECT IFNULL(SUM(`size`), 0) AS `size` FROM `object_count` LEFT JOIN `song` ON `song`.`id`=`object_count`.`object_id` AND `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = ?;";
            $db_s   = Dba::read($sql_s, $params);
            while ($results = Dba::fetch_assoc($db_s)) {
                $total = $total + $results['size'];
            }
            $sql_v = "SELECT IFNULL(SUM(`size`), 0) AS `size` FROM `object_count` LEFT JOIN `video` ON `video`.`id`=`object_count`.`object_id` AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'video' AND `object_count`.`user` = ?;";
            $db_v  = Dba::read($sql_v, $params);
            while ($results = Dba::fetch_assoc($db_v)) {
                $total = $total + $results['size'];
            }
            $sql_p = "SELECT IFNULL(SUM(`size`), 0) AS `size` FROM `object_count`LEFT JOIN `podcast_episode` ON `podcast_episode`.`id`=`object_count`.`object_id` AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`user` = ?;";
            $db_p  = Dba::read($sql_p, $params);
            while ($results = Dba::fetch_assoc($db_p)) {
                $total = $total + $results['size'];
            }
            $sql = "REPLACE INTO `user_data` SET `user` = ?, `key` = ?, `value` = ?;";
            if (self::_write($interactor, $sql, array($user_id, 'play_size', $total)) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * _update_500013
     *
     * Add tables for tracking deleted files. (deleted_song, deleted_video, deleted_podcast_episode)
     * Add username to the playlist table to stop pulling user all the time
     */
    private static function _update_500013(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        // deleted_song (id, addition_time, delete_time, title, file, catalog, total_count, total_skip, album, artist)
        $sql = "CREATE TABLE IF NOT EXISTS `deleted_song` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED DEFAULT '0', `delete_time` int(11) UNSIGNED DEFAULT '0', `title` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `file` varchar(4096) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0', `update_time` int(11) UNSIGNED DEFAULT '0', `album` int(11) UNSIGNED NOT NULL DEFAULT '0', `artist` int(11) UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        // deleted_video (id, addition_time, delete_time, title, file, catalog, total_count, total_skip)
        $sql = "CREATE TABLE IF NOT EXISTS `deleted_video` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `file` varchar(4096) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        // deleted_podcast_episode (id, addition_time, delete_time, title, file, catalog, total_count, total_skip, podcast)
        $sql = "CREATE TABLE IF NOT EXISTS `deleted_podcast_episode` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `file` varchar(4096) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0', `podcast` int(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        // add username to playlist and searches to stop calling the objects all the time
        $sql = "ALTER TABLE `playlist` ADD `username` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `search` ADD `username` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        // fill the data
        $sql = "UPDATE `playlist`, (SELECT `id`, `username` FROM `user`) AS `user` SET `playlist`.`username` = `user`.`username` WHERE `playlist`.`user` = `user`.`id`;";
        Dba::write($sql);
        $sql = "UPDATE `search`, (SELECT `id`, `username` FROM `user`) AS `user` SET `search`.`username` = `user`.`username` WHERE `search`.`user` = `user`.`id`;";
        Dba::write($sql);
        $sql = "UPDATE `playlist` SET `playlist`.`username` = ? WHERE `playlist`.`user` = -1;";
        Dba::write($sql, array(T_('System')));
        $sql = "UPDATE `search` SET `search`.`username` = ? WHERE `search`.`user` = -1;";
        Dba::write($sql, array(T_('System')));

        return true;
    }

    /**
     * _update_500014
     *
     * Add `episodes` to podcast table to track episode count
     */
    private static function _update_500014(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `podcast` ADD `episodes` int(11) UNSIGNED NOT NULL DEFAULT '0';";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "UPDATE `podcast`, (SELECT COUNT(`podcast_episode`.`id`) AS `episodes`, `podcast` FROM `podcast_episode` GROUP BY `podcast_episode`.`podcast`) AS `episode_count` SET `podcast`.`episodes` = `episode_count`.`episodes` WHERE `podcast`.`episodes` != `episode_count`.`episodes` AND `podcast`.`id` = `episode_count`.`podcast`;";
        Dba::write($sql);

        return true;
    }

    /**
     * _update_500015
     *
     * Add ui option ('hide_genres') Hide the Genre column in browse table rows
     */
    private static function _update_500015(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'hide_genres', 'Hide the Genre column in browse table rows', '0', 25, 'boolean', 'interface', 'browse');
    }

    /**
     * _update_510000
     *
     * Add podcast to the object_count table
     */
    private static function _update_510000(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_510001
     *
     * Add podcast to the cache_object_count tables
     */
    private static function _update_510001(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `cache_object_count_run` MODIFY COLUMN `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode');";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `cache_object_count` MODIFY COLUMN `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode');";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_510003
     *
     * Add live_stream to the rating table
     */
    private static function _update_510003(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('album','artist','song','stream','live_stream','video','playlist','tvshow','tvshow_season','podcast','podcast_episode');";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_510004
     *
     * Add waveform column to podcast_episode table
     */
    private static function _update_510004(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `podcast_episode` ADD COLUMN `waveform` mediumblob DEFAULT NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_510005
     *
     * Add ui option ('subsonic_always_download') Force Subsonic streams to download. (Enable scrobble in your client to record stats)
     */
    private static function _update_510005(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'subsonic_always_download', 'Force Subsonic streams to download. (Enable scrobble in your client to record stats)', '0', 25, 'boolean', 'options', 'subsonic');
    }

    /**
     * _update_520000
     *
     * Add ui options ('api_enable_3', 'api_enable_4', 'api_enable_5') to enable/disable specific API versions
     * Add ui option ('api_force_version') to force a specific API response (even if that version is disabled)
     */
    private static function _update_520000(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'api_enable_3', 'Enable API3 responses', '1', 25, 'boolean', 'options') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'api_enable_4', 'Enable API4 responses', '1', 25, 'boolean', 'options') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'api_enable_5', 'Enable API5 responses', '1', 25, 'boolean', 'options') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'api_force_version', 'Force a specific API response (even if that version is disabled)', '0', 25, 'special', 'options') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_520001
     *
     * Make sure preference names are always unique
     */
    private static function _update_520001(Interactor $interactor = null): bool
    {
        $sql        = "SELECT `id` FROM `preference` WHERE `name` IN (SELECT `name` FROM `preference` GROUP BY `name` HAVING count(`name`) >1) AND `id` NOT IN (SELECT MIN(`id`) FROM `preference` GROUP by `name`);";
        $dupe_prefs = Dba::read($sql);
        $pref_list  = array();
        while ($results = Dba::fetch_assoc($dupe_prefs)) {
            $pref_list[] = (int)$results['id'];
        }
        // delete duplicates (if they exist)
        foreach ($pref_list as $pref_id) {
            $sql = "DELETE FROM `preference` WHERE `id` = ?;";
            Dba::write($sql, array($pref_id));
        }
        $sql = "DELETE FROM `user_preference` WHERE `preference` NOT IN (SELECT `id` FROM `preference`);";
        Dba::write($sql);
        $sql = "ALTER TABLE `preference` ADD CONSTRAINT preference_UN UNIQUE KEY (`name`);";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_520002
     *
     * Add ui option ('show_playlist_username') Show playlist owner username in titles
     */
    private static function _update_520002(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'show_playlist_username', 'Show playlist owner username in titles', '1', 25, 'boolean', 'interface', 'browse');
    }

    /**
     * _update_520003
     *
     * Add ui option ('api_hidden_playlists') Hide playlists in Subsonic and API clients that start with this string
     */
    private static function _update_520003(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'api_hidden_playlists', 'Hide playlists in Subsonic and API clients that start with this string', '', 25, 'string', 'options');
    }

    /**
     * _update_520004
     *
     * Set 'plugins' category to lastfm_challenge preference
     */
    private static function _update_520004(Interactor $interactor = null): bool
    {
        $sql = "UPDATE `preference` SET `preference`.`catagory` = 'plugins' WHERE `preference`.`name` = 'lastfm_challenge'";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_520005
     *
     * Add ui option ('api_hide_dupe_searches') Hide smartlists that match playlist names in Subsonic and API clients
     */
    private static function _update_520005(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'api_hide_dupe_searches', 'Hide smartlists that match playlist names in Subsonic and API clients', '0', 25, 'boolean', 'options');
    }

    /**
     * _update_530000
     *
     * Create artist_map table and fill it with data
     */
    private static function _update_530000(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = 'MyISAM';
        // create the table
        $sql = "CREATE TABLE IF NOT EXISTS `artist_map` (`artist_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_artist_map` (`object_id`, `object_type`, `artist_id`), INDEX `object_id_index` (`object_id`), INDEX `artist_id_index` (`artist_id`), INDEX `artist_id_type_index` (`artist_id`, `object_type`), INDEX `object_id_type_index` (`object_id`, `object_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        // fill the data
        $sql = "INSERT IGNORE INTO `artist_map` (`artist_id`, `object_type`, `object_id`) SELECT DISTINCT `song`.`artist` AS `artist_id`, 'song', `song`.`id` FROM `song` WHERE `song`.`artist` > 0 AND `song`.`artist` > 0 UNION SELECT DISTINCT `album`.`album_artist` AS `artist_id`, 'album', `album`.`id` FROM `album` WHERE `album`.`album_artist` > 0 AND `album`.`album_artist` IS NOT NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530001
     *
     * Create album_map table and fill it with data
     */
    private static function _update_530001(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = 'MyISAM';
        // create the table
        $sql = "CREATE TABLE IF NOT EXISTS `album_map` (`album_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_album_map` (`object_id`, `object_type`, `album_id`), INDEX `object_id_index` (`object_id`), INDEX `album_id_type_index` (`album_id`, `object_type`), INDEX `object_id_type_index` (`object_id`, `object_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        // fill the data
        $sql = "INSERT IGNORE INTO `album_map` (`album_id`, `object_type`, `object_id`) SELECT DISTINCT `artist_map`.`object_id` AS `album_id`, 'album' AS `object_type`, `artist_map`.`artist_id` AS `object_id` FROM `artist_map` WHERE `artist_map`.`object_type` = 'album' AND `artist_map`.`object_id` > 0 UNION SELECT DISTINCT `song`.`album` AS `album_id`, 'song' AS `object_type`, `song`.`artist` AS `object_id` FROM `song` WHERE `song`.`album` > 0 UNION SELECT DISTINCT `song`.`album` AS `album_id`, 'song' AS `object_type`, `artist_map`.`artist_id` AS `object_id` FROM `artist_map` LEFT JOIN `song` ON `artist_map`.`object_type` = 'song' AND `artist_map`.`object_id` = `song`.`id` WHERE `song`.`album` IS NOT NULL AND `artist_map`.`object_type` = 'song';";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530002
     *
     * Use song_count & artist_count with album_map
     */
    private static function _update_530002(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `album` ADD `song_artist_count` smallint(5) unsigned DEFAULT 0 NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "UPDATE `album`, (SELECT COUNT(`song`.`id`) AS `song_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`song_count` = `song`.`song_count` WHERE `album`.`song_count` != `song`.`song_count` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT(`album_map`.`object_id`)) AS `artist_count`, `album_id` FROM `album_map` LEFT JOIN `album` ON `album`.`id` = `album_map`.`album_id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album_map`.`object_type` = 'song' AND `catalog`.`enabled` = '1' GROUP BY `album_id`) AS `album_map` SET `album`.`song_artist_count` = `album_map`.`artist_count` WHERE `album`.`id` = `album_map`.`album_id`;";
        Dba::write($sql);

        return true;
    }

    /**
     * _update_530003
     *
     * Drop id column from catalog_map
     * Alter `catalog_map` object_type charset and collation
     */
    private static function _update_530003(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `catalog_map` DROP COLUMN `id`;";
        Dba::write($sql);
        $sql = "ALTER TABLE `catalog_map` MODIFY COLUMN object_type varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530004
     *
     * Alter `album_map` charset and engine to MyISAM if engine set
     */
    private static function _update_530004(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `album_map` ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album_map` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530005
     *
     * Alter `artist_map` charset and engine to MyISAM if engine set
     */
    private static function _update_530005(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `artist_map` ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `artist_map` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530006
     *
     * Make sure the object_count table has all the correct primary artist/album rows
     */
    private static function _update_530006(Interactor $interactor = null): bool
    {
        $sql = "INSERT INTO `object_count` (object_type, object_id, `date`, `user`, agent, geo_latitude, geo_longitude, geo_name, count_type) SELECT 'album', `song`.`album`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` LEFT JOIN `song` ON `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_id` = `song`.`id` LEFT JOIN `object_count` AS `album_count` ON `album_count`.`object_type` = 'album' AND `object_count`.`date` = `album_count`.`date` AND `object_count`.`user` = `album_count`.`user` AND `object_count`.`agent` = `album_count`.`agent` AND `object_count`.`count_type` = `album_count`.`count_type` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `album_count`.`id` IS NULL AND `song`.`album` IS NOT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "INSERT INTO `object_count` (object_type, object_id, `date`, `user`, agent, geo_latitude, geo_longitude, geo_name, count_type) SELECT 'artist', `song`.`artist`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` LEFT JOIN `song` ON `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_id` = `song`.`id` LEFT JOIN `object_count` AS `artist_count` ON `artist_count`.`object_type` = 'artist' AND `object_count`.`date` = `artist_count`.`date` AND `object_count`.`user` = `artist_count`.`user` AND `object_count`.`agent` = `artist_count`.`agent` AND `object_count`.`count_type` = `artist_count`.`count_type` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `artist_count`.`id` IS NULL AND `song`.`artist` IS NOT NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530007
     *
     * Convert basic text columns into utf8/utf8_unicode_ci
     */
    private static function _update_530007(Interactor $interactor = null): bool
    {
        Dba::write("UPDATE `album` SET `mbid` = NULL WHERE CHAR_LENGTH(`mbid`) > 36;");
        Dba::write("UPDATE `album` SET `mbid_group` = NULL WHERE CHAR_LENGTH(`mbid`) > 36;");

        $sql = "ALTER TABLE `album` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album` MODIFY COLUMN `mbid_group` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('album','artist','song','stream','video','playlist','tvshow','tvshow_season','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user_flag` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user_shout` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `video` MODIFY COLUMN `mode` enum('abr','vbr','cbr') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530008
     *
     * Remove `user_activity` columns that are useless
     */
    private static function _update_530008(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `user_activity` DROP COLUMN `name_track`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user_activity` DROP COLUMN `name_artist`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user_activity` DROP COLUMN `name_album`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user_activity` DROP COLUMN `mbid_track`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user_activity` DROP COLUMN `mbid_artist`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user_activity` DROP COLUMN `mbid_album`;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530009
     *
     * Compact `object_count` columns
     */
    private static function _update_530009(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `object_count` MODIFY COLUMN `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `object_count` MODIFY COLUMN `agent` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `object_count` MODIFY COLUMN `geo_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530010
     *
     * Compact mbid columns back to 36 characters
     */
    private static function _update_530010(Interactor $interactor = null): bool
    {
        Dba::write("UPDATE `artist` SET `mbid` = NULL WHERE CHAR_LENGTH(`mbid`) > 36;");
        Dba::write("UPDATE `recommendation_item` SET `mbid` = NULL WHERE CHAR_LENGTH(`mbid`) > 36;");
        $sql = "ALTER TABLE `artist` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `recommendation_item` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `song_preview` MODIFY COLUMN `artist_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `wanted` MODIFY COLUMN `artist_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530011
     *
     * Compact `user` columns and enum `object_count`.`count_type`
     */
    private static function _update_530011(Interactor $interactor = null): bool
    {
        $collation  = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset    = (AmpConfig::get('database_charset', 'utf8mb4'));
        $rsstoken   = false;
        $sql        = "DESCRIBE `user`";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['Field'] == 'rsstoken') {
                $rsstoken = true;
            }
        }
        if (!$rsstoken) {
            $sql = "ALTER TABLE `user` ADD `rsstoken` varchar(255) NULL;";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }
        $sql = "ALTER TABLE `user` MODIFY COLUMN `rsstoken` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user` MODIFY COLUMN `validation` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user` MODIFY COLUMN `password` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user` MODIFY COLUMN `apikey` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `user` MODIFY COLUMN `username` varchar(128) CHARACTER SET $charset COLLATE $collation DEFAULT NULL NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530012
     *
     * Index data on object_count
     */
    private static function _update_530012(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `object_count` DROP KEY `object_count_full_index`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `object_count_full_index` USING BTREE ON `object_count` (`object_type`, `object_id`, `date`, `user`, `agent`, `count_type`);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `object_count` DROP KEY `object_count_type_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `object_count_type_IDX` USING BTREE ON `object_count` (`object_type`, `object_id`);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `object_count` DROP KEY `object_count_date_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `object_count_date_IDX` USING BTREE ON `object_count` (`date`, `count_type`);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `object_count` DROP KEY `object_count_user_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `object_count_user_IDX` USING BTREE ON `object_count` (`object_type`, `object_id`, `user`, `count_type`);";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530013
     *
     * Compact `cache_object_count`, `cache_object_count_run` columns
     */
    private static function _update_530013(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `cache_object_count` MODIFY COLUMN `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `cache_object_count_run` MODIFY COLUMN `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530014
     *
     * Use a smaller unique index on `object_count`
     */
    private static function _update_530014(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `object_count` DROP KEY `object_count_UNIQUE_IDX`;";
        Dba::write($sql);
        // delete duplicates and make sure they're gone
        $sql = "DELETE FROM `object_count` WHERE `id` IN (SELECT `id` FROM (SELECT `id` FROM `object_count` WHERE `object_id` IN (SELECT `object_id` FROM `object_count` GROUP BY `object_type`, `object_id`, `date`, `user`, `agent`, `count_type` HAVING COUNT(`object_id`) > 1) AND `id` NOT IN (SELECT MIN(`id`) FROM `object_count` GROUP BY `object_type`, `object_id`, `date`, `user`, `agent`, `count_type`)) AS `count`);";
        Dba::write($sql);
        $sql = "CREATE UNIQUE INDEX `object_count_UNIQUE_IDX` USING BTREE ON `object_count` (`object_type`, `object_id`, `date`, `user`, `agent`, `count_type`);";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_530015
     *
     * Add `show_album_artist` and `show_artist` preferences to show/hide Sidebar Browse menu links. (Fallback to Album Artist if both disabled)
     */
    private static function _update_530015(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'show_album_artist', 'Show \'Album Artists\' link in the main sidebar', '1', 25, 'boolean', 'interface', 'theme') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'show_artist', 'Show \'Artists\' link in the main sidebar', '0', 25, 'boolean', 'interface', 'theme') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_530016
     *
     * Missing type compared to previous version
     */
    private static function _update_530016(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('album','artist','song','stream','live_stream','video','playlist','tvshow','tvshow_season','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;") !== false);
    }

    /**
     * _update_540000
     *
     * Index `title` with `enabled` on `song` table to speed up searching
     */
    private static function _update_540000(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `song` DROP KEY `title_enabled_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `title_enabled_IDX` USING BTREE ON `song` (`title`, `enabled`);";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_540001
     *
     * Index album tables. `catalog`, `album_artist`, `original_year`, `release_type`, `release_status`, `mbid`, `mbid_group`
     */
    private static function _update_540001(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `album` DROP KEY `catalog_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `catalog_IDX` USING BTREE ON `album` (`catalog`);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album` DROP KEY `album_artist_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `album_artist_IDX` USING BTREE ON `album` (`album_artist`);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album` DROP KEY `original_year_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `original_year_IDX` USING BTREE ON `album` (`original_year`);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album` DROP KEY `release_type_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `release_type_IDX` USING BTREE ON `album` (`release_type`);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album` DROP KEY `release_status_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `release_status_IDX` USING BTREE ON `album` (`release_status`);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album` DROP KEY `mbid_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `mbid_IDX` USING BTREE ON `album` (`mbid`);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album` DROP KEY `mbid_group_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `mbid_group_IDX` USING BTREE ON `album` (`mbid_group`);";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_540002
     *
     * Index `object_type` with `date` in `object_count` table
     */
    private static function _update_540002(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `object_count` DROP KEY `object_type_date_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `object_type_date_IDX` USING BTREE ON `object_count` (`object_type`, `date`);";

        return (self::_write($interactor, $sql) !== false);
    }

    /** _update_550001
     *
     * Add tables `catalog_filter_group` and `catalog_filter_group_map` for catalog filtering by groups
     * Add column `catalog_filter_group` to `user` table to assign a filter group
     * Create a DEFAULT group
     */
    private static function _update_550001(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        // Add the new catalog_filter_group table
        $sql = "CREATE TABLE IF NOT EXISTS `catalog_filter_group` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        // Add the default group (autoincrement starts at 1 so force it to be 0)
        $sql = "INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('DEFAULT');";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "UPDATE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT';";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `catalog_filter_group` AUTO_INCREMENT = 1;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        // Add the new catalog_filter_group_map table
        $sql = "CREATE TABLE IF NOT EXISTS `catalog_filter_group_map` (`group_id` int(11) UNSIGNED NOT NULL, `catalog_id` int(11) UNSIGNED NOT NULL, `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, UNIQUE KEY (group_id,catalog_id)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        // Add the default access group to the user table
        $sql = "ALTER TABLE `user` ADD `catalog_filter_group` INT(11) UNSIGNED NOT NULL DEFAULT 0;";

        return (self::_write($interactor, $sql) !== false);
    }

    /** _update_550002
     *
     * Migrate catalog `filter_user` settings to catalog_filter groups
     * Assign all public catalogs to the DEFAULT group
     * Drop table `user_catalog`
     * Remove `filter_user` from the `catalog` table
     */
    private static function _update_550002(Interactor $interactor = null): bool
    {
        // Copy existing filters into individual groups for each user. (if a user only has access to public catalogs they are given the default list)
        $sql        = "SELECT `id`, `username` FROM `user`;";
        $db_results = Dba::read($sql);
        $user_list  = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $user_list[$row['id']] = $row['username'];
        }
        // If the user had a private catalog, create an individual group for them using the current filter and public catalogs.
        foreach ($user_list as $key => $value) {
            $group_id   = 0;
            $sql        = 'SELECT `filter_user` FROM `catalog` WHERE `filter_user` = ?;';
            $db_results = Dba::read($sql, array($key));
            if (Dba::num_rows($db_results)) {
                $sql = "INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('" . Dba::escape($value) . "');";
                Dba::write($sql);
                $group_id = (int)Dba::insert_id();
            }
            if ($group_id > 0) {
                $sql        = "SELECT `id`, `filter_user` FROM `catalog`;";
                $db_results = Dba::read($sql);
                while ($row = Dba::fetch_assoc($db_results)) {
                    $catalog = $row['id'];
                    $enabled = ($row['filter_user'] == 0 || $row['filter_user'] == $key)
                        ? 1
                        : 0;
                    $sql = "INSERT IGNORE INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) VALUES ($group_id, $catalog, $enabled);";
                    if (self::_write($interactor, $sql) === false) {
                        return false;
                    }
                }
                $sql = "UPDATE `user` SET `catalog_filter_group` = ? WHERE `id` = ?";
                Dba::write($sql, array($group_id, $key));
            }
        }

        // Add all public catalogs in the DEFAULT profile.
        $sql        = "SELECT `id` FROM `catalog` WHERE `filter_user` = 0;";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog = (int)$row['id'];
            $sql     = "INSERT IGNORE INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) VALUES (0, $catalog, 1);";
            if (self::_write($interactor, $sql) === false) {
                return false;
            }
        }
        $sql = "DROP TABLE IF EXISTS `user_catalog`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        // Drop filter_user but only if the migration has worked
        $sql = "ALTER TABLE `catalog` DROP COLUMN `filter_user`;";
        Dba::write($sql);

        return true;
    }

    /** _update_550003
     *
     * Add system preference `demo_use_search`, Use smartlists for base playlist in Democratic play
     */
    private static function _update_550003(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'demo_use_search', 'Democratic - Use smartlists for base playlist', '0', 25, 'boolean', 'playlist');
    }

    /** _update_550004
     *
     * Make `demo_use_search`a system preference correctly
     */
    private static function _update_550004(Interactor $interactor = null): bool
    {
        // Update previous update preference
        $sql = "UPDATE `preference` SET `catagory`='system' WHERE `name`='demo_use_search'";

        return (self::_write($interactor, $sql) !== false);
    }

    /** _update_550005
     *
     * Add `song_artist` and `album_artist` maps to catalog_map
     */
    private static function _update_550005(Interactor $interactor = null): bool
    {
        // delete bad maps if they exist
        $tables = ['song', 'album', 'album_disk', 'video', 'podcast', 'podcast_episode', 'live_stream'];
        foreach ($tables as $type) {
            $sql = "DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `$type`.`catalog` AS `catalog_id`, '$type' AS `map_type`, `$type`.`id` AS `object_id` FROM `$type` GROUP BY `$type`.`catalog`, `map_type`, `$type`.`id`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` AND `valid_maps`.`map_type` = `catalog_map`.`object_type` WHERE `catalog_map`.`object_type` = '$type' AND `valid_maps`.`object_id` IS NULL;";
            Dba::write($sql);
        }
        // delete catalog_map artists
        $sql = "DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` INNER JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` INNER JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` INNER JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` INNER JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL GROUP BY `album`.`catalog`, `artist_map`.`object_type`, `artist_map`.`artist_id`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` AND `valid_maps`.`map_type` = `catalog_map`.`object_type` WHERE `catalog_map`.`object_type` IN ('artist', 'song_artist', 'album_artist') AND `valid_maps`.`object_id` IS NULL;";
        Dba::write($sql);
        // insert catalog_map artists
        $sql = "INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL GROUP BY `catalog`, `artist_map`.`object_type`, `artist_map`.`artist_id`;";

        return (self::_write($interactor, $sql) !== false);
    }

    /** _update_600001
     *
     * Add user preference `webplayer_removeplayed`, Remove tracks before the current playlist item in the webplayer when played
     */
    private static function _update_600001(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'webplayer_removeplayed', 'Remove tracks before the current playlist item in the webplayer when played', '0', 25, 'special', 'streaming', 'player');
    }

    /** _update_600002
     *
     * Drop channel table
     */
    private static function _update_600002(Interactor $interactor = null): bool
    {
        $sql = "DROP TABLE IF EXISTS `channel`";

        return (self::_write($interactor, $sql) !== false);
    }

    /** _update_600003
     *
     * Add `total_skip` to podcast table
     */
    private static function _update_600003(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `podcast` ADD `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `total_count`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "UPDATE `podcast`, (SELECT SUM(`podcast_episode`.`total_skip`) AS `total_skip`, `podcast` FROM `podcast_episode` GROUP BY `podcast_episode`.`podcast`) AS `object_count` SET `podcast`.`total_skip` = `object_count`.`total_skip` WHERE `podcast`.`total_skip` != `object_count`.`total_skip` AND `podcast`.`id` = `object_count`.`podcast`;";
        Dba::write($sql);

        return true;
    }

    /**
     * _update_600004
     *
     * Add `disk` to song table
     * Create album_disk table and migrate user ratings & flags
     */
    private static function _update_600004(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = 'MyISAM';
        // add disk to song table
        $sql = "ALTER TABLE `song` ADD `disk` smallint(5) UNSIGNED DEFAULT NULL AFTER `album`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        // fill the data
        $sql = "UPDATE `song`, (SELECT DISTINCT `id`, `disk` FROM `album`) AS `album` SET `song`.`disk` = `album`.`disk` WHERE (`song`.`disk` != `album`.`disk` OR `song`.`disk` IS NULL) AND `song`.`album` = `album`.`id`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        // create the table
        $sql = "DROP TABLE IF EXISTS `album_disk`;";
        Dba::write($sql);
        $sql = "CREATE TABLE IF NOT EXISTS `album_disk` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `album_id` int(11) UNSIGNED NOT NULL, `disk` int(11) UNSIGNED NOT NULL, `disk_count` int(11) unsigned DEFAULT 0 NOT NULL, `time` bigint(20) UNSIGNED DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0, `song_count` smallint(5) UNSIGNED DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, UNIQUE KEY `unique_album_disk` (`album_id`, `disk`, `catalog`), INDEX `id_index` (`id`), INDEX `album_id_type_index` (`album_id`, `disk`), INDEX `id_disk_index` (`id`, `disk`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        // make sure ratings and counts will be entered
        $sql = "ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','genre','live_stream','playlist','podcast','podcast_episode','song','stream','tvshow','tvshow_season','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','genre','live_stream','playlist','podcast','podcast_episode','song','stream','tvshow','tvshow_season','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        // fill the data
        $sql = "INSERT IGNORE INTO `album_disk` (`album_id`, `disk`, `catalog`) SELECT DISTINCT `song`.`album` AS `album_id`, `song`.`disk` AS `disk`, `song`.`catalog` AS `catalog` FROM `song`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        // rating (id, `user`, object_type, object_id, rating)
        $sql = "INSERT IGNORE INTO `rating` (`object_type`, `object_id`, `user`, `rating`, `date`) SELECT DISTINCT 'album_disk', `album_disk`.`id`, `rating`.`user`, `rating`.`rating`, `rating`.`date` FROM `rating` LEFT JOIN `album` ON `rating`.`object_type` = 'album' AND `rating`.`object_id` = `album`.`id` LEFT JOIN `album_disk` ON `album`.`id` = `album_disk`.`album_id` LEFT JOIN `rating` AS `album_rating` ON `album_rating`.`object_type` = 'album' AND `rating`.`rating` = `album_rating`.`rating` AND `rating`.`user` = `album_rating`.`user` WHERE `rating`.`object_type` = 'album' AND `album_disk`.`id` IS NOT NULL;";
        Dba::write($sql);
        // user_flag (id, `user`, object_id, object_type, `date`)
        $sql = "INSERT IGNORE INTO `user_flag` (`object_type`, `object_id`, `user`, `date`) SELECT DISTINCT 'album_disk', `album_disk`.`id`, `user_flag`.`user`, `user_flag`.`date` FROM `user_flag` LEFT JOIN `album` ON `user_flag`.`object_type` = 'album' AND `user_flag`.`object_id` = `album`.`id` LEFT JOIN `album_disk` ON `album`.`id` = `album_disk`.`album_id` LEFT JOIN `user_flag` AS `album_flag` ON `album_flag`.`object_type` = 'album' AND `user_flag`.`date` = `album_flag`.`date` AND `user_flag`.`user` = `album_flag`.`user` WHERE `user_flag`.`object_type` = 'album' AND `album_disk`.`id` IS NOT NULL;";
        Dba::write($sql);
        Song::clear_cache();
        Artist::clear_cache();
        Album::clear_cache();

        return true;
    }

    /** _update_600005
     *
     * Migrate multi-disk albums to single album id's
     */
    private static function _update_600005(Interactor $interactor = null): bool
    {
        $sql        = "SELECT MIN(`id`) AS `id` FROM `album` GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`, `album`.`mbid_group` HAVING COUNT(`id`) > 1;";
        $db_results = Dba::read($sql);
        $album_list = array();
        $migrate    = array();
        // get the base album you will migrate into
        while ($row = Dba::fetch_assoc($db_results)) {
            $album_list[] = $row['id'];
        }
        // get all matching albums that will migrate into the base albums
        foreach ($album_list as $album_id) {
            $album  = new Album((int)$album_id);
            $f_name = $album->get_fullname(true);
            $where  = " WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ? ) ";
            $params = array($f_name, $f_name);
            if ($album->mbid) {
                $where .= 'AND `album`.`mbid` = ? ';
                $params[] = $album->mbid;
            } else {
                $where .= 'AND `album`.`mbid` IS NULL ';
            }
            if ($album->mbid_group) {
                $where .= 'AND `album`.`mbid_group` = ? ';
                $params[] = $album->mbid_group;
            } else {
                $where .= 'AND `album`.`mbid_group` IS NULL ';
            }
            if ($album->prefix) {
                $where .= 'AND `album`.`prefix` = ? ';
                $params[] = $album->prefix;
            }
            if ($album->album_artist) {
                $where .= 'AND `album`.`album_artist` = ? ';
                $params[] = $album->album_artist;
            }
            if ($album->original_year) {
                $where .= 'AND `album`.`original_year` = ? ';
                $params[] = $album->original_year;
            }
            if ($album->release_type) {
                $where .= 'AND `album`.`release_type` = ? ';
                $params[] = $album->release_type;
            }
            if ($album->release_status) {
                $where .= 'AND `album`.`release_status` = ? ';
                $params[] = $album->release_status;
            }

            $sql        = "SELECT DISTINCT `album`.`id`, MAX(`album`.`disk`) AS `disk` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` $where GROUP BY `album`.`id` ORDER BY `disk` ASC";
            $db_results = Dba::read($sql, $params);

            while ($row = Dba::fetch_assoc($db_results)) {
                if ($row['id'] !== $album_id) {
                    $migrate[] = array(
                        'old' => $row['id'],
                        'new' => $album_id
                    );
                }
            }
        }
        debug_event(self::class, 'update_600005: migrate {' . count($migrate) . '} albums', 4);
        // get the songs for these id's and migrate to the base id
        foreach ($migrate as $albums) {
            debug_event(self::class, 'update_600005: migrate album: ' . $albums['old'] . ' => ' . $albums['new'], 4);
            $sql = "UPDATE `song` SET `album` = ? WHERE `album` = ?;";
            if (self::_write($interactor, $sql, array($albums['new'], $albums['old'])) === false) {
                debug_event(self::class, 'update_600005: FAIL: album ' . $albums['old'], 1);

                return false;
            }
            // bulk migrate by album only (0 will let us migrate everything below)
            Song::migrate_album($albums['new'], 0, $albums['old']);
        }
        // check that the migration is finished
        $sql        = "SELECT MAX(`id`) AS `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song`) GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`, `album`.`mbid_group` HAVING COUNT(`id`) > 1;";
        $db_results = Dba::read($sql);
        if (Dba::fetch_assoc($db_results)) {
            debug_event(self::class, 'update_600005: FAIL', 1);

            return false;
        }
        // clean up this mess
        Catalog::clean_empty_albums();
        Song::clear_cache();
        Artist::clear_cache();
        Album::clear_cache();
        debug_event(self::class, 'update_600005: SUCCESS', 5);

        return true;
    }

    /** _update_600006
     *
     * Add `disk_count` to album table
     */
    private static function _update_600006(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `album` ADD `disk_count` int(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `disk`;") !== false);
    }

    /**
     * _update_600007
     *
     * Fill album_disk table update count tables
     */
    private static function _update_600007(Interactor $interactor = null): bool
    {
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT `album_disk`.`disk`) AS `disk_count`, `album_id` FROM `album_disk` GROUP BY `album_disk`.`album_id`) AS `album_disk` SET `album`.`disk_count` = `album_disk`.`disk_count` WHERE `album`.`disk_count` != `album_disk`.`disk_count` AND `album`.`id` = `album_disk`.`album_id`;";
        Dba::write($sql);
        $sql = "UPDATE `album_disk`, (SELECT COUNT(DISTINCT `album_disk`.`disk`) AS `disk_count`, `album_id` FROM `album_disk` GROUP BY `album_disk`.`album_id`) AS `disk_count` SET `album_disk`.`disk_count` = `disk_count`.`disk_count` WHERE `album_disk`.`disk_count` != `disk_count`.`disk_count` AND `album_disk`.`album_id` = `disk_count`.`album_id`;";
        Dba::write($sql);
        $sql = "UPDATE `album_disk`, (SELECT SUM(`time`) AS `time`, `album`, `disk` FROM `song` GROUP BY `album`, `disk`) AS `song` SET `album_disk`.`time` = `song`.`time` WHERE (`album_disk`.`time` != `song`.`time` OR `album_disk`.`time` IS NULL) AND `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk`;";
        Dba::write($sql);
        $sql = "UPDATE `album_disk`, (SELECT COUNT(DISTINCT `id`) AS `song_count`, `album`, `disk` FROM `song` GROUP BY `album`, `disk`) AS `song` SET `album_disk`.`song_count` = `song`.`song_count` WHERE `album_disk`.`song_count` != `song`.`song_count` AND `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk`;";
        Dba::write($sql);
        $sql = "UPDATE `album_disk`, (SELECT SUM(`song`.`total_count`) AS `total_count`, `album_disk`.`id` AS `object_id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` GROUP BY `album_disk`.`id`) AS `object_count` SET `album_disk`.`total_count` = `object_count`.`total_count` WHERE `album_disk`.`total_count` != `object_count`.`total_count` AND `album_disk`.`id` = `object_count`.`object_id`;";
        Dba::write($sql);
        if ($interactor) {
            $interactor->info(
                'update_table_counts',
                true
            );
        }
        // now that the data is in it can update counts
        Album::update_table_counts();
        Artist::update_table_counts();

        return true;
    }

    /**
     * _update_600008
     *
     * Rename `artist`.`album_group_count` => `album_disk_count`
     */
    private static function _update_600008(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `artist` CHANGE `album_group_count` `album_disk_count` smallint(5) unsigned DEFAULT 0 NULL;") !== false);
    }

    /**
     * _update_600009
     *
     * Drop `disk` from the `album` table
     */
    private static function _update_600009(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `album` DROP COLUMN `disk`;") !== false);
    }

    /**
     * _update_600010
     *
     * Rename `user_data` album keys
     */
    private static function _update_600010(Interactor $interactor = null): bool
    {
        // album was the ungrouped disks so rename those first
        $sql = "UPDATE IGNORE `user_data` SET `key` = 'album_disk' WHERE `key` = 'album';";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        // album_group is now the default state
        $sql = "UPDATE IGNORE `user_data` SET `key` = 'album' WHERE `key` = 'album_group';";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "DELETE FROM `user_data` WHERE `key` = 'album_group';";
        Dba::write($sql);

        return true;
    }

    /**
     * _update_600011
     *
     * Add `album_disk` to enum types for `object_count`, `rating` and `cache_object_count` tables
     */
    private static function _update_600011(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','genre','live_stream','playlist','podcast','podcast_episode','song','stream','tvshow','tvshow_season','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','genre','live_stream','playlist','podcast','podcast_episode','song','stream','tvshow','tvshow_season','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /** _update_600012
     *
     * Add `song_artist` and `album_artist` maps to catalog_map
     * This is a duplicate of `update_550004` But may have been skipped depending on your site's version history
     */
    private static function _update_600012(Interactor $interactor = null): bool
    {
        // delete bad maps if they exist
        $tables = ['song', 'album', 'video', 'podcast', 'podcast_episode', 'live_stream'];
        foreach ($tables as $type) {
            $sql = "DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `$type`.`catalog` AS `catalog_id`, '$type' AS `map_type`, `$type`.`id` AS `object_id` FROM `$type` GROUP BY `$type`.`catalog`, `map_type`, `$type`.`id`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` AND `valid_maps`.`map_type` = `catalog_map`.`object_type` WHERE `catalog_map`.`object_type` = '$type' AND `valid_maps`.`object_id` IS NULL;";
            Dba::write($sql);
        }
        // delete catalog_map artists
        $sql = "DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` INNER JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` INNER JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` INNER JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` INNER JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL GROUP BY `album`.`catalog`, `artist_map`.`object_type`, `artist_map`.`artist_id`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` AND `valid_maps`.`map_type` = `catalog_map`.`object_type` WHERE `catalog_map`.`object_type` IN ('artist', 'song_artist', 'album_artist') AND `valid_maps`.`object_id` IS NULL;";
        Dba::write($sql);
        // insert catalog_map artists
        $sql = "INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL GROUP BY `catalog`, `artist_map`.`object_type`, `artist_map`.`artist_id`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        Catalog::update_mapping('artist');
        Catalog::update_mapping('album');
        Catalog::update_mapping('album_disk');

        return true;
    }

    /**
     * _update_600013
     *
     * Add ui option 'api_enable_6' to enable/disable API6
     */
    private static function _update_600013(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'api_enable_6', 'Enable API6 responses', '1', 25, 'boolean', 'options');
    }

    /**
     * _update_600014
     *
     * Add `subtitle` to the album table
     */
    private static function _update_600014(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $sql       = "ALTER TABLE `album` ADD `subtitle` varchar(64) COLLATE $collation DEFAULT NULL AFTER `catalog_number`";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_600015
     *
     * Add `streamtoken` to user table allowing permalink music stream access
     */
    private static function _update_600015(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "ALTER TABLE `user` ADD `streamtoken` varchar(255) NULL AFTER `rsstoken`;") !== false);
    }

    /**
     * _update_600016
     *
     * Add `object_type_IDX` to artist_map table
     * Add `object_type_IDX` to catalog_map table
     */
    private static function _update_600016(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `album_map` DROP KEY `object_type_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `object_type_IDX` USING BTREE ON `album_map` (`object_type`);";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `catalog_map` DROP KEY `object_type_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `object_type_IDX` USING BTREE ON `catalog_map` (`object_type`);";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_600017 skipped.
     */

    /**
     * _update_600018
     *
     * Drop `user_playlist` table and recreate it
     */
    private static function _update_600018(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        $sql       = "DROP TABLE IF EXISTS `user_playlist`";
        Dba::write($sql);
        $sql = "CREATE TABLE IF NOT EXISTS `user_playlist` (`playqueue_time` int(11) UNSIGNED NOT NULL, `playqueue_client` varchar(255) CHARACTER SET $charset COLLATE $collation, user int(11) DEFAULT 0, `object_type` enum('song','live_stream','video','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci, `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0, `track` smallint(6) UNSIGNED NOT NULL DEFAULT 0, `current_track` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `current_time` smallint(5) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`playqueue_time`, `playqueue_client`, `user`, `track`), KEY `user` (`user`), KEY `object_type` (`object_type`), KEY `object_id` (`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_600019
     *
     * During migration some album_disk data may be missing it's object type
     */
    private static function _update_600019(Interactor $interactor = null): bool
    {
        $sql = "UPDATE IGNORE `rating` SET `object_type` = 'album_disk' WHERE `object_type` = '';";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "DELETE FROM `rating` WHERE `object_type` = '';";
        Dba::write($sql);
        $sql = "UPDATE IGNORE `object_count` SET `object_type` = 'album_disk' WHERE `object_type` = '';";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "DELETE FROM `object_count` WHERE `object_type` = '';";
        Dba::write($sql);
        // rating (id, `user`, object_type, object_id, rating)
        $sql = "INSERT IGNORE INTO `rating` (`object_type`, `object_id`, `user`, `rating`, `date`) SELECT DISTINCT 'album_disk', `album_disk`.`id`, `rating`.`user`, `rating`.`rating`, `rating`.`date` FROM `rating` LEFT JOIN `album` ON `rating`.`object_type` = 'album' AND `rating`.`object_id` = `album`.`id` LEFT JOIN `album_disk` ON `album`.`id` = `album_disk`.`album_id` LEFT JOIN `rating` AS `album_rating` ON `album_rating`.`object_type` = 'album' AND `rating`.`rating` = `album_rating`.`rating` AND `rating`.`user` = `album_rating`.`user` WHERE `rating`.`object_type` = 'album' AND `album_disk`.`id` IS NOT NULL;";
        Dba::write($sql);
        // user_flag (id, `user`, object_id, object_type, `date`)
        $sql = "INSERT IGNORE INTO `user_flag` (`object_type`, `object_id`, `user`, `date`) SELECT DISTINCT 'album_disk', `album_disk`.`id`, `user_flag`.`user`, `user_flag`.`date` FROM `user_flag` LEFT JOIN `album` ON `user_flag`.`object_type` = 'album' AND `user_flag`.`object_id` = `album`.`id` LEFT JOIN `album_disk` ON `album`.`id` = `album_disk`.`album_id` LEFT JOIN `user_flag` AS `album_flag` ON `album_flag`.`object_type` = 'album' AND `user_flag`.`date` = `album_flag`.`date` AND `user_flag`.`user` = `album_flag`.`user` WHERE `user_flag`.`object_type` = 'album' AND `album_disk`.`id` IS NOT NULL;";
        Dba::write($sql);

        return true;
    }

    /**
     * _update_600020
     *
     * Set system preferences to 100.
     * These options are only available to Admin users anyway
     */
    private static function _update_600020(Interactor $interactor = null): bool
    {
        return (self::_write($interactor, "UPDATE `preference` SET `level` = 100 WHERE `catagory` = 'system';") !== false);
    }

    /** _update_600021
     *
     * Extend `time` column for the song table
     */
    private static function _update_600021(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `song` MODIFY COLUMN `time` int(11) unsigned NOT NULL DEFAULT 0;";

        return (self::_write($interactor, $sql) !== false);
    }

    /** _update_600022
     *
     * Extend `time` column for the stream_playlist table
     */
    private static function _update_600022(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `stream_playlist` MODIFY COLUMN `time` int(11) NULL;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_600023
     *
     * Add upload_access_level to restrict uploads to certain users
     */
    private static function _update_600023(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'upload_access_level', 'Upload Access Level', '25', 100, 'special', 'system', 'upload');
    }

    /**
     * _update_600024
     *
     * Add ui option ('show_subtitle') Show Album subtitle on links (if available)
     * Add ui option ('show_original_year') Show Album original year on links (if available)
     */
    private static function _update_600024(Interactor $interactor = null): bool
    {
        if (self::_write_preference($interactor, 'show_subtitle', 'Show Album subtitle on links (if available)', '1', 25, 'boolean', 'interface', 'browse') === false) {
            return false;
        }
        if (self::_write_preference($interactor, 'show_original_year', 'Show Album original year on links (if available)', '1', 25, 'boolean', 'interface', 'browse') === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_600025
     *
     * Add ui option ('show_header_login') Show the login / registration links in the site header
     */
    private static function _update_600025(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'show_header_login', 'Show the login / registration links in the site header', '1', 100, 'boolean', 'system', 'interface');
    }

    /** _update_600026
     *
     * Add user preference `use_play2`, Use an alternative playback action for streaming if you have issues with playing music
     */
    private static function _update_600026(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'use_play2', 'Use an alternative playback action for streaming if you have issues with playing music', '0', 25, 'special', 'streaming', 'player');
    }

    /**
     * _update_600027
     *
     * Rename `subtitle` to `version` in the `album` table
     */
    private static function _update_600027(Interactor $interactor = null): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $sql       = "ALTER TABLE `album` CHANGE `subtitle` `version` varchar(64) COLLATE $collation DEFAULT NULL";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * _update_600028
     *
     * Add `bitrate`, `rate`, `mode` and `channels` to the `podcast_episode` table
     */
    private static function _update_600028(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `podcast_episode` ADD `channels` mediumint(9) DEFAULT NULL AFTER `catalog`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `podcast_episode` ADD `mode` enum('abr','vbr','cbr') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL AFTER `catalog`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `podcast_episode` ADD `rate` mediumint(8) UNSIGNED NOT NULL DEFAULT 0 AFTER `catalog`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `podcast_episode` ADD `bitrate` mediumint(8) UNSIGNED NOT NULL DEFAULT 0 AFTER `catalog`;";

        return (self::_write($interactor, $sql) !== false);
    }

    /**
     * update 600032
     *
     * Extend `object_type` enum list on `rating` table
     */
    private static function _update_600032(Interactor $interactor = null): bool
    {
        $sql = "DELETE FROM `rating` WHERE `object_type` IS NULL OR `object_type` NOT IN ('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'tvshow', 'tvshow_season', 'user', 'video')";
        Dba::write($sql);

        return (self::_write($interactor, "ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'tvshow', 'tvshow_season', 'user', 'video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;") !== false);
    }

    /**
     * update 600033
     *
     * Convert `object_type` to an enum on `user_flag` table
     */
    private static function _update_600033(Interactor $interactor = null): bool
    {
        $sql = "DELETE FROM `user_flag` WHERE `object_type` IS NULL OR `object_type` NOT IN ('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'tvshow', 'tvshow_season', 'user', 'video')";
        Dba::write($sql);

        return (self::_write($interactor, "ALTER TABLE `user_flag` MODIFY COLUMN `object_type` enum('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'tvshow', 'tvshow_season', 'user', 'video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;") !== false);
    }

    /**
     * update 600034
     *
     * Convert `object_type` to an enum on `image` table
     */
    private static function _update_600034(Interactor $interactor = null): bool
    {
        $sql = "DELETE FROM `image` WHERE `object_type` IS NULL OR `object_type` NOT IN ('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'tvshow', 'tvshow_season', 'user', 'video')";
        Dba::write($sql);

        return (self::_write($interactor, "ALTER TABLE `image` MODIFY COLUMN `object_type` enum('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'tvshow', 'tvshow_season', 'user', 'video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;") !== false);
    }

    /**
     * _update_600035
     *
     * Add `enabled` to `podcast_episode` table
     */
    private static function _update_600035(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `podcast_episode` DROP COLUMN `enabled`;";
        Dba::write($sql);
        $sql = "ALTER TABLE `podcast_episode` ADD COLUMN `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 AFTER `played`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        return true;
    }

    /**
     * _update_600036
     *
     * Update user `play_size` and catalog `size` fields to megabytes (Stop large catalogs overflowing 32bit ints)
     */
    private static function _update_600036(Interactor $interactor = null): bool
    {
        $sql       = "SELECT `id` FROM `user`";
        $db_users  = Dba::read($sql);
        $user_list = array();
        while ($results = Dba::fetch_assoc($db_users)) {
            $user_list[] = (int)$results['id'];
        }
        // After the change recalculate their total Bandwidth Usage
        foreach ($user_list as $user_id) {
            $total = User::get_play_size($user_id);
            $sql   = "REPLACE INTO `user_data` SET `user` = ?, `key` = ?, `value` = ?;";
            if (self::_write($interactor, $sql, array($user_id, 'play_size', $total)) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * _update_600037
     *
     * Update user server and user counts now that the scaling has changed
     */
    private static function _update_600037(Interactor $interactor = null): bool
    {
        if ($interactor) {
            $interactor->info(
                'update_table_counts',
                true
            );
        }
        // update server total counts
        $catalog_disable = AmpConfig::get('catalog_disable');
        // tables with media items to count, song-related tables and the rest
        $media_tables = array('song', 'video', 'podcast_episode');
        $items        = 0;
        $time         = 0;
        $size         = 0;
        foreach ($media_tables as $table) {
            $enabled_sql = ($catalog_disable) ? " WHERE `$table`.`enabled` = '1'" : '';
            $sql         = "SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`)/1024/1024, 0) FROM `$table`" . $enabled_sql;
            $db_results  = Dba::read($sql);
            $row         = Dba::fetch_row($db_results);
            // save the object and add to the current size
            $items += (int)($row[0] ?? 0);
            $time += (int)($row[1] ?? 0);
            $size += $row[2] ?? 0;
            Catalog::set_update_info($table, (int)($row[0] ?? 0));
        }
        Catalog::set_update_info('items', $items);
        Catalog::set_update_info('time', $time);
        Catalog::set_update_info('size', $size);
        User::update_counts();

        return true;
    }

    /**
     * _update_600038
     *
     * Update `access_list` in case you have a bad `user` column
     */
    private static function _update_600038(Interactor $interactor = null): bool
    {
        $sql = "UPDATE `access_list` SET `user` = -1 WHERE `user` = 0;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        return true;
    }

    /** _update_600039
     *
     * Add user preference `custom_timezone`, Custom timezone (Override PHP date.timezone)
     */
    private static function _update_600039(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'custom_timezone', 'Custom timezone (Override PHP date.timezone)', '', 25, 'string', 'interface', 'custom');
    }

    /** _update_600040
     *
     * Add `disksubtitle` to `song_data` and `album_disk` table
     */
    private static function _update_600040(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `song_data` DROP COLUMN `disksubtitle`;";
        Dba::write($sql);
        $sql = "ALTER TABLE `song_data` ADD COLUMN `disksubtitle` varchar(255) NULL DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `album_disk` DROP COLUMN `disksubtitle`;";
        Dba::write($sql);
        $sql = "ALTER TABLE `album_disk` ADD COLUMN `disksubtitle` varchar(255) NULL DEFAULT NULL;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        return true;
    }

    /** _update_600041
     *
     * Index `label` column on the `label_asso` table
     */
    private static function _update_600041(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `label_asso` DROP KEY `label_asso_label_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `label_asso_label_IDX` USING BTREE ON `label_asso` (`label`);";

        return (self::_write($interactor, $sql) !== false);
    }

    /** _update_600042
     *
     * Add user preference `bookmark_latest`, Only keep the latest media bookmark
     */
    private static function _update_600042(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'bookmark_latest', 'Only keep the latest media bookmark', '0', 25, 'boolean', 'options');
    }

    /** _update_600043
     *
     * Set correct preference type for `use_play2`
     * Add user preference `jp_volume`, Default webplayer volume
     */
    private static function _update_600043(Interactor $interactor = null): bool
    {
        $sql = "UPDATE `preference` SET `type` = 'boolean' WHERE `name` = 'use_play2'";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }

        return self::_write_preference($interactor, 'jp_volume', 'Default webplayer volume', 0.80, 25, 'special', 'streaming', 'player');
    }

    /** _update_600044
     *
     * Add system preference `perpetual_api_session`, API sessions do not expire
     */
    private static function _update_600044(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'perpetual_api_session', 'API sessions do not expire', '0', 100, 'boolean', 'system', 'backend');
    }

    /** _update_600045
     *
     * Add column `last_update` and `date`to search table
     */
    private static function _update_600045(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `search` DROP COLUMN `last_update`;";
        Dba::write($sql);
        $sql = "ALTER TABLE `search` ADD COLUMN `last_update` int(11) unsigned NOT NULL DEFAULT 0 AFTER `type`;";
        if (self::_write($interactor, $sql) === false) {
            return false;
        }
        $sql = "ALTER TABLE `search` DROP COLUMN `date`;";
        Dba::write($sql);

        return (self::_write($interactor, "ALTER TABLE `search` ADD COLUMN `date` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `type`;") !== false);
    }

    /** _update_600046
     *
     * Add user preference `home_recently_played_all`, Show all media types in Recently Played
     */
    private static function _update_600046(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'home_recently_played_all', 'Show all media types in Recently Played', '0', 25, 'bool', 'interface', 'home');
    }

    /** _update_600047
     *
     * Add user preference `show_wrapped`, Enable access to your personal "Spotify Wrapped" from your user page
     */
    private static function _update_600047(Interactor $interactor = null): bool
    {
        return self::_write_preference($interactor, 'show_wrapped', 'Enable access to your personal "Spotify Wrapped" from your user page', '0', 25, 'bool', 'interface', 'privacy');
    }

    /** _update_600048
     *
     * Add `date` column to rating table
     */
    private static function _update_600048(Interactor $interactor = null): bool
    {
        $sql = "ALTER TABLE `rating` DROP COLUMN `date`;";
        Dba::write($sql);

        return (self::_write($interactor, "ALTER TABLE `rating` ADD COLUMN `date` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `rating`;") !== false);
    }
}
