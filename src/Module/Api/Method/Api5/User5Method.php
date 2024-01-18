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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class User5Method
 */
final class User5Method
{
    public const ACTION = 'user';

    /**
     * user
     * MINIMUM_API_VERSION=380001
     *
     * This get a user's public information
     *
     * username = (string) $username
     */
    public static function user(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $username = (string) $input['username'];
        if (empty($username)) {
            debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Not Found: %s'), $username), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'username', $input['api_format']);

            return false;
        }

        $check_user = User::get_from_username($username);
        $valid      = ($check_user instanceof User && $check_user->isNew() !== false && in_array($check_user->id, static::getUserRepository()->getValid(true)));
        if (!$valid) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Not Found: %s'), $username), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'username', $input['api_format']);

            return false;
        }

        // get full info when you're an admin or searching for yourself
        $fullinfo = (($check_user->id == $user->id) || ($user->access === 100));
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json5_Data::user($check_user, $fullinfo, false);
                break;
            default:
                echo Xml5_Data::user($check_user, $fullinfo);
        }

        return true;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
