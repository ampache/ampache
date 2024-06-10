<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Repository\Model\Playlist;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Repository\Model\User;

/**
 * Class PlaylistSongs3Method
 */
final class PlaylistSongs3Method
{
    public const ACTION = 'playlist_songs';

    /**
     * playlist_songs
     * This returns the songs for a playlist
     */
    public static function playlist_songs(array $input, User $user): void
    {
        $playlist = new Playlist($input['filter']);
        $items    = $playlist->get_items();

        $results = array();
        foreach ($items as $object) {
            if ($object['object_type'] == 'song') {
                $results[] = $object['object_id'];
            }
        } // end foreach

        Xml3_Data::set_offset($input['offset'] ?? 0);
        Xml3_Data::set_limit($input['limit'] ?? 0);
        ob_end_clean();
        echo Xml3_Data::songs($results, $user, $items);
    }
}
