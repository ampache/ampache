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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Repository\UserActivityRepositoryInterface;

/**
 * Class FriendsTimeline5Method
 */
final class FriendsTimeline5Method
{
    public const ACTION = 'friends_timeline';

    /**
     * friends_timeline
     * MINIMUM_API_VERSION=380001
     *
     * This get current user friends timeline
     *
     * limit = (integer) //optional
     * since = (integer) UNIXTIME() //optional
     */
    public static function friends_timeline(array $input, User $user): bool
    {
        if (!AmpConfig::get('sociable')) {
            Api5::error(T_('Enable: sociable'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        $limit = (int)($input['limit'] ?? 0);
        $since = (int)($input['since'] ?? 0);
        $user  = $user->getId();

        $results = static::getUseractivityRepository()->getFriendsActivities(
            $user,
            $limit,
            $since
        );
        if (empty($results)) {
            Api5::empty('activity', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json5_Data::timeline($results);
                break;
            default:
                echo Xml5_Data::timeline($results);
        }

        return true;
    }

    private static function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }
}
