<?php

/**
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
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\UserActivityRepositoryInterface;

/**
 * Class Timeline3Method
 */
final class Timeline3Method
{
    public const ACTION = 'timeline';

    /**
     * timeline
     * This gets a user's timeline
     */
    public static function timeline(array $input, User $user): void
    {
        unset($user);
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            $limit    = (int)($input['limit'] ?? 0);
            $since    = (int)($input['since'] ?? 0);

            if (!empty($username)) {
                $user = User::get_from_username($username);
                if ($user instanceof User) {
                    if (Preference::get_by_user($user->id, 'allow_personal_info_recent')) {
                        $results = static::getUseractivityRepository()->getActivities(
                            $user->id,
                            $limit,
                            $since
                        );
                        ob_end_clean();
                        echo Xml3_Data::timeline($results);
                    }
                }
            } else {
                debug_event(self::class, 'Username required on timeline function call.', 1);
            }
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }
    }

    private static function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }
}
