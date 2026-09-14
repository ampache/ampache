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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
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
        private readonly JellyfinItemMapper $mapper,
        private readonly PlaylistRepositoryInterface $playlistRepository,
    ) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $query        = $request->getQueryParams();
        $fields       = $this->splitList((string) ($query['Fields'] ?? ''));
        $includeTypes = $this->splitList((string) ($query['IncludeItemTypes'] ?? ''));
        $parentId     = (string) ($query['ParentId'] ?? '');
        $onlyFavorite = str_contains((string) ($query['Filters'] ?? ''), 'IsFavorite');
        $startIndex   = max(0, (int) ($query['StartIndex'] ?? 0));
        $limitParam   = (string) ($query['Limit'] ?? '');
        $limit        = ($limitParam !== '') ? (int) $limitParam : 0;

        $items = $this->collect($parentId, $includeTypes, $user, $fields);

        if ($onlyFavorite) {
            $items = array_values(array_filter(
                $items,
                static fn(array $dto): bool => (bool) ($dto['UserData']['IsFavorite'] ?? false),
            ));
        }

        $total = count($items);
        $items = ($limit > 0) ? array_slice($items, $startIndex, $limit) : array_slice($items, $startIndex);

        return JellyfinResponse::json([
            'Items' => $items,
            'TotalRecordCount' => $total,
            'StartIndex' => $startIndex,
        ]);
    }

    /**
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function albumsForArtist(int $artistId, User $user, array $fields): array
    {
        $albums = [];
        foreach ($this->albumRepository->getAlbumByArtist($artistId) as $albumId) {
            $albums[] = $this->mapper->mapAlbum(new Album($albumId), $user, $fields);
        }

        return $albums;
    }

    /**
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function allAlbums(User $user, array $fields): array
    {
        $albums = [];
        foreach (Catalog::get_albums(0, 0, $user->get_catalogs('music')) as $albumId) {
            $albums[] = $this->mapper->mapAlbum(new Album($albumId), $user, $fields);
        }

        return $albums;
    }

    /**
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function allArtists(User $user, array $fields): array
    {
        $artists = [];
        foreach (Catalog::get_artists($user->get_catalogs('music')) as $artist) {
            $artists[] = $this->mapper->mapArtist($artist, $user, $fields);
        }

        return $artists;
    }

    /**
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function allPlaylists(User $user, array $fields): array
    {
        $ids = $this->playlistRepository->findIds(
            $user->getId(),
            $user->has_access(AccessLevelEnum::ADMIN),
            true,
            '',
            false,
            null,
        );

        $playlists = [];
        foreach ($ids as $playlistId) {
            $playlists[] = $this->mapper->mapPlaylist(new Playlist($playlistId), $user, $fields);
        }

        return $playlists;
    }

    /**
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function allSongs(User $user, array $fields): array
    {
        $songs = [];
        foreach (Catalog::get_all_song_ids(0, 0, $user->get_catalogs('music')) as $songId) {
            $songs[] = $this->mapper->mapSong(new Song($songId), $user, $fields);
        }

        return $songs;
    }

    /**
     * @param list<string> $includeTypes
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function collect(string $parentId, array $includeTypes, User $user, array $fields): array
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
            return $this->albumsForArtist($parentObjectId, $user, $fields);
        }
        if ($parentType === 'playlist' && $parentObjectId !== null) {
            return $this->songsForPlaylist($parentObjectId, $user, $fields);
        }

        // top-level: no ParentId, or the synthetic 'view' root — driven entirely by IncludeItemTypes
        $items = [];
        if (in_array('MusicAlbum', $includeTypes, true)) {
            array_push($items, ...$this->allAlbums($user, $fields));
        }
        if (in_array('MusicArtist', $includeTypes, true)) {
            array_push($items, ...$this->allArtists($user, $fields));
        }
        if (in_array('Audio', $includeTypes, true)) {
            array_push($items, ...$this->allSongs($user, $fields));
        }
        if (in_array('Playlist', $includeTypes, true)) {
            array_push($items, ...$this->allPlaylists($user, $fields));
        }

        return $items;
    }

    /**
     * @param list<string> $fields
     * @return list<array<string, mixed>>
     */
    private function songsForAlbum(int $albumId, User $user, array $fields): array
    {
        $album = new Album($albumId);
        if ($album->isNew()) {
            return [];
        }

        $songs = [];
        foreach ($album->get_songs() as $songId) {
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

        $songs = [];
        foreach ($playlist->get_items() as $row) {
            if ($row['object_type'] !== LibraryItemEnum::SONG) {
                continue;
            }
            $songs[] = $this->mapper->mapSong(new Song($row['object_id']), $user, $fields);
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
}
