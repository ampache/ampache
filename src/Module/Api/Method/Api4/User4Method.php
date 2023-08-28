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

use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class User4Method
 */
final class User4Method
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
     * username = (string) $username)
     * @return boolean
     */
    public static function user(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $username   = (string) $input['username'];
        $check_user = User::get_from_username($username);
        $valid      = $check_user !== null && in_array($check_user->id, static::getUserRepository()->getValid(true));
        if (!$valid) {
            Api4::message('error', T_('User_id not found'), '404', $input['api_format']);

            return false;
        }

        $fullinfo = false;
        // get full info when you're an admin or searching for yourself
        if (($check_user->id == $user->id) || ($user->access === 100)) {
            $fullinfo = true;
        }
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::user($check_user, $fullinfo);
                break;
            default:
                echo Xml4_Data::user($check_user, $fullinfo);
        }

        return true;
    } // user

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
