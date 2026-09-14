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

namespace Ampache\Module\Api\Jellyfin\Method\Similar;

use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinItemMapper;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /Items|Artists|Albums/{itemId}/Similar — one handler for all three, since `JellyfinId` already tells
 * us the real type. Falls back to a local heuristic when `Recommendation` has no `lastfm_api_key` to use.
 */
final class SimilarMethod implements JellyfinMethodInterface
{
    private const int DEFAULT_LIMIT = 12;

    public function __construct(
        private readonly AlbumRepositoryInterface $albumRepository,
        private readonly JellyfinItemMapper $mapper,
    ) {}

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

        $limitParam = (string) ($request->getQueryParams()['limit'] ?? '');
        $limit      = ($limitParam !== '') ? max(1, (int) $limitParam) : self::DEFAULT_LIMIT;

        $items = match ($type) {
            'song' => $this->similarSongs($id, $limit, $user),
            'artist' => $this->similarArtists($id, $limit, $user),
            'album' => $this->similarAlbums($id, $limit, $user),
            default => [],
        };

        return JellyfinResponse::json([
            'Items' => $items,
            'TotalRecordCount' => count($items),
            'StartIndex' => 0,
        ]);
    }

    /**
     * @param list<int> $ids
     * @param list<int> $pool
     * @return list<int>
     */
    private function fillWithRandom(array $ids, array $pool, int $exclude, int $limit): array
    {
        shuffle($pool);
        foreach ($pool as $candidate) {
            if ($candidate !== $exclude && !in_array($candidate, $ids, true)) {
                $ids[] = $candidate;
            }
            if (count($ids) >= $limit) {
                break;
            }
        }

        return $ids;
    }

    /** @return list<array<string, mixed>> */
    private function similarAlbums(int $albumId, int $limit, User $user): array
    {
        $seed = new Album($albumId);
        if ($seed->isNew()) {
            return [];
        }

        $ids = ($seed->album_artist !== null) ? $this->albumRepository->getAlbumByArtist($seed->album_artist) : [];
        $ids = array_values(array_filter($ids, static fn(int $candidate): bool => $candidate !== $albumId));

        if (count($ids) < $limit) {
            $pool = array_values(Catalog::get_albums(0, 0, $user->get_catalogs('music')));
            $ids  = $this->fillWithRandom($ids, $pool, $albumId, $limit);
        }
        $ids = array_slice($ids, 0, $limit);

        return array_map(fn(int $id): array => $this->mapper->mapAlbum(new Album($id), $user, []), $ids);
    }

    /** @return list<array<string, mixed>> */
    private function similarArtists(int $artistId, int $limit, User $user): array
    {
        $seed = new Artist($artistId);
        if ($seed->isNew()) {
            return [];
        }

        $ids = array_map('intval', array_column(Recommendation::get_artists_like($artistId, $limit), 'id'));
        $ids = array_values(array_filter($ids, static fn(int $candidate): bool => $candidate !== $artistId));

        if (count($ids) < $limit) {
            $catalogArtists = Catalog::get_artists($user->get_catalogs('music'));
            $pool           = array_values(array_map(static fn(Artist $artist): int => $artist->id, $catalogArtists));
            $ids            = $this->fillWithRandom($ids, $pool, $artistId, $limit);
        }
        $ids = array_slice($ids, 0, $limit);

        return array_map(fn(int $id): array => $this->mapper->mapArtist(new Artist($id), $user, []), $ids);
    }

    /** @return list<array<string, mixed>> */
    private function similarSongs(int $songId, int $limit, User $user): array
    {
        $seed = new Song($songId);
        if ($seed->isNew()) {
            return [];
        }

        $ids = array_map('intval', array_column(Recommendation::get_songs_like($songId, $limit), 'id'));
        $ids = array_values(array_filter($ids, static fn(int $candidate): bool => $candidate !== $songId));

        if (count($ids) < $limit) {
            $album = new Album($seed->album);
            $ids   = $this->fillWithRandom($ids, array_values($album->get_songs()), $songId, $limit);
        }
        if (count($ids) < $limit) {
            $pool = array_values(Catalog::get_all_song_ids(0, 0, $user->get_catalogs('music')));
            $ids  = $this->fillWithRandom($ids, $pool, $songId, $limit);
        }
        $ids = array_slice($ids, 0, $limit);

        return array_map(fn(int $id): array => $this->mapper->mapSong(new Song($id), $user, []), $ids);
    }
}
