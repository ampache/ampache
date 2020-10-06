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

use Access;
use AmpConfig;
use Api;
use Session;
use User;

final class PlaylistAddSongMethod
{
    /**
     * playlist_add_song
     * MINIMUM_API_VERSION=380001
     *
     * This adds a song to a playlist
     *
     * @param array $input
     * filter = (string) UID of playlist
     * song   = (string) UID of song to add to playlist
     * check  = (integer) 0,1 Check for duplicates //optional, default = 0
     * @return boolean
     */
    public static function playlist_add_song($input)
    {
        if (!Api::check_parameter($input, array('filter', 'song'), 'playlist_add_song')) {
            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        ob_end_clean();
        $playlist = new \Playlist($input['filter']);
        $song     = $input['song'];
        if (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id)) {
            Api::message('error', T_('Access denied to this playlist'), '412', $input['api_format']);

            return false;
        }
        if ((AmpConfig::get('unique_playlist') || (int) $input['check'] == 1) && in_array($song, $playlist->get_songs())) {
            Api::message('error', T_("Can't add a duplicate item when check is enabled"), '400', $input['api_format']);

            return false;
        }
        $playlist->add_songs(array($song), true);
        Api::message('success', 'song added to playlist', null, $input['api_format']);
        Session::extend($input['auth']);

        return true;
    }
}
