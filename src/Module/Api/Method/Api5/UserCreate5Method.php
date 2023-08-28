<?php

/*
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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;

/**
 * Class UserCreate5Method
 */
final class UserCreate5Method
{
    public const ACTION = 'user_create';

    /**
     * user_create
     * MINIMUM_API_VERSION=400001
     *
     * Create a new user.
     * Requires the username, password and email.
     *
     * @param array $input
     * @param User $user
     * username = (string) $username
     * fullname = (string) $fullname //optional
     * password = (string) hash('sha256', $password))
     * email    = (string) $email
     * disable  = (integer) 0,1 //optional, default = 0
     * @return boolean
     */
    public static function user_create(array $input, User $user): bool
    {
        if (!Api5::check_access('interface', 100, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api5::check_parameter($input, array('username', 'password', 'email'), self::ACTION)) {
            return false;
        }
        $username             = $input['username'];
        $fullname             = $input['fullname'] ?? $username;
        $email                = urldecode($input['email']);
        $password             = $input['password'];
        $disable              = (bool)($input['disable'] ?? false);
        $access               = 25;
        $catalog_filter_group = 0;
        $user_id              = User::create($username, $fullname, $email, null, $password, $access, $catalog_filter_group, null, null, $disable, true);

        if ($user_id > 0) {
            Api5::message('successfully created: ' . $username, $input['api_format']);
            Catalog::count_table('user');

            return true;
        }
        if (User::id_from_username($username) > 0) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Bad Request: %s'), $username), '4710', self::ACTION, 'username', $input['api_format']);

            return false;
        }
        if (User::id_from_email($email) > 0) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Bad Request: %s'), $email), '4710', self::ACTION, 'email', $input['api_format']);

            return false;
        }
        Api5::error(T_('Bad Request'), '4710', self::ACTION, 'system', $input['api_format']);

        return false;
    }
}
