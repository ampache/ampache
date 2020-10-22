<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Lib\ApiMethods;

use AmpConfig;
use Api;
use Core;
use Rating;
use Session;
use User;

/**
 * Class RateMethod
 * @package Lib\ApiMethods
 */
final class RateMethod
{
    private const ACTION = 'rate';

    /**
     * rate
     * MINIMUM_API_VERSION=380001
     *
     * This rates a library item
     *
     * @param array $input
     * type   = (string) 'song', 'album', 'artist' $type
     * id     = (integer) $object_id
     * rating = (integer) 0,1|2|3|4|5 $rating
     * @return boolean|void
     */
    public static function rate(array $input)
    {
        if (!AmpConfig::get('ratings')) {
            Api::error(T_('Enable: ratings'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('type', 'id', 'rating'), self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];
        $rating    = (string) $input['rating'];
        $user      = User::get_from_username(Session::username($input['auth']));
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

            return false;
        }
        if (!in_array($rating, array('0', '1', '2', '3', '4', '5'))) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $rating), '4710', self::ACTION, 'rating', $input['api_format']);

            return false;
        }

        if (!Core::is_library_item($type) || !$object_id) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);
        } else {
            $item = new $type($object_id);
            if (!$item->id) {
                /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
                Api::error(sprintf(T_('Not Found: %s'), $object_id), '4704', self::ACTION, 'id', $input['api_format']);

                return false;
            }
            $rate = new Rating($object_id, $type);
            $rate->set_rating($rating, $user->id);
            Api::message('rating set to ' . $rating . ' for ' . $object_id, $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    }
}
