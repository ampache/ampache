<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;

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
     */
    public static function get_version()
    {
        $version = "";
        /* Make sure that update_info exits */
        $sql        = "SHOW TABLES LIKE 'update_info'";
        $db_results = Dba::read($sql);
        if (!Dba::dbh()) {
            header("Location: test.php");
        }

        // If no table
        if (!Dba::num_rows($db_results)) {
            // They can't upgrade, they are too old
            header("Location: test.php");
        } else {
            // If we've found the update_info table, let's get the version from it
            $sql        = "SELECT `key`, `value` FROM `update_info` WHERE `key`='db_version'";
            $db_results = Dba::read($sql);
            $results    = Dba::fetch_assoc($db_results);
            $version    = $results['value'];
        }

        return $version;
    } // get_version

    /**
     * check_tables
     *
     * is something missing? why is it missing!?
     * @param bool $execute
     * @return array
     */
    public static function check_tables(bool $execute = false)
    {
        $db_version = (int)self::get_version();
        $missing    = array();
        $collation  = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset    = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine     = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        $tables     = array(
            'image' => "CREATE TABLE IF NOT EXISTS `image` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `image` mediumblob DEFAULT NULL, `width` int(4) UNSIGNED DEFAULT 0, `height` int(4) UNSIGNED DEFAULT 0, `mime` varchar(64) COLLATE $collation DEFAULT NULL, `size` varchar(64) COLLATE $collation DEFAULT NULL, `object_type` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `kind` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`), KEY `object_type` (`object_type`), KEY `object_id` (`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'tmp_browse' => "CREATE TABLE IF NOT EXISTS `tmp_browse` ( `id` int(13) NOT NULL AUTO_INCREMENT, `sid` varchar(128) COLLATE $collation NOT NULL, `data` longtext COLLATE $collation NOT NULL, `object_data` longtext COLLATE $collation DEFAULT NULL, PRIMARY KEY (`sid`,`id`)) ENGINE=MyISAM DEFAULT CHARSET=$charset COLLATE=$collation;",
            'share' => "CREATE TABLE IF NOT EXISTS `share` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `allow_stream` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `allow_download` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `expire_days` int(4) UNSIGNED NOT NULL DEFAULT 0, `max_counter` int(4) UNSIGNED NOT NULL DEFAULT 0, `secret` varchar(20) COLLATE $collation DEFAULT NULL, `counter` int(4) UNSIGNED NOT NULL DEFAULT 0, `creation_date` int(11) UNSIGNED NOT NULL DEFAULT 0, `lastvisit_date` int(11) UNSIGNED NOT NULL DEFAULT 0, `public_url` varchar(255) COLLATE $collation DEFAULT NULL, `description` varchar(255) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'broadcast' => "CREATE TABLE IF NOT EXISTS `broadcast` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `name` varchar(64) COLLATE $collation DEFAULT NULL, `description` varchar(256) COLLATE $collation DEFAULT NULL, `is_private` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `song` int(11) UNSIGNED NOT NULL DEFAULT 0, `started` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `listeners` int(11) UNSIGNED NOT NULL DEFAULT 0, `key` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'player_control' => "CREATE TABLE IF NOT EXISTS `player_control` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `cmd` varchar(32) COLLATE $collation DEFAULT NULL, `value` varchar(256) COLLATE $collation DEFAULT NULL, `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `send_date` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'recommendation' => "CREATE TABLE IF NOT EXISTS `recommendation` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `last_update` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'recommendation_item' => "CREATE TABLE IF NOT EXISTS `recommendation_item` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `recommendation` int(11) UNSIGNED NOT NULL, `recommendation_id` int(11) UNSIGNED DEFAULT NULL, `name` varchar(256) COLLATE $collation DEFAULT NULL, `rel` varchar(256) COLLATE $collation DEFAULT NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'license' => "CREATE TABLE IF NOT EXISTS `license` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(80) COLLATE $collation DEFAULT NULL, `description` varchar(256) COLLATE $collation DEFAULT NULL, `external_link` varchar(256) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine AUTO_INCREMENT=15 DEFAULT CHARSET=$charset COLLATE=$collation;",
            'daap_session' => "CREATE TABLE IF NOT EXISTS `daap_session` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `creationdate` int(11) UNSIGNED NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'tvshow' => "CREATE TABLE IF NOT EXISTS `tvshow` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(80) COLLATE $collation DEFAULT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, `year` int(11) UNSIGNED DEFAULT NULL, `prefix` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'tvshow_season' => "CREATE TABLE IF NOT EXISTS `tvshow_season` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `season_number` int(11) UNSIGNED NOT NULL, `tvshow` int(11) UNSIGNED NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'tvshow_episode' => "CREATE TABLE IF NOT EXISTS `tvshow_episode` ( `id` int(11) UNSIGNED NOT NULL, `original_name` varchar(80) COLLATE $collation DEFAULT NULL, `season` int(11) UNSIGNED NOT NULL, `episode_number` int(11) UNSIGNED NOT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'movie' => "CREATE TABLE IF NOT EXISTS `movie` ( `id` int(11) UNSIGNED NOT NULL, `original_name` varchar(80) COLLATE $collation DEFAULT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, `year` int(11) UNSIGNED DEFAULT NULL, `prefix` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'personal_video' => "CREATE TABLE IF NOT EXISTS `personal_video` ( `id` int(11) UNSIGNED NOT NULL, `location` varchar(256) COLLATE $collation DEFAULT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'clip' => "CREATE TABLE IF NOT EXISTS `clip` ( `id` int(11) UNSIGNED NOT NULL, `artist` int(11) DEFAULT NULL, `song` int(11) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'tag_merge' => "CREATE TABLE IF NOT EXISTS `tag_merge` ( `tag_id` int(11) NOT NULL, `merged_to` int(11) NOT NULL, PRIMARY KEY (`tag_id`,`merged_to`), KEY `merged_to` (`merged_to`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'label' => "CREATE TABLE IF NOT EXISTS `label` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(80) COLLATE $collation DEFAULT NULL, `category` varchar(40) COLLATE $collation DEFAULT NULL, `summary` text COLLATE $collation DEFAULT NULL, `address` varchar(256) COLLATE $collation DEFAULT NULL, `email` varchar(128) COLLATE $collation DEFAULT NULL, `website` varchar(256) COLLATE $collation DEFAULT NULL, `user` int(11) UNSIGNED DEFAULT NULL, `creation_date` int(11) UNSIGNED DEFAULT NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `country` varchar(64) COLLATE $collation DEFAULT NULL, `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'label_asso' => "CREATE TABLE IF NOT EXISTS `label_asso` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `label` int(11) UNSIGNED NOT NULL, `artist` int(11) UNSIGNED NOT NULL, `creation_date` int(11) UNSIGNED DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'user_pvmsg' => "CREATE TABLE IF NOT EXISTS `user_pvmsg` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `subject` varchar(80) COLLATE $collation DEFAULT NULL, `message` text COLLATE $collation DEFAULT NULL, `from_user` int(11) UNSIGNED NOT NULL, `to_user` int(11) UNSIGNED NOT NULL, `is_read` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `creation_date` int(11) UNSIGNED DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'user_follower' => "CREATE TABLE IF NOT EXISTS `user_follower` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `follow_user` int(11) UNSIGNED NOT NULL, `follow_date` int(11) UNSIGNED DEFAULT NULL, `creation_date` int(11) UNSIGNED DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'metadata_field' => "CREATE TABLE IF NOT EXISTS `metadata_field` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(255) COLLATE $collation DEFAULT NULL, `public` tinyint(1) NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'metadata' => "CREATE TABLE IF NOT EXISTS `metadata` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `object_id` int(11) UNSIGNED NOT NULL, `field` int(11) UNSIGNED NOT NULL, `data` text COLLATE $collation NOT NULL, `type` varchar(50) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`), KEY `field` (`field`), KEY `object_id` (`object_id`), KEY `type` (`type`), KEY `objecttype` (`object_id`,`type`), KEY `objectfield` (`object_id`,`field`,`type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'podcast' => "CREATE TABLE IF NOT EXISTS `podcast` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `feed` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) NOT NULL, `title` varchar(255) COLLATE $collation DEFAULT NULL, `website` varchar(255) COLLATE $collation DEFAULT NULL, `description` varchar(4096) COLLATE $collation DEFAULT NULL, `language` varchar(5) COLLATE $collation DEFAULT NULL, `copyright` varchar(255) COLLATE $collation DEFAULT NULL, `generator` varchar(64) COLLATE $collation DEFAULT NULL, `lastbuilddate` int(11) UNSIGNED NOT NULL DEFAULT 0, `lastsync` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `episodes` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'podcast_episode' => "CREATE TABLE IF NOT EXISTS `podcast_episode` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `title` varchar(255) COLLATE $collation DEFAULT NULL, `guid` varchar(255) COLLATE $collation DEFAULT NULL, `podcast` int(11) NOT NULL, `state` varchar(32) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `source` varchar(4096) COLLATE $collation DEFAULT NULL, `size` bigint(20) UNSIGNED NOT NULL DEFAULT 0, `time` int(11) UNSIGNED NOT NULL DEFAULT 0, `website` varchar(255) COLLATE $collation DEFAULT NULL, `description` varchar(4096) COLLATE $collation DEFAULT NULL, `author` varchar(64) COLLATE $collation DEFAULT NULL, `category` varchar(64) COLLATE $collation DEFAULT NULL, `played` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `pubdate` int(11) UNSIGNED NOT NULL, `addition_time` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0, `waveform` mediumblob DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'cache_object_count' => "CREATE TABLE IF NOT EXISTS `cache_object_count` ( `object_id` int(11) UNSIGNED NOT NULL, `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `count` int(11) UNSIGNED NOT NULL DEFAULT 0, `threshold` int(11) UNSIGNED NOT NULL DEFAULT 0, `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, PRIMARY KEY (`object_id`,`object_type`,`threshold`,`count_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'cache_object_count_run' => "CREATE TABLE IF NOT EXISTS `cache_object_count_run` ( `object_id` int(11) UNSIGNED NOT NULL, `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `count` int(11) UNSIGNED NOT NULL DEFAULT 0, `threshold` int(11) UNSIGNED NOT NULL DEFAULT 0, `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, PRIMARY KEY (`object_id`,`object_type`,`threshold`,`count_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'catalog_map' => "CREATE TABLE IF NOT EXISTS `catalog_map` ( `catalog_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_catalog_map` (`object_id`,`object_type`,`catalog_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'user_playlist' => "CREATE TABLE IF NOT EXISTS `user_playlist` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) DEFAULT NULL, `object_type` enum('song','live_stream','video','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0, `track` smallint(6) DEFAULT NULL, `current_track` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `current_time` smallint(5) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`), KEY `user` (`user`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'user_data' => "CREATE TABLE IF NOT EXISTS `user_data` ( `user` int(11) DEFAULT NULL, `key` varchar(128) COLLATE $collation DEFAULT NULL, `value` varchar(255) COLLATE $collation DEFAULT NULL, UNIQUE KEY `unique_data` (`user`,`key`), KEY `user` (`user`), KEY `key` (`key`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'deleted_song' => "CREATE TABLE IF NOT EXISTS `deleted_song` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED DEFAULT 0, `delete_time` int(11) UNSIGNED DEFAULT 0, `title` varchar(255) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, `update_time` int(11) UNSIGNED DEFAULT 0, `album` int(11) UNSIGNED NOT NULL DEFAULT 0, `artist` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'deleted_video' => "CREATE TABLE IF NOT EXISTS `deleted_video` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'deleted_podcast_episode' => "CREATE TABLE IF NOT EXISTS `deleted_podcast_episode` ( `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, `podcast` int(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;",
            'artist_map' => "CREATE TABLE IF NOT EXISTS `artist_map` ( `artist_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_artist_map` (`object_id`,`object_type`,`artist_id`), KEY `object_id_index` (`object_id`), KEY `artist_id_index` (`artist_id`), KEY `artist_id_type_index` (`artist_id`,`object_type`), KEY `object_id_type_index` (`object_id`,`object_type`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            'album_map' => "CREATE TABLE IF NOT EXISTS `album_map` ( `album_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_album_map` (`object_id`,`object_type`,`album_id`), KEY `object_id_index` (`object_id`), KEY `album_id_type_index` (`album_id`,`object_type`), KEY `object_id_type_index` (`object_id`,`object_type`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            'catalog_filter_group' => "CREATE TABLE IF NOT EXISTS `catalog_filter_group` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('DEFAULT'); UPDATE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT'; ALTER TABLE `catalog_filter_group` AUTO_INCREMENT = 1;",
            'catalog_filter_group_map' => "CREATE TABLE IF NOT EXISTS `catalog_filter_group_map` (`group_id` int(11) UNSIGNED NOT NULL, `catalog_id` int(11) UNSIGNED NOT NULL, `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, UNIQUE KEY (group_id,catalog_id)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;"
        );
        $versions   = array(
            'image' => 360003,
            'tmp_browse' => 360005,
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
            'tag_merge' => 370018,
            'label' => 370033,
            'label_asso' => 370033,
            'user_pvmsg' => 370034,
            'user_follower' => 370034,
            'metadata_field' => 370041,
            'metadata' => 370041,
            'podcast' => 380001,
            'podcast_episode' => 380001,
            'cache_object_count' => 400008,
            'cache_object_count_run' => 400008,
            'catalog_map' => 500004,
            'user_playlist' => 500006,
            'user_data' => 500006,
            'deleted_song' => 500013,
            'deleted_video' => 500013,
            'deleted_podcast_episode' => 500013,
            'artist_map' => 530000,
            'album_map' => 530001,
            'catalog_filter_group' => 550001,
            'catalog_filter_group_map' => 550001
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
     * Make the version number pretty.
     * @param string $data
     * @return string
     */
    public static function format_version($data)
    {
        return substr($data, 0, strlen((string)$data) - 5) . '.' . substr($data, strlen((string)$data) - 5, 1) . ' Build:' . substr($data, strlen((string)$data) - 4, strlen((string)$data));
    }

    /**
     * need_update
     *
     * Checks to see if we need to update ampache at all.
     */
    public static function need_update(): bool
    {
        $current_version = self::get_version();

        if (!is_array(self::$versions)) {
            self::$versions = self::populate_version();
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
     * populate_version
     * just sets an array the current differences
     * that require an update
     * @return array
     */
    public static function populate_version()
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

        $update_string = "* Copy default .htaccess configurations.<br />";
        $version[]     = array('version' => '360051', 'description' => $update_string);

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

        $update_string = ""; // REMOVED update
        $version[]     = array('version' => '400017', 'description' => $update_string);

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

        $update_string = "**IMPORTANT UPDATE NOTES**<br />For large catalogs this will be slow!<br />* Create catalog_map table and fill it with data";
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

        $update_string = ""; // REMOVED update
        $version[]     = array('version' => '510002', 'description' => $update_string);

        $update_string = "* Add live_stream to the rating table";
        $version[]     = array('version' => '510003', 'description' => $update_string);

        $update_string = "* Add waveform column to podcast_episode table";
        $version[]     = array('version' => '510004', 'description' => $update_string);

        $update_string = "* Add ui option ('subsonic_always_download') Force Subsonic streams to download. (Enable scrobble in your client to record stats)";
        $version[]     = array('version' => '510005', 'description' => $update_string);

        $update_string = "* Add ui options ('api_enable_3', 'api_enable_4', 'api_enable_5') to enable/disable specific API versions<br />* Add ui option ('api_force_version') to to force a specific API response (even if that version is disabled)";
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

        $update_string = "**IMPORTANT UPDATE NOTES**<br />For large catalogs this will be slow!<br />* Create artist_map table and fill it with data";
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
        $current_version = self::get_version();
        if (!is_array(self::$versions)) {
            self::$versions = self::populate_version();
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
     * This function actually updates the db.
     * it goes through versions and finds the ones
     * that need to be run. Checking to make sure
     * the function exists first.
     */
    public static function run_update(): bool
    {
        debug_event(self::class, 'run_update: starting', 4);
        /* Nuke All Active session before we start the mojo */
        $sql = "TRUNCATE session";
        Dba::write($sql);

        // Prevent the script from timing out, which could be bad
        set_time_limit(0);

        $current_version = self::get_version();

        // Run a check to make sure that they don't try to upgrade from a version that won't work.
        if ($current_version < '350008') {
            echo '<p class="database-update">Database version too old, please upgrade to <a href="https://github.com/ampache/ampache/releases/download/3.8.2/ampache-3.8.2_all.zip">Ampache-3.8.2</a> first</p>';

            return false;
        }

        $methods = get_class_methods(Update::class);

        if (!is_array((self::$versions))) {
            self::$versions = self::populate_version();
        }

        debug_event(self::class, 'run_update: checking versions', 4);
        foreach (self::$versions as $version) {
            // If it's newer than our current version let's see if a function
            // exists and run the bugger.
            if ($version['version'] > $current_version) {
                $update_function = "update_" . $version['version'];
                if (in_array($update_function, $methods)) {
                    $success = call_user_func(array('Ampache\Module\System\Update', $update_function));

                    // If the update fails drop out
                    if ($success) {
                        debug_event(self::class, 'run_update: successfully updated to ' . $version['version'], 3);
                        self::set_version('db_version', $version['version']);
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
    } // run_update

    /**
     * set_version
     *
     * This updates the 'update_info' which is used by the updater and plugins
     * @param string $key
     * @param $value
     */
    private static function set_version($key, $value)
    {
        $sql = "UPDATE `update_info` SET `value` = ? WHERE `key` = ?";
        Dba::write($sql, array($value, $key));
    }

    /**
     * update_360001
     *
     * This adds the MB UUIDs to the different tables as well as some additional
     * cleanup.
     */
    public static function update_360001(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE `album` ADD `mbid` CHAR (36) AFTER `prefix`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `artist` ADD `mbid` CHAR (36) AFTER `prefix`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `song` ADD `mbid` CHAR (36) AFTER `track`";
        $retval &= (Dba::write($sql) !== false);

        // Remove any RIO related information from the database as the plugin has been removed
        $sql = "DELETE FROM `update_info` WHERE `key` LIKE 'Plugin_Ri%'";
        Dba::write($sql);
        $sql = "DELETE FROM `preference` WHERE `name` LIKE 'rio_%'";
        Dba::write($sql);

        return $retval;
    }

    /**
     * update_360002
     *
     * This update makes changes to the cataloging to accomodate the new method
     * for syncing between Ampache instances.
     */
    public static function update_360002(): bool
    {
        $retval = true;
        // Drop the key from catalog and ACL
        $sql = "ALTER TABLE `catalog` DROP `key`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `access_list` DROP `key`";
        $retval &= (Dba::write($sql) !== false);

        // Add in Username / Password for catalog - to be used for remote catalogs
        $sql = "ALTER TABLE `catalog` ADD `remote_username` VARCHAR (255) AFTER `catalog_type`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `catalog` ADD `remote_password` VARCHAR (255) AFTER `remote_username`";
        $retval &= (Dba::write($sql) !== false);

        // Adjust the Filename field in song, make it gi-normous. If someone has
        // anything close to this file length, they seriously need to reconsider
        // what they are doing.
        $sql = "ALTER TABLE `song` CHANGE `file` `file` VARCHAR (4096)";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `video` CHANGE `file` `file` VARCHAR (4096)";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `live_stream` CHANGE `url` `url` VARCHAR (4096)";
        $retval &= (Dba::write($sql) !== false);

        // Index the Artist, Album, and Song tables for fulltext searches.
        $sql = "ALTER TABLE `artist` ADD FULLTEXT(`name`)";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `album` ADD FULLTEXT(`name`)";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `song` ADD FULLTEXT(`title`)";
        $retval &= (Dba::write($sql) !== false);

        // Now add in the min_object_count preference and the random_method
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('bandwidth', '50', 'Bandwidth', '5', 'integer', 'interface')";
        Dba::write($sql);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('features', '50', 'Features', '5', 'integer', 'interface')";
        Dba::write($sql);

        return $retval;
    }

    /**
     * update_360003
     *
     * This update moves the image data to its own table.
     */
    public static function update_360003(): bool
    {
        $retval    = true;
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        $sql       = "CREATE TABLE `image` (`id` int(11) unsigned NOT NULL auto_increment, `image` mediumblob NOT NULL, `mime` varchar(64) NOT NULL, `size` varchar(64) NOT NULL, `object_type` varchar(64) NOT NULL, `object_id` int(11) unsigned NOT NULL, PRIMARY KEY  (`id`), KEY `object_type` (`object_type`), KEY `object_id` (`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation";
        $retval &= (Dba::write($sql) !== false);

        foreach (array('album', 'artist') as $type) {
            $sql        = "SELECT `" . $type . "_id` AS `object_id`, `art`, `art_mime` FROM `" . $type . "_data` WHERE `art` IS NOT NULL";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `object_type`, `object_id`) VALUES('" . Dba::escape($row['art']) . "', '" . $row['art_mime'] . "', 'original', '" . $type . "', '" . $row['object_id'] . "')";
                Dba::write($sql);
            }
            $sql = "DROP TABLE `" . $type . "_data`";
            $retval &= (Dba::write($sql) !== false);
        }

        return $retval;
    }

    /**
     * update_360004
     *
     * This update creates an index on the rating table.
     */
    public static function update_360004()
    {
        return (Dba::write("CREATE UNIQUE INDEX `unique_rating` ON `rating` (`user`, `object_type`, `object_id`);") !== false);
    }

    /**
     * update_360005
     *
     * This changes the tmp_browse table around.
     */
    public static function update_360005(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "DROP TABLE IF EXISTS `tmp_browse`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `tmp_browse` (`id` int(13) NOT NULL auto_increment, `sid` varchar(128) CHARACTER SET $charset NOT NULL default '', `data` longtext NOT NULL, `object_data` longtext, PRIMARY KEY  (`sid`, `id`)) ENGINE=$engine DEFAULT CHARSET=utf8";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_360006
     *
     * This adds the table for newsearch/dynamic playlists
     */
    public static function update_360006(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (Dba::write("CREATE TABLE `search` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) NOT NULL, `type` enum('private', 'public') CHARACTER SET $charset DEFAULT NULL, `rules` mediumtext NOT NULL, `name` varchar(255) CHARACTER SET $charset DEFAULT NULL, `logic_operator` varchar(3) CHARACTER SET $charset DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine AUTO_INCREMENT=4 DEFAULT CHARSET=$charset;") !== false);
    }

    /**
     * update_360008
     *
     * Fix bug that caused the remote_username/password fields to not be created.
     * FIXME: Huh?
     */
    public static function update_360008(): bool
    {
        $retval          = true;
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
            $retval &= (Dba::write($sql) !== false);
        }
        if (!$remote_password) {
            $sql = "ALTER TABLE `catalog` ADD `remote_password` VARCHAR (255) AFTER `remote_username`";
            $retval &= (Dba::write($sql) !== false);
        }

        return $retval;
    }

    /**
     * update_360009
     *
     * The main session table was already updated to use varchar(64) for the ID,
     * tmp_playlist needs the same change
     */
    public static function update_360009(): bool
    {
        return (Dba::write("ALTER TABLE `tmp_playlist` CHANGE `session` `session` VARCHAR(64);") !== false);
    }

    /**
     * update_360010
     *
     * MBz NGS means collaborations have more than one MBID (the ones
     * belonging to the underlying artists).  We need a bigger column.
     */
    public static function update_360010(): bool
    {
        return (Dba::write("ALTER TABLE `artist` CHANGE `mbid` `mbid` VARCHAR(36);") !== false);
    }

    /**
     * update_360011
     *
     * We need a place to store actual playlist data for downloadable
     * playlist files.
     */
    public static function update_360011(): bool
    {
        return (Dba::write("CREATE TABLE `stream_playlist` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `sid` varchar(64) NOT NULL, `url` text NOT NULL, `info_url` text DEFAULT NULL, `image_url` text DEFAULT NULL, `title` varchar(255) DEFAULT NULL, `author` varchar(255) DEFAULT NULL, `album` varchar(255) DEFAULT NULL, `type` varchar(255) DEFAULT NULL, `time` smallint(5) DEFAULT NULL, PRIMARY KEY (`id`), KEY `sid` (`sid`));") !== false);
    }

    /**
     * update_360012
     *
     * Drop the enum on session.type
     */
    public static function update_360012(): bool
    {
        return (Dba::write("ALTER TABLE `session` CHANGE `type` `type` VARCHAR(16) DEFAULT NULL;") !== false);
    }

    /**
     * update_360013
     *
     * MyISAM works better out of the box for the stream_playlist table
     */
    public static function update_360013(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (Dba::write("ALTER TABLE `stream_playlist` ENGINE=$engine;") !== false);
    }

    /**
     * update_360014
     *
     * PHP session IDs are an ever-growing beast.
     */
    public static function update_360014(): bool
    {
        $retval = true;

        $retval &= (Dba::write("ALTER TABLE `stream_playlist` CHANGE `sid` `sid` VARCHAR(256);") !== false);
        $retval &= (Dba::write("ALTER TABLE `tmp_playlist` CHANGE `session` `session` VARCHAR(256);") !== false);
        $retval &= (Dba::write("ALTER TABLE `session` CHANGE `id` `id` VARCHAR(256) NOT NULL;") !== false);

        return $retval;
    }

    /**
     * update_360015
     *
     * This inserts the Iframes preference...
     */
    public static function update_360015(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('iframes', '1', 'Iframes', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /*
     * update_360016
     *
     * Add Now Playing filtered per user preference option
     */
    public static function update_360016(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('now_playing_per_user', '1', 'Now playing filtered per user', 50, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360017
     *
     * New table to store user flags.
     */
    public static function update_360017(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (Dba::write("CREATE TABLE `user_flag` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) NOT NULL, `object_id` int(11) unsigned NOT NULL, `object_type` varchar(32) CHARACTER SET $charset DEFAULT NULL, `date` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`), UNIQUE KEY `unique_userflag` (`user`, `object_type`, `object_id`), KEY `object_id` (`object_id`)) ENGINE=$engine;") !== false);
    }

    /**
     * update_360018
     *
     * Add Album default sort preference...
     */
    public static function update_360018(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('album_sort', '0', 'Album Default Sort', 25, 'string', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360019
     *
     * Add Show number of times a song was played preference
     */
    public static function update_360019(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('show_played_times', '0', 'Show # played', 25, 'string', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360020
     *
     * Catalog types are plugins now
     */
    public static function update_360020(): bool
    {
        $retval    = true;
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `catalog_local` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `path` VARCHAR(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::write($sql);
        $sql = "CREATE TABLE `catalog_remote` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `uri` VARCHAR(255) COLLATE $collation NOT NULL, `username` VARCHAR(255) COLLATE $collation NOT NULL, `password` VARCHAR(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::write($sql);

        $sql        = "SELECT `id`, `catalog_type`, `path`, `remote_username`, `remote_password` FROM `catalog`";
        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            if ($results['catalog_type'] == 'local') {
                $sql = "INSERT INTO `catalog_local` (`path`, `catalog_id`) VALUES (?, ?)";
                $retval &= (Dba::write($sql, array($results['path'], $results['id'])) !== false);
            } elseif ($results['catalog_type'] == 'remote') {
                $sql = "INSERT INTO `catalog_remote` (`uri`, `username`, `password`, `catalog_id`) VALUES (?, ?, ?, ?)";
                $retval &= (Dba::write($sql, array($results['path'], $results['remote_username'], $results['remote_password'], $results['id'])) !== false);
            }
        }

        $sql = "ALTER TABLE `catalog` DROP `path`, DROP `remote_username`, DROP `remote_password`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `catalog` MODIFY COLUMN `catalog_type` varchar(128)";
        $retval &= (Dba::write($sql) !== false);
        $sql = "UPDATE `artist` SET `mbid` = NULL WHERE `mbid` = ''";
        $retval &= (Dba::write($sql) !== false);
        $sql = "UPDATE `album` SET `mbid` = NULL WHERE `mbid` = ''";
        $retval &= (Dba::write($sql) !== false);
        $sql = "UPDATE `song` SET `mbid` = NULL WHERE `mbid` = ''";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_360021
     *
     * Add insertion date on Now Playing and option to show the current song in page title for Web player
     */
    public static function update_360021(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE `now_playing` ADD `insertion` INT (11) AFTER `expire`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('song_page_title', '1', 'Show current song in Web player page title', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360022
     *
     * Remove unused live_stream fields and add codec field
     */
    public static function update_360022(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE `live_stream` ADD `codec` VARCHAR(32) NULL AFTER `catalog`, DROP `frequency`, DROP `call_sign`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `stream_playlist` ADD `codec` VARCHAR(32) NULL AFTER `time`";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_360023
     *
     * Enable/Disable SubSonic and Plex backend
     */
    public static function update_360023(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('subsonic_backend', '1', 'Use SubSonic backend', 100, 'boolean', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('plex_backend', '0', 'Use Plex backend', 100, 'boolean', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360024
     *
     * Drop unused flagged table
     */
    public static function update_360024(): bool
    {
        return (Dba::write("DROP TABLE IF EXISTS `flagged`;") !== false);
    }

    /**
     * update_360025
     *
     * Add options to enable HTML5 / Flash on web players
     */
    public static function update_360025(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('webplayer_flash', '1', 'Authorize Flash Web Player(s)', 25, 'boolean', 'streaming')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('webplayer_html5', '1', 'Authorize HTML5 Web Player(s)', 25, 'boolean', 'streaming')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360026
     *
     * Add agent field in `object_count` table
     */
    public static function update_360026(): bool
    {
        return (Dba::write("ALTER TABLE `object_count` ADD `agent` VARCHAR(255) NULL AFTER `user`;") !== false);
    }

    /**
     * update_360027
     *
     * Personal information: allow/disallow to show my personal information into now playing and recently played lists.
     */
    public static function update_360027(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('allow_personal_info', '1', 'Allow to show my personal info to other users (now playing, recently played)', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360028
     *
     * Personal information: allow/disallow to show in now playing.
     * Personal information: allow/disallow to show in recently played.
     * Personal information: allow/disallow to show time and/or agent in recently played.
     */
    public static function update_360028(): bool
    {
        $retval = true;

        // Update previous update preference
        $sql = "UPDATE `preference` SET `name`='allow_personal_info_now', `description`='Personal information visibility - Now playing' WHERE `name`='allow_personal_info'";
        $retval &= (Dba::write($sql) !== false);

        // Insert new recently played preference
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('allow_personal_info_recent', '1', 'Personal information visibility - Recently played / actions', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        // Insert streaming time preference
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('allow_personal_info_time', '1', 'Personal information visibility - Recently played - Allow to show streaming date/time', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        // Insert streaming agent preference
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('allow_personal_info_agent', '1', 'Personal information visibility - Recently played - Allow to show streaming agent', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360029
     *
     * New table to store wanted releases
     */
    public static function update_360029(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (Dba::write("CREATE TABLE `wanted` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) NOT NULL, `artist` int(11) NOT NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL, `name` varchar(255) CHARACTER SET $charset NOT NULL, `year` int(4) NULL, `date` int(11) unsigned NOT NULL DEFAULT '0', `accepted` tinyint(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), UNIQUE KEY `unique_wanted` (`user`, `artist`, `mbid`)) ENGINE=$engine;") !== false);
    }

    /**
     * update_360030
     *
     * New table to store song previews
     */
    public static function update_360030(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (Dba::write("CREATE TABLE `song_preview` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `session` varchar(256) CHARACTER SET $charset NOT NULL, `artist` int(11) NOT NULL, `title` varchar(255) CHARACTER SET $charset NOT NULL, `album_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL, `disk` int(11) NULL, `track` int(11) NULL, `file` varchar(255) CHARACTER SET $charset NULL, PRIMARY KEY (`id`)) ENGINE=$engine;") !== false);
    }

    /**
     * update_360031
     *
     * Add option to fix header/sidebars position on compatible themes
     */
    public static function update_360031(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('ui_fixed', '0', 'Fix header position on compatible themes', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360032
     *
     * Add check update automatically option
     */
    public static function update_360032(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('autoupdate', '1', 'Check for Ampache updates automatically', 25, 'boolean', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        Preference::insert('autoupdate_lastcheck', 'AutoUpdate last check time', '', '25', 'string', 'internal');
        Preference::insert('autoupdate_lastversion', 'AutoUpdate last version from last check', '', '25', 'string', 'internal');
        Preference::insert('autoupdate_lastversion_new', 'AutoUpdate last version from last check is newer', '', '25', 'boolean', 'internal');

        return $retval;
    }

    /**
     * update_360033
     *
     * Add song waveform as song data
     */
    public static function update_360033(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE `song_data` ADD `waveform` MEDIUMBLOB NULL AFTER `language`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `user_shout` ADD `data` VARCHAR(256) NULL AFTER `object_type`";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_360034
     *
     * Add settings for confirmation when closing window and auto-pause between tabs
     */
    public static function update_360034(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('webplayer_confirmclose', '0', 'Confirmation when closing current playing window', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('webplayer_pausetabs', '1', 'Auto-pause betweens tabs', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360035
     *
     * Add beautiful stream url setting
     * Reverted https://github.com/ampache/ampache/commit/0c26c336269624d75985e46d324e2bc8108576ee
     * with adding update_380012.
     * Because it was changed after many systems have already performed this update.
     * Fix for this is update_380012 that actually readds the preference string.
     * So all users have a consistent database.
     */
    public static function update_360035(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('stream_beautiful_url', '0', 'Use beautiful stream url', 100, 'boolean', 'streaming')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360036
     *
     * Remove some unused parameters
     */
    public static function update_360036(): bool
    {
        $retval = true;

        $sql = "DELETE FROM `preference` WHERE `name` LIKE 'ellipse_threshold_%'";
        $retval &= (Dba::write($sql) !== false);
        $sql = "DELETE FROM `preference` WHERE `name` = 'min_object_count'";
        $retval &= (Dba::write($sql) !== false);
        $sql = "DELETE FROM `preference` WHERE `name` = 'bandwidth'";
        $retval &= (Dba::write($sql) !== false);
        $sql = "DELETE FROM `preference` WHERE `name` = 'features'";
        $retval &= (Dba::write($sql) !== false);
        $sql = "DELETE FROM `preference` WHERE `name` = 'tags_userlist'";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_360037
     *
     * Add sharing features
     */
    public static function update_360037(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `share` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `allow_stream` tinyint(1) unsigned NOT NULL DEFAULT '0', `allow_download` tinyint(1) unsigned NOT NULL DEFAULT '0', `expire_days` int(4) unsigned NOT NULL DEFAULT '0', `max_counter` int(4) unsigned NOT NULL DEFAULT '0', `secret` varchar(20) CHARACTER SET $charset NULL, `counter` int(4) unsigned NOT NULL DEFAULT '0', `creation_date` int(11) unsigned NOT NULL DEFAULT '0', `lastvisit_date` int(11) unsigned NOT NULL DEFAULT '0', `public_url` varchar(255) CHARACTER SET $charset NULL, `description` varchar(255) CHARACTER SET $charset NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('share', '0', 'Allow Share', 100, 'boolean', 'options')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('share_expire', '7', 'Share links default expiration days (0=never)', 100, 'integer', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '7')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360038
     *
     * Add missing albums browse on missing artists
     */
    public static function update_360038(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `wanted` ADD `artist_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL AFTER `artist`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `wanted` MODIFY `artist` int(11) NULL";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `song_preview` ADD `artist_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL AFTER `artist`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `song_preview` MODIFY `artist` int(11) NULL";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_360039
     *
     * Add website field on users
     */
    public static function update_360039(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        return (Dba::write("ALTER TABLE `user` ADD `website` varchar(255) CHARACTER SET $charset NULL AFTER `email`;") !== false);
    }

    /**
     * update_360040 skipped.
     */

    /**
     * update_360041
     *
     * Add channels
     */
    public static function update_360041(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (Dba::write("CREATE TABLE `channel` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(64) CHARACTER SET $charset NULL, `description` varchar(256) CHARACTER SET $charset NULL, `url` varchar(256) CHARACTER SET $charset NULL, `interface` varchar(64) CHARACTER SET $charset NULL, `port` int(11) unsigned NOT NULL DEFAULT '0', `fixed_endpoint` tinyint(1) unsigned NOT NULL DEFAULT '0', `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `is_private` tinyint(1) unsigned NOT NULL DEFAULT '0', `random` tinyint(1) unsigned NOT NULL DEFAULT '0', `loop` tinyint(1) unsigned NOT NULL DEFAULT '0', `admin_password` varchar(20) CHARACTER SET $charset NULL, `start_date` int(11) unsigned NOT NULL DEFAULT '0', `max_listeners` int(11) unsigned NOT NULL DEFAULT '0', `peak_listeners` int(11) unsigned NOT NULL DEFAULT '0', `listeners` int(11) unsigned NOT NULL DEFAULT '0', `connections` int(11) unsigned NOT NULL DEFAULT '0', `stream_type` varchar(8) CHARACTER SET $charset NOT NULL DEFAULT 'mp3', `bitrate` int(11) unsigned NOT NULL DEFAULT '128', `pid` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine;") !== false);
    }

    /**
     * update_360042
     *
     * Add broadcasts and player control
     */
    public static function update_360042(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `broadcast` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `name` varchar(64) CHARACTER SET $charset NULL, `description` varchar(256) CHARACTER SET $charset NULL, `is_private` tinyint(1) unsigned NOT NULL DEFAULT '0', `song` int(11) unsigned NOT NULL DEFAULT '0', `started` tinyint(1) unsigned NOT NULL DEFAULT '0', `listeners` int(11) unsigned NOT NULL DEFAULT '0', `key` varchar(32) CHARACTER SET $charset NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `player_control` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `cmd` varchar(32) CHARACTER SET $charset NOT NULL, `value` varchar(256) CHARACTER SET $charset NULL, `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `send_date` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_360043
     *
     * Add slideshow on currently played artist preference
     */
    public static function update_360043(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('slideshow_time', '0', 'Artist slideshow inactivity time', 25, 'integer', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360044
     *
     * Add artist description/recommendation external service data cache
     */
    public static function update_360044(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "ALTER TABLE `artist` ADD `summary` TEXT CHARACTER SET $charset NULL, ADD `placeformed` varchar(64) NULL, ADD `yearformed` int(4) NULL, ADD `last_update` int(11) unsigned NOT NULL DEFAULT '0'";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `recommendation` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `last_update` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `recommendation_item` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `recommendation` int(11) unsigned NOT NULL, `recommendation_id` int(11) unsigned NULL, `name` varchar(256) NULL, `rel` varchar(256) NULL, `mbid` varchar(36) NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_360045
     *
     * Set user field on playlists as optional
     */
    public static function update_360045(): bool
    {
        return (Dba::write("ALTER TABLE `playlist` MODIFY `user` int(11) NULL;") !== false);
    }

    /**
     * update_360046
     *
     * Add broadcast web player by default preference
     */
    public static function update_360046(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('broadcast_by_default', '0', 'Broadcast web player by default', 25, 'boolean', 'streaming')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360047
     *
     * Add apikey field on users
     */
    public static function update_360047(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        return (Dba::write("ALTER TABLE `user` ADD `apikey` varchar(255) CHARACTER SET $charset NULL AFTER `website`;") !== false);
    }

    /**
     * update_360048
     *
     * Add concerts options
     */
    public static function update_360048(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('concerts_limit_future', '0', 'Limit number of future events', 25, 'integer', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('concerts_limit_past', '0', 'Limit number of past events', 25, 'integer', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360049
     *
     * Add album group multiple disks setting
     */
    public static function update_360049(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('album_group', '0', 'Album - Group multiple disks', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360050
     *
     * Add top menu setting
     */
    public static function update_360050(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('topmenu', '0', 'Top menu', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_360051
     *
     * REMOVED
     */
    public static function update_360051(): bool
    {
        return true;
    }

    /**
     * update_370001
     *
     * Drop unused dynamic_playlist tables and add session id to votes
     */
    public static function update_370001(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        $sql = "DROP TABLE dynamic_playlist";
        $retval &= (Dba::write($sql) !== false);
        $sql = "DROP TABLE dynamic_playlist_data";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `user_vote` ADD `sid` varchar(256) CHARACTER SET $charset NULL AFTER `date`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('demo_clear_sessions', '0', 'Clear democratic votes of expired user sessions', 25, 'boolean', 'playlist')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_370002
     *
     * Add tag persistent merge reference
     */
    public static function update_370002(): bool
    {
        return (Dba::write("ALTER TABLE `tag` ADD `merged_to` int(11) NULL AFTER `name`;") !== false);
    }

    /**
     * update_370003
     *
     * Add show/hide donate button preference
     */
    public static function update_370003(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('show_donate', '1', 'Show donate button in footer', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_370004
     *
     * Add license information and user's artist association
     */
    public static function update_370004(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('upload_catalog', '-1', 'Uploads catalog destination', 75, 'integer', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '-1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('allow_upload', '0', 'Allow users to upload media', 75, 'boolean', 'options')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('upload_subdir', '1', 'Upload: create a subdirectory per user (recommended)', 75, 'boolean', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('upload_user_artist', '0', 'Upload: consider the user sender as the track\'s artist', 75, 'boolean', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('upload_script', '', 'Upload: run the following script after upload (current directory = upload target directory)', 75, 'string', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('upload_allow_edit', '1', 'Upload: allow users to edit uploaded songs', 75, 'boolean', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "ALTER TABLE `artist` ADD `user` int(11) NULL AFTER `last_update`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `license` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(80) NOT NULL, `description` varchar(256) NULL, `external_link` varchar(256) NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('0 - default', '')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY', 'https://creativecommons.org/licenses/by/3.0/')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY NC', 'https://creativecommons.org/licenses/by-nc/3.0/')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY NC ND', 'https://creativecommons.org/licenses/by-nc-nd/3.0/')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY NC SA', 'https://creativecommons.org/licenses/by-nc-sa/3.0/')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY ND', 'https://creativecommons.org/licenses/by-nd/3.0/')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('CC BY SA', 'https://creativecommons.org/licenses/by-sa/3.0/')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('Licence Art Libre', 'http://artlibre.org/licence/lal/')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('Yellow OpenMusic', 'http://openmusic.linuxtag.org/yellow.html')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('Green OpenMusic', 'http://openmusic.linuxtag.org/green.html')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('Gnu GPL Art', 'http://gnuart.org/english/gnugpl.html')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('WTFPL', 'https://en.wikipedia.org/wiki/WTFPL')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('FMPL', 'http://www.fmpl.org/fmpl.html')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `license`(`name`, `external_link`) VALUES ('C Reaction', 'http://morne.free.fr/Necktar7/creaction.htm')";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `song` ADD `user_upload` int(11) NULL AFTER `addition_time`, ADD `license` int(11) NULL AFTER `user_upload`";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_370005
     *
     * Add new column album_artist into table album
     *
     */
    public static function update_370005(): bool
    {
        return (Dba::write("ALTER TABLE `song` ADD `album_artist` int(11) unsigned DEFAULT NULL AFTER `artist`;") !== false);
    }

    /**
     * update_370006
     *
     * Add random and limit options to smart playlists
     *
     */
    public static function update_370006(): bool
    {
        return (Dba::write("ALTER TABLE `search` ADD `random` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `logic_operator`, ADD `limit` int(11) unsigned NOT NULL DEFAULT '0' AFTER `random`;") !== false);
    }

    /**
     * update_370007
     *
     * Add DAAP backend preference
     */
    public static function update_370007(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('daap_backend', '0', 'Use DAAP backend', 100, 'boolean', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('daap_pass', '', 'DAAP backend password', 100, 'string', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "CREATE TABLE `daap_session` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `creationdate` int(11) unsigned NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_370008
     *
     * Add UPnP backend preference
     *
     */
    public static function update_370008(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('upnp_backend', '0', 'Use UPnP backend', 100, 'boolean', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_370009
     *
     * Enhance video support with TVShows and Movies
     */
    public static function update_370009(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "ALTER TABLE `video` ADD `release_date` date NULL AFTER `enabled`, ADD `played` tinyint(1) unsigned DEFAULT '0' NOT NULL AFTER `enabled`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `tvshow` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(80) NOT NULL, `summary` varchar(256) NULL, `year` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `tvshow_season` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `season_number` int(11) unsigned NOT NULL, `tvshow` int(11) unsigned NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `tvshow_episode` (`id` int(11) unsigned NOT NULL, `original_name` varchar(80) NULL, `season` int(11) unsigned NOT NULL, `episode_number` int(11) unsigned NOT NULL, `summary` varchar(256) NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `movie` (`id` int(11) unsigned NOT NULL, `original_name` varchar(80) NULL, `summary` varchar(256) NULL, `year` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `personal_video` (`id` int(11) unsigned NOT NULL, `location` varchar(256) NULL, `summary` varchar(256) NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `clip` (`id` int(11) unsigned NOT NULL, `artist` int(11) NULL, `song` int(11) NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('allow_video', '1', 'Allow video features', 75, 'integer', 'options')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "ALTER TABLE `image` ADD `kind` VARCHAR(32) NULL DEFAULT 'default' AFTER `object_id`";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_370010
     *
     * Add MusicBrainz Album Release Group identifier
     */
    public static function update_370010(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        return (Dba::write("ALTER TABLE `album` ADD `mbid_group` varchar(36) CHARACTER SET $charset NULL;") !== false);
    }

    /**
     * update_370011
     *
     * Add Prefix to TVShows and Movies
     */
    public static function update_370011(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        $sql = "ALTER TABLE `tvshow` ADD `prefix` varchar(32) CHARACTER SET $charset NULL";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `movie` ADD `prefix` varchar(32) CHARACTER SET $charset NULL";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_370012
     *
     * Add metadata information to albums / songs / videos
     */
    public static function update_370012(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        $sql = "ALTER TABLE `album` ADD `release_type` varchar(32) CHARACTER SET $charset NULL";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `song` ADD `composer` varchar(256) CHARACTER SET $charset NULL, ADD `channels` MEDIUMINT NULL";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `video` ADD `channels` MEDIUMINT NULL, ADD `bitrate` MEDIUMINT(8) NULL, ADD `video_bitrate` MEDIUMINT(8) NULL, ADD `display_x` MEDIUMINT(8) NULL, ADD `display_y` MEDIUMINT(8) NULL, ADD `frame_rate` FLOAT NULL, ADD `mode` ENUM('abr', 'vbr', 'cbr') NULL DEFAULT 'cbr'";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('album_release_type', '1', 'Album - Group per release type', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_370013
     *
     * Replace iframe with ajax page load
     */
    public static function update_370013(): bool
    {
        $retval = true;

        $sql = "DELETE FROM `preference` WHERE `name` = 'iframes'";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('ajax_load', '1', 'Ajax page load', 25, 'boolean', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update 370014
     *
     * Modified release_date of table video to signed int(11)
     */
    public static function update_370014(): bool
    {
        return (Dba::write("ALTER TABLE `video` CHANGE COLUMN `release_date` `release_date` INT NULL DEFAULT NULL;") !== false);
    }

    /**
     * update 370015
     *
     * Add session_remember table to store remember tokens
     */
    public static function update_370015(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (Dba::write("CREATE TABLE `session_remember` (`username` varchar(16) NOT NULL, `token` varchar(32) NOT NULL, `expire` int(11) NULL, PRIMARY KEY (`username`, `token`)) ENGINE=$engine;") !== false);
    }

    /**
     * update 370016
     *
     * Add limit of media count for direct play preference
     */
    public static function update_370016(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('direct_play_limit', '0', 'Limit direct play to maximum media count', 25, 'integer', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update 370017
     *
     * Add home display settings
     */
    public static function update_370017(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('home_moment_albums', '1', 'Show Albums of the moment at home page', 25, 'integer', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('home_moment_videos', '1', 'Show Videos of the moment at home page', 25, 'integer', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('home_recently_played', '1', 'Show Recently Played at home page', 25, 'integer', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('home_now_playing', '1', 'Show Now Playing at home page', 25, 'integer', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('custom_logo', '', 'Custom logo url', 25, 'string', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /*
     * update 370018
     *
     * Enhance tag persistent merge reference.
     */
    public static function update_370018(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE IF NOT EXISTS `tag_merge` (`tag_id` int(11) NOT NULL, `merged_to` int(11) NOT NULL, FOREIGN KEY (`tag_id`) REFERENCES `tag` (`tag_id`), FOREIGN KEY (`merged_to`) REFERENCES `tag` (`tag_id`), PRIMARY KEY (`tag_id`, `merged_to`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `tag_merge` (`tag_id`, `merged_to`) SELECT `tag`.`id`, `tag`.`merged_to` FROM `tag` WHERE `merged_to` IS NOT NULL";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `tag` DROP COLUMN `merged_to`";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `tag` ADD COLUMN `is_hidden` TINYINT(1) NOT NULL DEFAULT 0";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update 370019
     *
     * Add album group order setting
     */
    public static function update_370019(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('album_release_type_sort', 'album,ep,live,single', 'Album - Group per release type Sort', 25, 'string', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, 'album,ep,live,single')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update 370020
     *
     * Add webplayer browser notification settings
     */
    public static function update_370020(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('browser_notify', '1', 'WebPlayer browser notifications', 25, 'integer', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('browser_notify_timeout', '10', 'WebPlayer browser notifications timeout (seconds)', 25, 'integer', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '10')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update 370021
     *
     * Add rating to playlists, tvshows and tvshows seasons
     */
    public static function update_370021(): bool
    {
        return (Dba::write("ALTER TABLE `rating` CHANGE `object_type` `object_type` ENUM ('artist', 'album', 'song', 'stream', 'video', 'playlist', 'tvshow', 'tvshow_season') NULL;") !== false);
    }

    /**
     * update 370022
     *
     * Add users geolocation
     */
    public static function update_370022(): bool
    {
        $retval = true;

        $sql    = "ALTER TABLE `session` ADD COLUMN `geo_latitude` DECIMAL(10,6) NULL, ADD COLUMN `geo_longitude` DECIMAL(10,6) NULL, ADD COLUMN `geo_name` VARCHAR(255) NULL";
        $retval &= (Dba::write($sql) !== false);
        $sql    = "ALTER TABLE `object_count` ADD COLUMN `geo_latitude` DECIMAL(10,6) NULL, ADD COLUMN `geo_longitude` DECIMAL(10,6) NULL, ADD COLUMN `geo_name` VARCHAR(255) NULL";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('geolocation', '0', 'Allow geolocation', 25, 'integer', 'options')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update 370023
     *
     * Add Aurora.js webplayer option
     */
    public static function update_370023(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('webplayer_aurora', '1', 'Authorize JavaScript decoder (Aurora.js) in Web Player(s)', 25, 'boolean', 'streaming')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update 370024
     *
     * Add count_type column to object_count table
     */
    public static function update_370024(): bool
    {
        return (Dba::write("ALTER TABLE `object_count` ADD COLUMN `count_type` VARCHAR(16) NOT NULL DEFAULT 'stream';") !== false);
    }

    /**
     * update 370025
     *
     * Add state and city fields to user table
     */
    public static function update_370025(): bool
    {
        return (Dba::write("ALTER TABLE `user` ADD COLUMN `state` VARCHAR(64) NULL, ADD COLUMN `city` VARCHAR(64) NULL;") !== false);
    }

    /**
     * update 370026
     *
     * Add replay gain fields to song_data table
     */
    public static function update_370026(): bool
    {
        return (Dba::write("ALTER TABLE `song_data` ADD COLUMN `replaygain_track_gain` DECIMAL(10,6) NULL, ADD COLUMN `replaygain_track_peak` DECIMAL(10,6) NULL, ADD COLUMN `replaygain_album_gain` DECIMAL(10,6) NULL, ADD COLUMN `replaygain_album_peak` DECIMAL(10,6) NULL;") !== false);
    }

    /**
     * update_370027
     *
     * Move column album_artist from table song to table album
     *
     */
    public static function update_370027(): bool
    {
        $retval = true;

        $sql    = "ALTER TABLE `album` ADD `album_artist` int(11) unsigned DEFAULT NULL AFTER `release_type`";
        $retval &= (Dba::write($sql) !== false);
        $sql    = "UPDATE `album` INNER JOIN `song` ON `album`.`id` = `song`.`album` SET `album`.`album_artist` = `song`.`album_artist`";
        $retval &= (Dba::write($sql) !== false);
        $sql    = "ALTER TABLE `song` DROP COLUMN `album_artist`";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }



    /**
     * update_370028
     *
     * Add width and height in table image
     *
     */
    public static function update_370028(): bool
    {
        $retval = true;

        $sql        = "SELECT `width` FROM `image`";
        $db_results = Dba::read($sql);
        if (!$db_results) {
            $sql    = "ALTER TABLE `image` ADD `width` int(4) unsigned DEFAULT 0 AFTER `image`";
            $retval &= (Dba::write($sql) !== false);
        }
        $sql        = "SELECT `height` FROM `image`";
        $db_results = Dba::read($sql);
        if (!$db_results) {
            $sql    = "ALTER TABLE `image` ADD `height` int(4) unsigned DEFAULT 0 AFTER `width`";
            $retval &= (Dba::write($sql) !== false);
        }

        return $retval;
    }

    /**
     * update_370029
     *
     * Set image column from image table as nullable.
     *
     */
    public static function update_370029(): bool
    {
        return (Dba::write("ALTER TABLE `image` CHANGE COLUMN `image` `image` MEDIUMBLOB NULL DEFAULT NULL;") !== false);
    }

    /**
     * update_370030
     *
     * Add an option to allow users to remove uploaded songs.
     */
    public static function update_370030(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('upload_allow_remove', '1', 'Upload: allow users to remove uploaded songs', 75, 'boolean', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_370031
     *
     * Add an option to customize login art, favicon and text footer.
     */
    public static function update_370031(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('custom_login_logo', '', 'Custom login page logo url', 75, 'string', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('custom_favicon', '', 'Custom favicon url', 75, 'string', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('custom_text_footer', '', 'Custom text footer', 75, 'string', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_370032
     *
     * Add WebDAV backend preference.
     */
    public static function update_370032(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('webdav_backend', '0', 'Use WebDAV backend', 100, 'boolean', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_370033
     *
     * Add Label tables.
     */
    public static function update_370033(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `label` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(80) NOT NULL, `category` varchar(40) NULL, `summary` TEXT CHARACTER SET $charset NULL, `address` varchar(256) NULL, `email` varchar(128) NULL, `website` varchar(256) NULL, `user` int(11) unsigned NULL, `creation_date` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `label_asso` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `label` int(11) unsigned NOT NULL, `artist` int(11) unsigned NOT NULL, `creation_date` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_370034
     *
     * Add User messages and user follow tables.
     */
    public static function update_370034(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `user_pvmsg` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `subject` varchar(80) NOT NULL, `message` TEXT CHARACTER SET $charset NULL, `from_user` int(11) unsigned NOT NULL, `to_user` int(11) unsigned NOT NULL, `is_read` tinyint(1) unsigned NOT NULL DEFAULT '0', `creation_date` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `user_follower` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `follow_user` int(11) unsigned NOT NULL, `follow_date` int(11) unsigned  NULL, PRIMARY KEY (`id`)) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('notify_email', '0', 'Receive notifications by email (shouts, private messages, ...)', 25, 'boolean', 'options')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_370035
     *
     * Add option on user fullname to show/hide it publicly
     */
    public static function update_370035(): bool
    {
        return (Dba::write("ALTER TABLE `user` ADD COLUMN `fullname_public` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';") !== false);
    }

    /**
     * update_370036
     *
     * Add field for track number when generating streaming playlists
     */
    public static function update_370036(): bool
    {
        return (Dba::write("ALTER TABLE `stream_playlist` ADD COLUMN `track_num` SMALLINT(5) DEFAULT '0';") !== false);
    }

    /**
     * update_370037
     *
     * Delete http_port preference (use ampache.cfg.php configuration instead)
     */
    public static function update_370037(): bool
    {
        return (Dba::write("DELETE FROM `preference` WHERE `name` = 'http_port';") !== false);
    }

    /**
     * update_370038
     *
     * Add theme color option
     */
    public static function update_370038(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('theme_color', 'dark', 'Theme color',0, 'special', 'interface')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, 'dark')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_370039
     *
     * Renamed false named sample_rate option name in preference table
     */
    public static function update_370039(): bool
    {
        return (Dba::write("UPDATE `preference` SET `name` = 'transcode_bitrate' WHERE `preference`.`name` = 'sample_rate';") !== false);
    }

    /**
     * update_370040
     *
     * Add user_activity table
     */
    public static function update_370040(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (Dba::write("CREATE TABLE `user_activity` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `user` INT(11) NOT NULL, `action` varchar(20) NOT NULL, `object_id` INT(11) UNSIGNED NOT NULL, `object_type` VARCHAR(32) NOT NULL, `activity_date` INT(11) UNSIGNED NOT NULL) ENGINE=$engine;") !== false);
    }

    /**
     * update_370041
     *
     * Add Metadata tables and preferences
     */
    public static function update_370041(): bool
    {
        $retval    = true;
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `metadata_field` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) NOT NULL, `public` tinyint(1) NOT NULL, UNIQUE KEY `name` (`name`) ) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `metadata` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `object_id` INT(11) UNSIGNED NOT NULL, `field` INT(11) UNSIGNED NOT NULL, `data` text COLLATE $collation NOT NULL, `type` varchar(50) CHARACTER SET $charset DEFAULT NULL, KEY `field` (`field`), KEY `object_id` (`object_id`), KEY `type` (`type`), KEY `objecttype` (`object_id`, `type`), KEY `objectfield` (`object_id`, `field`, `type`) ) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('disabled_custom_metadata_fields', '', 'Disable custom metadata fields (ctrl / shift click to select multiple)', 100, 'string', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('disabled_custom_metadata_fields_input', '', 'Disable custom metadata fields. Insert them in a comma separated list. They will add to the fields selected above.', 100, 'string', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_380001
     *
     * Add podcasts
     */
    public static function update_380001(): bool
    {
        $retval  = true;
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE `podcast` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `feed` varchar(4096) NOT NULL, `catalog` int(11) NOT NULL, `title` varchar(255) CHARACTER SET $charset NOT NULL, `website` varchar(255) NULL, `description` varchar(4096) CHARACTER SET $charset NULL, `language` varchar(5) NULL, `copyright` varchar(64) NULL, `generator` varchar(64) NULL, `lastbuilddate` int(11) UNSIGNED DEFAULT '0' NOT NULL, `lastsync` int(11) UNSIGNED DEFAULT '0' NOT NULL) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE `podcast_episode` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) CHARACTER SET $charset NOT NULL, `guid` varchar(255) NOT NULL, `podcast` int(11) NOT NULL, `state` varchar(32) NOT NULL, `file` varchar(4096) CHARACTER SET $charset NULL, `source` varchar(4096) NULL, `size` bigint(20) UNSIGNED DEFAULT '0' NOT NULL, `time` smallint(5) UNSIGNED DEFAULT '0' NOT NULL, `website` varchar(255) NULL, `description` varchar(4096) CHARACTER SET $charset NULL, `author` varchar(64) NULL, `category` varchar(64) NULL, `played` tinyint(1) UNSIGNED DEFAULT '0' NOT NULL, `pubdate` int(11) UNSIGNED NOT NULL, `addition_time` int(11) UNSIGNED NOT NULL) ENGINE=$engine";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('podcast_keep', '10', 'Podcast: # latest episodes to keep', 100, 'integer', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '10')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('podcast_new_download', '1', 'Podcast: # episodes to download when new episodes are available', 100, 'integer', 'system')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql = "ALTER TABLE `rating` CHANGE `object_type` `object_type` ENUM ('artist', 'album', 'song', 'stream', 'video', 'playlist', 'tvshow', 'tvshow_season', 'podcast', 'podcast_episode') NULL";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_380002
     *
     * Add bookmarks
     */
    public static function update_380002(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        return (Dba::write("CREATE TABLE `bookmark` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `user` int(11) UNSIGNED NOT NULL, `position` int(11) UNSIGNED DEFAULT '0' NOT NULL, `comment` varchar(255) CHARACTER SET $charset NOT NULL, `object_type` varchar(64) NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `creation_date` int(11) UNSIGNED DEFAULT '0' NOT NULL, `update_date` int(11) UNSIGNED DEFAULT '0' NOT NULL) ENGINE=$engine;") !== false);
    }

    /**
     * update_380003
     *
     * Add unique constraint on tag_map table
     */
    public static function update_380003(): bool
    {
        return (Dba::write("ALTER IGNORE TABLE `tag_map` ADD UNIQUE INDEX `UNIQUE_TAG_MAP` (`object_id`, `object_type`, `user`, `tag_id`);") !== false);
    }

    /**
     * update_380004
     *
     * Add preference subcategory
     */
    public static function update_380004(): bool
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        return (Dba::write("ALTER TABLE `preference` ADD `subcatagory` varchar(128) CHARACTER SET $charset DEFAULT NULL AFTER `catagory`;") !== false);
    }

    /**
     * update_380005
     *
     * Add manual update flag on artist
     */
    public static function update_380005(): bool
    {
        return (Dba::write("ALTER TABLE `artist` ADD COLUMN `manual_update` SMALLINT(1) DEFAULT '0';") !== false);
    }

    /**
     * update_380006
     *
     * Add library item context menu option
     */
    public static function update_380006(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('libitem_contextmenu', '1', 'Library item context menu',0, 'boolean', 'interface', 'library')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_380007
     *
     * Add upload rename pattern and ignore duplicate options
     */
    public static function update_380007(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('upload_catalog_pattern', '0', 'Rename uploaded file according to catalog pattern', 100, 'boolean', 'system', 'upload')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('catalog_check_duplicate', '0', 'Check library item at import time and disable duplicates', 100, 'boolean', 'system', 'catalog')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_380008
     *
     * Add browse filter and light sidebar options
     */
    public static function update_380008(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('browse_filter', '0', 'Show filter box on browse', 25, 'boolean', 'interface', 'library')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('sidebar_light', '0', 'Light sidebar by default', 25, 'boolean', 'interface', 'theme')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_380009
     *
     * Add update date to playlist
     */
    public static function update_380009(): bool
    {
        return (Dba::write("ALTER TABLE `playlist` ADD COLUMN `last_update` int(11) unsigned NOT NULL DEFAULT '0';") !== false);
    }

    /**
     * update_380010
     *
     * Add custom blank album/video default image and alphabet browsing options
     */
    public static function update_380010(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('custom_blankalbum', '', 'Custom blank album default image', 75, 'string', 'interface', 'custom')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('custom_blankmovie', '', 'Custom blank video default image', 75, 'string', 'interface', 'custom')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('libitem_browse_alpha', '', 'Alphabet browsing by default for following library items (album,artist,...)', 75, 'string', 'interface', 'library')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_380011
     *
     * Fix username max size to be the same one across all tables.
     */
    public static function update_380011(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE session MODIFY username VARCHAR(255)";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE session_remember MODIFY username VARCHAR(255)";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE user MODIFY username VARCHAR(255)";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE user MODIFY fullname VARCHAR(255)";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_380012
     *
     * Fix change in https://github.com/ampache/ampache/commit/0c26c336269624d75985e46d324e2bc8108576ee
     * That left the user base with an inconsistent database.
     * For more information, please look at update_360035.
     */
    public static function update_380012(): bool
    {
        $retval = true;

        $sql = "UPDATE `preference` SET `description`='Enable url rewriting' WHERE `preference`.`name`='stream_beautiful_url'";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400000
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
    public static function update_400000(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE `podcast` MODIFY `copyright` VARCHAR(255)";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE `user_activity` ADD COLUMN `name_track` VARCHAR(255) NULL DEFAULT NULL, ADD COLUMN `name_artist` VARCHAR(255) NULL DEFAULT NULL, ADD COLUMN `name_album` VARCHAR(255) NULL DEFAULT NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE `user_activity` ADD COLUMN `mbid_track` VARCHAR(255) NULL DEFAULT NULL, ADD COLUMN `mbid_artist` VARCHAR(255) NULL DEFAULT NULL, ADD COLUMN `mbid_album` VARCHAR(255) NULL DEFAULT NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "INSERT IGNORE INTO `search` (`user`, `type`, `rules`, `name`, `logic_operator`, `random`, `limit`) VALUES (-1, 'public', '[[\"artistrating\",\"equal\",\"5\",null]]', 'Artist 5*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"4\",null]]', 'Artist 4*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"3\",null]]', 'Artist 3*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"2\",null]]', 'Artist 2*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"1\",null]]', 'Artist 1*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"5\",null]]', 'Album 5*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"4\",null]]', 'Album 4*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"3\",null]]', 'Album 3*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"2\",null]]', 'Album 2*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"1\",null]]', 'Album 1*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"5\",null]]', 'Song 5*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"4\",null]]', 'Song 4*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"3\",null]]', 'Song 3*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"2\",null]]', 'Song 2*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"1\",null]]', 'Song 1*', 'AND', 0, 0);";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_backend');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'myplex_username');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'myplex_authtoken');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'myplex_published');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_uniqid');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_servername');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_public_address');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_public_port');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_local_auth');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_match_email');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `preference` WHERE `preference`.`name` IN ('plex_backend', 'myplex_username', 'myplex_authtoken', 'myplex_published', 'plex_uniqid', 'plex_servername', 'plex_public_address', 'plex_public_port ', 'plex_local_auth', 'plex_match_email');";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400001
     *
     * Make sure people on older databases have the same preference categories
     */
    public static function update_400001(): bool
    {
        $retval = true;
        $sql    = "UPDATE `preference` SET `preference`.`subcatagory` = 'library' WHERE `preference`.`name` in ('album_sort', 'show_played_times', 'album_group', 'album_release_type', 'album_release_type_sort', 'libitem_contextmenu', 'browse_filter', 'libitem_browse_alpha') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'backend' WHERE `preference`.`name` in ('subsonic_backend', 'daap_backend', 'daap_pass', 'upnp_backend', 'webdav_backend') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'catalog' WHERE `preference`.`name` = 'catalog_check_duplicate' AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'custom' WHERE `preference`.`name` in ('site_title', 'custom_logo', 'custom_login_logo', 'custom_favicon', 'custom_text_footer', 'custom_blankalbum', 'custom_blankmovie') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'feature' WHERE `preference`.`name` in ('download', 'allow_stream_playback', 'allow_democratic_playback', 'share', 'allow_video', 'geolocation') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'home' WHERE `preference`.`name` in ('now_playing_per_user', 'home_moment_albums', 'home_moment_videos', 'home_recently_played', 'home_now_playing') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'httpq' WHERE `preference`.`name` = 'httpq_active' AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'lastfm' WHERE `preference`.`name` in ('lastfm_grant_link', 'lastfm_challenge') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'localplay' WHERE `preference`.`name` in ('localplay_controller', 'localplay_level', 'allow_localplay_playback') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'metadata' WHERE `preference`.`name` in ('disabled_custom_metadata_fields', 'disabled_custom_metadata_fields_input') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'mpd' WHERE `preference`.`name` = 'mpd_active' AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'notification' WHERE `preference`.`name` in ('browser_notify', 'browser_notify_timeout') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'player' WHERE `preference`.`name` in ('show_lyrics', 'song_page_title', 'webplayer_flash', 'webplayer_html5', 'webplayer_confirmclose', 'webplayer_pausetabs', 'slideshow_time', 'broadcast_by_default', 'direct_play_limit', 'webplayer_aurora') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'podcast' WHERE `preference`.`name` in ('podcast_keep', 'podcast_new_download') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'privacy' WHERE `preference`.`name` in ('allow_personal_info_now', 'allow_personal_info_recent', 'allow_personal_info_time', 'allow_personal_info_agent') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'query' WHERE `preference`.`name` in ('popular_threshold', 'offset_limit', 'stats_threshold', 'concerts_limit_future', 'concerts_limit_past') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'share' WHERE `preference`.`name` = 'share_expire' AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'shoutcast' WHERE `preference`.`name` = 'shoutcast_active' AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'theme' WHERE `preference`.`name` in ('theme_name', 'ui_fixed', 'topmenu', 'theme_color', 'sidebar_light') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'transcoding' WHERE `preference`.`name` in ('transcode_bitrate', 'rate_limit', 'transcode') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'update' WHERE `preference`.`name` in ('autoupdate', 'autoupdate_lastcheck', 'autoupdate_lastversion', 'autoupdate_lastversion_new') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`subcatagory` = 'upload' WHERE `preference`.`name` in ('upload_catalog', 'allow_upload', 'upload_subdir', 'upload_user_artist', 'upload_script', 'upload_allow_edit', 'upload_allow_remove', 'upload_catalog_pattern') AND `preference`.`subcatagory` IS NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400002
     *
     * Update disk to allow 1 instead of making it 0 by default
     * Add barcode catalog_number and original_year
     * Drop catalog_number from song_data
     */
    public static function update_400002(): bool
    {
        $retval = true;
        $sql    = "UPDATE `album` SET `album`.`disk` = 1 WHERE `album`.`disk` = 0;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE `album` ADD `original_year` INT(4) NULL, ADD `barcode` VARCHAR(64) NULL, ADD `catalog_number` VARCHAR(64) NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE `song_data` DROP `catalog_number`";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400003
     *
     * Make sure preference names are updated to current strings
     */
    public static function update_400003(): bool
    {
        $retval = true;
        $sql    = "UPDATE `preference` SET `preference`.`description` = 'Force HTTP playback regardless of port' WHERE `preference`.`name` = 'force_http_play' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Playback Type' WHERE `preference`.`name` = 'play_type' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'httpQ Active Instance' WHERE `preference`.`name` = 'httpq_active' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Now Playing filtered per user' WHERE `preference`.`name` = 'now_playing_per_user' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Use Subsonic backend' WHERE `preference`.`name` = 'subsonic_backend' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Share Now Playing information' WHERE `preference`.`name` = 'allow_personal_info_now' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Share Recently Played information' WHERE `preference`.`name` = 'allow_personal_info_recent' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Share Recently Played information - Allow access to streaming date/time' WHERE `preference`.`name` = 'allow_personal_info_time' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Share Recently Played information - Allow access to streaming agent' WHERE `preference`.`name` = 'allow_personal_info_agent' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Enable URL Rewriting' WHERE `preference`.`name` = 'stream_beautiful_url' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Destination catalog' WHERE `preference`.`name` = 'upload_catalog' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Allow user uploads' WHERE `preference`.`name` = 'allow_upload' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Create a subdirectory per user' WHERE `preference`.`name` = 'upload_subdir' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Consider the user sender as the track''s artist' WHERE `preference`.`name` = 'upload_user_artist' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Post-upload script (current directory = upload target directory)' WHERE `preference`.`name` = 'upload_script' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Allow users to edit uploaded songs' WHERE `preference`.`name` = 'upload_allow_edit' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Allow users to remove uploaded songs' WHERE `preference`.`name` = 'upload_allow_remove' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Show Albums of the Moment' WHERE `preference`.`name` = 'home_moment_albums' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Show Videos of the Moment' WHERE `preference`.`name` = 'home_moment_videos' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Custom URL - Logo' WHERE `preference`.`name` = 'custom_logo' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Custom URL - Login page logo' WHERE `preference`.`name` = 'custom_login_logo' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Custom URL - Favicon' WHERE `preference`.`name` = 'custom_favicon' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Album - Default sort' WHERE `preference`.`name` = 'album_sort' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Allow Geolocation' WHERE `preference`.`name` = 'Geolocation' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Allow Video Features' WHERE `preference`.`name` = 'allow_video' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Democratic - Clear votes for expired user sessions' WHERE `preference`.`name` = 'demo_clear_sessions' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Allow Transcoding' WHERE `preference`.`name` = 'transcoding' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Authorize Flash Web Player' WHERE `preference`.`name` = 'webplayer_flash' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Authorize HTML5 Web Player' WHERE `preference`.`name` = 'webplayer_html5' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Web Player browser notifications' WHERE `preference`.`name` = 'browser_notify' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Web Player browser notifications timeout (seconds)' WHERE `preference`.`name` = 'browser_notify_timeout' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Authorize JavaScript decoder (Aurora.js) in Web Player' WHERE `preference`.`name` = 'webplayer_aurora' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Show Now Playing' WHERE `preference`.`name` = 'home_now_playing' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Show Recently Played' WHERE `preference`.`name` = 'home_recently_played' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = '# latest episodes to keep' WHERE `preference`.`name` = 'podcast_keep' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = '# episodes to download when new episodes are available' WHERE `preference`.`name` = 'podcast_new_download' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Allow Transcoding' WHERE `preference`.`name` = 'transcode' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Allow E-mail notifications' WHERE `preference`.`name` = 'notify_email' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Custom metadata - Disable these fields' WHERE `preference`.`name` = 'disabled_custom_metadata_fields' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Custom metadata - Define field list' WHERE `preference`.`name` = 'disabled_custom_metadata_fields_input' ";
        $retval &= (Dba::write($sql) !== false);

        $sql = "UPDATE `preference` SET `preference`.`description` = 'Auto-pause between tabs' WHERE `preference`.`name` = 'webplayer_pausetabs' ";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400004
     *
     * delete upload_user_artist database settings
     */
    public static function update_400004(): bool
    {
        $retval = true;

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'upload_user_artist');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `preference` WHERE `preference`.`name` = 'upload_user_artist';";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400005
     *
     * Add a last_count to searches to speed up access requests
     */
    public static function update_400005(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `search` ADD `last_count` INT(11) NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400006
     *
     * drop shoutcast_active preferences and localplay_shoutcast table
     */
    public static function update_400006(): bool
    {
        $retval = true;

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'shoutcast_active');";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `preference` WHERE `preference`.`name` = 'shoutcast_active';";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DROP TABLE IF EXISTS `localplay_shoutcast`";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400007
     *
     * Add ui option for skip_count display
     * Add ui option for displaying dates in a custom format
     */
    public static function update_400007(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('show_skipped_times', '0', 'Show # skipped', 25, 'boolean', 'interface', 'library')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('custom_datetime', '', 'Custom datetime', 25, 'string', 'interface', 'custom')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_400008
     *
     * Add system option for cron based cache and create related tables
     */
    public static function update_400008(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('cron_cache', '0', 'Cache computed SQL data (eg. media hits stats) using a cron', 25, 'boolean', 'system', 'catalog')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        $tables    = ['cache_object_count', 'cache_object_count_run'];
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        foreach ($tables as $table) {
            $sql = "CREATE TABLE IF NOT EXISTS `" . $table . "` (`object_id` int(11) unsigned NOT NULL, `object_type` enum('album', 'artist', 'song', 'playlist', 'genre', 'catalog', 'live_stream', 'video', 'podcast_episode') CHARACTER SET $charset NOT NULL, `count` int(11) unsigned NOT NULL DEFAULT '0', `threshold` int(11) unsigned NOT NULL DEFAULT '0', `count_type` varchar(16) NOT NULL, PRIMARY KEY (`object_id`, `object_type`, `threshold`, `count_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
            $retval &= (Dba::write($sql) !== false);
        }

        $sql = "UPDATE `preference` SET `level`=75 WHERE `preference`.`name`='stats_threshold'";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400009
     *
     * Add ui option for forcing unique items to playlists
     */
    public static function update_400009(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('unique_playlist', '0', 'Only add unique items to playlists', 25, 'boolean', 'playlist', null)";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_400010
     *
     * Add a last_duration to searches to speed up access requests
     */
    public static function update_400010(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `search` ADD `last_duration` INT(11) NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400011
     *
     * Allow negative track numbers for albums
     * Truncate database tracks to 0 when greater than 32767
     */
    public static function update_400011(): bool
    {
        $retval = true;
        $sql    = "UPDATE `song` SET `track` = 0 WHERE `track` > 32767;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE `song` MODIFY COLUMN `track` SMALLINT DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400012
     *
     * Add a rss token to use an RSS unauthenticated feed.
     */
    public static function update_400012(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `user` ADD `rsstoken` VARCHAR(255) NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400013
     *
     * Extend Democratic cooldown beyond 255.
     */
    public static function update_400013(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `democratic` MODIFY COLUMN `cooldown` int(11) unsigned DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400014
     *
     * Add last_duration to playlist
     * Add time to artist and album
     */
    public static function update_400014(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE `playlist` ADD COLUMN `last_duration` int(11) unsigned NOT NULL DEFAULT '0'";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE `album` ADD COLUMN `time` smallint(5) unsigned NOT NULL DEFAULT '0'";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE `artist` ADD COLUMN `time` smallint(5) unsigned NOT NULL DEFAULT '0'";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    } //

    /**
     * update_400015
     *
     * Extend artist time. smallint was too small
     */
    public static function update_400015(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE `artist` MODIFY COLUMN `time` int(11) unsigned DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400016
     *
     * Extend album and make artist even bigger. This should cover everyone.
     */
    public static function update_400016(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE `album` MODIFY COLUMN `time` bigint(20) unsigned DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE `artist` MODIFY COLUMN `time` int(11) unsigned DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400017
     *
     * Removed.
     */
    public static function update_400017(): bool
    {
        return true;
    }

    /**
     * update_400018
     *
     * Extend video bitrate to unsigned. There's no reason for a negative bitrate.
     */
    public static function update_400018(): bool
    {
        $retval = true;
        $sql    = "UPDATE `video` SET `video_bitrate` = 0 WHERE `video_bitrate` < 0;";
        $retval &= (Dba::write($sql) !== false);

        $sql = "ALTER TABLE `video` MODIFY COLUMN `video_bitrate` int(11) unsigned DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400019
     *
     * Put of_the_moment into a per user preference
     */
    public static function update_400019(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('of_the_moment', '6', 'Set the amount of items Album/Video of the Moment will display', 25, 'integer', 'interface', 'home')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '6')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_400020
     *
     * Customizable login background image
     */
    public static function update_400020(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('custom_login_background', '', 'Custom URL - Login page background', 75, 'string', 'interface', 'custom')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_400021
     *
     * Add r128 gain columns to song_data
     */
    public static function update_400021(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE `song_data` ADD `r128_track_gain` smallint(5) DEFAULT NULL, ADD `r128_album_gain` smallint(5) DEFAULT NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400022
     *
     * Extend allowed time for podcast_episodes
     */
    public static function update_400022(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE `podcast_episode` MODIFY COLUMN `time` int(11) unsigned DEFAULT 0 NOT NULL; ";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400023
     *
     * delete concerts_limit_past and concerts_limit_future database settings
     */
    public static function update_400023(): bool
    {
        $retval = true;

        $sql = "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` IN ('concerts_limit_past', 'concerts_limit_future'));";
        $retval &= (Dba::write($sql) !== false);

        $sql = "DELETE FROM `preference` WHERE `preference`.`name` IN ('concerts_limit_past', 'concerts_limit_future');";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_400024
     *
     * Add song_count, album_count and album_group_count to artist
     */
    public static function update_400024(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `artist` ADD `song_count` smallint(5) unsigned DEFAULT 0 NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `artist` ADD `album_count` smallint(5) unsigned DEFAULT 0 NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `artist` ADD `album_group_count` smallint(5) unsigned DEFAULT 0 NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_500000
     *
     * Delete duplicate files in the song table
     */
    public static function update_500000(): bool
    {
        $retval = true;
        $sql    = "DELETE `dupe` FROM `song` AS `dupe`, `song` AS `orig` WHERE `dupe`.`id` > `orig`.`id` AND `dupe`.`file` <=> `orig`.`file`;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_500001
     *
     * Add `release_status`, `addition_time`, `catalog` to album table
     * Add `mbid`, `country`, `active` to label table
     * Fill the album `catalog` and `time` values using the song table
     * Fill the artist `album_count`, `album_group_count` and `song_count` values
     */
    public static function update_500001(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `album` ADD `release_status` varchar(32) DEFAULT NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `album` ADD `addition_time` int(11) UNSIGNED DEFAULT 0 NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `album` ADD `catalog` int(11) UNSIGNED NOT NULL DEFAULT '0';";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `label` ADD `mbid` varchar(36) DEFAULT NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `label` ADD `country` varchar(64) DEFAULT NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `label` ADD `active` tinyint(1) UNSIGNED NOT NULL DEFAULT '1';";
        $retval &= (Dba::write($sql) !== false);
        $sql = "UPDATE `album`, (SELECT min(`song`.`catalog`) AS `catalog`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`catalog` = `song`.`catalog` WHERE `album`.`catalog` != `song`.`catalog` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);
        $sql = "UPDATE `album`, (SELECT SUM(`song`.`time`) AS `time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`time` = `song`.`time` WHERE `album`.`time` != `song`.`time` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);
        $sql = "UPDATE `album`, (SELECT MIN(`song`.`addition_time`) AS `addition_time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`addition_time` = `song`.`addition_time` WHERE `album`.`addition_time` != `song`.`addition_time` AND `song`.`album` = `album`.`id`;";
        Dba::write($sql);

        return $retval;
    }

    /**
     * update_500002
     *
     * Create `total_count` and `total_skip` to album, artist, song, video and podcast_episode tables
     * Fill counts into the columns
     */
    public static function update_500002(): bool
    {
        $retval = true;
        // tables which usually calculate a count
        $tables = ['album', 'artist', 'song', 'video', 'podcast_episode'];
        foreach ($tables as $type) {
            $sql = "ALTER TABLE `$type` ADD `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0';";
            $retval &= (Dba::write($sql) !== false);
            $sql = "UPDATE `$type`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = '$type' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `$type`.`total_count` = `object_count`.`total_count` WHERE `$type`.`total_count` != `object_count`.`total_count` AND `$type`.`id` = `object_count`.`object_id`;";
            Dba::write($sql);
        }
        // tables that also have a skip count
        $tables = ['song', 'video', 'podcast_episode'];
        foreach ($tables as $type) {
            $sql = "ALTER TABLE `$type` ADD `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0';";
            $retval &= (Dba::write($sql) !== false);
            $sql = "UPDATE `$type`, (SELECT COUNT(`object_count`.`object_id`) AS `total_skip`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = '$type' AND `object_count`.`count_type` = 'skip' GROUP BY `object_count`.`object_id`) AS `object_count` SET `$type`.`total_skip` = `object_count`.`total_skip` WHERE `$type`.`total_skip` != `object_count`.`total_skip` AND `$type`.`id` = `object_count`.`object_id`;";
            Dba::write($sql);
        }

        return $retval;
    }

    /**
     * update_500003
     *
     * add `catalog` to podcast_episode table
     */
    public static function update_500003(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `podcast_episode` ADD `catalog` int(11) UNSIGNED NOT NULL DEFAULT '0';";
        $retval &= (Dba::write($sql) !== false);
        $sql = "UPDATE `podcast_episode`, (SELECT min(`podcast`.`catalog`) AS `catalog`, `podcast`.`id` FROM `podcast` GROUP BY `podcast`.`id`) AS `podcast` SET `podcast_episode`.`catalog` = `podcast`.`catalog` WHERE `podcast_episode`.`catalog` != `podcast`.`catalog` AND `podcast_episode`.`podcast` = `podcast`.`id` AND `podcast`.`catalog` > 0;";
        Dba::write($sql);

        return $retval;
    }

    /**
     * update_500004
     *
     * Create catalog_map table and fill it with data
     */
    public static function update_500004(): bool
    {
        $retval    = true;
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        // create the table
        $sql = "CREATE TABLE IF NOT EXISTS `catalog_map` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `catalog_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `unique_catalog_map` (`object_id`, `object_type`, `catalog_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        $retval &= (Dba::write($sql) !== false);
        // fill the data
        $tables = ['album', 'song', 'video', 'podcast_episode'];
        foreach ($tables as $type) {
            $sql = "REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `$type`.`catalog`, '$type', `$type`.`id` FROM `$type` WHERE `$type`.`catalog` > 0;";
            $retval &= (Dba::write($sql) !== false);
        }
        // artist is a special one as it can be across multiple tables
        $sql = "REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `song`.`catalog`, 'artist', `artist`.`id` FROM `artist` LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` WHERE `song`.`catalog` > 0;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `album`.`catalog`, 'artist', `artist`.`id` FROM `artist` LEFT JOIN `album` ON `album`.`album_artist` = `artist`.`id` WHERE `album`.`catalog` > 0;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_500005
     *
     * Add song_count, artist_count to album
     */
    public static function update_500005(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `album` ADD `song_count` smallint(5) unsigned DEFAULT 0 NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `album` ADD `artist_count` smallint(5) unsigned DEFAULT 0 NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "REPLACE INTO `update_info` SET `key`= 'album_group', `value`= (SELECT COUNT(DISTINCT(`album`.`id`)) AS `count` FROM `album` WHERE `id` in (SELECT MIN(`id`) FROM `album` GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`, `album`.`mbid_group`));";
        Dba::write($sql);
        $sql = "UPDATE `album`, (SELECT COUNT(`song`.`id`) AS `song_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`song_count` = `song`.`song_count` WHERE `album`.`song_count` != `song`.`song_count` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT(`song`.`artist`)) AS `artist_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`artist_count` = `song`.`artist_count` WHERE `album`.`artist_count` != `song`.`artist_count` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);

        return $retval;
    }

    /**
     * update_500006
     *
     * Add user_playlist table
     */
    public static function update_500006(): bool
    {
        $retval    = true;
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        $sql       = "CREATE TABLE IF NOT EXISTS `user_playlist` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) DEFAULT NULL, `object_type` enum('song', 'live_stream', 'video', 'podcast_episode') CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL DEFAULT '0', `track` smallint(6) DEFAULT NULL, `current_track` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `current_time` smallint(5) UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY (`id`),KEY `user` (`user`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE TABLE IF NOT EXISTS `user_data` (`user` int(11) DEFAULT NULL, `key` varchar(128) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `value` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, KEY `user` (`user`), KEY `key` (`key`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_500007
     *
     * Add a 'Browse' category to interface preferences
     * Add ui option ('show_license') for hiding license column in song rows
     */
    public static function update_500007(): bool
    {
        $retval = true;
        $sql    = "UPDATE `preference` SET `preference`.`subcatagory` = 'browse' WHERE `preference`.`name` IN ('show_played_times', 'browse_filter', 'libitem_browse_alpha', 'show_skipped_times')";
        $retval &= (Dba::write($sql) !== false);

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('show_license', '1', 'Show License', 25, 'boolean', 'interface', 'browse')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_500008
     *
     * Add filter_user to catalog table, set unique on user_data
     */
    public static function update_500008(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `catalog` ADD `filter_user` int(11) unsigned DEFAULT 0 NOT NULL;";
        $retval &= (Dba::write($sql) !== false);

        $tables = ['podcast', 'live_stream'];
        foreach ($tables as $type) {
            $sql = "REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `$type`.`catalog`, '$type', `$type`.`id` FROM `$type` WHERE `$type`.`catalog` > 0;";
            $retval &= (Dba::write($sql) !== false);
        }
        $sql = "ALTER TABLE `user_data` ADD UNIQUE `unique_data` (`user`, `key`);";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_500009
     *
     * Add ui option ('use_original_year') Browse by Original Year for albums (falls back to Year)
     */
    public static function update_500009(): bool
    {
        $retval = true;
        $sql    = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('use_original_year', '0', 'Browse by Original Year for albums (falls back to Year)', 25, 'boolean', 'interface', 'browse')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_500010
     *
     * Add ui option ('hide_single_artist') Hide the Song Artist column for Albums with one Artist
     */
    public static function update_500010(): bool
    {
        $retval = true;
        $sql    = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('hide_single_artist', '0', 'Hide the Song Artist column for Albums with one Artist', 25, 'boolean', 'interface', 'browse')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_500011
     *
     * Add `total_count` to podcast table and fill counts into the column
     */
    public static function update_500011(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `podcast` ADD `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0';";
        $retval &= (Dba::write($sql) !== false);
        $sql = "UPDATE `podcast`, (SELECT SUM(`podcast_episode`.`total_count`) AS `total_count`, `podcast` FROM `podcast_episode` GROUP BY `podcast_episode`.`podcast`) AS `object_count` SET `podcast`.`total_count` = `object_count`.`total_count` WHERE `podcast`.`total_count` != `object_count`.`total_count` AND `podcast`.`id` = `object_count`.`podcast`;";
        Dba::write($sql);

        return $retval;
    }

    /**
     * update_500012
     *
     * Move user bandwidth calculations out of the user format function into the user_data table
     */
    public static function update_500012(): bool
    {
        $retval          = true;
        $sql             = "SELECT `id` FROM `user`";
        $db_users        = Dba::read($sql);
        $user_list       = array();
        while ($results  = Dba::fetch_assoc($db_users)) {
            $user_list[] = (int)$results['id'];
        }
        // Calculate their total Bandwidth Usage
        foreach ($user_list as $user_id) {
            $params = array($user_id);
            $total  = 0;
            $sql_s  = "SELECT SUM(`song`.`size`) AS `size` FROM `object_count` LEFT JOIN `song` ON `song`.`id`=`object_count`.`object_id` AND `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = ?;";
            $db_s   = Dba::read($sql_s, $params);
            while ($results  = Dba::fetch_assoc($db_s)) {
                $total = $total + (int)$results['size'];
            }
            $sql_v = "SELECT SUM(`video`.`size`) AS `size` FROM `object_count` LEFT JOIN `video` ON `video`.`id`=`object_count`.`object_id` AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'video' AND `object_count`.`user` = ?;";
            $db_v  = Dba::read($sql_v, $params);
            while ($results  = Dba::fetch_assoc($db_v)) {
                $total = $total + (int)$results['size'];
            }
            $sql_p = "SELECT SUM(`podcast_episode`.`size`) AS `size` FROM `object_count`LEFT JOIN `podcast_episode` ON `podcast_episode`.`id`=`object_count`.`object_id` AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`user` = ?;";
            $db_p  = Dba::read($sql_p, $params);
            while ($results  = Dba::fetch_assoc($db_p)) {
                $total = $total + (int)$results['size'];
            }
            $retval &= (Dba::write("REPLACE INTO `user_data` SET `user`= ?, `key`= ?, `value`= ?;", array($user_id, 'play_size', $total)) !== false);
        }

        return $retval;
    }

    /**
     * update_500013
     *
     * Add tables for tracking deleted files. (deleted_song, deleted_video, deleted_podcast_episode)
     * Add username to the playlist table to stop pulling user all the time
     */
    public static function update_500013(): bool
    {
        $retval    = true;
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        // deleted_song (id, addition_time, delete_time, title, file, catalog, total_count, total_skip, album, artist)
        $sql = "CREATE TABLE IF NOT EXISTS `deleted_song` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED DEFAULT '0', `delete_time` int(11) UNSIGNED DEFAULT '0', `title` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `file` varchar(4096) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0', `update_time` int(11) UNSIGNED DEFAULT '0', `album` int(11) UNSIGNED NOT NULL DEFAULT '0', `artist` int(11) UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        $retval &= (Dba::write($sql) !== false);

        // deleted_video (id, addition_time, delete_time, title, file, catalog, total_count, total_skip)
        $sql = "CREATE TABLE IF NOT EXISTS `deleted_video` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `file` varchar(4096) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        $retval &= (Dba::write($sql) !== false);

        // deleted_podcast_episode (id, addition_time, delete_time, title, file, catalog, total_count, total_skip, podcast)
        $sql = "CREATE TABLE IF NOT EXISTS `deleted_podcast_episode` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `file` varchar(4096) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0', `podcast` int(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        $retval &= (Dba::write($sql) !== false);

        // add username to playlist and searches to stop calling the objects all the time
        $sql = "ALTER TABLE `playlist` ADD `username` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `search` ADD `username` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL;";
        $retval &= (Dba::write($sql) !== false);

        // fill the data
        $sql = "UPDATE `playlist`, (SELECT `id`, `username` FROM `user`) AS `user` SET `playlist`.`username` = `user`.`username` WHERE `playlist`.`user` = `user`.`id`;";
        Dba::write($sql);
        $sql = "UPDATE `search`, (SELECT `id`, `username` FROM `user`) AS `user` SET `search`.`username` = `user`.`username` WHERE `search`.`user` = `user`.`id`;";
        Dba::write($sql);
        $sql = "UPDATE `playlist` SET `playlist`.`username` = ? WHERE `playlist`.`user` = -1;";
        Dba::write($sql, array(T_('System')));
        $sql = "UPDATE `search` SET `search`.`username` = ? WHERE `search`.`user` = -1;";
        Dba::write($sql, array(T_('System')));

        return $retval;
    }

    /**
     * update_500014
     *
     * Add `episodes` to podcast table to track episode count
     */
    public static function update_500014(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `podcast` ADD `episodes` int(11) UNSIGNED NOT NULL DEFAULT '0';";
        $retval &= (Dba::write($sql) !== false);
        $sql = "UPDATE `podcast`, (SELECT COUNT(`podcast_episode`.`id`) AS `episodes`, `podcast` FROM `podcast_episode` GROUP BY `podcast_episode`.`podcast`) AS `episode_count` SET `podcast`.`episodes` = `episode_count`.`episodes` WHERE `podcast`.`episodes` != `episode_count`.`episodes` AND `podcast`.`id` = `episode_count`.`podcast`;";
        Dba::write($sql);

        return $retval;
    }

    /**
     * update_500015
     *
     * Add ui option ('hide_genres') Hide the Genre column in browse table rows
     */
    public static function update_500015(): bool
    {
        $retval = true;
        $sql    = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('hide_genres', '0', 'Hide the Genre column in browse table rows', 25, 'boolean', 'interface', 'browse')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_510000
     *
     * Add podcast to the object_count table
     */
    public static function update_510000(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum('album', 'artist', 'song', 'playlist', 'genre', 'catalog', 'live_stream', 'video', 'podcast', 'podcast_episode');";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_510001
     *
     * Add podcast to the cache_object_count tables
     */
    public static function update_510001(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `cache_object_count_run` MODIFY COLUMN `object_type` enum('album', 'artist', 'song', 'playlist', 'genre', 'catalog', 'live_stream', 'video', 'podcast', 'podcast_episode');";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `cache_object_count` MODIFY COLUMN `object_type` enum('album', 'artist', 'song', 'playlist', 'genre', 'catalog', 'live_stream', 'video', 'podcast', 'podcast_episode');";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_510002
     *
     * Removed.
     */
    public static function update_510002(): bool
    {
        return true;
    }

    /**
     * update_510003
     *
     * Add live_stream to the rating table
     */
    public static function update_510003(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('artist', 'album', 'song', 'stream', 'live_stream', 'video', 'playlist', 'tvshow', 'tvshow_season', 'podcast', 'podcast_episode');";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_510004
     *
     * Add waveform column to podcast_episode table
     */
    public static function update_510004(): bool
    {
        $retval = true;

        $sql = "ALTER TABLE `podcast_episode` ADD COLUMN `waveform` mediumblob DEFAULT NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_510005
     *
     * Add ui option ('subsonic_always_download') Force Subsonic streams to download. (Enable scrobble in your client to record stats)
     */
    public static function update_510005(): bool
    {
        $retval = true;
        $sql    = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('subsonic_always_download', '0', 'Force Subsonic streams to download. (Enable scrobble in your client to record stats)', 25, 'boolean', 'options', 'subsonic')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_520000
     *
     * Add ui options ('api_enable_3', 'api_enable_4', 'api_enable_5') to enable/disable specific API versions
     * Add ui option ('api_force_version') to force a specific API response (even if that version is disabled)
     */
    public static function update_520000(): bool
    {
        $retval = true;
        $sql    = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('api_enable_3', '1', 'Enable API3 responses', 25, 'boolean', 'options')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql    = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('api_enable_4', '1', 'Enable API4 responses', 25, 'boolean', 'options')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql    = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('api_enable_5', '1', 'Enable API5 responses', 25, 'boolean', 'options')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);
        $sql    = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('api_force_version', '0', 'Force a specific API response (even if that version is disabled)', 25, 'special', 'options')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_520001
     *
     * Make sure preference names are always unique
     */
    public static function update_520001(): bool
    {
        $sql             = "SELECT `id` FROM `preference` WHERE `name` IN (SELECT `name` FROM `preference` GROUP BY `name` HAVING count(`name`) >1) AND `id` NOT IN (SELECT MIN(`id`) FROM `preference` GROUP by `name`);";
        $dupe_prefs      = Dba::read($sql);
        $pref_list       = array();
        while ($results  = Dba::fetch_assoc($dupe_prefs)) {
            $pref_list[] = (int)$results['id'];
        }
        // delete duplicates (if they exist)
        foreach ($pref_list as $pref_id) {
            $sql    = "DELETE FROM `preference` WHERE `id` = ?;";
            Dba::write($sql, array($pref_id));
        }
        $sql    = "DELETE FROM `user_preference` WHERE `preference` NOT IN (SELECT `id` FROM `preference`);";
        Dba::write($sql);
        $sql    = "ALTER TABLE `preference` ADD CONSTRAINT preference_UN UNIQUE KEY (`name`);";
        $retval = (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_520002
     *
     * Add ui option ('show_playlist_username') Show playlist owner username in titles
     */
    public static function update_520002(): bool
    {
        $retval = true;
        $sql    = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('show_playlist_username', '1', 'Show playlist owner username in titles', 25, 'boolean', 'interface', 'browse')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_520003
     *
     * Add ui option ('api_hidden_playlists') Hide playlists in Subsonic and API clients that start with this string
     */
    public static function update_520003(): bool
    {
        $retval = true;
        $sql    = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('api_hidden_playlists', '', 'Hide playlists in Subsonic and API clients that start with this string', 25, 'string', 'options')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_520004
     *
     * Set 'plugins' category to lastfm_challenge preference
     */
    public static function update_520004(): bool
    {
        $retval = true;
        $sql    = "UPDATE `preference` SET `preference`.`catagory` = 'plugins' WHERE `preference`.`name` = 'lastfm_challenge'";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_520005
     *
     * Add ui option ('api_hide_dupe_searches') Hide smartlists that match playlist names in Subsonic and API clients
     */
    public static function update_520005(): bool
    {
        $retval = true;
        $sql    = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('api_hide_dupe_searches', '0', 'Hide smartlists that match playlist names in Subsonic and API clients', 25, 'boolean', 'options')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_530000
     *
     * Create artist_map table and fill it with data
     */
    public static function update_530000(): bool
    {
        $retval    = true;
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = 'MyISAM';
        // create the table
        $sql = "CREATE TABLE IF NOT EXISTS `artist_map` (`artist_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_artist_map` (`object_id`, `object_type`, `artist_id`), INDEX `object_id_index` (`object_id`), INDEX `artist_id_index` (`artist_id`), INDEX `artist_id_type_index` (`artist_id`, `object_type`), INDEX `object_id_type_index` (`object_id`, `object_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        $retval &= (Dba::write($sql) !== false);
        // fill the data
        $sql = "INSERT IGNORE INTO `artist_map` (`artist_id`, `object_type`, `object_id`) SELECT DISTINCT `song`.`artist` AS `artist_id`, 'song', `song`.`id` FROM `song` WHERE `song`.`artist` > 0 UNION SELECT DISTINCT `album`.`album_artist` AS `artist_id`, 'album', `album`.`id` FROM `album` WHERE `album`.`album_artist` > 0;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530001
     *
     * Create album_map table and fill it with data
     */
    public static function update_530001(): bool
    {
        $retval    = true;
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = 'MyISAM';
        // create the table
        $sql = "CREATE TABLE IF NOT EXISTS `album_map` (`album_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_album_map` (`object_id`, `object_type`, `album_id`), INDEX `object_id_index` (`object_id`), INDEX `album_id_type_index` (`album_id`, `object_type`), INDEX `object_id_type_index` (`object_id`, `object_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        $retval &= (Dba::write($sql) !== false);
        // fill the data
        $sql = "INSERT IGNORE INTO `album_map` (`album_id`, `object_type`, `object_id`)  SELECT DISTINCT `artist_map`.`object_id` AS `album_id`, 'album' AS `object_type`, `artist_map`.`artist_id` AS `object_id` FROM `artist_map` WHERE `artist_map`.`object_type` = 'album' AND `artist_map`.`object_id` IS NOT NULL UNION SELECT DISTINCT `song`.`album` AS `album_id`, 'song' AS `object_type`, `song`.`artist` AS `object_id` FROM `song` WHERE `song`.`album` IS NOT NULL UNION SELECT DISTINCT `song`.`album` AS `album_id`, 'song' AS `object_type`, `artist_map`.`artist_id` AS `object_id` FROM `artist_map` LEFT JOIN `song` ON `artist_map`.`object_type` = 'song' AND `artist_map`.`object_id` = `song`.`id` WHERE `song`.`album` IS NOT NULL AND `artist_map`.`object_type` = 'song';";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530002
     *
     * Use song_count & artist_count with album_map
     */
    public static function update_530002(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `album` ADD `song_artist_count` smallint(5) unsigned DEFAULT 0 NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "UPDATE `album`, (SELECT COUNT(`song`.`id`) AS `song_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`song_count` = `song`.`song_count` WHERE `album`.`song_count` != `song`.`song_count` AND `album`.`id` = `song`.`album`;";
        Dba::write($sql);
        $sql = "UPDATE `album`, (SELECT COUNT(DISTINCT(`album_map`.`object_id`)) AS `artist_count`, `album_id` FROM `album_map` LEFT JOIN `album` ON `album`.`id` = `album_map`.`album_id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album_map`.`object_type` = 'song' AND `catalog`.`enabled` = '1' GROUP BY `album_id`) AS `album_map` SET `album`.`song_artist_count` = `album_map`.`artist_count` WHERE `album`.`id` = `album_map`.`album_id`;";
        Dba::write($sql);

        return $retval;
    }

    /**
     * update_530003
     *
     * Drop id column from catalog_map
     * Alter `catalog_map` object_type charset and collation
     */
    public static function update_530003(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `catalog_map` DROP COLUMN `id`;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `catalog_map` MODIFY COLUMN object_type varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530004
     *
     * Alter `album_map` charset and engine to MyISAM if engine set
     */
    public static function update_530004(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `album_map` ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `album_map` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530005
     *
     * Alter `artist_map` charset and engine to MyISAM if engine set
     */
    public static function update_530005(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `artist_map` ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `artist_map` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530006
     *
     * Make sure the object_count table has all the correct primary artist/album rows
     */
    public static function update_530006(): bool
    {
        $retval = true;
        $sql    = "INSERT INTO `object_count` (object_type, object_id, `date`, `user`, agent, geo_latitude, geo_longitude, geo_name, count_type) SELECT 'album', `song`.`album`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` LEFT JOIN `song` ON `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_id` = `song`.`id` LEFT JOIN `object_count` AS `album_count` ON `album_count`.`object_type` = 'album' AND `object_count`.`date` = `album_count`.`date` AND `object_count`.`user` = `album_count`.`user` AND `object_count`.`agent` = `album_count`.`agent` AND `object_count`.`count_type` = `album_count`.`count_type` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `album_count`.`id` IS NULL AND `song`.`album` IS NOT NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "INSERT INTO `object_count` (object_type, object_id, `date`, `user`, agent, geo_latitude, geo_longitude, geo_name, count_type) SELECT 'artist', `song`.`artist`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` LEFT JOIN `song` ON `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_id` = `song`.`id` LEFT JOIN `object_count` AS `artist_count` ON `artist_count`.`object_type` = 'artist' AND `object_count`.`date` = `artist_count`.`date` AND `object_count`.`user` = `artist_count`.`user` AND `object_count`.`agent` = `artist_count`.`agent` AND `object_count`.`count_type` = `artist_count`.`count_type` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `artist_count`.`id` IS NULL AND `song`.`artist` IS NOT NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530007
     *
     * Convert basic text columns into utf8/utf8_unicode_ci
     */
    public static function update_530007(): bool
    {
        $retval = true;
        Dba::write("UPDATE `album` SET `mbid` = NULL WHERE CHAR_LENGTH(`mbid`) > 36;");
        Dba::write("UPDATE `album` SET `mbid_group` = NULL WHERE CHAR_LENGTH(`mbid`) > 36;");

        $retval &= (Dba::write("ALTER TABLE `album` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;") !== false);
        $retval &= (Dba::write("ALTER TABLE `album` MODIFY COLUMN `mbid_group` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;") !== false);
        $retval &= (Dba::write("ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;") !== false);
        $retval &= (Dba::write("ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('artist','album','song','stream','video','playlist','tvshow','tvshow_season','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;") !== false);
        $retval &= (Dba::write("ALTER TABLE `user_flag` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;") !== false);
        $retval &= (Dba::write("ALTER TABLE `user_shout` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;") !== false);
        $retval &= (Dba::write("ALTER TABLE `video` MODIFY COLUMN `mode` enum('abr','vbr','cbr') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;") !== false);

        return $retval;
    }

    /**
     * update_530008
     *
     * Remove `user_activity` columns that are useless
     */
    public static function update_530008(): bool
    {
        $retval = true;
        $retval &= (Dba::write("ALTER TABLE `user_activity` DROP COLUMN `name_track`;") !== false);
        $retval &= (Dba::write("ALTER TABLE `user_activity` DROP COLUMN `name_artist`;") !== false);
        $retval &= (Dba::write("ALTER TABLE `user_activity` DROP COLUMN `name_album`;") !== false);
        $retval &= (Dba::write("ALTER TABLE `user_activity` DROP COLUMN `mbid_track`;") !== false);
        $retval &= (Dba::write("ALTER TABLE `user_activity` DROP COLUMN `mbid_artist`;") !== false);
        $retval &= (Dba::write("ALTER TABLE `user_activity` DROP COLUMN `mbid_album`;") !== false);

        return $retval;
    }

    /**
     * update_530009
     *
     * Compact `object_count` columns
     */
    public static function update_530009(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `object_count` MODIFY COLUMN `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `object_count` MODIFY COLUMN `agent` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `object_count` MODIFY COLUMN `geo_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530010
     *
     * Compact mbid columns back to 36 characters
     */
    public static function update_530010(): bool
    {
        $retval = true;
        Dba::write("UPDATE `artist` SET `mbid` = NULL WHERE CHAR_LENGTH(`mbid`) > 36;");
        Dba::write("UPDATE `recommendation_item` SET `mbid` = NULL WHERE CHAR_LENGTH(`mbid`) > 36;");
        $sql    = "ALTER TABLE `artist` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `recommendation_item` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `song_preview` MODIFY COLUMN `artist_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `wanted` MODIFY COLUMN `artist_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530011
     *
     * Compact `user` columns and enum `object_count`.`count_type`
     */
    public static function update_530011(): bool
    {
        $retval     = true;
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
            $sql = "ALTER TABLE `user` ADD `rsstoken` VARCHAR(255) NULL;";
            $retval &= (Dba::write($sql) !== false);
        }
        $sql = "ALTER TABLE `user` MODIFY COLUMN `rsstoken` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `user` MODIFY COLUMN `validation` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `user` MODIFY COLUMN `password` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `user` MODIFY COLUMN `apikey` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `user` MODIFY COLUMN `username` varchar(128) CHARACTER SET $charset COLLATE $collation DEFAULT NULL NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530012
     *
     * Index data on object_count
     */
    public static function update_530012(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `object_count` DROP KEY `object_count_full_index`;";
        Dba::write($sql);
        $sql = "ALTER TABLE `object_count` DROP KEY `object_count_type_IDX`;";
        Dba::write($sql);
        $sql = "ALTER TABLE `object_count` DROP KEY `object_count_date_IDX`;";
        Dba::write($sql);
        $sql = "ALTER TABLE `object_count` DROP KEY `object_count_user_IDX`;";
        Dba::write($sql);
        $sql = "ALTER TABLE `object_count` DROP KEY `object_count_unique`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `object_count_full_index` USING BTREE ON `object_count` (`object_type`, `object_id`, `date`, `user`, `agent`, `count_type`);";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE INDEX `object_count_type_IDX` USING BTREE ON `object_count` (`object_type`, `object_id`);";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE INDEX `object_count_date_IDX` USING BTREE ON `object_count` (`date`, `count_type`);";
        $retval &= (Dba::write($sql) !== false);
        $sql = "CREATE INDEX `object_count_user_IDX` USING BTREE ON `object_count` (`object_type`, `object_id`, `user`, `count_type`);";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530013
     *
     * Compact `cache_object_count`, `cache_object_count_run` columns
     */
    public static function update_530013(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `cache_object_count` MODIFY COLUMN `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;";
        $retval &= (Dba::write($sql) !== false);
        $sql    = "ALTER TABLE `cache_object_count_run` MODIFY COLUMN `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530014
     *
     * Use a smaller unique index on `object_count`
     */
    public static function update_530014(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `object_count` DROP KEY `object_count_UNIQUE_IDX`;";
        Dba::write($sql);
        // delete duplicates and make sure they're gone
        $sql = "DELETE FROM `object_count` WHERE `id` IN (SELECT `id` FROM (SELECT `id` FROM `object_count` WHERE `object_id` IN (SELECT `object_id` FROM `object_count` GROUP BY `object_type`, `object_id`, `date`, `user`, `agent`, `count_type` HAVING COUNT(`object_id`) > 1) AND `id` NOT IN (SELECT MIN(`id`) FROM `object_count` GROUP BY `object_type`, `object_id`, `date`, `user`, `agent`, `count_type`)) AS `count`);";
        Dba::write($sql);
        $sql = "CREATE UNIQUE INDEX `object_count_UNIQUE_IDX` USING BTREE ON `object_count` (`object_type`, `object_id`, `date`, `user`, `agent`, `count_type`);";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_530015
     *
     * Add `show_album_artist` and `show_artist` preferences to show/hide Sidebar Browse menu links. (Fallback to Album Artist if both disabled)
     */
    public static function update_530015(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('show_album_artist', '1', 'Show \'Album Artists\' link in the main sidebar', 25, 'boolean', 'interface', 'theme')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '1')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`, `subcatagory`) VALUES ('show_artist', '0', 'Show \'Artists\' link in the main sidebar', 25, 'boolean', 'interface', 'theme')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /**
     * update_530016
     *
     * Missing type compared to previous version
     */
    public static function update_530016(): bool
    {
        return (Dba::write("ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('artist','album','song','stream','live_stream','video','playlist','tvshow','tvshow_season','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;") !== false);
    }

    /**
     * update_540000
     *
     * Index `title` with `enabled` on `song` table to speed up searching
     */
    public static function update_540000(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `song` DROP KEY `title_enabled_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `title_enabled_IDX` USING BTREE ON `song` (`title`, `enabled`);";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_540001
     *
     * Index album tables. `catalog`, `album_artist`, `original_year`, `release_type`, `release_status`, `mbid`, `mbid_group`
     */
    public static function update_540001(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `album` DROP KEY `catalog_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `catalog_IDX` USING BTREE ON `album` (`catalog`);";
        $retval &= (Dba::write($sql) !== false);
        $sql    = "ALTER TABLE `album` DROP KEY `album_artist_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `album_artist_IDX` USING BTREE ON `album` (`album_artist`);";
        $retval &= (Dba::write($sql) !== false);
        $sql    = "ALTER TABLE `album` DROP KEY `original_year_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `original_year_IDX` USING BTREE ON `album` (`original_year`);";
        $retval &= (Dba::write($sql) !== false);
        $sql    = "ALTER TABLE `album` DROP KEY `release_type_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `release_type_IDX` USING BTREE ON `album` (`release_type`);";
        $retval &= (Dba::write($sql) !== false);
        $sql    = "ALTER TABLE `album` DROP KEY `release_status_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `release_status_IDX` USING BTREE ON `album` (`release_status`);";
        $retval &= (Dba::write($sql) !== false);
        $sql    = "ALTER TABLE `album` DROP KEY `mbid_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `mbid_IDX` USING BTREE ON `album` (`mbid`);";
        $retval &= (Dba::write($sql) !== false);
        $sql    = "ALTER TABLE `album` DROP KEY `mbid_group_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `mbid_group_IDX` USING BTREE ON `album` (`mbid_group`);";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /**
     * update_540002
     *
     * Index `object_type` with `date` in `object_count` table
     */
    public static function update_540002(): bool
    {
        $retval = true;
        $sql    = "ALTER TABLE `object_count` DROP KEY `object_type_date_IDX`;";
        Dba::write($sql);
        $sql = "CREATE INDEX `object_type_date_IDX` USING BTREE ON `object_count` (`object_type`, `date`);";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /** update_550001
     *
     * Add tables `catalog_filter_group` and `catalog_filter_group_map` for catalog filtering by groups
     * Add column `catalog_filter_group` to `user` table to assign a filter group
     * Create a DEFAULT group
     */
    public static function update_550001(): bool
    {
        $retval     = true;
        $collation  = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset    = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine     = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        // Add the new catalog_filter_group table
        $sql = "CREATE TABLE IF NOT EXISTS `catalog_filter_group` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        $retval &= (Dba::write($sql) !== false);

        // Add the default group (autoincrement starts at 1 so force it to be 0)
        $sql = "INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('DEFAULT');";
        $retval &= (Dba::write($sql) !== false);
        $sql = "UPDATE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT';";
        $retval &= (Dba::write($sql) !== false);
        $sql = "ALTER TABLE `catalog_filter_group` AUTO_INCREMENT = 1;";
        $retval &= (Dba::write($sql) !== false);

        // Add the new catalog_filter_group_map table
        $sql = "CREATE TABLE IF NOT EXISTS `catalog_filter_group_map` (`group_id` int(11) UNSIGNED NOT NULL, `catalog_id` int(11) UNSIGNED NOT NULL, `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, UNIQUE KEY (group_id,catalog_id)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        $retval &= (Dba::write($sql) !== false);

        // Add the default access group to the user table
        $sql = "ALTER TABLE `user` ADD `catalog_filter_group` INT(11) UNSIGNED NOT NULL DEFAULT 0;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /** update_550002
     *
     * Migrate catalog `filter_user` settings to catalog_filter groups
     * Assign all public catalogs to the DEFAULT group
     * Drop table `user_catalog`
     * Remove `filter_user` from the `catalog` table
     */
    public static function update_550002(): bool
    {
        $retval = true;

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
                    $retval &= (Dba::write($sql) !== false);
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
            $retval &= (Dba::write($sql) !== false);
        }
        $sql = "DROP TABLE IF EXISTS `user_catalog`;";
        $retval &= (Dba::write($sql) !== false);

        if ($retval) {
            // Drop filter_user but only if the migration has worked
            $sql = "ALTER TABLE `catalog` DROP COLUMN `filter_user`;";
            Dba::write($sql);
        }

        return $retval;
    }

    /** update_550003
     *
     * Add system preference `demo_use_search`, Use smartlists for base playlist in Democratic play
     */
    public static function update_550003(): bool
    {
        $retval = true;

        $sql = "INSERT INTO `preference` (`name`, `value`, `description`, `level`, `type`, `catagory`) VALUES ('demo_use_search', '0', 'Democratic - Use smartlists for base playlist', 25, 'boolean', 'playlist')";
        $retval &= (Dba::write($sql) !== false);
        $row_id = Dba::insert_id();
        $sql    = "INSERT INTO `user_preference` VALUES (-1, ?, '0')";
        $retval &= (Dba::write($sql, array($row_id)) !== false);

        return $retval;
    }

    /** update_550004
     *
     * Make `demo_use_search`a system preference correctly
     */
    public static function update_550004(): bool
    {
        $retval = true;

        // Update previous update preference
        $sql = "UPDATE `preference` SET `catagory`='system' WHERE `name`='demo_use_search'";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }

    /** update_550005
     *
     * Add `song_artist` and `album_artist` maps to catalog_map
     */
    public static function update_550005(): bool
    {
        $retval = true;

        // delete bad maps if they exist
        $tables = ['album', 'song', 'video', 'podcast', 'podcast_episode', 'live_stream'];
        foreach ($tables as $type) {
            $sql = "DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `$type`.`catalog` AS `catalog_id`, '$type' AS `map_type`, `$type`.`id` AS `object_id` FROM `$type` GROUP BY `$type`.`catalog`, `map_type`, `$type`.`id`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` AND `valid_maps`.`map_type` = `catalog_map`.`object_type` WHERE `catalog_map`.`object_type` = '$type' AND `valid_maps`.`object_id` IS NULL;";
            Dba::write($sql);
        }
        // delete catalog_map artists
        $sql = "DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` INNER JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` INNER JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` INNER JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` INNER JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL GROUP BY `album`.`catalog`, `artist_map`.`object_type`, `artist_map`.`artist_id`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` AND `valid_maps`.`map_type` = `catalog_map`.`object_type` WHERE `catalog_map`.`object_type` IN ('artist', 'song_artist', 'album_artist') AND `valid_maps`.`object_id` IS NULL;";
        Dba::write($sql);
        // insert catalog_map artists
        $sql = "INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL GROUP BY `catalog`, `artist_map`.`object_type`, `artist_map`.`artist_id`;";
        $retval &= (Dba::write($sql) !== false);

        return $retval;
    }
} // end update.class
