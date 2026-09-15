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
use Ampache\Module\Api\Jellyfin\JellyfinItemMapper;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET/POST/DELETE /Playlists/{playlistId}/Items. `entryIds` (DELETE) is treated as a plain song item id in
 * v1 — real per-entry ids only matter for a playlist holding the same song twice (`unique_playlist` gates).
 */
final class PlaylistItemsMethod implements JellyfinMethodInterface
{
    public function __construct(private readonly JellyfinItemMapper $mapper) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $itemId = (string) ($request->getAttribute('playlistId') ?? '');
        if (!JellyfinId::isType($itemId, 'playlist')) {
            return JellyfinResponse::notFound();
        }

        $playlistId = JellyfinId::decodeId($itemId);
        $playlist   = ($playlistId !== null) ? new Playlist($playlistId) : null;
        if ($playlist === null || $playlist->isNew()) {
            return JellyfinResponse::notFound();
        }

        return match (strtoupper($request->getMethod())) {
            'POST' => $this->add($request, $playlist, $user),
            'DELETE' => $this->remove($request, $playlist, $user),
            default => $this->list($playlist, $request, $user),
        };
    }

    private function add(ServerRequestInterface $request, Playlist $playlist, User $user): JellyfinResponse
    {
        if (!$playlist->has_collaborate($user)) {
            return JellyfinResponse::forbidden();
        }

        $medias = [];
        foreach ($this->splitList((string) ($request->getQueryParams()['ids'] ?? '')) as $encodedId) {
            if (JellyfinId::isType($encodedId, 'song')) {
                $songId = JellyfinId::decodeId($encodedId);
                if ($songId !== null) {
                    $medias[] = ['object_type' => 'song', 'object_id' => $songId];
                }
            }
        }
        if ($medias !== []) {
            $playlist->add_medias($medias);
        }

        return JellyfinResponse::noContent();
    }

    private function list(Playlist $playlist, ServerRequestInterface $request, User $user): JellyfinResponse
    {
        if ($playlist->type !== 'public' && !$playlist->has_collaborate($user)) {
            return JellyfinResponse::forbidden();
        }

        $query      = $request->getQueryParams();
        $startIndex = max(0, (int) ($query['startIndex'] ?? 0));
        $limitParam = (string) ($query['limit'] ?? '');
        $limit      = ($limitParam !== '') ? (int) $limitParam : 0;

        $songIds = array_values(array_map(
            static fn(array $row): int => (int) $row['object_id'],
            array_filter($playlist->get_items(), static fn(array $row): bool => $row['object_type'] === LibraryItemEnum::SONG),
        ));
        Song::build_cache($songIds);
        Rating::build_cache('song', $songIds);
        Userflag::build_cache('song', $songIds);

        $songs = [];
        foreach ($songIds as $songId) {
            $songs[] = $this->mapper->mapSong(new Song($songId), $user, []);
        }

        $total = count($songs);
        $songs = ($limit > 0) ? array_slice($songs, $startIndex, $limit) : array_slice($songs, $startIndex);

        return JellyfinResponse::json([
            'Items' => $songs,
            'TotalRecordCount' => $total,
            'StartIndex' => $startIndex,
        ]);
    }

    private function remove(ServerRequestInterface $request, Playlist $playlist, User $user): JellyfinResponse
    {
        if (!$playlist->has_collaborate($user)) {
            return JellyfinResponse::forbidden();
        }

        foreach ($this->splitList((string) ($request->getQueryParams()['entryIds'] ?? '')) as $encodedId) {
            if (JellyfinId::isType($encodedId, 'song')) {
                $songId = JellyfinId::decodeId($encodedId);
                if ($songId !== null) {
                    $playlist->delete_song($songId);
                }
            }
        }

        return JellyfinResponse::noContent();
    }

    /** @return list<string> */
    private function splitList(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value)), static fn(string $item): bool => $item !== ''));
    }
}
