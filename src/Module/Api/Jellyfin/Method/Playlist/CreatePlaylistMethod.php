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
use Ampache\Module\Api\Jellyfin\JellyfinRequestBody;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /Playlists — always creates a new playlist (`existing: false`), since real Jellyfin playlists aren't
 * unique by name the way `Playlist::create()`'s default duplicate-reuse behavior assumes.
 */
final class CreatePlaylistMethod implements JellyfinMethodInterface
{
    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $query = $request->getQueryParams();
        $body  = json_decode((string) $request->getBody(), true);
        if (!is_array($body)) {
            $body = [];
        }

        $name = (string) ($query['name'] ?? JellyfinRequestBody::field($body, 'Name') ?? '');
        if ($name === '') {
            $name = 'New Playlist';
        }

        $idsRaw   = (string) ($query['ids'] ?? '');
        $bodyIds  = JellyfinRequestBody::field($body, 'Ids');
        $ids      = ($idsRaw !== '')
            ? $this->splitList($idsRaw)
            : array_map('strval', is_array($bodyIds) ? $bodyIds : []);

        $isPublic = (bool) (JellyfinRequestBody::field($body, 'IsPublic') ?? false);
        $type     = $isPublic ? 'public' : 'private';

        $playlistId = Playlist::create($name, $type, $user->getId(), false);
        if ($playlistId === null) {
            return JellyfinResponse::json(['error' => 'Could not create playlist'], 500);
        }

        $medias = [];
        foreach ($ids as $encodedId) {
            if (JellyfinId::isType($encodedId, 'song')) {
                $songId = JellyfinId::decodeId($encodedId);
                if ($songId !== null) {
                    $medias[] = ['object_type' => 'song', 'object_id' => $songId];
                }
            }
        }
        if ($medias !== []) {
            new Playlist($playlistId)->add_medias($medias);
        }

        return JellyfinResponse::json(['Id' => JellyfinId::encode('playlist', $playlistId)]);
    }

    /** @return list<string> */
    private function splitList(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value)), static fn(string $item): bool => $item !== ''));
    }
}
