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
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /Playlists/{playlistId} (narrow `PlaylistDto` — name/art/etc. come from `GET /Items/{id}` instead) and
 * POST /Playlists/{playlistId} (rename and/or replace the full song list; both fields are optional).
 */
final class PlaylistMethod implements JellyfinMethodInterface
{
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
        if ($playlist->type !== 'public' && !$playlist->has_collaborate($user)) {
            return JellyfinResponse::forbidden();
        }

        return (strtoupper($request->getMethod()) === 'POST')
            ? $this->update($request, $playlist, $user)
            : $this->fetch($playlist);
    }

    private function fetch(Playlist $playlist): JellyfinResponse
    {
        $itemIds = [];
        foreach ($playlist->get_items() as $row) {
            if ($row['object_type'] !== LibraryItemEnum::SONG) {
                continue;
            }
            $itemIds[] = JellyfinId::encode('song', $row['object_id']);
        }

        return JellyfinResponse::json([
            'OpenAccess' => $playlist->type === 'public',
            'Shares' => [],
            'ItemIds' => $itemIds,
        ]);
    }

    private function update(ServerRequestInterface $request, Playlist $playlist, User $user): JellyfinResponse
    {
        $body = json_decode((string) $request->getBody(), true);
        if (!is_array($body)) {
            $body = [];
        }

        $name = JellyfinRequestBody::field($body, 'Name');
        $ids  = JellyfinRequestBody::field($body, 'Ids');

        // renaming is owner/admin-only, matching native PlaylistEditMethod; replacing the track list is a
        // collaborator-level operation, matching PlaylistItemsMethod/PlaylistItemMoveMethod
        if ($name !== null) {
            if (!$playlist->has_access($user)) {
                return JellyfinResponse::forbidden();
            }

            $playlist->update(['name' => (string) $name]);
        }

        if (is_array($ids)) {
            if (!$playlist->has_collaborate($user)) {
                return JellyfinResponse::forbidden();
            }

            foreach ($playlist->get_items() as $row) {
                if ($row['object_type'] === LibraryItemEnum::SONG) {
                    $playlist->delete_song($row['object_id']);
                }
            }

            $medias = [];
            foreach ($ids as $encodedId) {
                if (is_string($encodedId) && JellyfinId::isType($encodedId, 'song')) {
                    $songId = JellyfinId::decodeId($encodedId);
                    if ($songId !== null) {
                        $medias[] = ['object_type' => 'song', 'object_id' => $songId];
                    }
                }
            }
            if ($medias !== []) {
                $playlist->add_medias($medias);
            }
        }

        return JellyfinResponse::noContent();
    }
}
