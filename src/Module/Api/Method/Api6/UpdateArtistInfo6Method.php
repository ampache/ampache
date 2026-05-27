<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;

/**
 * Class UpdateArtistInfo6Method
 * @package Lib\Api6Methods
 */
final class UpdateArtistInfo6Method
{
    public const ACTION = 'update_artist_info';

    /**
     * update_artist_info
     * MINIMUM_API_VERSION=400001
     *
     * Update artist information and fetch similar artists from last.fm
     * Make sure lastfm_api_key is set in your configuration file
     *
     * id = (string) $artist_id
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function update_artist_info(array $input, User $user): bool
    {
        if (!Api6::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $input['id'] = $input['filter'] ?? $input['id'] ?? null;
        if (!Api6::check_parameter($input, ['id'], self::ACTION)) {
            return false;
        }

        $object_id = (int) $input['id'];
        $item      = new Artist($object_id);
        if ($item->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api6::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'id', $input['api_format']);

            return false;
        }

        $info = Recommendation::get_artist_info($object_id);
        $like = Recommendation::get_artists_like($object_id);
        // update your object, you need at least catalog_manager access to the db
        if (
            array_key_exists('id', $info) && $info['id'] !== null ||
            count($like) > 0
        ) {
            Api6::message('Updated artist info: ' . $object_id, $input['api_format']);

            return true;
        }
        /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
        Api6::error(sprintf('Bad Request: %s', $object_id), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

        return true;
    }
}
