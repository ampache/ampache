<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;

/**
 * Class RateMethod
 * @package Lib\ApiMethods
 */
final class RateMethod
{
    public const ACTION = 'rate';

    /**
     * rate
     * MINIMUM_API_VERSION=380001
     *
     * This rates a library item
     *
     * type   = (string) 'song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season' $type
     * id     = (integer) $object_id
     * rating = (integer) 0|1|2|3|4|5 $rating
     */
    public static function rate(array $input, User $user): bool
    {
        if (!AmpConfig::get('ratings')) {
            Api::error(T_('Enable: ratings'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('type', 'id', 'rating'), self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];
        $rating    = (string) $input['rating'];
        // confirm the correct data
        if (!in_array(strtolower($type), array('song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }
        if (!in_array($rating, array('0', '1', '2', '3', '4', '5'))) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $rating), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'rating', $input['api_format']);

            return false;
        }

        $className = ObjectTypeToClassNameMapper::map($type);
        if (!$className || !$object_id) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);
        } else {
            /** @var library_item $item */
            $item = new $className($object_id);
            if ($item->isNew()) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf(T_('Not Found: %s'), $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'id', $input['api_format']);

                return false;
            }
            $rate = new Rating($object_id, $type);
            $rate->set_rating((int)$rating, $user->id);
            Api::message('rating set to ' . $rating . ' for ' . $object_id, $input['api_format']);
        }

        return true;
    }
}
