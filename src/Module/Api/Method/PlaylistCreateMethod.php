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

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\System\Session;

/**
 * Class PlaylistCreateMethod
 * @package Lib\ApiMethods
 */
final class PlaylistCreateMethod
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
     * type = (string) 'public', 'private'
     * @return boolean
     */
    public static function playlist_create(array $input): bool
    {
        if (!Api::check_parameter($input, array('name'), self::ACTION)) {
            return false;
        }
        $name = $input['name'];
        $type = (isset($input['type'])) ? $input['type'] : 'private';
        $user = User::get_from_username(Session::username($input['auth']));
        if ($type != 'private') {
            $type = 'public';
        }

        $object_id = Playlist::create($name, $type, $user->id);
        if (!$object_id) {
            Api::error(T_('Bad Request'), '4710', self::ACTION, 'input', $input['api_format']);

            return false;
        }
        Catalog::count_table('playlist');
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::playlists(array($object_id), $user->id, false, false);
                break;
            default:
                echo Xml_Data::playlists(array($object_id), $user->id);
        }

        return true;
    }
}
