<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;

/**
 * Class PlaylistCreate5Method
 */
final class PlaylistCreate5Method
{
    public const ACTION = 'playlist_create';

    /**
     * playlist_create
     * MINIMUM_API_VERSION=380001
     *
     * Create a new playlist and return it
     *
     * name = (string) Playlist name
     * type = (string) 'public', 'private'
     */
    public static function playlist_create(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, array('name'), self::ACTION)) {
            return false;
        }
        $name = $input['name'];
        $type = (isset($input['type'])) ? $input['type'] : 'private';
        if ($type != 'private') {
            $type = 'public';
        }

        $object_id = Playlist::create($name, $type, $user->id);
        if (!$object_id) {
            Api5::error(T_('Bad Request'), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'input', $input['api_format']);

            return false;
        }
        Catalog::count_table('playlist');
        switch ($input['api_format']) {
            case 'json':
                echo Json5_Data::playlists(array($object_id), $user, false, false);
                break;
            default:
                echo Xml5_Data::playlists(array($object_id), $user);
        }

        return true;
    }
}
