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

debug_event('cron', 'started cron process', 4);

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

if (AmpConfig::get('memory_cache')) {
    //album::build_cache($ids)
    //playlist::build_cache($ids)
    //art::build_cache($object_ids)
    //song_preview::build_cache($song_ids)
    //tag::build_cache($ids)
    //useractivity::build_cache($ids)
    //video::build_cache($ids = array())
    //song::build_cache($song_ids, $limit_threshold = '')
    //artist::build_cache($ids, $extra = false, $limit_threshold = '')

    // artist, album, song, playlist, podcast, podcast_episode
    foreach (User::get_valid_users() as $user_id) {
        //rating::build_cache($type, $ids, $user_id = null)
        //userflag::build_cache($type, $ids, $user_id = null)
    } //end foreach $user_id
}
    debug_event('cron', 'finished cron process', 4);
