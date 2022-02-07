<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Session;

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
     * username = (string) $username)
     * @return boolean
     */
    public static function user(array $input): bool
    {
        if (!Api4::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $username = (string) $input['username'];
        $user     = User::get_from_username($username);
        if ($user->id) {
            $apiuser  = User::get_from_username(Session::username($input['auth']));
            $fullinfo = false;
            // get full info when you're an admin or searching for yourself
            if (($user->id == $apiuser->id) || (Access::check('interface', 100, $apiuser->id))) {
                $fullinfo = true;
            }
            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    echo Json4_Data::user($user, $fullinfo);
                break;
                default:
                    echo Xml4_Data::user($user, $fullinfo);
            }
        } else {
            Api4::message('error', T_('User_id not found'), '404', $input['api_format']);
        }

        return true;
    } // user
}
