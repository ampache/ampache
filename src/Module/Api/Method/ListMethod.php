<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;

/**
 * Class ListMethod
 * @package Lib\ApiMethods
 */
final class ListMethod
{
    public const ACTION = 'list';

    /**
     * list
     * MINIMUM_API_VERSION=6.0.0
     *
     * This takes a named array of objects and returning `id`, `name`, `prefix` and `basename`
     *
     * type        = (string) 'song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'share', 'video', 'live_stream'
     * filter      = (string) //optional
     * exact       = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add         = Api::set_filter(date) //optional
     * update      = Api::set_filter(date) //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     */
    public static function list(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('type'), self::ACTION)) {
            return false;
        }
        $album_artist = ((string)$input['type'] == 'album_artist');
        $type         = ($album_artist) ? 'artist' : (string)$input['type'];
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            Api::error(T_('Enable: video'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('podcast') && ($type == 'podcast' || $type == 'podcast_episode')) {
            Api::error(T_('Enable: podcast'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('share') && $type == 'share') {
            Api::error(T_('Enable: share'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('live_stream') && $type == 'live_stream') {
            Api::error(T_('Enable: live_stream'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        $hide = (array_key_exists('hide_search', $input) && (int)$input['hide_search'] == 1) || AmpConfig::get('hide_search', false);
        // confirm the correct data
        if (!in_array(strtolower($type), array('song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'live_stream'))) {
            Api::error(sprintf(T_('Bad Request: %s'), $type), '4710', self::ACTION, 'type', $input['api_format']);

            return false;
        }
        $browse = Api::getBrowse();
        $browse->reset_filters();
        if ($album_artist) {
            $browse->set_type('album_artist');
        } else {
            $browse->set_type($type);
        }
        $browse->set_sort('name', 'ASC');

        $method = (array_key_exists('exact', $input) && (int)$input['exact'] == 1) ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter'] ?? '', $browse);
        Api::set_filter('add', $input['add'] ?? '', $browse);
        Api::set_filter('update', $input['update'] ?? '', $browse);

        $name_type = $type;
        if ($type == 'playlist') {
            $browse->set_filter('playlist_type', $user->id);
            if (!$hide) {
                $name_type = 'playlist_search';
                $objects   = array(
                    'playlist' => $browse->get_objects(),
                    'search' => Playlist::get_smartlists($user->id)
                );
            } else {
                $objects = $browse->get_objects();
            }
        } else {
            $objects = $browse->get_objects();
        }
        if (empty($objects)) {
            Api::empty($type, $input['api_format']);

            return false;
        }

        $results = Catalog::get_name_array($objects, $name_type);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset'] ?? 0);
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::lists($results);
                break;
            default:
                Xml_Data::set_offset($input['offset'] ?? 0);
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::lists($results);
        }

        return true;
    }
}
