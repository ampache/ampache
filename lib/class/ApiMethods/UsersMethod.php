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
use JSON_Data;
use Session;
use User;
use XML_Data;

/**
 * Class UsersMethod
 * @package Lib\ApiMethods
 */
final class UsersMethod
{
    const ACTION = 'users';

    /**
     * users
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get ids and usernames for your site
     *
     * @param array $input
     * @return boolean
     */
    public static function users(array $input)
    {
        $users = User::get_valid_users();
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
