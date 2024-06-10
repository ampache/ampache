<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\User\Following\UserFollowTogglerInterface;

/**
 * Class ToggleFollow4Method
 */
final class ToggleFollow4Method
{
    public const ACTION = 'toggle_follow';

    /**
     * toggle_follow
     * MINIMUM_API_VERSION=380001
     *
     * This will follow/unfollow a user
     *
     * username = (string) $username
     */
    public static function toggle_follow(array $input, User $user): bool
    {
        if (!AmpConfig::get('sociable')) {
            Api4::message('error', T_('Access Denied: social features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $username = $input['username'];
        if (!empty($username)) {
            $leader = User::get_from_username($username);
            if ($leader instanceof User) {
                static::getUserFollowToggler()->toggle(
                    $leader->id,
                    $user->id
                );
                ob_end_clean();
                Api4::message('success', 'follow toggled for: ' . $user->id, null, $input['api_format']);
            }
        }

        return true;
    }

    private static function getUserFollowToggler(): UserFollowTogglerInterface
    {
        global $dic;

        return $dic->get(UserFollowTogglerInterface::class);
    }
}
