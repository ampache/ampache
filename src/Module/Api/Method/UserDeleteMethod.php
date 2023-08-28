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

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;

/**
 * Class UserDeleteMethod
 * @package Lib\ApiMethods
 */
final class UserDeleteMethod
{
    public const ACTION = 'user_delete';

    /**
     * user_delete
     * MINIMUM_API_VERSION=400001
     *
     * Delete an existing user.
     * Takes the username in parameter.
     *
     * @param array $input
     * @param User $user
     * username = (string) $username)
     * @return boolean
     */
    public static function user_delete(array $input, User $user): bool
    {
        if (!Api::check_access('interface', 100, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $username = $input['username'];
        $del_user = User::get_from_username($username);
        // don't delete yourself or admins
        if ($del_user instanceof User && $del_user->username !== $user->username && $del_user->access < 100 && $del_user->delete()) {
            Api::message('successfully deleted: ' . $username, $input['api_format']);
            Catalog::count_table('user');

            return true;
        }
        /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
        Api::error(sprintf(T_('Bad Request: %s'), $username), '4710', self::ACTION, 'system', $input['api_format']);

        return false;
    }
}
