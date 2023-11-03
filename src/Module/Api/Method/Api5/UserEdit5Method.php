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

use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;

/**
 * Class UserUpdateMethod
 * @package Lib\ApiMethods
 */
final class UserEdit5Method
{
    public const ACTION = 'user_edit';

    /**
     * user_edit
     * MINIMUM_API_VERSION=6.0.0
     *
     * Update an existing user.
     * Takes the username with optional parameters.
     *
     * @param array $input
     * @param User $user
     * username          = (string) $username
     * password          = (string) hash('sha256', $password)) //optional
     * fullname          = (string) $fullname //optional
     * email             = (string) $email //optional
     * website           = (string) $website //optional
     * state             = (string) $state //optional
     * city              = (string) $city //optional
     * disable           = (integer) 0,1 true to disable, false to enable //optional
     * group             = (integer) Catalog filter group for the new user //optional, default = 0
     * maxbitrate        = (integer) $maxbitrate //optional
     * fullname_public   = (integer) 0,1 true to enable, false to disable using fullname in public display //optional
     * reset_apikey      = (integer) 0,1 true to reset a user Api Key //optional
     * reset_streamtoken = (integer) 0,1 true to reset a user Stream Token //optional
     * clear_stats       = (integer) 0,1 true reset all stats for this user //optional
     * @return boolean
     */
    public static function user_edit(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }

        return UserUpdate5Method::user_update($input, $user);
    } // tag_albums
}
