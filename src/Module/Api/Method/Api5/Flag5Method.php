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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Api5;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;

/**
 * Class Flag5Method
 */
final class Flag5Method
{
    public const ACTION = 'flag';

    /**
     * flag
     * MINIMUM_API_VERSION=400001
     *
     * This flags a library item as a favorite
     * Setting flag to true (1) will set the flag
     * Setting flag to false (0) will remove the flag
     *
     * type = (string) 'song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season' $type
     * id   = (integer) $object_id
     * flag = (integer) 0,1 $flag
     */
    public static function flag(array $input, User $user): bool
    {
        if (!AmpConfig::get('ratings')) {
            Api5::error(T_('Enable: ratings'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api5::check_parameter($input, array('type', 'id', 'flag'), self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];
        $flag      = (bool)($input['flag'] ?? false);
        // confirm the correct data
        if (!in_array(strtolower($type), array('song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season'))) {
            Api5::error(sprintf(T_('Bad Request: %s'), $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }

        $className = ObjectTypeToClassNameMapper::map($type);
        if (!$className || !$object_id) {
            Api5::error(sprintf(T_('Bad Request: %s'), $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);
        } else {
            /** @var library_item $item */
            $item = new $className($object_id);
            if ($item->isNew()) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api5::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'id', $input['api_format']);

                return false;
            }
            $userflag = new Userflag($object_id, $type);
            if ($userflag->set_flag($flag, $user->id)) {
                $message = ($flag) ? 'flag ADDED to ' : 'flag REMOVED from ';
                Api5::message($message . $object_id, $input['api_format']);

                return true;
            }
            Api5::error('flag failed ' . $object_id, ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);
        }

        return true;
    }
}
