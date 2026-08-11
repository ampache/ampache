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

namespace Ampache\Repository;

use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumFieldEnum;

interface AlbumRepositoryInterface
{
    /**
     * Maps an artist onto an album, as either its album-artist (`album`) or one of its track artists (`song`)
     */
    public function addAlbumMap(int $albumId, string $objectType, int $objectId): void;

    /**
     * Cleans out unused albums
     */
    public function collectGarbage(): void;

    /**
     * @param int[] $albumIds
     */
    public function collectGarbageForAlbums(array $albumIds): void;

    /**
     * Removes the album_map rows whose album, artist or song has gone, leaving the albums themselves alone
     */
    public function collectOrphanedAlbumMaps(): void;

    /**
     * Inserts a new album row and returns its id, or 0 when the write failed
     *
     * @param array{name: string, prefix: ?string, year: int, mbid: ?string, mbid_group: ?string, release_type: ?string, release_status: ?string, album_artist: ?int, original_year: ?string, barcode: ?string, catalog_number: ?string, version: ?string, catalog: int} $properties
     */
    public function create(array $properties, int $additionTime): int;

    /**
     * Deletes the album entry
     */
    public function delete(
        Album $album,
    ): void;

    /**
     * Removes an album that has no songs left, together with the maps that only existed for it
     */
    public function deleteEmpty(int $albumId): void;

    /**
     * Finds the album that already carries exactly these properties, matching what create() would write
     *
     * @param array{name: string, prefix: ?string, year: int, mbid: ?string, mbid_group: ?string, release_type: ?string, release_status: ?string, album_artist: ?int, original_year: ?string, barcode: ?string, catalog_number: ?string, version: ?string, catalog: int} $properties
     */
    public function findByProperties(array $properties): ?int;

    /**
     * Reads the albums that hold no songs at all, with the artist each was credited to
     *
     * @return list<array{id: int, album_artist: ?int}>
     */
    public function findEmpty(): array;

    /**
     * Reads the artist an album should be credited to when it has no album_artist but only one distinct song artist
     *
     * @return array{artist_name: string, artist_prefix: ?string, album_artist: int}|null
     */
    public function findSoleSongArtist(int $albumId): ?array;

    /**
     * Get the primary album_artist
     */
    public function getAlbumArtistId(int $albumId): ?int;

    /**
     * gets the album ids that this artist is a part of
     * Return Album only
     *
     * @return list<int>
     */
    public function getAlbumByArtist(
        int $artistId,
    ): array;

    /**
     * Counts the distinct artists mapped onto an album, across both the album and song mappings
     */
    public function getArtistCount(int $albumId): int;

    /**
     * This returns the ids of artists that have songs/albums mapped
     *
     * @return list<int>
     */
    public function getArtistMap(Album $album, string $objectType): array;

    /**
     * gets the album ids that this artist is a part of
     * Return Album or AlbumDisk based on album_group preference
     *
     * @return list<int>|array<string, list<int>>
     */
    public function getByArtist(
        int $artistId,
        ?int $catalogId = null,
        bool $group_release_type = false,
    ): array;

    /**
     * gets the album id that is part of this mbid_group
     *
     * @return list<int>
     */
    public function getByMbidGroup(
        string $musicBrainzId,
    ): array;

    /**
     * gets the album id has the same artist and title
     *
     * @return list<int>
     */
    public function getByName(
        string $name,
        int $artistId,
    ): array;

    /**
     * Reads the albums of one catalog, optionally only the ones with no original-size art
     *
     * @return list<int>
     */
    public function getIdsByCatalog(int $catalogId, bool $missingArtOnly = false): array;

    /**
     * Reads a page of the albums holding songs in the given catalogs, by name
     *
     * @param array<int|string>|null $catalogIds every catalog when null or empty
     * @return list<int>
     */
    public function getIdsByCatalogs(?array $catalogIds, int $size = 0, int $offset = 0): array;

    /**
     * Reads a page of the albums holding songs in the given catalogs, grouped by their album artist
     *
     * @param array<int|string>|null $catalogIds every catalog when null or empty
     * @return list<int>
     */
    public function getIdsByCatalogsOrderedByArtist(?array $catalogIds, int $size = 0, int $offset = 0): array;

    /**
     * Reads the albums that carry no album_artist, which the scanner then tries to fill in
     *
     * @return list<int>
     */
    public function getIdsMissingAlbumArtist(): array;

    /**
     * This returns the ids of artists mapped onto an album, by album id rather than by object
     *
     * @return list<int>
     */
    public function getMappedObjectIds(int $albumId, string $objectType): array;

    /**
     * Get item prefix, basename and name by the album id
     *
     * @return array{prefix: string, basename: string, name: string}
     */
    public function getNames(int $albumId): array;

    /**
     * This returns a number of random albums
     *
     * @return list<int>
     */
    public function getRandom(
        int $userId,
        ?int $count = 1,
        int $catalogId = 0,
    ): array;

    /**
     * This returns a number of random album_disks
     *
     * @return list<int>
     */
    public function getRandomAlbumDisk(
        int $userId,
        ?int $count = 1,
    ): array;

    /**
     * gets a random order of songs from this album
     *
     * @return list<int> Song ids
     */
    public function getRandomSongs(
        int $albumId,
    ): array;

    /**
     * gets a random order of songs from this album group
     *
     * @return list<int> Song ids
     */
    public function getRandomSongsByAlbumDisk(
        int $albumDiskId,
    ): array;

    /**
     * Reads whole album rows for the in-process cache, in one statement instead of one per object
     *
     * @param array<int|string> $albumIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $albumIds): array;

    /**
     * Reads the sole artist shared by every song on an album, or null when the album has more than one
     */
    public function getSoleSongArtistId(int $albumId): ?int;

    /**
     * Reads every song row on an album, unordered and not scoped to the user's catalogs unlike getSongs()
     *
     * @return list<int>
     */
    public function getSongIds(int $albumId): array;

    /**
     * gets songs from this album
     *
     * @return list<int> Album ids
     */
    public function getSongs(
        int $albumId,
    ): array;

    /**
     * gets songs from this album_disk id
     *
     * @return list<int> Song ids
     */
    public function getSongsByAlbumDisk(
        int $albumDiskId,
    ): array;

    /**
     * Reads a page of the albums a verify pass walks, taking the file and update time from their songs
     *
     * @return list<array{id: int, file: string, min_update_time: int}>
     */
    public function getVerifyRowsByCatalog(int $catalogId, int $limit, bool $onlyStale, int $lastUpdate, int $offset = 0): array;

    /**
     * Whether the album is one of the placeholders the scanner parks songs on when their real album is unknown
     */
    public function isOrphan(int $albumId): bool;

    /**
     * Drops the album_map row, undoing addAlbumMap()
     */
    public function removeAlbumMap(int $albumId, string $objectType, int $objectId): void;

    /**
     * Drops the album_map row only once the artist_map no longer backs it, and reports whether it did
     */
    public function removeUnusedAlbumMap(int $albumId, string $objectType, int $objectId): bool;

    /**
     * Writes a single album column, bounded by the enum because the column name goes into the statement
     */
    public function setField(int $albumId, AlbumFieldEnum $field, int|string|null $value): bool;

    /**
     * Recomputes the cached totals on every album and disk, and backfills any album_disk the scanner missed
     */
    public function updateAllCounts(): void;

    /**
     * Rolls the skip totals of every album and album disk up from their songs
     */
    public function updateAllSkipCounts(): void;

    /**
     * Recomputes the cached totals on one album and its disks, after a song on it changed
     */
    public function updateCounts(int $albumId): void;
}
