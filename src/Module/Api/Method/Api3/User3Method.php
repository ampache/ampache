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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Repository\Model\User;
use Ampache\Module\Api\Xml3_Data;

/**
 * Class User3Method
 */
final class User3Method
{
    public const ACTION = 'user';

    /**
     * user
     * This get an user public information
     * @param array $input
     * @param User $user
     */
    public static function user(array $input, User $user)
    {
        unset($user);
        $username = $input['username'];
        if (!empty($username)) {
            $user = User::get_from_username($username);
            if ($user !== null) {
                ob_end_clean();
                echo Xml3_Data::user($user);
            } else {
                debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
            }
        } else {
            debug_event(self::class, 'Username required on user function call.', 1);
        }
    } // user
}
