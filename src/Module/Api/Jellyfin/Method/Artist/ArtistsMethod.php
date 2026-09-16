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

namespace Ampache\Module\Api\Jellyfin\Method\Artist;

use Ampache\Module\Api\Jellyfin\JellyfinItemMapper;
use Ampache\Module\Api\Jellyfin\JellyfinRequestBody;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /Artists and GET /Artists/AlbumArtists — bare top-level artist browses, distinct from `/Items` (found
 * missing via a live mobile client's own root-navigation query, which never goes through `/Items` at all).
 */
final class ArtistsMethod implements JellyfinMethodInterface
{
    public function __construct(
        private readonly ArtistRepositoryInterface $artistRepository,
        private readonly JellyfinItemMapper $mapper,
    ) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $query        = $request->getQueryParams();
        $onlyFavorite = str_contains((string) (JellyfinRequestBody::field($query, 'Filters') ?? ''), 'IsFavorite')
            || strtolower((string) (JellyfinRequestBody::field($query, 'IsFavorite') ?? '')) === 'true';
        $startIndex = max(0, (int) (JellyfinRequestBody::field($query, 'StartIndex') ?? 0));
        $limitParam = (string) (JellyfinRequestBody::field($query, 'Limit') ?? '');
        $limit      = ($limitParam !== '') ? (int) $limitParam : 0;
        $sortBy     = $this->splitList((string) (JellyfinRequestBody::field($query, 'SortBy') ?? ''));
        $descending = strcasecmp((string) (JellyfinRequestBody::field($query, 'SortOrder') ?? ''), 'Descending') === 0;

        $sort = match (true) {
            in_array('Random', $sortBy, true) => 'random',
            in_array('DateCreated', $sortBy, true) => 'newest',
            default => null,
        };

        // one extra row past the requested page, just to detect that more remain
        $fetchSize = ($limit > 0) ? $startIndex + $limit + 1 : 0;

        $items = $this->allArtists($user, $fetchSize, $sort, $descending, $onlyFavorite);

        // a bounded fetch can't report the real total without fetching everything, so a full page plus the
        // peeked row reports a lower bound instead of a false "that's everything" once the cap is hit
        $total = (($fetchSize > 0) && count($items) > $startIndex + $limit)
            ? $startIndex + $limit + 1
            : count($items);
        $items = ($limit > 0) ? array_slice($items, $startIndex, $limit) : array_slice($items, $startIndex);

        return JellyfinResponse::json([
            'Items' => $items,
            'TotalRecordCount' => $total,
            'StartIndex' => $startIndex,
        ]);
    }

    /**
     * @param 'random'|'newest'|null $sort
     * @return list<array<string, mixed>>
     */
    private function allArtists(User $user, int $fetchSize, ?string $sort, bool $descending, bool $onlyFavorite): array
    {
        if ($onlyFavorite) {
            $artistIds = $this->favoriteIds('artist', $user, $fetchSize, $sort, $descending);
            $this->warmArtists($artistIds);

            $result = [];
            foreach ($artistIds as $artistId) {
                $result[] = $this->mapper->mapArtist(new Artist($artistId), $user, []);
            }

            return $result;
        }

        $catalogs = $user->get_catalogs('music');

        if ($sort === null) {
            $artists = $this->applyOrder(Catalog::get_artists($catalogs, $fetchSize, 0), $sort, $descending);
            $this->warmArtists(array_map(static fn(Artist $artist): int => $artist->id, $artists));

            $result = [];
            foreach ($artists as $artist) {
                $result[] = $this->mapper->mapArtist($artist, $user, []);
            }

            return $result;
        }

        $artistIds = match ($sort) {
            'random' => ($fetchSize > 0)
                ? $this->artistRepository->getRandom($user->getId(), $fetchSize)
                : $this->shuffleArray(array_map(static fn(Artist $artist): int => $artist->id, Catalog::get_artists($catalogs))),
            'newest' => Stats::get_newest('artist', ($fetchSize > 0) ? $fetchSize : -1, 0, 0, $user),
        };
        $artistIds = $this->applyOrder($artistIds, $sort, $descending);

        $this->warmArtists($artistIds);

        $result = [];
        foreach ($artistIds as $artistId) {
            $result[] = $this->mapper->mapArtist(new Artist($artistId), $user, []);
        }

        return $result;
    }

    /**
     * The default fetch already comes back ascending (by name) and `newest` already comes back descending
     * (by addition date), so only a mismatch between that and the requested SortOrder needs a flip.
     *
     * @template T
     * @param array<int, T> $ids
     * @param 'random'|'newest'|null $sort
     * @return array<int, T>
     */
    private function applyOrder(array $ids, ?string $sort, bool $descending): array
    {
        $needsReverse = match ($sort) {
            'newest' => !$descending,
            'random' => false,
            default => $descending,
        };

        return $needsReverse ? array_reverse($ids) : $ids;
    }

    /**
     * A DB-level Filters=IsFavorite lookup, bounded by how much the user has flagged rather than the
     * whole library.
     *
     * @param 'random'|'newest'|null $sort
     * @return array<int, int>
     */
    private function favoriteIds(string $type, User $user, int $fetchSize, ?string $sort, bool $descending): array
    {
        $ids = Userflag::get_latest($type, $user, ($fetchSize > 0) ? $fetchSize : -1, 0, 0, 0, true, 0);

        return ($sort === 'random') ? $this->shuffleArray($ids) : $this->applyOrder($ids, 'newest', $descending);
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, int>
     */
    private function shuffleArray(array $ids): array
    {
        shuffle($ids);

        return $ids;
    }

    /** @return list<string> */
    private function splitList(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), static fn(string $item): bool => $item !== ''));
    }

    /**
     * @param array<int> $ids
     */
    private function warmArtists(array $ids): void
    {
        Artist::build_cache($ids);
        Rating::build_cache('artist', $ids);
        Userflag::build_cache('artist', $ids);
    }
}
