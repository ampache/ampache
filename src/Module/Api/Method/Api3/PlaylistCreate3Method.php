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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Playlist;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\User;

/**
 * Class PlaylistCreate3Method
 */
final class PlaylistCreate3Method
{
    public const ACTION = 'playlist_create';

    /**
     * playlist_create
     * Create a new playlist and return it
     */
    public static function playlist_create(array $input, User $user): void
    {
        $name = $input['name'];
        $type = $input['type'];
        if ($type != 'private') {
            $type = 'public';
        }
        Catalog::count_table('playlist');

        $uid = Playlist::create($name, $type, $user->id);
        echo Xml3_Data::playlists(array($uid));
    }
}
