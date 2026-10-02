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
use Ampache\Module\Api\Jellyfin\JellyfinRequestBody;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Random;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
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

    // extra rows over what's still missing, so a few landing on ids already picked don't fall short
    private const int FILL_BUFFER = 8;

    public function __construct(
        private readonly AlbumRepositoryInterface $albumRepository,
        private readonly ArtistRepositoryInterface $artistRepository,
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

        $limitParam = (string) (JellyfinRequestBody::field($request->getQueryParams(), 'limit') ?? '');
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
     * Folds candidates into $ids, skipping the excluded seed and anything already present, until $limit is met.
     *
     * @param list<int> $ids
     * @param array<int, int> $candidates
     * @return list<int>
     */
    private function fillFrom(array $ids, array $candidates, int $exclude, int $limit): array
    {
        foreach ($candidates as $candidate) {
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
        if ($seed->isNew() || !Catalog::has_access($seed->getCatalogId(), $user->getId())) {
            return [];
        }

        $ids = ($seed->album_artist !== null) ? $this->albumRepository->getAlbumByArtist($seed->album_artist) : [];
        $ids = array_values(array_filter($ids, static fn(int $candidate): bool => $candidate !== $albumId));

        if (count($ids) < $limit) {
            // a bounded random pull, never the whole library, to fill whatever is still missing
            $pool = $this->albumRepository->getRandom($user->getId(), $limit - count($ids) + self::FILL_BUFFER);
            $ids  = $this->fillFrom($ids, $pool, $albumId, $limit);
        }
        $ids = array_slice($ids, 0, $limit);

        Album::build_cache($ids);
        Rating::build_cache('album', $ids);
        Userflag::build_cache('album', $ids);

        return array_map(fn(int $id): array => $this->mapper->mapAlbum(new Album($id), $user), $ids);
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
            // a bounded random pull, never the whole library, to fill whatever is still missing
            $pool = $this->artistRepository->getRandom($user->getId(), $limit - count($ids) + self::FILL_BUFFER);
            $ids  = $this->fillFrom($ids, $pool, $artistId, $limit);
        }
        $ids = array_slice($ids, 0, $limit);

        Artist::build_cache($ids);
        Rating::build_cache('artist', $ids);
        Userflag::build_cache('artist', $ids);

        return array_map(fn(int $id): array => $this->mapper->mapArtist(new Artist($id), $user, []), $ids);
    }

    /** @return list<array<string, mixed>> */
    private function similarSongs(int $songId, int $limit, User $user): array
    {
        $seed = new Song($songId);
        if ($seed->isNew() || !Catalog::has_access($seed->getCatalogId(), $user->getId())) {
            return [];
        }

        $ids = array_map('intval', array_column(Recommendation::get_songs_like($songId, $limit), 'id'));
        $ids = array_values(array_filter($ids, static fn(int $candidate): bool => $candidate !== $songId));

        if (count($ids) < $limit) {
            $albumSongs = array_values(new Album($seed->album)->get_songs());
            shuffle($albumSongs);
            $ids = $this->fillFrom($ids, $albumSongs, $songId, $limit);
        }
        if (count($ids) < $limit) {
            // a bounded random pull, never the whole library, to fill whatever is still missing
            $ids = $this->fillFrom($ids, Random::get_default($limit - count($ids) + self::FILL_BUFFER, $user), $songId, $limit);
        }
        $ids = array_slice($ids, 0, $limit);

        Song::build_cache($ids);
        Rating::build_cache('song', $ids);
        Userflag::build_cache('song', $ids);
        Bookmark::build_cache('song', $ids, $user->getId());

        return array_map(fn(int $id): array => $this->mapper->mapSong(new Song($id), $user, []), $ids);
    }
}
