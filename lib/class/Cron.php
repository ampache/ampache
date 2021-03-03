<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Lib;

use Album;
use AmpConfig;
use Art;
use Artist;
use Catalog;
use Dba;
use Playlist;
use Podcast;
use Rating;
use Song;
use Tag;
use User;
use Userflag;
use Video;

/**
 * Class Cron
 * @package Lib
 */
final class Cron
{

    /**
     * set_cron_date
     * Record when the cron has finished.
     */
    public static function set_cron_date(): void
    {
        Dba::write(
            sprintf(
                'REPLACE INTO `update_info` SET `key`= \'%s\', `value`=UNIX_TIMESTAMP()',
                Dba::escape('cron_date')
            )
        );
    }

    /**
     * get_cron_date
     * This returns the date cron has finished.
     */
    public static function get_cron_date(): int
    {
        $name = Dba::escape('cron_date');

        $db_results = Dba::read(
            'SELECT * FROM `update_info` WHERE `key` = ?',
            [$name]
        );

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int) $results['value'];
        }

        return 0;
    }

    /**
     * @deprecated Currently not in use
     *
     * run_cron_cache
     * Run live memory cache processes.
     * @param integer $user_id
     */
    public static function run_cron_cache(int $user_id = 0): void
    {
        if (AmpConfig::get('memory_cache')) {
            debug_event('cron', 'Filling memory cache', 4);
            $catalogs = Catalog::get_catalogs();
            // run for a single user if they've logged on to speed things up
            if ($user_id > 0) {
                $users = array($user_id);
            } else {
                $users = User::get_valid_users();
            }

            foreach ($catalogs as $catalog_id) {
                debug_event('cron', 'Catalog memory cache for catalog ' . (string) $catalog_id, 4);
                $catalog = Catalog::create_from_id($catalog_id);
                // cache album details
                $albums = $catalog->get_album_ids();
                Album::build_cache($albums);

                // cache artist details
                $artists = $catalog->get_artist_ids();
                Artist::build_cache($artists, true);

                // cache song details
                $songs = $catalog->get_song_ids();
                Song::build_cache($songs);

                // cache playlist details
                $playlists = Playlist::get_playlists(-1);
                Playlist::build_cache($playlists);

                // cache art details
                Art::build_cache($artists, 'artist');
                Art::build_cache($albums, 'album');

                $videos = array();
                if (AmpConfig::get('allow_video')) {
                    $videos = $catalog->get_video_ids();
                    Video::build_cache($videos);
                }

                /**
                 * Fill rating and hear/flag details for each user
                 */
                foreach ($users as $user_id) {
                    debug_event('cron', 'Filling memory cache for user: ' . $user_id, 5);
                    // artists
                    Rating::build_cache('artist', $artists, $user_id);
                    Userflag::build_cache('artist', $artists, $user_id);
                    // albums
                    Rating::build_cache('album', $albums, $user_id);
                    Userflag::build_cache('album', $albums, $user_id);
                    // songs
                    Rating::build_cache('song', $songs, $user_id);
                    Userflag::build_cache('song', $songs, $user_id);
                    // videos
                    if (AmpConfig::get('allow_video')) {
                        Rating::build_cache('video', $videos, $user_id);
                        Userflag::build_cache('video', $videos, $user_id);
                    }
                    // playlists
                    $user_playlist = Playlist::get_playlists($user_id);
                    Rating::build_cache('playlist', $user_playlist, $user_id);
                    Userflag::build_cache('playlist', $user_playlist, $user_id);
                    // podcasts
                    if (AmpConfig::get('podcast')) {
                        $podcasts = $catalog->get_podcast_ids();
                        foreach ($podcasts as $podcast_id) {
                            Rating::build_cache('podcast', $podcasts, $user_id);
                            Userflag::build_cache('podcast', $podcasts, $user_id);
                            // podcast_episodes
                            $podcast          = new Podcast($podcast_id);
                            $podcast_episodes = $podcast->get_episodes();
                            Rating::build_cache('podcast_episode', $podcast_episodes, $user_id);
                            Userflag::build_cache('podcast_episode', $podcast_episodes, $user_id);
                        } // end foreach $podcasts
                    }
                } // end foreach $user_id
            } // end foreach $catalogs

            // artist tags
            $artist_tags = Tag::get_tag_ids('artist');
            Tag::build_cache($artist_tags);
            Tag::build_map_cache('artist', $artist_tags);
            // album tags
            $album_tags  = Tag::get_tag_ids('album');
            Tag::build_cache($album_tags);
            Tag::build_map_cache('album', $album_tags);
            // song tags
            $song_tags   = Tag::get_tag_ids('song');
            Tag::build_cache($song_tags);
            Tag::build_map_cache('song', $song_tags);

            debug_event('cron', 'Completed filling memory cache', 5);
        }
    }
}
