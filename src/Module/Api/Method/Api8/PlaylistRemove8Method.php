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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Method\AbstractPlaylistRemoveMethod;
use Ampache\Repository\Model\Playlist;
use Override;

/**
 * Removes an object from a playlist by object id and type, or by track number
 *
 * This replaces playlist_remove_song and only exists in api version 8. It names the item `id`, is
 * type aware, and speaks of items rather than songs when clearing.
 */
final class PlaylistRemove8Method extends AbstractPlaylistRemoveMethod
{
    public const string ACTION = 'playlist_remove';

    public const string REST_ACTION = 'playlist_remove_edit';

    protected const string CLEARED_MESSAGE = 'all items removed from playlist';

    protected const string ITEM_KEY = 'id';

    /**
     * Reads `object_type` first, falling back to the deprecated `type` (see
     * AbstractPlaylistRemoveMethod); `type` will be removed in API9
     *
     * @param array<string, mixed> $input
     */
    #[Override]
    protected function hasItem(Playlist $playlist, int $track, array $input): bool
    {
        return $playlist->has_item($track, null, (string) ($input['object_type'] ?? $input['type'] ?? 'song'));
    }
}
