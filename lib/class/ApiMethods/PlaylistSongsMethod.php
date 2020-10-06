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

use Access;
use Api;
use JSON_Data;
use Session;
use User;
use XML_Data;

final class PlaylistSongsMethod
{
    /**
     * playlist_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs for a playlist
     *
     * @param array $input
     * filter = (string) UID of playlist
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function playlist_songs($input)
    {
        if (!Api::check_parameter($input, array('filter'), 'playlist_songs')) {
            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        $uid  = scrub_in($input['filter']);
        debug_event('api.class', 'User ' . $user->id . ' loading playlist: ' . $input['filter'], 5);

        $playlist = (str_replace('smart_', '', $uid) === $uid)
            ? new \Playlist((int) $uid)
            : new \Search((int) str_replace('smart_', '', $uid), 'song', $user);

        if (!$playlist->type == 'public' && (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id))) {
            Api::message('error', T_('Access denied to this playlist'), '412', $input['api_format']);

            return false;
        }

        $items = $playlist->get_items();
        $songs = array();
        foreach ($items as $object) {
            if ($object['object_type'] == 'song') {
                $songs[] = $object['object_id'];
            }
        } // end foreach

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::songs($songs, $user->id);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::songs($songs, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    }
}
