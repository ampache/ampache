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
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\System\Session;

/**
 * Class PlaylistsMethod
 * @package Lib\ApiMethods
 */
final class PlaylistsMethod
{
    const ACTION = 'playlists';

    /**
     * playlists
     * MINIMUM_API_VERSION=380001
     *
     * This returns playlists based on the specified filter
     *
     * @param array $input
     * filter      = (string) Alpha-numeric search term (match all if missing) //optional
     * exact       = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add         = Api::set_filter(date) //optional
     * update      = Api::set_filter(date) //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     * show_dupes  = (integer) 0,1, if true ignore 'api_hide_dupe_searches' setting //optional
     * @return boolean
     */
    public static function playlists(array $input): bool
    {
        $user       = User::get_from_username(Session::username($input['auth']));
        $like       = !((array_key_exists('', $input) && (int)$input['exact'] == 1));
        $hide       = (array_key_exists('hide_search', $input) && (int)$input['hide_search'] == 1) || AmpConfig::get('hide_search', false);
        $filter     = (string)($input['filter'] ?? '');
        $show_dupes = (bool)($input['show_dupes'] ?? false);

        // regular playlists
        $playlists = Playlist::get_playlists($user->id, $filter, $like, true, $show_dupes);
        // merge with the smartlists
        if (!$hide) {
            $searches  = Playlist::get_smartlists($user->id, $filter, true, $show_dupes);
            $playlists = array_merge($playlists, $searches);
        }
        if (empty($playlists)) {
            Api::empty('playlist', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset'] ?? 0);
                Json_Data::set_limit($input['limit'] ?? 0);
                echo Json_Data::playlists($playlists, $user->id);
                break;
            default:
                Xml_Data::set_offset($input['offset'] ?? 0);
                Xml_Data::set_limit($input['limit'] ?? 0);
                echo Xml_Data::playlists($playlists, $user->id);
        }

        return true;
    }
}
