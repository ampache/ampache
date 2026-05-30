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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

/**
 * Class Flag6Method
 * @package Lib\Api6Methods
 */
final class Flag6Method
{
    public const string ACTION = 'flag';

    /**
     * flag
     * MINIMUM_API_VERSION=400001
     *
     * This flags a library item as a favorite
     * Setting flag to true (1) will set the flag
     * Setting flag to false (0) will remove the flag
     *
     * id   = (string) $object_id
     * type = (string) 'song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video' $type
     * flag = (integer) 0,1 $flag
     * date = (integer) UNIXTIME() //optional
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     type: string,
     *     flag: int,
     *     date?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function flag(array $input, User $user): bool
    {
        if (!AmpConfig::get('ratings')) {
            Api6::error('Enable: ratings', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        $input['id'] = $input['filter'] ?? $input['id'] ?? null;
        if (!Api6::check_parameter($input, ['type', 'id', 'flag'], self::ACTION)) {
            return false;
        }

        ob_end_clean();
        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];
        $flag      = make_bool($input['flag']);
        $date      = (int)($input['date'] ?? time());

        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video'])) {
            Api6::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        // searches are playlists but not in the database
        if (
            $type === 'playlist' &&
            $object_id === 0
        ) {
            $type      = 'search';
            $object_id = (int) str_replace('smart_', '', (string)$input['id']);
        }

        $className = ObjectTypeToClassNameMapper::map($type);

        if (!$className || !$object_id) {
            Api6::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);
        } else {
            /** @var library_item $item */
            $item = new $className($object_id);
            if ($item->isNew()) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api6::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'id', $input['api_format']);

                return false;
            }
            $userflag = new Userflag($object_id, $type);
            if ($userflag->set_flag($flag, $user->id, $date)) {
                $message = ($flag) ? 'flag ADDED to ' : 'flag REMOVED from ';
                Api6::message($message . $object_id, $input['api_format']);

                return true;
            }
            Api6::error('flag failed ' . $object_id, ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        return true;
    }
}
