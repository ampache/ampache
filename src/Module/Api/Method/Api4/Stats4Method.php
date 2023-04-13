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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Statistics\Stats;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;

/**
 * Class Stats4Method
 */
final class Stats4Method
{
    public const ACTION = 'stats';

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
     * @param User $user
     * type     = (string)  'song'|'album'|'artist'
     * filter   = (string)  'newest'|'highest'|'frequent'|'recent'|'forgotten'|'flagged'|'random'
     * user_id  = (integer) //optional
     * username = (string)  //optional
     * offset   = (integer) //optional
     * limit    = (integer) //optional
     * @return boolean
     */
    public static function stats(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('type', 'filter'), self::ACTION)) {
            return false;
        }
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
            $limit = (int) AmpConfig::get('popular_threshold', 10);
        }

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
                $threshold = AmpConfig::get('stats_threshold', 7);
                $results   = Stats::get_top($type, $limit, $threshold, $offset);
                break;
            case 'recent':
            case 'forgotten':
                debug_event(self::class, 'stats ' . $input['filter'], 4);
                $newest = $input['filter'] == 'recent';
                if ($user->id) {
                    $results = $user->get_recently_played($type, $limit, $offset, $newest);
                } else {
                    $results = Stats::get_recent($type, $limit, $offset, $newest);
                }
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
                        $results = Random::get_default($limit, $user);
                        break;
                    case 'artist':
                        $results = static::getArtistRepository()->getRandom(
                            $user_id,
                            $limit
                        );
                        break;
                    case 'album':
                        $results = static::getAlbumRepository()->getRandom(
                            $user_id,
                            $limit
                        );
                }
        }

        ob_end_clean();
        if (!isset($results)) {
            Api4::message('error', 'No Results', '404', $input['api_format']);

            return false;
        }

        debug_event(self::class, 'stats found results searching for ' . $type, 5);
        if ($type === 'song') {
            switch ($input['api_format']) {
                case 'json':
                    echo Json4_Data::songs($results, $user);
                    break;
                default:
                    echo Xml4_Data::songs($results, $user);
            }
        }
        if ($type === 'artist') {
            switch ($input['api_format']) {
                case 'json':
                    echo Json4_Data::artists($results, array(), $user);
                    break;
                default:
                    echo Xml4_Data::artists($results, array(), $user);
            }
        }
        if ($type === 'album') {
            switch ($input['api_format']) {
                case 'json':
                    echo Json4_Data::albums($results, array(), $user);
                    break;
                default:
                    echo Xml4_Data::albums($results, array(), $user);
            }
        }

        return true;
    } // stats

    /**
     * @deprecated Inject by constructor
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getArtistRepository(): ArtistRepositoryInterface
    {
        global $dic;

        return $dic->get(ArtistRepositoryInterface::class);
    }
}
