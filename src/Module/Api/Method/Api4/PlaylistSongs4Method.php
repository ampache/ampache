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

use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Session;

/**
 * Class PlaylistSongs4Method
 */
final class PlaylistSongs4Method
{
    public const ACTION = 'playlist_songs';

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
    public static function playlist_songs(array $input): bool
    {
        if (!Api4::check_parameter($input, array('filter'), 'playlist_songs')) {
            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        $uid  = scrub_in($input['filter']);
        debug_event(self::class, 'User ' . $user->id . ' loading playlist: ' . $input['filter'], 5);
        if (str_replace('smart_', '', $uid) === $uid) {
            // Playlists
            $playlist = new Playlist((int) $uid);
        } else {
            // Smartlists
            $playlist = new Search((int) str_replace('smart_', '', $uid), 'song', $user);
        }
        if (!$playlist->type == 'public' && (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id))) {
            Api4::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);

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
                Json4_Data::set_offset($input['offset'] ?? 0);
                Json4_Data::set_limit($input['limit'] ?? 0);
                echo Json4_Data::songs($songs, $user->id);
            break;
            default:
                Xml4_Data::set_offset($input['offset'] ?? 0);
                Xml4_Data::set_limit($input['limit'] ?? 0);
                echo Xml4_Data::songs($songs, $user->id);
        }

        return true;
    } // playlist_songs
}
