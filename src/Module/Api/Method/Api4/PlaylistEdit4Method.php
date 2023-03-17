<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Authorization\Access;

/**
 * Class PlaylistEdit4Method
 */
final class PlaylistEdit4Method
{
    public const ACTION = 'playlist_edit';

    /**
     * playlist_edit
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=400003
     *
     * This modifies name and type of playlist.
     * Changed name and type to optional and the playlist id is mandatory
     *
     * @param array $input
     * @param User $user
     * filter = (string) UID of playlist
     * name   = (string) 'new playlist name' //optional
     * type   = (string) 'public', 'private' //optional
     * items  = (string) comma-separated song_id's (replace existing items with a new object_id) //optional
     * tracks = (string) comma-separated playlisttrack numbers matched to items in order //optional
     * sort   = (integer) 0,1 sort the playlist by 'Artist, Album, Song' //optional
     * @return boolean
     */
    public static function playlist_edit(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $items = explode(',', (string)($input['items'] ?? ''));
        $order = explode(',', (string)($input['tracks'] ?? ''));
        $sort  = (int)($input['sort'] ?? 0);
        // calculate whether we are editing the track order too
        $playlist_edit = array();
        if (count($items) == count($order) && count($items) > 0) {
            $playlist_edit = array_combine($order, $items);
        }

        ob_end_clean();
        $playlist = new Playlist($input['filter']);

        // don't continue if you didn't actually get a playlist or the access level
        if (!$playlist->id || (!$playlist->has_access($user->id) && !$user->access === 100)) {
            Api4::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);

            return false;
        }
        $name = $input['name'] ?? $playlist->name;
        $type = $input['type'] ?? $playlist->type;
        // update name/type
        if ($name !== $playlist->name || $type !== $playlist->type) {
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
        // if you didn't make any changes; tell me
        if (!($name || $type) && !$change_made) {
            Api4::message('error', T_('Nothing was changed'), '401', $input['api_format']);

            return false;
        }
        Api4::message('success', 'playlist changes saved', null, $input['api_format']);

        return true;
    } // playlist_edit
}
