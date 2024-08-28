<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
     * type     = (string)  'song'|'album'|'artist'
     * filter   = (string)  'newest'|'highest'|'frequent'|'recent'|'forgotten'|'flagged'|'random'
     * user_id  = (integer) //optional
     * username = (string)  //optional
     * offset   = (integer) //optional
     * limit    = (integer) //optional
     */
    public static function stats(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, ['type', 'filter'], self::ACTION)) {
            return false;
        }
        $user_id = $user->id;
        // override your user if you're looking at others
        if (array_key_exists('username', $input) && User::get_from_username($input['username'])) {
            $user    = User::get_from_username($input['username']);
            $user_id = $user->id;
        } elseif (array_key_exists('user_id', $input)) {
            $userTwo = new User($user_id);
            if (!$userTwo->isNew()) {
                $user_id = (int)$input['user_id'];
                $user    = new User($user_id);
            }
        }
        // moved type to filter and allowed multiple type selection
        $type   = $input['type'];
        $offset = (int)($input['offset'] ?? 0);
        $limit  = (int)($input['limit'] ?? 0);
        // original method only searched albums and had poor method inputs
        if (in_array($type, ['newest', 'highest', 'frequent', 'recent', 'forgotten', 'flagged'])) {
            $type            = 'album';
            $input['filter'] = $type;
        }
        if ($limit < 1) {
            $limit = (int) AmpConfig::get('popular_threshold', 10);
        }

        switch ($input['filter']) {
            case 'newest':
                $results = Stats::get_newest($type, $limit, $offset);
                break;
            case 'highest':
                $results = Rating::get_highest($type, $limit, $offset);
                break;
            case 'frequent':
                $threshold = AmpConfig::get('stats_threshold', 7);
                $results   = Stats::get_top($type, $limit, $threshold, $offset);
                break;
            case 'recent':
            case 'forgotten':
                $newest = $input['filter'] == 'recent';
                if ($user->isNew()) {
                    $results = Stats::get_recent($type, $limit, $offset, $newest);
                } else {
                    $results = $user->get_recently_played($type, $limit, $offset, $newest);
                }
                break;
            case 'flagged':
                $results = Userflag::get_latest($type, $user_id, $limit, $offset);
                break;
            case 'random':
            default:
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
                    echo Json4_Data::artists($results, [], $user);
                    break;
                default:
                    echo Xml4_Data::artists($results, [], $user);
            }
        }
        if ($type === 'album') {
            switch ($input['api_format']) {
                case 'json':
                    echo Json4_Data::albums($results, [], $user);
                    break;
                default:
                    echo Xml4_Data::albums($results, [], $user);
            }
        }

        return true;
    }

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
