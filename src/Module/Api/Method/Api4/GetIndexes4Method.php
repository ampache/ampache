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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\System\Session;

/**
 * Class GetIndexes4Method
 */
final class GetIndexes4Method
{
    public const ACTION = 'get_indexes';

    /**
     * get_indexes
     * MINIMUM_API_VERSION=400001
     *
     * This takes a collection of inputs and returns ID + name for the object type
     *
     * @param array $input
     * type        = (string) 'song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'share', 'video'
     * filter      = (string) //optional
     * exact       = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add         = Api::set_filter(date) //optional
     * update      = Api::set_filter(date) //optional
     * include     = (integer) 0,1 include songs if available for that object //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     * @return boolean
     */
    public static function get_indexes(array $input): bool
    {
        if (!Api4::check_parameter($input, array('type'), 'get_indexes')) {
            return false;
        }
        $type = ((string) $input['type'] == 'album_artist') ? 'artist' : (string) $input['type'];
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api4::message('error', T_('Access Denied: allow_video is not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('podcast') && ($type == 'podcast' || $type == 'podcast_episode')) {
            Api4::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('share') && $type == 'share') {
            Api4::message('error', T_('Access Denied: sharing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        $user    = User::get_from_username(Session::username($input['auth']));
        $include = (array_key_exists('include', $input) && (int)$input['include'] == 1);
        $hide    = (array_key_exists('hide_search', $input) && (int)$input['hide_search'] == 1) || AmpConfig::get('hide_search', false);
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'share', 'video'))) {
            Api4::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return false;
        }
        $browse = Api4::getBrowse();
        $browse->reset_filters();
        $browse->set_type($type);
        $browse->set_sort('name', 'ASC');

        $method = (array_key_exists('exact', $input) && (int)$input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter'] ?? '', $browse);
        Api::set_filter('add', $input['add'] ?? '', $browse);
        Api::set_filter('update', $input['update'] ?? '', $browse);
        // set the album_artist filter (if enabled)
        if ((string) $input['type'] == 'album_artist') {
            Api::set_filter('album_artist', true, $browse);
        }

        if ($type == 'playlist') {
            $browse->set_filter('playlist_type', $user->id);
            if (!$hide) {
                $objects = array_merge($browse->get_objects(), Playlist::get_smartlists($user->id));
            } else {
                $objects = $browse->get_objects();
            }
        } else {
            $objects = $browse->get_objects();
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json4_Data::set_offset($input['offset'] ?? 0);
                Json4_Data::set_limit($input['limit'] ?? 0);
                echo Json4_Data::indexes($objects, $type, $user->id, $include);
                break;
            default:
                Xml4_Data::set_offset($input['offset'] ?? 0);
                Xml4_Data::set_limit($input['limit'] ?? 0);
                echo Xml4_Data::indexes($objects, $type, $user->id, true, $include);
        }

        return true;
    } // get_indexes
}
