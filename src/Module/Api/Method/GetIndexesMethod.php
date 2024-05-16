<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class GetIndexesMethod
 * @package Lib\ApiMethods
 */
final class GetIndexesMethod
{
    public const ACTION = 'get_indexes';

    /**
     * get_indexes
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * This takes a collection of inputs and returns ID + name for the object type
     * Add 'include' to allow indexing all song tracks (enabled for xml by default)
     *
     * This method is depreciated and will be removed in **API7** (Use list)
     *
     * type        = (string) 'song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'share', 'video', 'live_stream'
     * filter      = (string) //optional
     * exact       = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add         = Api::set_filter(date) //optional
     * update      = Api::set_filter(date) //optional
     * include     = (integer) 0,1 include songs if available for that object //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     */
    public static function get_indexes(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('type'), self::ACTION)) {
            return false;
        }
        $album_artist = ((string)$input['type'] == 'album_artist');
        $type         = ($album_artist) ? 'artist' : (string)$input['type'];
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api::error('Enable: video', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('podcast') && ($type == 'podcast' || $type == 'podcast_episode')) {
            Api::error('Enable: podcast', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('share') && $type == 'share') {
            Api::error('Enable: share', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('live_stream') && $type == 'live_stream') {
            Api::error('Enable: live_stream', ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        $include = (array_key_exists('include', $input) && (int)$input['include'] == 1);
        $hide    = (array_key_exists('hide_search', $input) && (int)$input['hide_search'] == 1) || AmpConfig::get('hide_search', false);
        // confirm the correct data
        if (!in_array(strtolower($type), array('song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'live_stream'))) {
            Api::error(sprintf('Bad Request: %s', $type), ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'type', $input['api_format']);

            return false;
        }
        $browse = Api::getBrowse();
        if ($type === 'playlist') {
            $browse->set_filter('playlist_type', 1);
            if ($hide === false) {
                $browse->set_type('playlist_search');
            }
        } elseif ($album_artist) {
            $browse->set_type('album_artist');
        } else {
            $browse->set_type($type);
        }
        $browse->set_sort('name', 'ASC');

        $method = (array_key_exists('exact', $input) && (int)$input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter'] ?? '', $browse);
        Api::set_filter('add', $input['add'] ?? '', $browse);
        Api::set_filter('update', $input['update'] ?? '', $browse);

        $results = $browse->get_objects();
        if (empty($results)) {
            Api::empty($type, $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset'] ?? 0);
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::indexes($results, $type, $user, $include);
                break;
            default:
                Xml_Data::set_offset($input['offset'] ?? 0);
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::indexes($results, $type, $user, true, $include);
        }

        return true;
    }
}
