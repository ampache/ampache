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
use Api;
use Playlist;
use Session;
use User;

/**
 * Class PlaylistRemoveSongMethod
 * @package Lib\ApiMethods
 */
final class PlaylistRemoveSongMethod
{
    private const ACTION = 'playlist_remove_song';

    /**
     * playlist_remove_song
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400001
     * CHANGED_IN_API_VERSION=420000
     *
     * This removes a song from a playlist using track number in the list or song ID.
     * Pre-400001 the api required 'track' instead of 'song'.
     * 420000+: added clear to allow you to clear a playlist without getting all the tracks.
     *
     * @param array $input
     * filter = (string) UID of playlist
     * song   = (string) UID of song to remove from the playlist //optional
     * track  = (string) track number to remove from the playlist //optional
     * clear  = (integer) 0,1 Clear the whole playlist //optional, default = 0
     * @return boolean
     */
    public static function playlist_remove_song(array $input)
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        ob_end_clean();
        $playlist = new Playlist($input['filter']);
        if (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id)) {
            Api::error(T_('Require: 100'), '4742', self::ACTION, 'account', $input['api_format']);
        } else {
            if ((int) $input['clear'] === 1) {
                $playlist->delete_all();
                Api::message('all songs removed from playlist', $input['api_format']);
            } elseif ($input['song']) {
                $track = (int) scrub_in($input['song']);
                if (!$playlist->has_item($track)) {
                    Api::error(T_('Not Found'), '4704', self::ACTION, 'song', $input['api_format']);

                    return false;
                }
                $playlist->delete_song($track);
                $playlist->regenerate_track_numbers();
                Api::message('song removed from playlist', $input['api_format']);
            } elseif ($input['track']) {
                $track = (int) scrub_in($input['track']);
                if (!$playlist->has_item(null, $track)) {
                    Api::error(T_('Not Found'), '4704', self::ACTION, 'track', $input['api_format']);

                    return false;
                }
                $playlist->delete_track_number($track);
                $playlist->regenerate_track_numbers();
                Api::message('song removed from playlist', $input['api_format']);
            }
        }
        Session::extend($input['auth']);

        return true;
    }
}
