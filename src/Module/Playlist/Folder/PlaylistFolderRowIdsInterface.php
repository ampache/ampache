<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Playlist\Folder;

use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;

/**
 * Shared by `PlaylistFolderAction` and `RefreshPlaylistFolderAction`, so the initial render and a drag-to-reorder
 * refresh always build the same row order from the same rule.
 */
interface PlaylistFolderRowIdsInterface
{
    /**
     * The subfolders of $folder interleaved with the playlists and smartlists filed in it, in `sort_order`,
     * each id encoded as `playlist_folder-N`/`playlist-N`/`search-N` for `PlaylistFolderListRenderer` to
     * split back apart.
     *
     * @return list<string>
     */
    public function getRowIds(User $user, ?PlaylistFolder $folder): array;
}
