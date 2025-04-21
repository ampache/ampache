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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\Model\User;

/**
 * Class NowPlayingMethod
 * @package Lib\ApiMethods
 */
final class NowPlayingMethod
{
    public const ACTION = 'now_playing';

    /**
     * now_playing
     * MINIMUM_API_VERSION=6.3.1
     *
     * Get what is currently being played by all users.
     *
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function now_playing(array $input, User $user): bool
    {
        unset($user);
        $results = Stream::get_now_playing();

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset((int)($input['offset'] ?? 0));
                Json_Data::set_limit($input['limit'] ?? 0);
                Json_Data::set_count(count($results));
                echo Json_Data::now_playing($results);
                break;
            default:
                Xml_Data::set_offset((int)($input['offset'] ?? 0));
                Xml_Data::set_limit($input['limit'] ?? 0);
                Xml_Data::set_count(count($results));
                echo Xml_Data::now_playing($results);
        }

        return true;
    }
}
