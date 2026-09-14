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

namespace Ampache\Module\Api\Jellyfin\Method\Playlist;

use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /Playlists/{playlistId}/Items/{itemId}/Move/{newIndex} — `newIndex` is 0-based per the real API;
 * Ampache's track numbers are 1-based, so the stored position is always `newIndex + 1`.
 */
final class PlaylistItemMoveMethod implements JellyfinMethodInterface
{
    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $playlistItemId = (string) ($request->getAttribute('playlistId') ?? '');
        $itemId         = (string) ($request->getAttribute('itemId') ?? '');
        $newIndex       = (int) ($request->getAttribute('newIndex') ?? -1);
        if (!JellyfinId::isType($playlistItemId, 'playlist') || !JellyfinId::isType($itemId, 'song') || $newIndex < 0) {
            return JellyfinResponse::notFound();
        }

        $playlistId = JellyfinId::decodeId($playlistItemId);
        $songId     = JellyfinId::decodeId($itemId);
        $playlist   = ($playlistId !== null) ? new Playlist($playlistId) : null;
        if ($playlist === null || $playlist->isNew() || $songId === null) {
            return JellyfinResponse::notFound();
        }

        if (!$playlist->has_collaborate($user)) {
            return JellyfinResponse::forbidden();
        }

        $playlist->set_by_track_number($songId, $newIndex + 1);

        return JellyfinResponse::noContent();
    }
}
