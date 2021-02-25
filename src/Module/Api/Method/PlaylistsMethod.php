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
use Ampache\Module\Authorization\Access;
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
     * add         = self::set_filter(date) //optional
     * update      = self::set_filter(date) //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     * @return boolean
     */
    public static function playlists(array $input)
    {
        $user    = User::get_from_username(Session::username($input['auth']));
        $like    = ((int) $input['exact'] == 1) ? false : true;
        $hide    = ((int) $input['hide_search'] == 1) || AmpConfig::get('hide_search', false);
        $user_id = (!Access::check('interface', 100, $user->id)) ? $user->id : -1;
        $public  = !Access::check('interface', 100, $user->id);

        // regular playlists
        $playlist_ids = Playlist::get_playlists($public, $user_id, (string) $input['filter'], $like);
        // merge with the smartlists
        if (!$hide) {
            $playlist_ids = array_merge($playlist_ids, Playlist::get_smartlists($public, $user_id, (string) $input['filter'], $like));
        }
        if (empty($playlist_ids)) {
            Api::empty('playlist', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                Json_Data::set_offset($input['offset']);
                Json_Data::set_limit($input['limit']);
                echo Json_Data::playlists($playlist_ids, $user->id);
                break;
            default:
                Xml_Data::set_offset($input['offset']);
                Xml_Data::set_limit($input['limit']);
                echo Xml_Data::playlists($playlist_ids, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    }
}
