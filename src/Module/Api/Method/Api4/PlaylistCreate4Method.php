<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;

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
     * Create a new playlist and return it
     *
     * name = (string) Playlist name
     * type = (string) 'public'|'private'
     *
     * @param array{
     *     name: string,
     *     type?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function playlist_create(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, ['name', 'type'], self::ACTION)) {
            return false;
        }
        $name = $input['name'];
        $type = $input['type'] ?? 'public';
        if ($type !== 'private' && $type !== 'public') {
            $type = 'public';
        }

        $uid = Playlist::create($name, $type, $user->id);
        Catalog::count_table('playlist');
        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::playlists([(int)$uid], $user, $input['auth']);
                break;
            default:
                echo Xml4_Data::playlists([(int)$uid], $user, $input['auth']);
        }

        return true;
    }
}
