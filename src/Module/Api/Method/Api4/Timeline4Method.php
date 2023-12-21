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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Repository\UserActivityRepositoryInterface;

/**
 * Class TimelineMethod
 */
final class Timeline4Method
{
    public const ACTION = 'timeline';

    /**
     * timeline
     * MINIMUM_API_VERSION=380001
     *
     * This gets a user timeline from their username
     *
     * username = (string)
     * limit    = (integer) //optional
     * since    = (integer) UNIXTIME() //optional
     */
    public static function timeline(array $input, User $user): bool
    {
        unset($user);
        if (AmpConfig::get('sociable')) {
            if (!Api4::check_parameter($input, array('username'), self::ACTION)) {
                return false;
            }
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
                        switch ($input['api_format']) {
                            case 'json':
                                echo Json4_Data::timeline($results);
                                break;
                            default:
                                echo Xml4_Data::timeline($results);
                        }
                    }
                }
            }
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }

        return true;
    }

    private static function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }
}
