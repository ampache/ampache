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

namespace Ampache\Module\Api\Jellyfin\Method\UserData;

use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinItemMapper;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST/DELETE /UserPlayedItems/{itemId} (+ legacy /Users/{userId}/PlayedItems/{itemId}) — songs only, since
 * `Song::update_played()` is a global (not per-user) flag; there's no equivalent for album/artist/playlist.
 */
final class PlayedMethod implements JellyfinMethodInterface
{
    public function __construct(private readonly JellyfinItemMapper $mapper) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $itemId = (string) ($request->getAttribute('itemId') ?? '');
        if (!JellyfinId::isType($itemId, 'song')) {
            return JellyfinResponse::notFound();
        }

        $songId = JellyfinId::decodeId($itemId);
        $song   = ($songId !== null) ? new Song($songId) : null;
        if ($song === null || $song->isNew()) {
            return JellyfinResponse::notFound();
        }

        Song::update_played(strtoupper($request->getMethod()) === 'POST', $song->id);
        $song->played = strtoupper($request->getMethod()) === 'POST';

        return JellyfinResponse::json($this->mapper->mapUserData('song', $song->id, $user, $song));
    }
}
