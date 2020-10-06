<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

namespace Lib\ApiMethods;

use AmpConfig;
use Api;

final class ScrobbleMethod
{
    /**
     * scrobble
     * MINIMUM_API_VERSION=400001
     *
     * Search for a song using text info and then record a play if found.
     * This allows other sources to record play history to Ampache
     *
     * @param array $input
     * song       = (string)  $song_name
     * artist     = (string)  $artist_name
     * album      = (string)  $album_name
     * songmbid   = (string)  $song_mbid //optional
     * artistmbid = (string)  $artist_mbid //optional
     * albummbid  = (string)  $album_mbid //optional
     * date       = (integer) UNIXTIME() //optional
     * client     = (string)  $agent //optional
     * @return boolean
     */
    public static function scrobble($input)
    {
        if (!Api::check_parameter($input, array('song', 'artist', 'album'), 'scrobble')) {
            return false;
        }
        ob_end_clean();
        $charset     = AmpConfig::get('site_charset');
        $song_name   = (string) html_entity_decode(scrub_out($input['song']), ENT_QUOTES, $charset);
        $artist_name = (string) html_entity_decode(scrub_in((string) $input['artist']), ENT_QUOTES, $charset);
        $album_name  = (string) html_entity_decode(scrub_in((string) $input['album']), ENT_QUOTES, $charset);
        $song_mbid   = (string) scrub_in($input['song_mbid']); //optional
        $artist_mbid = (string) scrub_in($input['artist_mbid']); //optional
        $album_mbid  = (string) scrub_in($input['album_mbid']); //optional
        $date        = (is_numeric(scrub_in($input['date']))) ? (int) scrub_in($input['date']) : time(); //optional
        $user        = \User::get_from_username(\Session::username($input['auth']));
        $user_id     = $user->id;
        $valid       = in_array($user->id, \User::get_valid_users());

        // validate supplied user
        if ($valid === false) {
            Api::message('error', T_('User_id not found'), '404', $input['api_format']);

            return false;
        }

        // validate minimum required options
        debug_event('api.class', 'scrobble searching for:' . $song_name . ' - ' . $artist_name . ' - ' . $album_name, 4);
        if (!$song_name || !$album_name || !$artist_name) {
            Api::message('error', T_('Invalid input options'), '400', $input['api_format']);

            return false;
        }

        // validate client string or fall back to 'api'
        $agent = ($input['client'])
            ? $input['client']
            : 'api';
        $scrobble_id = \Song::can_scrobble($song_name, $artist_name, $album_name, (string) $song_mbid, (string) $artist_mbid, (string) $album_mbid);

        if ($scrobble_id === '') {
            Api::message('error', T_('Failed to scrobble: No item found!'), '404', $input['api_format']);
        } else {
            $item = new \Song((int) $scrobble_id);
            if (!$item->id) {
                Api::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            debug_event('api.class', 'scrobble: ' . $item->id . ' for ' . $user->username . ' using ' . $agent . ' ' . (string) time(), 5);

            // internal scrobbling (user_activity and object_count tables)
            $item->set_played($user_id, $agent, array(), $date);

            // scrobble plugins
            \User::save_mediaplay($user, $item);

            Api::message('success', 'successfully scrobbled: ' . $scrobble_id, null, $input['api_format']);
        }
        \Session::extend($input['auth']);

        return true;
    }
}
