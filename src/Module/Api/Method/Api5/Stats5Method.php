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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Api5;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Session;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;

/**
 * Class Stats5Method
 */
final class Stats5Method
{
    public const ACTION = 'stats';

    /**
     * stats
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * Get some items based on some simple search types and filters. (Random by default)
     * This method HAD partial backwards compatibility with older api versions but it has now been removed
     *
     * type     = (string)  'song', 'album', 'artist', 'video', 'playlist', 'podcast', 'podcast_episode'
     * filter   = (string)  'newest', 'highest', 'frequent', 'recent', 'forgotten', 'flagged', 'random' (Default: random) //optional
     * user_id  = (integer) //optional
     * username = (string)  //optional
     * offset   = (integer) //optional
     * limit    = (integer) Default: 10 (popular_threshold) //optional
     *
     * @param array{
     *     type: string,
     *     filter?: string,
     *     user_id?: int,
     *     username?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function stats(array $input, User $user): bool
    {
        if (!Api5::check_parameter($input, ['type'], self::ACTION)) {
            return false;
        }
        $type   = (string) $input['type'];
        $offset = (int) ($input['offset'] ?? 0);
        $limit  = (int) ($input['limit'] ?? 0);
        if ($limit < 1) {
            $limit = (int)AmpConfig::get('popular_threshold', 10);
        }
        // do you allow video?
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api5::error(T_('Enable: video'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('podcast') && ($type == 'podcast' || $type == 'podcast_episode')) {
            Api5::error(T_('Enable: podcast'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'album', 'artist', 'video', 'playlist', 'podcast', 'podcast_episode'])) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api5::error(sprintf(T_('Bad Request: %s'), $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

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

        $results = [];
        $filter  = $input['filter'] ?? '';
        switch ($filter) {
            case 'newest':
                $results = Stats::get_newest($type, $limit, $offset, 0, $user);
                $offset  = 0;
                $limit   = 0;
                break;
            case 'highest':
                $results = Rating::get_highest($type, $limit, $offset, $user_id);
                $offset  = 0;
                $limit   = 0;
                break;
            case 'frequent':
                $threshold = (int)AmpConfig::get('stats_threshold', 7);
                $results   = Stats::get_top($type, $limit, $threshold, $offset);
                $offset    = 0;
                $limit     = 0;
                break;
            case 'recent':
            case 'forgotten':
                $newest  = $filter == 'recent';
                $results = ($user->id)
                    ? $user->get_recently_played($type, $limit, $offset, $newest)
                    : Stats::get_recent($type, $limit, $offset, null, $newest);
                $offset = 0;
                $limit  = 0;
                break;
            case 'flagged':
                $results = Userflag::get_latest($type, $user, $limit, $offset);
                $offset  = 0;
                $limit   = 0;
                break;
            case 'random':
            default:
                switch ($type) {
                    case 'song':
                        $results = Random::get_default($limit, $user);
                        break;
                    case 'artist':
                        $results = self::getArtistRepository()->getRandom(
                            $user_id,
                            $limit
                        );
                        break;
                    case 'album':
                        $results = self::getAlbumRepository()->getRandom(
                            $user_id,
                            $limit
                        );
                        break;
                    case 'playlist':
                        $browse = Api::getBrowse($user);
                        $browse->set_type('playlist_search');
                        $browse->set_sort('rand');
                        $browse->set_filter('playlist_open', $user->getId());

                        $hide_string = str_replace('%', '\%', str_replace('_', '\_', (string)Preference::get_by_user($user->getId(), 'api_hidden_playlists')));
                        if (!empty($hide_string)) {
                            $browse->set_filter('not_starts_with', $hide_string);
                        }

                        $results = $browse->get_objects();
                        break;
                    case 'video':
                    case 'podcast':
                    case 'podcast_episode':
                        $browse = Api::getBrowse($user);
                        $browse->set_type($type);
                        $browse->set_sort('rand');
                        $results = $browse->get_objects();
                }
        }
        if (empty($results)) {
            Api5::empty($type, $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($type) {
            case 'song':
                switch ($input['api_format']) {
                    case 'json':
                        Json5_Data::set_offset($offset);
                        Json5_Data::set_limit($limit);
                        echo Json5_Data::songs($results, $user);
                        break;
                    default:
                        Xml5_Data::set_offset($offset);
                        Xml5_Data::set_limit($limit);
                        echo Xml5_Data::songs($results, $user);
                }
                break;
            case 'artist':
                switch ($input['api_format']) {
                    case 'json':
                        Json5_Data::set_offset($offset);
                        Json5_Data::set_limit($limit);
                        echo Json5_Data::artists($results, [], $user);
                        break;
                    default:
                        Xml5_Data::set_offset($offset);
                        Xml5_Data::set_limit($limit);
                        echo Xml5_Data::artists($results, [], $user);
                }
                break;
            case 'album':
                switch ($input['api_format']) {
                    case 'json':
                        Json5_Data::set_offset($offset);
                        Json5_Data::set_limit($limit);
                        echo Json5_Data::albums($results, [], $user);
                        break;
                    default:
                        Xml5_Data::set_offset($offset);
                        Xml5_Data::set_limit($limit);
                        echo Xml5_Data::albums($results, [], $user);
                }
                break;
            case 'playlist':
                switch ($input['api_format']) {
                    case 'json':
                        Json5_Data::set_offset($offset);
                        Json5_Data::set_limit($limit);
                        echo Json5_Data::playlists($results, $user);
                        break;
                    default:
                        Xml5_Data::set_offset($offset);
                        Xml5_Data::set_limit($limit);
                        echo Xml5_Data::playlists($results, $user);
                }
                break;
            case 'video':
                switch ($input['api_format']) {
                    case 'json':
                        Json5_Data::set_offset($offset);
                        Json5_Data::set_limit($limit);
                        echo Json5_Data::videos($results, $user);
                        break;
                    default:
                        Xml5_Data::set_offset($offset);
                        Xml5_Data::set_limit($limit);
                        echo Xml5_Data::videos($results, $user);
                }
                Session::extend($input['auth'], AccessTypeEnum::API->value);
                break;
            case 'podcast':
                switch ($input['api_format']) {
                    case 'json':
                        Json5_Data::set_offset($offset);
                        Json5_Data::set_limit($limit);
                        echo Json5_Data::podcasts($results, $user);
                        break;
                    default:
                        Xml5_Data::set_offset($offset);
                        Xml5_Data::set_limit($limit);
                        echo Xml5_Data::podcasts($results, $user);
                }
                break;
            case 'podcast_episode':
                switch ($input['api_format']) {
                    case 'json':
                        Json5_Data::set_offset($offset);
                        Json5_Data::set_limit($limit);
                        echo Json5_Data::podcast_episodes($results, $user);
                        break;
                    default:
                        Xml5_Data::set_offset($offset);
                        Xml5_Data::set_limit($limit);
                        echo Xml5_Data::podcast_episodes($results, $user);
                }
                break;
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
