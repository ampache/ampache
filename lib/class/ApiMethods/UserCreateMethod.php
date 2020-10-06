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

use Api;
use Session;

final class UserCreateMethod
{
    /**
     * user_create
     * MINIMUM_API_VERSION=400001
     *
     * Create a new user.
     * Requires the username, password and email.
     *
     * @param array $input
     * username = (string) $username
     * fullname = (string) $fullname //optional
     * password = (string) hash('sha256', $password))
     * email    = (string) $email
     * disable  = (integer) 0,1 //optional, default = 0
     * @return boolean
     */
    public static function user_create($input)
    {
        if (!Api::check_access('interface', 100, \User::get_from_username(Session::username($input['auth']))->id, 'user_create', $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('username', 'password', 'email'), 'user_create')) {
            return false;
        }
        $username = $input['username'];
        $fullname = $input['fullname'] ?: $username;
        $email    = $input['email'];
        $password = $input['password'];
        $disable  = (bool) $input['disable'];
        $access   = 25;
        $user_id  = \User::create($username, $fullname, $email, null, $password, $access, null, null, $disable, true);

        if ($user_id > 0) {
            Api::message('success', 'successfully created: ' . $username, null, $input['api_format']);

            return true;
        }
        Api::message('error', 'failed to create: ' . $username, '400', $input['api_format']);
        Session::extend($input['auth']);

        return false;
    }
}
