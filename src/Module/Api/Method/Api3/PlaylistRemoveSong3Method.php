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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;

/**
 * Class PlaylistRemoveSong3Method
 */
final class PlaylistRemoveSong3Method
{
    public const ACTION = 'playlist_remove_song';

    /**
     * playlist_remove_song
     * This remove a song from a playlist
     */
    public static function playlist_remove_song(array $input, User $user): void
    {
        unset($user);
        ob_end_clean();
        $playlist = new Playlist($input['filter']);
        $track    = (int) scrub_in((string) $input['track']);
        if (!$playlist->has_access()) {
            echo Xml3_Data::error(401, T_('Access denied to this playlist.'));
        } else {
            $playlist->delete_track_number($track);
            echo Xml3_Data::single_string('success');
        }
    }
}
