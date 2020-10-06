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
use JSON_Data;
use XML_Data;

final class FriendsTimelineMethod
{
    /**
     * friends_timeline
     * MINIMUM_API_VERSION=380001
     *
     * This get current user friends timeline
     *
     * @param array $input
     * limit = (integer) //optional
     * since = (integer) UNIXTIME() //optional
     */
    public static function friends_timeline($input)
    {
        if (AmpConfig::get('sociable')) {
            $limit = (int) ($input['limit']);
            $since = (int) ($input['since']);
            $user  = \User::get_from_username(\Session::username($input['auth']))->id;

            if ($user > 0) {
                $activities = \Useractivity::get_friends_activities($user, $limit, $since);
                ob_end_clean();
                switch ($input['api_format']) {
                    case 'json':
                        echo JSON_Data::timeline($activities);
                        break;
                    default:
                        echo XML_Data::timeline($activities);
                }
            }
        } else {
            debug_event('api.class', 'Sociable feature is not enabled.', 3);
        }
        \Session::extend($input['auth']);
    }
}
