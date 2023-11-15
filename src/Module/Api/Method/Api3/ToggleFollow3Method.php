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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\User;
use Ampache\Module\User\Following\UserFollowTogglerInterface;

/**
 * Class ToggleFollow3Method
 */
final class ToggleFollow3Method
{
    public const ACTION = 'toggle_follow';

    /**
     * toggle_follow
     * This follow/unfollow a user
     */
    public static function toggle_follow(array $input, User $user): void
    {
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            if (!empty($username)) {
                $leader = User::get_from_username($username);
                if ($leader instanceof User) {
                    static::getUserFollowToggler()->toggle(
                        $leader->id,
                        $user->id
                    );
                    ob_end_clean();
                    echo Xml3_Data::single_string('success');
                }
            } else {
                debug_event(self::class, 'Username to toggle required on follow function call.', 1);
            }
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }
    } // toggle_follow

    private static function getUserFollowToggler(): UserFollowTogglerInterface
    {
        global $dic;

        return $dic->get(UserFollowTogglerInterface::class);
    }
}
