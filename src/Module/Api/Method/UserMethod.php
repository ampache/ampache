<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Authorization\Access;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class UserMethod
 * @package Lib\ApiMethods
 */
final class UserMethod
{
    public const ACTION = 'user';

    /**
     * user
     * MINIMUM_API_VERSION=380001
     *
     * This get a user's public information
     *
     * @param array $input
     * @param User $user
     * username = (string) $username
     * @return boolean
     */
    public static function user(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $username = (string) $input['username'];
        if (empty($username)) {
            debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $username), '4704', self::ACTION, 'username', $input['api_format']);

            return false;
        }

        $check_user = User::get_from_username($username);
        $valid      = ($check_user instanceof User && in_array($check_user->id, static::getUserRepository()->getValid(true)));
        if (!$valid) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $username), '4704', self::ACTION, 'username', $input['api_format']);

            return false;
        }

        // get full info when you're an admin or searching for yourself
        $fullinfo = (($check_user->id == $user->id) || (Access::check('interface', 100, $user->id)));

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::user($check_user, $fullinfo, false);
                break;
            default:
                echo Xml_Data::user($check_user, $fullinfo);
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
