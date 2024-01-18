<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class UserCreateMethod
 * @package Lib\ApiMethods
 */
final class UserCreateMethod
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
     * group    = (integer) Catalog filter group for the new user //optional, default = 0
     */
    public static function user_create(array $input, User $user): bool
    {
        if (!Api::check_access('interface', 100, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('username', 'password', 'email'), self::ACTION)) {
            return false;
        }
        $username             = $input['username'];
        $fullname             = $input['fullname'] ?? $username;
        $email                = urldecode($input['email']);
        $password             = $input['password'];
        $disable              = (bool)($input['disable'] ?? false);
        $access               = 25;
        $catalog_filter_group = $input['group'] ?? 0;
        $user_id              = User::create($username, $fullname, $email, '', $password, $access, $catalog_filter_group, '', '', $disable, true);

        if ($user_id > 0) {
            Api::message('successfully created: ' . $username, $input['api_format']);
            Catalog::count_table('user');

            return true;
        }

        $userRepository = self::getUserRepository();

        if ($userRepository->idByUsername($username) > 0) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $username), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'username', $input['api_format']);

            return false;
        }
        if ($userRepository->idByEmail($email) > 0) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $email), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'email', $input['api_format']);

            return false;
        }
        Api::error(T_('Bad Request'), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

        return false;
    }

    /**
     * @todo Inject by constructor
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
