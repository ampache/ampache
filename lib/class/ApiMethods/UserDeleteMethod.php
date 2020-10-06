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
use Session;

final class UserDeleteMethod
{
    /**
     * user_delete
     * MINIMUM_API_VERSION=400001
     *
     * Delete an existing user.
     * Takes the username in parameter.
     *
     * @param array $input
     * username = (string) $username)
     * @return boolean
     */
    public static function user_delete($input)
    {
        if (!Api::check_access('interface', 100, \User::get_from_username(Session::username($input['auth']))->id, 'user_delete', $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('username'), 'user_delete')) {
            return false;
        }
        $username = $input['username'];
        $user     = \User::get_from_username($username);
        // don't delete yourself or admins
        if ($user->id && Session::username($input['auth']) != $username && !Access::check('interface', 100, $user->id)) {
            $user->delete();
            Api::message('success', 'successfully deleted: ' . $username, null, $input['api_format']);

            return true;
        }
        Api::message('error', 'failed to delete: ' . $username, '400', $input['api_format']);
        Session::extend($input['auth']);

        return false;
    }
}
