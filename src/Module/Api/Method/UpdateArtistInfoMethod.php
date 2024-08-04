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

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Util\Recommendation;

/**
 * Class UpdateArtistInfoMethod
 * @package Lib\ApiMethods
 */
final class UpdateArtistInfoMethod
{
    public const ACTION = 'update_artist_info';

    /**
     * update_artist_info
     * MINIMUM_API_VERSION=400001
     *
     * Update artist information and fetch similar artists from last.fm
     * Make sure lastfm_api_key is set in your configuration file
     *
     * id = (integer) $artist_id
     */
    public static function update_artist_info(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['id'], self::ACTION)) {
            return false;
        }

        if (!Api::check_access('interface', 75, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $object_id = (int) $input['id'];
        $item      = new Artist($object_id);
        if ($item->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'id', $input['api_format']);

            return false;
        }

        // update your object, you need at least catalog_manager access to the db
        if (!empty(Recommendation::get_artist_info($object_id) || !empty(Recommendation::get_artists_like($object_id)))) {
            Api::message('Updated artist info: ' . (string) $object_id, $input['api_format']);

            return true;
        }
        /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
        Api::error(sprintf('Bad Request: %s', $object_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

        return true;
    }
}
