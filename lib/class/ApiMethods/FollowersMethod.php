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
use JSON_Data;
use Session;
use User;
use XML_Data;

/**
 * Class FollowersMethod
 * @package Lib\ApiMethods
 */
final class FollowersMethod
{
    private const ACTION = 'followers';

    /**
     * followers
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400004
     *
     * This gets followers of the user
     * Error when user not found or no followers
     *
     * @param array $input
     * username = (string) $username
     * @return boolean
     */
    public static function followers(array $input)
    {
        if (!AmpConfig::get('sociable')) {
            Api::error(T_('Enable: sociable'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $username = $input['username'];
        $user     = User::get_from_username($username);
        if (!$user->id) {
            debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $username), '4704', self::ACTION, 'username', $input['api_format']);

            return false;
        }

        $users = $user->get_followers();
        if (empty($users)) {
            Api::empty('user', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::users($users);
                break;
            default:
                echo XML_Data::users($users);
        }
        Session::extend($input['auth']);

        return true;
    }
}
