<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\System\Session;

/**
 * Class PlaylistCreate4Method
 */
final class PlaylistCreate4Method
{
    public const ACTION = 'playlist_create';

    /**
     * playlist_create
     * MINIMUM_API_VERSION=380001
     *
     * This create a new playlist and return it
     *
     * @param array $input
     * name = (string) Alpha-numeric search term
     * type = (string) 'public'|'private'
     * @return boolean
     */
    public static function playlist_create(array $input): bool
    {
        if (!Api4::check_parameter($input, array('name', 'type'), 'playlist_create')) {
            return false;
        }
        $name    = $input['name'];
        $type    = $input['type'];
        $user    = User::get_from_username(Session::username($input['auth']));
        $user_id = $user->id;
        if ($type != 'private') {
            $type = 'public';
        }

        $uid = Playlist::create($name, $type, $user->id);
        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::playlists(array($uid), $user_id);
            break;
            default:
                echo Xml4_Data::playlists(array($uid), $user_id);
        }
        Catalog::count_table('playlist');

        return true;
    } // playlist_create
}
