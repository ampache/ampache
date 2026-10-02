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
use Ampache\Module\Api\Jellyfin\JellyfinItemMapper;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\JellyfinUserView;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /Items/{itemId} — a single item by id, whatever its type.
 *
 * Real clients (confirmed: Finamp) fetch the synthetic UserView root by id right after listing
 * `/Views`/`/UserViews`, to render the library tab itself — a missing `'view'` case here 404s that lookup
 * and blocks every downstream tab from ever loading, even though `/Views` happily lists the same id. The
 * `'genre'` case closes the same gap for a genre id `MusicGenresMethod`/browse listings hand out.
 */
final class ItemMethod implements JellyfinMethodInterface
{
    public function __construct(private readonly JellyfinItemMapper $mapper) {}

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

        $dto = match ($type) {
            'song' => $this->songDto($id, $user),
            'album' => $this->albumDto($id, $user),
            'artist' => $this->artistDto($id, $user),
            'playlist' => $this->playlistDto($id, $user),
            'view' => JellyfinUserView::build(),
            'genre' => $this->genreDto($id, $user),
            default => null,
        };

        if ($dto === null) {
            return JellyfinResponse::notFound();
        }

        return JellyfinResponse::json($dto);
    }

    /** @return array<string, mixed>|null */
    private function albumDto(int $id, User $user): ?array
    {
        $album = new Album($id);

        return ($album->isNew() || !Catalog::has_access($album->getCatalogId(), $user->getId()))
            ? null
            : $this->mapper->mapAlbum($album, $user);
    }

    /** @return array<string, mixed>|null */
    private function artistDto(int $id, User $user): ?array
    {
        $artist = new Artist($id);

        return $artist->isNew() ? null : $this->mapper->mapArtist($artist, $user, ['Overview']);
    }

    /** @return array<string, mixed>|null */
    private function genreDto(int $id, User $user): ?array
    {
        $tag = new Tag($id);
        if ($tag->isNew() || $tag->name === null) {
            return null;
        }

        // song-tag genres are the only kind this surface has, matching MusicGenresMethod's own scope
        return $this->mapper->mapGenre(
            [
                'id' => $tag->id,
                'name' => (string) $tag->name,
                'is_hidden' => $tag->is_hidden,
                'count' => $tag->song
            ],
            $user
        );
    }

    /** @return array<string, mixed>|null */
    private function playlistDto(int $id, User $user): ?array
    {
        $playlist = new Playlist($id);
        if ($playlist->isNew() || ($playlist->type !== 'public' && !$playlist->has_collaborate($user))) {
            return null;
        }

        return $this->mapper->mapPlaylist($playlist, $user);
    }

    /** @return array<string, mixed>|null */
    private function songDto(int $id, User $user): ?array
    {
        $song = new Song($id);

        return ($song->isNew() || !Catalog::has_access($song->getCatalogId(), $user->getId()))
            ? null
            : $this->mapper->mapSong($song, $user, ['MediaSources']);
    }
}
