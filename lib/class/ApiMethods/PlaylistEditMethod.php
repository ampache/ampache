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
use Playlist;
use Session;
use User;

/**
 * Class PlaylistEditMethod
 * @package Lib\ApiMethods
 */
final class PlaylistEditMethod
{
    private const ACTION = 'playlist_edit';

    /**
     * playlist_edit
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=400003
     *
     * This modifies name and type of playlist.
     * Changed name and type to optional and the playlist id is mandatory
     *
     * @param array $input
     * filter = (string) UID of playlist
     * name   = (string) 'new playlist name' //optional
     * type   = (string) 'public', 'private' //optional
     * items  = (string) comma-separated song_id's (replace existing items with a new object_id) //optional
     * tracks = (string) comma-separated playlisttrack numbers matched to items in order //optional
     * sort   = (integer) 0,1 sort the playlist by 'Artist, Album, Song' //optional
     * @return boolean
     */
    public static function playlist_edit(array $input)
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $name  = $input['name'];
        $type  = $input['type'];
        $items = explode(',', (string) $input['items']);
        $order = explode(',', (string) $input['tracks']);
        $sort  = (int) $input['sort'];
        // calculate whether we are editing the track order too
        $playlist_edit = array();
        if (count($items) == count($order) && count($items) > 0) {
            $playlist_edit = array_combine($order, $items);
        }

        $user = User::get_from_username(Session::username($input['auth']));
        ob_end_clean();
        $playlist = new Playlist($input['filter']);

        // don't continue if you didn't actually get a playlist or the access level
        if (!$playlist->id || (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id))) {
            Api::error(T_('Require: 100'), '4742', self::ACTION, 'account', $input['api_format']);

            return false;
        }
        // update name/type
        if ($name || $type) {
            $array = [
                "name" => $name,
                "pl_type" => $type,
            ];
            $playlist->update($array);
        }
        $change_made = false;
        // update track order with new id's
        if (!empty($playlist_edit)) {
            foreach ($playlist_edit as $track => $song) {
                if ($song > 0 && $track > 0) {
                    $playlist->set_by_track_number((int) $song, (int) $track);
                    $change_made = true;
                }
            }
        }
        if ($sort > 0) {
            $playlist->sort_tracks();
            $change_made = true;
        }
        Session::extend($input['auth']);
        // if you didn't make any changes; tell me
        if (!($name || $type) && !$change_made) {
            Api::error(T_('Bad Request'), '4710', self::ACTION, 'input', $input['api_format']);

            return false;
        }
        Api::message('playlist changes saved', $input['api_format']);

        return true;
    }
}
