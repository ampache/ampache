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
use JSON_Data;
use Playlist;
use Session;
use User;
use XML_Data;

/**
 * Class PlaylistsMethod
 * @package Lib\ApiMethods
 */
final class PlaylistsMethod
{
    const ACTION = 'playlists';

    /**
     * playlists
     * MINIMUM_API_VERSION=380001
     *
     * This returns playlists based on the specified filter
     *
     * @param array $input
     * filter = (string) Alpha-numeric search term (match all if missing) //optional
     * exact  = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add    = self::set_filter(date) //optional
     * update = self::set_filter(date) //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function playlists(array $input)
    {
        $user   = User::get_from_username(Session::username($input['auth']));
        $method = ($input['exact']) ? false : true;
        $userid = (!Access::check('interface', 100, $user->id)) ? $user->id : -1;
        $public = !Access::check('interface', 100, $user->id);

        // regular playlists
        $playlist_ids = Playlist::get_playlists($public, $userid, (string) $input['filter'], $method);
        // merge with the smartlists
        $playlist_ids = array_merge($playlist_ids, Playlist::get_smartlists($public, $userid, (string) $input['filter'], $method));
        if (empty($playlist_ids)) {
            Api::empty('playlist', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::playlists($playlist_ids);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::playlists($playlist_ids);
        }
        Session::extend($input['auth']);

        return true;
    }
}
