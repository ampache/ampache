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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\UserActivityRepositoryInterface;

/**
 * Class FriendsTimeline3Method
 */
final class FriendsTimeline3Method
{
    const ACTION = 'friends_timeline';

    /**
     * friends_timeline
     * This get current user friends timeline
     * @param array $input
     * @param User $user
     */
    public static function friends_timeline(array $input, User $user)
    {
        if (AmpConfig::get('sociable')) {
            $limit = (int)($input['limit'] ?? 0);
            $since = (int)($input['since'] ?? 0);

            $results = static::getUseractivityRepository()->getActivities(
                $user->id,
                $limit,
                $since
            );
            ob_end_clean();
            echo Xml3_Data::timeline($results);
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }
    } // friends_timeline

    private static function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }
}
