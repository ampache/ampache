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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\UserFollowerRepositoryInterface;

/**
 * Class Followers3Method
 */
final class Followers3Method
{
    public const ACTION = 'followers';

    /**
     * followers
     * This gets a user's followers
     */
    public static function followers(array $input, User $user): void
    {
        unset($user);
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            if (!empty($username)) {
                $user = User::get_from_username($username);
                if ($user instanceof User) {
                    $results = static::getUserFollowerRepository()->getFollowers($user);
                    ob_end_clean();
                    echo Xml3_Data::users($results);
                } else {
                    debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
                }
            } else {
                debug_event(self::class, 'Username required on followers function call.', 1);
            }
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }
    }

    /**
     * @deprecated inject by constructor
     */
    private static function getUserFollowerRepository(): UserFollowerRepositoryInterface
    {
        global $dic;

        return $dic->get(UserFollowerRepositoryInterface::class);
    }
}
