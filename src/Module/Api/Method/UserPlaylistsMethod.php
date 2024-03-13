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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;

/**
 * Class UserPlaylistsMethod
 * @package Lib\ApiMethods
 */
final class UserPlaylistsMethod
{
    public const ACTION = 'user_playlists';

    /**
     * user_playlists
     * MINIMUM_API_VERSION=6.3.0
     *
     * This returns playlists based on the specified filter (Does not include searches / smartlists)
     *
     * filter      = (string) Alpha-numeric search term (match all if missing) //optional
     * exact       = (integer) 0,1, if true filter is exact rather than fuzzy //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     *
     * @param array<string, mixed> $input
     */
    public static function user_playlists(array $input, User $user): bool
    {
        $like    = !(array_key_exists('exact', $input) && (int)$input['exact'] == 1);
        $filter  = (string)($input['filter'] ?? '');
        $results = Playlist::get_playlists($user->id, $filter, $like, false, true, true);

        if (empty($results)) {
            Api::empty('playlist', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset'] ?? 0);
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::playlists($results, $user);
                break;
            default:
                Xml_Data::set_offset($input['offset'] ?? 0);
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::playlists($results, $user);
        }

        return true;
    }
}
