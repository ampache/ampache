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

use Album;
use AmpConfig;
use Api;
use Artist;
use JSON_Data;
use Random;
use Rating;
use Session;
use Stats;
use User;
use Userflag;
use XML_Data;

/**
 * Class StatsMethod
 * @package Lib\ApiMethods
 */
final class StatsMethod
{
    private const ACTION = 'stats';

    /**
     * stats
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * Get some items based on some simple search types and filters. (Random by default)
     * This method HAD partial backwards compatibility with older api versions but it has now been removed
     *
     * @param array $input
     * type     = (string)  'song', 'album', 'artist'
     * filter   = (string)  'newest', 'highest', 'frequent', 'recent', 'forgotten', 'flagged', 'random' (Default: random) //optional
     * user_id  = (integer) //optional
     * username = (string)  //optional
     * offset   = (integer) //optional
     * limit    = (integer) //optional
     * @return boolean
     */
    public static function stats(array $input)
    {
        if (!Api::check_parameter($input, array('type'), self::ACTION)) {
            return false;
        }
        $type   = (string) $input['type'];
        $offset = (int) $input['offset'];
        $limit  = (int) $input['limit'];
        if ($limit < 1) {
            $limit = AmpConfig::get('popular_threshold', 10);
        }
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist'))) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(T_('Bad Request'), '4710', self::ACTION, $type, $input['api_format']);

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

        $results = array();
        switch ($input['filter']) {
            case 'newest':
                debug_event(self::class, 'stats newest', 5);
                $results = Stats::get_newest($type, $limit, $offset);
                break;
            case 'highest':
                debug_event(self::class, 'stats highest', 4);
                $results = Rating::get_highest($type, $limit, $offset);
                break;
            case 'frequent':
                debug_event(self::class, 'stats frequent', 4);
                $threshold = AmpConfig::get('stats_threshold');
                $results   = Stats::get_top($type, $limit, $threshold, $offset);
                break;
            case 'recent':
            case 'forgotten':
                debug_event(self::class, 'stats ' . $input['filter'], 4);
                $newest  = $input['filter'] == 'recent';
                $results = ($user->id)
                    ? $user->get_recently_played($limit, $type, $newest)
                    : Stats::get_recent($type, $limit, $offset, $newest);
                break;
            case 'flagged':
                debug_event(self::class, 'stats flagged', 4);
                $results = Userflag::get_latest($type, $user_id, $limit, $offset);
                break;
            case 'random':
            default:
                debug_event(self::class, 'stats random ' . $type, 4);
                switch ($type) {
                    case 'song':
                        $results = Random::get_default($limit, $user_id);
                        break;
                    case 'artist':
                        $results = Artist::get_random($limit, false, $user_id);
                        break;
                    case 'album':
                        $results = Album::get_random($limit, false, $user_id);
                }
        }
        if (empty($results)) {
            Api::empty($type, $input['api_format']);

            return false;
        }

        ob_end_clean();
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
}
