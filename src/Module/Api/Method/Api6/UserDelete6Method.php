<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;

/**
 * Class UserDelete6Method
 * @package Lib\Api6Methods
 */
final class UserDelete6Method
{
    public const string ACTION = 'user_delete';

    public const string REST_ACTION = 'users_delete';

    /**
     * user_delete
     * MINIMUM_API_VERSION=400001
     *
     * Delete an existing user.
     * Takes the username in parameter.
     *
     * filter   = (integer|string) filter by user id OR username //optional
     * username = (string) $username
     *
     * @param array{
     *     filter?: int|string,
     *     username?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function user_delete(array $input, User $user): bool
    {
        if (!Api6::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }

        $input['username'] = $input['filter'] ?? $input['username'] ?? null;
        if (!Api6::check_parameter($input, ['username'], self::ACTION)) {
            return false;
        }

        $username = $input['username'];
        $del_user = (is_numeric($username))
            ? User::get_from_id((int)$username)
            : User::get_from_username((string)$username);

        if ($del_user === null) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api6::error(sprintf('Bad Request: %s', $username), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

            return false;
        }

        // don't delete yourself or admins
        if ($del_user->username !== $user->username && $del_user->access < 100 && $del_user->delete()) {
            Api6::message('successfully deleted: ' . $username, $input['api_format']);
            Catalog::count_table('user');

            return true;
        }
        /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
        Api6::error(sprintf('Bad Request: %s', $username), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'system', $input['api_format']);

        return false;
    }

    /**
     * @param array{
     *     filter?: int|string,
     *     username?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public static function users_delete(array $input, User $user): bool
    {
        return self::user_delete($input, $user);
    }
}
