<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option); any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

define('NO_SESSION','1');
define('CLI', 1);

$path   = dirname(__FILE__);
$prefix = realpath($path . '/../');
require_once $prefix . '/lib/init.php';

ob_end_flush();

debug_event('cron', 'started cron process', 3);

/**
 * Catalog garbage_collection covers these functions
 *
 * Song::garbage_collection();
 * Album::garbage_collection();
 * Artist::garbage_collection();
 * Video::garbage_collection();
 * Movie::garbage_collection();
 * Art::garbage_collection();
 * Stats::garbage_collection();
 * Rating::garbage_collection();
 * Userflag::garbage_collection();
 * Useractivity::garbage_collection();
 * Playlist::garbage_collection();
 * Tmp_Playlist::garbage_collection(); FIXME Duplicated with Session
 * Shoutbox::garbage_collection();
 * Tag::garbage_collection();
 * Metadata::garbage_collection();
 * MetadataField::garbage_collection();
 */
Catalog::garbage_collection();

/**
 * Session garbage_collection covers these functions.
 *
 * Query::garbage_collection();
 * Stream_Playlist::garbage_collection();
 * Song_Preview::garbage_collection();
 * Tmp_Playlist::garbage_collection(); FIXME Duplicated with Catalog
 */
Session::garbage_collection();

/**
 * Clean up remaining functions.
 *
 */
Share::garbage_collection();
Stream::garbage_collection();
Podcast_Episode::garbage_collection();
Bookmark::garbage_collection();
Recommendation::garbage_collection();

/**
 * Run compute_cache
 */
require_once $prefix . '/bin/compute_cache.inc';

if (AmpConfig::get('memory_cache')) {
    debug_event('cron', 'Filling memory cache', 4);
    $catalogs = Catalog::get_catalogs();
    $users    = User::get_valid_users();

    foreach ($catalogs as $catalog_id) {
        debug_event('cron', 'Catalog memory cache for ' . (string) $catalog_id, 4);
        $catalog = Catalog::create_from_id($catalog_id);
        // cache album details
        $albums = $catalog->get_album_ids();
        Album::build_cache($albums);

        // cache artist details
        $artists = $catalog->get_artist_ids();
        Artist::build_cache($artists, true, '');

        //Playlist::build_cache($ids)
        //Art::build_cache($object_ids)
        //Song_preview::build_cache($song_ids)
        //Tag::build_cache($ids)
        //UserActivity::build_cache($ids)
        //Video::build_cache($ids)
        //Song::build_cache($song_ids, $limit_threshold = '')

        // artist, album, song, playlist, podcast, podcast_episode
        foreach ($users as $user_id) {
            // artists
            Rating::build_cache('artist', $artists, $user_id);
            Userflag::build_cache('artist', $artists, $user_id);
            // albums
            Rating::build_cache('album', $albums, $user_id);
            Userflag::build_cache('album', $albums, $user_id);
        } // end foreach $user_id
    } // end foreach $catalogs
    debug_event('cron', 'Completed filling memory cache', 5);
}
debug_event('cron', 'finished cron process', 4);
