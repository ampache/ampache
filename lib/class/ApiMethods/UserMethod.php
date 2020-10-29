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

use Access;
use Api;
use JSON_Data;
use Session;
use User;
use XML_Data;

/**
 * Class UserMethod
 * @package Lib\ApiMethods
 */
final class UserMethod
{
    private const ACTION = 'user';

    /**
     * user
     * MINIMUM_API_VERSION=380001
     *
     * This get a user's public information
     *
     * @param array $input
     * username = (string) $username
     * @return boolean
     */
    public static function user(array $input)
    {
        if (!Api::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $username = (string) $input['username'];
        if (empty($username)) {
            debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $username), '4704', self::ACTION, 'username', $input['api_format']);

            return false;
        }

        $user  = User::get_from_username($username);
        $valid = in_array($user->id, User::get_valid_users(true));
        if (!$valid || !$user->id) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $username), '4704', self::ACTION, 'username', $input['api_format']);

            return false;
        }

        $apiuser  = User::get_from_username(Session::username($input['auth']));
        $fullinfo = false;
        // get full info when you're an admin or searching for yourself
        if (($user->id == $apiuser->id) || (Access::check('interface', 100, $apiuser->id))) {
            $fullinfo = true;
        }
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::user($user, $fullinfo);
                break;
            default:
                echo XML_Data::user($user, $fullinfo);
        }
        Session::extend($input['auth']);

        return true;
    }
}
