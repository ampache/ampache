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
use Ampache\Module\Api\Jellyfin\JellyfinRequestBody;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\JellyfinUserView;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Random;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /Items (also reached via the legacy /Users/{userId}/Items). Driven by IncludeItemTypes/Recursive when
 * there's no ParentId, or by ParentId scoping (album/artist/playlist) otherwise; real Playlist rows only.
 */
final class ItemsMethod implements JellyfinMethodInterface
{
    public function __construct(
        private readonly AlbumRepositoryInterface $albumRepository,
        private readonly ArtistRepositoryInterface $artistRepository,
        private readonly JellyfinItemMapper $mapper,
        private readonly PlaylistRepositoryInterface $playlistRepository,
    ) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $query        = $request->getQueryParams();
        $fields       = $this->splitList((string) (JellyfinRequestBody::field($query, 'Fields') ?? ''));
        $includeTypes = $this->splitList((string) (JellyfinRequestBody::field($query, 'IncludeItemTypes') ?? ''));
        $parentId     = (string) (JellyfinRequestBody::field($query, 'ParentId') ?? '');
        $onlyFavorite = str_contains((string) (JellyfinRequestBody::field($query, 'Filters') ?? ''), 'IsFavorite');
        $startIndex   = max(0, (int) (JellyfinRequestBody::field($query, 'StartIndex') ?? 0));
        $limitParam   = (string) (JellyfinRequestBody::field($query, 'Limit') ?? '');
        $limit        = ($limitParam !== '') ? (int) $limitParam : 0;
        $sortBy       = $this->splitList((string) (JellyfinRequestBody::field($query, 'SortBy') ?? ''));
        $descending   = strcasecmp((string) (JellyfinRequestBody::field($query, 'SortOrder') ?? ''), 'Descending') === 0;

        $sort = match (true) {
            in_array('Random', $sortBy, true) => 'random',
            in_array('DateCreated', $sortBy, true) => 'newest',
            default => null,
        };

        // one extra row past the requested page, just to detect that more remain
        $fetchSize = ($limit > 0) ? $startIndex + $limit + 1 : 0;

        $items = $this->collect($parentId, $includeTypes, $user, $fields, $fetchSize, $sort, $descending, $onlyFavorite);

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
     * @return list<array<string, mixed>>
     */
    private function albumsForArtist(int $artistId, User $user): array
    {
        $albumIds = $this->albumRepository->getAlbumByArtist($artistId);
        $this->warmAlbums($albumIds);

        // an artist reports catalog 0, which no filter group holds, so its albums are filtered instead
        $albums = [];
        foreach ($albumIds as $albumId) {
            $album = new Album($albumId);
            if (Catalog::has_access($album->getCatalogId(), $user->getId())) {
                $albums[] = $this->mapper->mapAlbum($album, $user);
            }
        }

        return $albums;
    }

    /**
     * @param 'random'|'newest'|null $sort
     * @return list<array<string, mixed>>
     */
    private function allAlbums(User $user, int $fetchSize, ?string $sort, bool $descending, bool $onlyFavorite): array
    {
        if ($onlyFavorite) {
            $albumIds = $this->favoriteIds('album', $user, $fetchSize, $sort, $descending);
        } else {
            $catalogs = $user->get_catalogs('music');
            $albumIds = match ($sort) {
                'random' => ($fetchSize > 0)
                    ? $this->albumRepository->getRandom($user->getId(), $fetchSize)
                    : $this->shuffleArray(Catalog::get_albums(0, 0, $catalogs)),
                'newest' => Stats::get_newest('album', ($fetchSize > 0) ? $fetchSize : -1, 0, 0, $user),
                default => Catalog::get_albums($fetchSize, 0, $catalogs),
            };
            $albumIds = $this->applyOrder($albumIds, $sort, $descending);
        }

        $this->warmAlbums($albumIds);

        $albums = [];
        foreach ($albumIds as $albumId) {
            $albums[] = $this->mapper->mapAlbum(new Album($albumId), $user);
        }

        return $albums;
    }

