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

final class RateMethod
{
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
    public static function rate($input)
    {
        if (!AmpConfig::get('ratings')) {
            Api::message('error', T_('Access Denied: Rating features are not enabled.'), '403', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('type', 'id', 'rating'), 'rate')) {
            return false;
        }
        ob_end_clean();
        $type      = (string) $input['type'];
        $object_id = $input['id'];
        $rating    = $input['rating'];
        $user      = \User::get_from_username(\Session::username($input['auth']));
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist'))) {
            Api::message('error', T_('Incorrect object type') . ' ' . $type, '400', $input['api_format']);

            return false;
        }
        if (!in_array($rating, array('0', '1', '2', '3', '4', '5'))) {
            Api::message('error', T_('Ratings must be between [0-5]. ' . $rating . ' is invalid'), '400', $input['api_format']);

            return false;
        }

        if (!Core::is_library_item($type) || !$object_id) {
            Api::message('error', T_('Wrong library item type'), '400', $input['api_format']);
        } else {
            $item = new $type($object_id);
            if (!$item->id) {
                Api::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            $rate = new \Rating($object_id, $type);
            $rate->set_rating($rating, $user->id);
            Api::message('success', 'rating set to ' . $rating . ' for ' . $object_id, null, $input['api_format']);
        }
        \Session::extend($input['auth']);

        return true;
    }
}
