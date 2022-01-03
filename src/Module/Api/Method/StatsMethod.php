<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Session;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;

/**
 * Class StatsMethod
 * @package Lib\ApiMethods
 */
final class StatsMethod
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
     * @param array $input
     * type     = (string)  'song', 'album', 'artist', 'video', 'playlist', 'podcast', 'podcast_episode'
     * filter   = (string)  'newest', 'highest', 'frequent', 'recent', 'forgotten', 'flagged', 'random' (Default: random) //optional
     * user_id  = (integer) //optional
     * username = (string)  //optional
     * offset   = (integer) //optional
     * limit    = (integer) //optional
     * @return boolean
     */
    public static function stats(array $input): bool
    {
        if (!Api::check_parameter($input, array('type'), self::ACTION)) {
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
            Api::error(T_('Enable: video'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('podcast') && ($type == 'podcast' || $type == 'podcast_episode')) {
            Api::error(T_('Enable: podcast'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'video', 'playlist', 'podcast', 'podcast_episode'))) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(T_('Bad Request'), '4710', self::ACTION, $type, $input['api_format']);

            return false;
        }

        // set a default user
        $user    = User::get_from_username(Session::username($input['auth']));
        $user_id = $user->id;
        // override your user if you're looking at others
        if (array_key_exists('username', $input)) {
            $user    = User::get_from_username($input['username']);
            $user_id = $user->id;
        } elseif (array_key_exists('user_id', $input)) {
            $user_id = (int) $input['user_id'];
            $user    = new User($user_id);
        }

        $results = array();
        $filter  = $input['filter'] ?? '';
        switch ($filter) {
            case 'newest':
                debug_event(self::class, 'stats newest', 5);
                $results = Stats::get_newest($type, $limit, $offset, 0, $user_id);
                $offset  = 0;
                $limit   = 0;
                break;
            case 'highest':
                debug_event(self::class, 'stats highest', 4);
                $results = Rating::get_highest($type, $limit, $offset, $user_id);
                $offset  = 0;
                $limit   = 0;
                break;
            case 'frequent':
                debug_event(self::class, 'stats frequent', 4);
                $threshold = (int)AmpConfig::get('stats_threshold', 7);
                $results   = Stats::get_top($type, $limit, $threshold, $offset);
                $offset    = 0;
                $limit     = 0;
                break;
            case 'recent':
            case 'forgotten':
                debug_event(self::class, 'stats ' . $filter, 4);
                $newest  = $filter == 'recent';
                $results = ($user->id)
                    ? $user->get_recently_played($type, $limit, $offset, $newest)
                    : Stats::get_recent($type, $limit, $offset, $newest);
                $offset = 0;
                $limit  = 0;
                break;
            case 'flagged':
                debug_event(self::class, 'stats flagged', 4);
                $results = Userflag::get_latest($type, $user_id, $limit, $offset);
                $offset  = 0;
                $limit   = 0;
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
                        break;
                    case 'playlist':
                        $playlists = Playlist::get_playlists($user_id, '', true, true, false);
                        $searches  = Playlist::get_smartlists($user_id, '', true, false);
                        $results   = array_merge($playlists, $searches);
                        shuffle($results);
                        break;
                    case 'video':
                    case 'podcast':
                    case 'podcast_episode':
                        $browse = Api::getBrowse();
                        $browse->reset_filters();
                        $browse->set_type($type);
                        $browse->set_sort('random');
                        $results = $browse->get_objects();
                }
        }
        if (empty($results)) {
            Api::empty($type, $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($type) {
            case 'song':
                switch ($input['api_format']) {
                    case 'json':
                        Json_Data::set_offset($offset);
                        Json_Data::set_limit($limit);
                        echo Json_Data::songs($results, $user->id);
                        break;
                    default:
                        XML_Data::set_offset($offset);
                        XML_Data::set_limit($limit);
                        echo Xml_Data::songs($results, $user->id);
                }
                break;
            case 'artist':
                switch ($input['api_format']) {
                    case 'json':
                        Json_Data::set_offset($offset);
                        Json_Data::set_limit($limit);
                        echo Json_Data::artists($results, array(), $user->id);
                        break;
                    default:
                        XML_Data::set_offset($offset);
                        XML_Data::set_limit($limit);
                        echo Xml_Data::artists($results, array(), $user->id);
                }
                break;
            case 'album':
                switch ($input['api_format']) {
                    case 'json':
                        Json_Data::set_offset($offset);
                        Json_Data::set_limit($limit);
                        echo Json_Data::albums($results, array(), $user->id);
                        break;
                    default:
                        XML_Data::set_offset($offset);
                        XML_Data::set_limit($limit);
                        echo Xml_Data::albums($results, array(), $user->id);
                }
                break;
            case 'playlist':
                switch ($input['api_format']) {
                    case 'json':
                        Json_Data::set_offset($offset);
                        Json_Data::set_limit($limit);
                        echo Json_Data::playlists($results, $user->id);
                        break;
                    default:
                        XML_Data::set_offset($offset);
                        XML_Data::set_limit($limit);
                        echo Xml_Data::playlists($results, $user->id);
                }
                break;
            case 'video':
                switch ($input['api_format']) {
                    case 'json':
                        Json_Data::set_offset($offset);
                        Json_Data::set_limit($limit);
                        echo Json_Data::videos($results, $user->id);
                        break;
                    default:
                        Xml_Data::set_offset($offset);
                        Xml_Data::set_limit($limit);
                        echo Xml_Data::videos($results, $user->id);
                }
                Session::extend($input['auth']);
                break;
            case 'podcast':
                switch ($input['api_format']) {
                    case 'json':
                        Json_Data::set_offset($offset);
                        Json_Data::set_limit($limit);
                        echo Json_Data::podcasts($results, $user->id);
                        break;
                    default:
                        XML_Data::set_offset($offset);
                        XML_Data::set_limit($limit);
                        echo XML_Data::podcasts($results, $user->id);
                }
                break;
            case 'podcast_episode':
                switch ($input['api_format']) {
                    case 'json':
                        Json_Data::set_offset($offset);
                        Json_Data::set_limit($limit);
                        echo Json_Data::podcast_episodes($results, $user->id);
                        break;
                    default:
                        XML_Data::set_offset($offset);
                        XML_Data::set_limit($limit);
                        echo XML_Data::podcast_episodes($results, $user->id);
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
