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

final class FlagMethod
{
    /**
     * flag
     * MINIMUM_API_VERSION=400001
     *
     * This flags a library item as a favorite
     * Setting flag to true (1) will set the flag
     * Setting flag to false (0) will remove the flag
     *
     * @param array $input
     * type = (string) 'song', 'album', 'artist', 'playlist' $type
     * id   = (integer) $object_id
     * flag = (integer) 0,1 $flag
     * @return boolean
     */
    public static function flag($input)
    {
        if (!AmpConfig::get('userflags')) {
            Api::message('error', T_('Access Denied: UserFlag features are not enabled.'), '403', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('type', 'id', 'flag'), 'flag')) {
            return false;
        }
        ob_end_clean();
        $type      = $input['type'];
        $object_id = $input['id'];
        $flag      = (bool) $input['flag'];
        $user      = \User::get_from_username(\Session::username($input['auth']));
        $user_id   = null;
        if ((int) $user->id > 0) {
            $user_id = $user->id;
        }
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'playlist'))) {
            Api::message('error', T_('Incorrect object type') . ' ' . $type, '400', $input['api_format']);

            return false;
        }

        if (!\Core::is_library_item($type) || !$object_id) {
            Api::message('error', T_('Wrong library item type'), '400', $input['api_format']);
        } else {
            $item = new $type($object_id);
            if (!$item->id) {
                Api::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            $userflag = new \Userflag($object_id, $type);
            if ($userflag->set_flag($flag, $user_id)) {
                $message = ($flag) ? 'flag ADDED to ' : 'flag REMOVED from ';
                Api::message('success', $message . $object_id, null, $input['api_format']);

                return true;
            }
            Api::message('error', 'flag failed ' . $object_id, '400', $input['api_format']);
        }
        \Session::extend($input['auth']);

        return true;
    }
}
