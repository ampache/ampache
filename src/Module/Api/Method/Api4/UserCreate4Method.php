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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class UserCreate4Method
 */
final class UserCreate4Method
{
    public const ACTION = 'user_create';

    /**
     * user_create
     * MINIMUM_API_VERSION=400001
     *
     * Create a new user.
     * Requires the username, password and email.
     *
     * username = (string) $username
     * fullname = (string) $fullname //optional
     * password = (string) hash('sha256', $password)
     * email    = (string) $email
     * disable  = (integer) 0,1 //optional, default = 0
     */
    public static function user_create(array $input, User $user): bool
    {
        if (!Api4::check_access('interface', 100, $user->id, 'user_create', $input['api_format'])) {
            return false;
        }
        if (!Api4::check_parameter($input, array('username', 'password', 'email'), self::ACTION)) {
            return false;
        }
        $username             = $input['username'];
        $fullname             = $input['fullname'] ?? $username;
        $email                = urldecode($input['email']);
        $password             = $input['password'];
        $disable              = (bool)($input['disable'] ?? false);
        $access               = 25;
        $catalog_filter_group = 0;
        $user_id              = User::create($username, $fullname, $email, '', $password, $access, $catalog_filter_group, '', '', $disable, true);

        if ($user_id > 0) {
            Api4::message('success', 'successfully created: ' . $username, null, $input['api_format']);
            Catalog::count_table('user');

            return true;
        }

        $userRepository = self::getUserRepository();

        if ($userRepository->idByUsername($username) > 0) {
            Api4::message('error', 'username already exists: ' . $username, '400', $input['api_format']);

            return false;
        }
        if ($userRepository->idByEmail($email) > 0) {
            Api4::message('error', 'email already exists: ' . $email, '400', $input['api_format']);

            return false;
        }
        Api4::message('error', 'failed to create: ' . $username, '400', $input['api_format']);

        return false;
    } // user_create

    /**
     * @todo Inject by constructor
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
