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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;

/**
 * Class PlaylistRemoveSong4Method
 */
final class PlaylistRemoveSong4Method
{
    public const ACTION = 'playlist_remove_song';

    /**
     * playlist_remove_song
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400001
     * CHANGED_IN_API_VERSION=420000
     *
     * This removes a song from a playlist using track number in the list or song ID.
     * Pre-400001 the api required 'track' instead of 'song'.
     * 420000+: added clear to allow you to clear a playlist without getting all the tracks.
     *
     * filter = (string) UID of playlist
     * song   = (string) UID of song to remove from the playlist //optional
     * track  = (string) track number to remove from the playlist //optional
     * clear  = (integer) 0,1 Clear the whole playlist //optional, default = 0
     */
    public static function playlist_remove_song(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $playlist = new Playlist($input['filter']);
        if (!$playlist->has_access($user->id) && $user->access !== 100) {
            Api4::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);
        } else {
            if (array_key_exists('clear', $input) && (int)$input['clear'] === 1) {
                $playlist->delete_all();
                Api4::message('success', 'all songs removed from playlist', null, $input['api_format']);
            } elseif (array_key_exists('song', $input)) {
                $track = (int) scrub_in((string) $input['song']);
                if (!$playlist->has_item($track)) {
                    Api4::message('error', T_('Song not found in playlist'), '404', $input['api_format']);

                    return false;
                }
                $playlist->delete_song($track);
                $playlist->regenerate_track_numbers();
                Api4::message('success', 'song removed from playlist', null, $input['api_format']);
            } elseif (array_key_exists('track', $input)) {
                $track = (int) scrub_in((string) $input['track']);
                if (!$playlist->has_item(null, $track)) {
                    Api4::message('error', T_('Track ID not found in playlist'), '404', $input['api_format']);

                    return false;
                }
                $playlist->delete_track_number($track);
                $playlist->regenerate_track_numbers();
                Api4::message('success', 'song removed from playlist', null, $input['api_format']);
            }
        }

        return true;
    }
}
