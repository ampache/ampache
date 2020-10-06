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

use Api;
use JSON_Data;
use Session;
use User;
use XML_Data;

final class PlaylistCreateMethod
{
    /**
     * playlist_create
     * MINIMUM_API_VERSION=380001
     *
     * This create a new playlist and return it
     *
     * @param array $input
     * name = (string) Alpha-numeric search term
     * type = (string) 'public', 'private'
     * @return boolean
     */
    public static function playlist_create($input)
    {
        if (!Api::check_parameter($input, array('name'), 'playlist_create')) {
            return false;
        }
        $name = $input['name'];
        $type = (isset($input['type'])) ? $input['type'] : 'private';
        $user = User::get_from_username(Session::username($input['auth']));
        if ($type != 'private') {
            $type = 'public';
        }

        $uid = \Playlist::create($name, $type, $user->id);
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::playlists(array($uid));
                break;
            default:
                echo XML_Data::playlists(array($uid));
        }
        Session::extend($input['auth']);

        return true;
    }
}