    /**
     * @param list<string> $fields
     * @param 'random'|'newest'|null $sort
     * @return list<array<string, mixed>>
     */
    private function allArtists(User $user, array $fields, int $fetchSize, ?string $sort, bool $descending, bool $onlyFavorite): array
    {
        if ($onlyFavorite) {
            $artistIds = $this->favoriteIds('artist', $user, $fetchSize, $sort, $descending);
            $this->warmArtists($artistIds);

            $result = [];
            foreach ($artistIds as $artistId) {
                $result[] = $this->mapper->mapArtist(new Artist($artistId), $user, $fields);
            }

            return $result;
        }

        $catalogs = $user->get_catalogs('music');

        if ($sort === null) {
            $artists = $this->applyOrder(Catalog::get_artists($catalogs, $fetchSize), $sort, $descending);
            $this->warmArtists(array_map(static fn(Artist $artist): int => $artist->id, $artists));

            $result = [];
            foreach ($artists as $artist) {
                $result[] = $this->mapper->mapArtist($artist, $user, $fields);
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
            $result[] = $this->mapper->mapArtist(new Artist($artistId), $user, $fields);
        }

        return $result;
    }

    /**
     * @param 'random'|'newest'|null $sort
     * @return list<array<string, mixed>>
     */
    private function allPlaylists(User $user, int $fetchSize, ?string $sort, bool $descending, bool $onlyFavorite): array
    {
        // findIds has no size/offset of its own, so this is the one type still bounded after the fact
        // rather than at the query — playlist counts are small next to a library's albums/artists/songs
        $ids = $this->playlistRepository->findIds(
            $user->getId(),
            $user->has_access(AccessLevelEnum::ADMIN),
            true,
            '',
            false,
            null,
        );

        if ($onlyFavorite) {
            // intersect rather than querying favorites alone, so a stale flag never outruns visibility
            $favoriteIds = Userflag::get_latest('playlist', $user, -1, 0, 0, 0, true);
            $ids         = array_values(array_intersect($ids, $favoriteIds));
        }

        if ($sort === 'newest') {
            $this->warmPlaylists($ids);
            usort($ids, static fn(int $a, int $b): int => new Playlist($b)->last_update <=> new Playlist($a)->last_update);
        } elseif ($sort === 'random') {
            $ids = $this->shuffleArray($ids);
        }
        $ids = $this->applyOrder($ids, $sort, $descending);

        if ($fetchSize > 0) {
            $ids = array_slice($ids, 0, $fetchSize);
        }
        $this->warmPlaylists($ids);

        $playlists = [];
        foreach ($ids as $playlistId) {
            $playlists[] = $this->mapper->mapPlaylist(new Playlist($playlistId), $user);
        }

        return $playlists;
    }

    /**
     * @param list<string> $fields
     * @param 'random'|'newest'|null $sort
     * @return list<array<string, mixed>>
     */
    private function allSongs(User $user, array $fields, int $fetchSize, ?string $sort, bool $descending, bool $onlyFavorite): array
    {
        if ($onlyFavorite) {
            $songIds = $this->favoriteIds('song', $user, $fetchSize, $sort, $descending);
        } else {
            $catalogs = $user->get_catalogs('music');
            $songIds  = match ($sort) {
                'random' => ($fetchSize > 0)
                    ? Random::get_default($fetchSize, $user)
                    : $this->shuffleArray(Catalog::get_all_song_ids(0, 0, $catalogs)),
                'newest' => Stats::get_newest('song', ($fetchSize > 0) ? $fetchSize : -1, 0, 0, $user),
                default => Catalog::get_all_song_ids($fetchSize, 0, $catalogs),
            };
            $songIds = $this->applyOrder($songIds, $sort, $descending);
        }

        $this->warmSongs($songIds, $user);

        $songs = [];
        foreach ($songIds as $songId) {
            $songs[] = $this->mapper->mapSong(new Song($songId), $user, $fields);
        }

        return $songs;
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
     * @param list<string> $includeTypes
     * @param list<string> $fields
     * @param 'random'|'newest'|null $sort
     * @return list<array<string, mixed>>
     */
    private function collect(string $parentId, array $includeTypes, User $user, array $fields, int $fetchSize, ?string $sort, bool $descending, bool $onlyFavorite): array
    {
        // no ParentId, no IncludeItemTypes at all: the client wants the root library views, not "nothing"
        // (confirmed against real Symfonium sync traffic — this exact shape is how it discovers libraries)
        if ($parentId === '' && $includeTypes === []) {
            return [JellyfinUserView::build()];
        }

        $parentType     = ($parentId !== '') ? JellyfinId::decodeType($parentId) : null;
        $parentObjectId = ($parentId !== '') ? JellyfinId::decodeId($parentId) : null;

        if ($parentType === 'album' && $parentObjectId !== null) {
            return $this->songsForAlbum($parentObjectId, $user, $fields);
        }
        if ($parentType === 'artist' && $parentObjectId !== null) {
            return $this->albumsForArtist($parentObjectId, $user);
        }
        if ($parentType === 'playlist' && $parentObjectId !== null) {
            return $this->songsForPlaylist($parentObjectId, $user, $fields);
        }

        // top-level: no ParentId, or the synthetic 'view' root — driven entirely by IncludeItemTypes
        $items = [];
        if (in_array('MusicAlbum', $includeTypes, true)) {
            array_push($items, ...$this->allAlbums($user, $fetchSize, $sort, $descending, $onlyFavorite));
        }
        if (in_array('MusicArtist', $includeTypes, true)) {
            array_push($items, ...$this->allArtists($user, $fields, $fetchSize, $sort, $descending, $onlyFavorite));
        }
        if (in_array('Audio', $includeTypes, true)) {
            array_push($items, ...$this->allSongs($user, $fields, $fetchSize, $sort, $descending, $onlyFavorite));
        }
        if (in_array('Playlist', $includeTypes, true)) {
            array_push($items, ...$this->allPlaylists($user, $fetchSize, $sort, $descending, $onlyFavorite));
        }

        return $items;
    }

    /**
     * A DB-level Filters=IsFavorite lookup, bounded by how much the user has flagged rather than the
     * whole library — album/artist/song carry no visibility of their own, unlike a playlist.
     *
     * @param 'random'|'newest'|null $sort
     * @return array<int, int>
     */
    private function favoriteIds(string $type, User $user, int $fetchSize, ?string $sort, bool $descending): array
    {
        $ids = Userflag::get_latest($type, $user, ($fetchSize > 0) ? $fetchSize : -1, 0, 0, 0, true);

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

    /**
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function songsForAlbum(int $albumId, User $user, array $fields): array
    {
        $album = new Album($albumId);
        if ($album->isNew() || !Catalog::has_access($album->getCatalogId(), $user->getId())) {
            return [];
        }

        $songIds = $album->get_songs();
        $this->warmSongs($songIds, $user);

        $songs = [];
        foreach ($songIds as $songId) {
            $songs[] = $this->mapper->mapSong(new Song($songId), $user, $fields);
        }

        return $songs;
    }

    /**
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function songsForPlaylist(int $playlistId, User $user, array $fields): array
    {
        $playlist = new Playlist($playlistId);
        if ($playlist->isNew() || ($playlist->type !== 'public' && !$playlist->has_collaborate($user))) {
            return [];
        }

        $rows    = array_values(array_filter(
            $playlist->get_items(),
            static fn(array $row): bool => $row['object_type'] === LibraryItemEnum::SONG,
        ));
        $songIds = array_map(static fn(array $row): int => (int) $row['object_id'], $rows);
        $this->warmSongs($songIds, $user);

        $songs = [];
        foreach ($songIds as $songId) {
            $songs[] = $this->mapper->mapSong(new Song($songId), $user, $fields);
        }

        return $songs;
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
    private function warmAlbums(array $ids): void
    {
        Album::build_cache($ids);
        Rating::build_cache('album', $ids);
        Userflag::build_cache('album', $ids);
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

    /**
     * @param array<int> $ids
     */
    private function warmPlaylists(array $ids): void
    {
        Playlist::build_cache($ids);
        Rating::build_cache('playlist', $ids);
        Userflag::build_cache('playlist', $ids);
    }

    /**
     * @param array<int> $ids
     */
    private function warmSongs(array $ids, User $user): void
    {
        Song::build_cache($ids);
        Rating::build_cache('song', $ids);
        Userflag::build_cache('song', $ids);
        Bookmark::build_cache('song', $ids, $user->getId());
    }
}
