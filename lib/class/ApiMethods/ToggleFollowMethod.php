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

use AmpConfig;
use Api;

final class ToggleFollowMethod
{
    /**
     * toggle_follow
     * MINIMUM_API_VERSION=380001
     *
     * This will follow/unfollow a user
     *
     * @param array $input
     * username = (string) $username
     * @return boolean
     */
    public static function toggle_follow($input)
    {
        if (!AmpConfig::get('sociable')) {
            Api::message('error', T_('Access Denied: social features are not enabled.'), '403', $input['api_format']);

            return false;
        }
        if (!Api::check_parameter($input, array('username'), 'toggle_follow')) {
            return false;
        }
        $username = $input['username'];
        if (!empty($username)) {
            $user = \User::get_from_username($username);
            if ($user !== null) {
                \User::get_from_username(\Session::username($input['auth']))->toggle_follow($user->id);
                ob_end_clean();
                Api::message('success', 'follow toggled for: ' . $user->id, null, $input['api_format']);
            }
        }
        \Session::extend($input['auth']);

        return true;
    }
}
