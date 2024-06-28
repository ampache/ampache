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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\User\Following\UserFollowTogglerInterface;

/**
 * Class ToggleFollow5Method
 */
final class ToggleFollow5Method
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
            Api5::error(T_('Enable: sociable'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api5::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        $username = $input['username'];
        if (!empty($username)) {
            $leader = User::get_from_username($username);
            if ($leader instanceof User) {
                static::getUserFollowToggler()->toggle(
                    $leader->getId(),
                    $user->getId()
                );
                ob_end_clean();
                Api5::message('follow toggled for: ' . $user->id, $input['api_format']);
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
