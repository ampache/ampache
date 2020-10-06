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
use Api;
use JSON_Data;
use Session;
use User;
use XML_Data;

final class StatsMethod
{
    /**
     * stats
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400001
     *
     * Get some items based on some simple search types and filters
     * This method has partial backwards compatibility with older api versions
     * but should be updated to follow the current input values
     *
     * @param array $input
     * type     = (string)  'song', 'album', 'artist'
     * filter   = (string)  'newest', 'highest', 'frequent', 'recent', 'forgotten', 'flagged', 'random'
     * user_id  = (integer) //optional
     * username = (string)  //optional
     * offset   = (integer) //optional
     * limit    = (integer) //optional
     * @return boolean
     */
    public static function stats($input)
    {
        if (!Api::check_parameter($input, array('type', 'filter'), 'stats')) {
            return false;
        }
        // set a default user
        $user    = User::get_from_username(Session::username($input['auth']));
        $user_id = $user->id;
        // override your user if you're looking at others
        if ($input['username']) {
            $user    = User::get_from_username($input['username']);
            $user_id = $user->id;
        } elseif ($input['user_id']) {
            $user_id = (int) $input['user_id'];
            $user    = new User($user_id);
        }
        // moved type to filter and allowed multiple type selection
        $type   = $input['type'];
        $offset = (int) $input['offset'];
        $limit  = (int) $input['limit'];
        // original method only searched albums and had poor method inputs
        if (in_array($type, array('newest', 'highest', 'frequent', 'recent', 'forgotten', 'flagged'))) {
            $type            = 'album';
            $input['filter'] = $type;
        }
        if ($limit < 1) {
            $limit = AmpConfig::get('popular_threshold', 10);
        }

        $results = array();
        switch ($input['filter']) {
            case 'newest':
                debug_event('api.class', 'stats newest', 5);
                $results = \Stats::get_newest($type, $limit, $offset);
                break;
            case 'highest':
                debug_event('api.class', 'stats highest', 4);
                $results = \Rating::get_highest($type, $limit, $offset);
                break;
            case 'frequent':
                debug_event('api.class', 'stats frequent', 4);
                $threshold = AmpConfig::get('stats_threshold');
                $results   = \Stats::get_top($type, $limit, $threshold, $offset);
                break;
            case 'recent':
            case 'forgotten':
                debug_event('api.class', 'stats ' . $input['filter'], 4);
                $newest  = $input['filter'] == 'recent';
                $results = ($user->id)
                    ? $user->get_recently_played($limit, $type, $newest)
                    : \Stats::get_recent($type, $limit, $offset, $newest);
                break;
            case 'flagged':
                debug_event('api.class', 'stats flagged', 4);
                $results = \Userflag::get_latest($type, $user_id, $limit, $offset);
                break;
            case 'random':
            default:
                debug_event('api.class', 'stats random ' . $type, 4);
                switch ($type) {
                    case 'song':
                        $results = \Random::get_default($limit, $user_id);
                        break;
                    case 'artist':
                        $results = \Artist::get_random($limit, false, $user_id);
                        break;
                    case 'album':
                        $results = \Album::get_random($limit, false, $user_id);
                }
        }

        if (!empty($results)) {
            ob_end_clean();
            debug_event('api.class', 'stats found results searching for ' . $type, 5);
            if ($type === 'song') {
                switch ($input['api_format']) {
                    case 'json':
                        echo JSON_Data::songs($results, $user->id);
                        break;
                    default:
                        echo XML_Data::songs($results, $user->id);
                }
            }
            if ($type === 'artist') {
                switch ($input['api_format']) {
                    case 'json':
                        echo JSON_Data::artists($results, array(), $user->id);
                        break;
                    default:
                        echo XML_Data::artists($results, array(), $user->id);
                }
            }
            if ($type === 'album') {
                switch ($input['api_format']) {
                    case 'json':
                        echo JSON_Data::albums($results, array(), $user->id);
                        break;
                    default:
                        echo XML_Data::albums($results, array(), $user->id);
                }
            }
            Session::extend($input['auth']);

            return true;
        }
        Api::message('error', 'No Results', '404', $input['api_format']);

        return false;
    }
}
