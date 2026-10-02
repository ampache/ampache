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

namespace Ampache\Module\Api\Jellyfin\Method\Items;

use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * DELETE /Items/{itemId} — the spec describes this generically ("deletes an item from the library and
 * filesystem"), but the only real use found (confirmed: gelly's "Delete Playlist" action) is deleting a
 * playlist the caller owns. Destructive song/album file deletion is out of scope until a real client is
 * found to need it — this only handles the playlist case, matching native `DeletePlaylistAction`'s own
 * owner-or-admin gate, and 403s anything else rather than silently doing nothing.
 */
final class ItemDeleteMethod implements JellyfinMethodInterface
{
    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $itemId = (string) ($request->getAttribute('itemId') ?? '');
        $type   = JellyfinId::decodeType($itemId);
        $id     = JellyfinId::decodeId($itemId);
        if ($type === null || $id === null) {
            return JellyfinResponse::notFound();
        }

        if ($type !== 'playlist') {
            return JellyfinResponse::forbidden();
        }

        $playlist = new Playlist($id);
        if ($playlist->isNew()) {
            return JellyfinResponse::notFound();
        }
        if (!$playlist->has_access($user)) {
            return JellyfinResponse::forbidden();
        }

        $playlist->delete();

        return JellyfinResponse::noContent();
    }
}
