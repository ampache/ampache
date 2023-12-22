<?php

declare(strict_types=0);

/**
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

namespace Ampache\Module\Database;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\Dba;

final class DatabaseCharsetUpdater implements DatabaseCharsetUpdaterInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(ConfigContainerInterface $configContainer)
    {
        $this->configContainer = $configContainer;
    }

    public function update(): void
    {
        $database           = $this->configContainer->get('database_name');
        $translated_charset = Dba::translate_to_mysqlcharset($this->configContainer->get('site_charset'));
        $target_charset     = $translated_charset['charset'];
        $engine_sql         = ($translated_charset['charset'] == 'utf8mb4') ? 'ENGINE=InnoDB' : 'ENGINE=MYISAM';
        $target_collation   = $translated_charset['collation'];

        // Alter the charset for the entire database
        $sql = "ALTER DATABASE `$database` DEFAULT CHARACTER SET $target_charset COLLATE $target_collation";
        Dba::write($sql);

        $sql        = "SHOW TABLES";
        $db_results = Dba::read($sql);

        // Go through the tables!
        while ($row = Dba::fetch_row($db_results)) {
            $sql              = "DESCRIBE `" . $row['0'] . "`";
            $describe_results = Dba::read($sql);

            // Change the table engine
            $sql = "ALTER TABLE `" . $row['0'] . "` $engine_sql";
            Dba::write($sql);
            // Change the tables default charset and collation
            $sql = "ALTER TABLE `" . $row['0'] . "` CONVERT TO CHARACTER SET $target_charset COLLATE $target_collation";
            Dba::write($sql);

            // Iterate through the columns of the table
            while ($table = Dba::fetch_assoc($describe_results)) {
                if (
                    (strpos($table['Type'], 'varchar') !== false) ||
                    (strpos($table['Type'], 'enum') !== false) ||
                    (strpos($table['Table'], 'text') !== false)
                ) {
                    $sql             = "ALTER TABLE `" . $row['0'] . "` MODIFY `" . $table['Field'] . "` " . $table['Type'] . " CHARACTER SET " . $target_charset . " COLLATE $target_collation";
                    $charset_results = Dba::write($sql);
                    if (!$charset_results) {
                        debug_event(__CLASS__, 'Unable to update the charset of ' . $table['Field'] . '.' . $table['Type'] . ' to ' . $target_charset . " COLLATE $target_collation", 3);
                    } // if it fails
                }
            }
        }
        // Convert all the table columns which (probably) didn't convert
        Dba::write("ALTER TABLE `access_list` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `access_list` MODIFY COLUMN `type` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `album` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `album` MODIFY COLUMN `prefix` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `album` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `album` MODIFY COLUMN `mbid_group` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `album` MODIFY COLUMN `release_type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `album` MODIFY COLUMN `barcode` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `album` MODIFY COLUMN `catalog_number` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `album_map` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `artist` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `artist` MODIFY COLUMN `prefix` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `artist` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `artist` MODIFY COLUMN `summary` text CHARACTER SET $target_charset COLLATE $target_collation;");
        Dba::write("ALTER TABLE `artist` MODIFY COLUMN `placeformed` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `artist_map` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `bookmark` MODIFY COLUMN `comment` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `bookmark` MODIFY COLUMN `object_type` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `broadcast` MODIFY COLUMN `name` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `broadcast` MODIFY COLUMN `description` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `broadcast` MODIFY COLUMN `key` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `cache_object_count` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','genre','live_stream','playlist','podcast','podcast_episode','song','stream','tvshow','tvshow_season','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        Dba::write("ALTER TABLE `cache_object_count` MODIFY COLUMN `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        Dba::write("ALTER TABLE `cache_object_count_run` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','genre','live_stream','playlist','podcast','podcast_episode','song','stream','tvshow','tvshow_season','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        Dba::write("ALTER TABLE `cache_object_count_run` MODIFY COLUMN `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        Dba::write("ALTER TABLE `catalog` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `catalog` MODIFY COLUMN `catalog_type` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `catalog` MODIFY COLUMN `rename_pattern` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `catalog` MODIFY COLUMN `sort_pattern` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `catalog` MODIFY COLUMN `gather_types` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `catalog_local` MODIFY COLUMN `path` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `catalog_remote` MODIFY COLUMN `uri` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `catalog_remote` MODIFY COLUMN `username` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `catalog_remote` MODIFY COLUMN `password` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `democratic` MODIFY COLUMN `name` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `image` MODIFY COLUMN `mime` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `image` MODIFY COLUMN `size` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `image` MODIFY COLUMN `object_type` enum('album', 'album_disk', 'artist', 'catalog', 'genre', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'stream', 'tvshow', 'tvshow_season', 'user', 'video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        Dba::write("ALTER TABLE `image` MODIFY COLUMN `kind` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `ip_history` MODIFY COLUMN `agent` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `label` MODIFY COLUMN `name` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `label` MODIFY COLUMN `category` varchar(40) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `label` MODIFY COLUMN `summary` text CHARACTER SET $target_charset COLLATE $target_collation;");
        Dba::write("ALTER TABLE `label` MODIFY COLUMN `address` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `label` MODIFY COLUMN `email` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `label` MODIFY COLUMN `website` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `license` MODIFY COLUMN `name` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `license` MODIFY COLUMN `description` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `license` MODIFY COLUMN `external_link` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `live_stream` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `live_stream` MODIFY COLUMN `site_url` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `live_stream` MODIFY COLUMN `url` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `live_stream` MODIFY COLUMN `codec` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `localplay_httpq` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `localplay_httpq` MODIFY COLUMN `host` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `localplay_httpq` MODIFY COLUMN `password` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `localplay_mpd` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `localplay_mpd` MODIFY COLUMN `host` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `localplay_mpd` MODIFY COLUMN `password` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `metadata` MODIFY COLUMN `type` varchar(50) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `metadata_field` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `movie` MODIFY COLUMN `original_name` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `movie` MODIFY COLUMN `summary` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `movie` MODIFY COLUMN `prefix` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `now_playing` MODIFY COLUMN `id` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        Dba::write("ALTER TABLE `now_playing` MODIFY COLUMN `object_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','genre','live_stream','playlist','podcast','podcast_episode','song','stream','tvshow','tvshow_season','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `object_count` MODIFY COLUMN `agent` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `object_count` MODIFY COLUMN `geo_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `object_count` MODIFY COLUMN `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `personal_video` MODIFY COLUMN `location` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `personal_video` MODIFY COLUMN `summary` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `player_control` MODIFY COLUMN `cmd` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `player_control` MODIFY COLUMN `value` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `player_control` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `playlist` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `playlist` MODIFY COLUMN `type` enum('private','public') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `playlist_data` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast` MODIFY COLUMN `feed` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast` MODIFY COLUMN `website` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast` MODIFY COLUMN `description` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast` MODIFY COLUMN `language` varchar(5) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast` MODIFY COLUMN `copyright` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast` MODIFY COLUMN `generator` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `guid` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `file` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `source` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `website` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `description` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `author` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `podcast_episode` MODIFY COLUMN `category` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `preference` MODIFY COLUMN `name` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `preference` MODIFY COLUMN `value` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `preference` MODIFY COLUMN `description` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `preference` MODIFY COLUMN `type` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `preference` MODIFY COLUMN `catagory` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `preference` MODIFY COLUMN `subcatagory` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('album', 'album_disk', 'artist', 'catalog', 'genre', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'stream', 'tvshow', 'tvshow_season', 'user', 'video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        Dba::write("ALTER TABLE `recommendation` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `recommendation_item` MODIFY COLUMN `name` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `recommendation_item` MODIFY COLUMN `rel` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `recommendation_item` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `search` MODIFY COLUMN `type` enum('private','public') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `search` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `search` MODIFY COLUMN `logic_operator` varchar(3) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `session` MODIFY COLUMN `id` varchar(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        Dba::write("ALTER TABLE `session` MODIFY COLUMN `username` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `session` MODIFY COLUMN `type` varchar(16) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `session` MODIFY COLUMN `agent` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `session` MODIFY COLUMN `geo_name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `session_remember` MODIFY COLUMN `username` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        Dba::write("ALTER TABLE `session_remember` MODIFY COLUMN `token` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        Dba::write("ALTER TABLE `session_stream` MODIFY COLUMN `id` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        Dba::write("ALTER TABLE `session_stream` MODIFY COLUMN `agent` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `share` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `share` MODIFY COLUMN `secret` varchar(20) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `share` MODIFY COLUMN `public_url` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `share` MODIFY COLUMN `description` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `song` MODIFY COLUMN `file` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `song` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `song` MODIFY COLUMN `mode` enum('abr','vbr','cbr') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `song` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `song` MODIFY COLUMN `composer` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `song_data` MODIFY COLUMN `label` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `song_data` MODIFY COLUMN `language` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `song_preview` MODIFY COLUMN `session` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `song_preview` MODIFY COLUMN `artist_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `song_preview` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `song_preview` MODIFY COLUMN `album_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `song_preview` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `song_preview` MODIFY COLUMN `file` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `sid` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `author` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `album` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `type` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `stream_playlist` MODIFY COLUMN `codec` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `tag` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `tag_map` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `tmp_browse` MODIFY COLUMN `sid` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation NOT NULL;");
        Dba::write("ALTER TABLE `tmp_playlist` MODIFY COLUMN `session` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `tmp_playlist` MODIFY COLUMN `type` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `tmp_playlist` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `tmp_playlist_data` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `tvshow` MODIFY COLUMN `name` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `tvshow` MODIFY COLUMN `summary` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `tvshow` MODIFY COLUMN `prefix` varchar(32) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `tvshow_episode` MODIFY COLUMN `original_name` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `tvshow_episode` MODIFY COLUMN `summary` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `update_info` MODIFY COLUMN `key` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `update_info` MODIFY COLUMN `value` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user` MODIFY COLUMN `username` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user` MODIFY COLUMN `fullname` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user` MODIFY COLUMN `email` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user` MODIFY COLUMN `website` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user` MODIFY COLUMN `apikey` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user` MODIFY COLUMN `password` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user` MODIFY COLUMN `validation` varchar(128) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user` MODIFY COLUMN `state` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user` MODIFY COLUMN `city` varchar(64) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user` MODIFY COLUMN `rsstoken` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user_activity` MODIFY COLUMN `action` varchar(20) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user_activity` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `user_flag` MODIFY COLUMN `object_type` enum('album', 'album_disk', 'artist', 'catalog', 'genre', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'stream', 'tvshow', 'tvshow_season', 'user', 'video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
        Dba::write("ALTER TABLE `user_preference` MODIFY COLUMN `value` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user_pvmsg` MODIFY COLUMN `subject` varchar(80) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user_pvmsg` MODIFY COLUMN `message` text CHARACTER SET $target_charset COLLATE $target_collation;");
        Dba::write("ALTER TABLE `user_shout` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `user_shout` MODIFY COLUMN `data` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `user_vote` MODIFY COLUMN `sid` varchar(256) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `video` MODIFY COLUMN `file` varchar(4096) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `video` MODIFY COLUMN `title` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `video` MODIFY COLUMN `video_codec` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `video` MODIFY COLUMN `audio_codec` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `video` MODIFY COLUMN `mime` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
        Dba::write("ALTER TABLE `video` MODIFY COLUMN `mode` enum('abr','vbr','cbr') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `wanted` MODIFY COLUMN `artist_mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `wanted` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        Dba::write("ALTER TABLE `wanted` MODIFY COLUMN `name` varchar(255) CHARACTER SET $target_charset COLLATE $target_collation DEFAULT NULL;");
    }
}
