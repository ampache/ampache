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
use Session;
use User;
use Userflag;

/**
 * Class FlagMethod
 * @package Lib\ApiMethods
 */
final class FlagMethod
{
    private const ACTION = 'flag';

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
    public static function flag(array $input)
    {
        if (!AmpConfig::get('userflags')) {
            Api::error(T_('Enable: userflags'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('type', 'id', 'flag'), self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];
        $flag      = (bool) $input['flag'];
        $user      = User::get_from_username(Session::username($input['auth']));
        $user_id   = null;
        if ((int) $user->id > 0) {
            $user_id = $user->id;
        }
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'playlist'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

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
            $userflag = new Userflag($object_id, $type);
            if ($userflag->set_flag($flag, $user_id)) {
                $message = ($flag) ? 'flag ADDED to ' : 'flag REMOVED from ';
                Api::message($message . $object_id, $input['api_format']);

                return true;
            }
            Api::error('flag failed ' . $object_id, '4710', self::ACTION, 'system', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    }
}
