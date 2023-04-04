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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\UserActivityRepositoryInterface;

/**
 * Class Timeline5Method
 */
final class Timeline5Method
{
    public const ACTION = 'timeline';

    /**
     * timeline
     * MINIMUM_API_VERSION=380001
     *
     * This gets a user timeline from their username
     *
     * @param array $input
     * @param User $user
     * username = (string)
     * limit    = (integer) //optional
     * since    = (integer) UNIXTIME() //optional
     * @return boolean
     */
    public static function timeline(array $input, User $user): bool
    {
        if (!AmpConfig::get('sociable')) {
            Api5::error(T_('Enable: sociable'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api5::check_parameter($input, array('username'), self::ACTION)) {
            return false;
        }
        unset($user);
        $username = $input['username'];
        $limit    = (int) ($input['limit']);
        $since    = (int) ($input['since']);

        if (!empty($username)) {
            $user = User::get_from_username($username);
            if ($user !== null) {
                if (Preference::get_by_user($user->id, 'allow_personal_info_recent')) {
                    $results = static::getUseractivityRepository()->getActivities(
                        $user->getId(),
                        $limit,
                        $since
                    );
                    ob_end_clean();
                    switch ($input['api_format']) {
                        case 'json':
                            echo Json5_Data::timeline($results);
                            break;
                        default:
                            echo Xml5_Data::timeline($results);
                    }
                }
            }
        }

        return true;
    }

    private static function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }
}
