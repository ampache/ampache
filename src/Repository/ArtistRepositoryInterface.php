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

use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ArtistFieldEnum;

interface ArtistRepositoryInterface
{
    /**
     * Maps an artist onto a song or album; duplicates are ignored so the scanner can call it unconditionally
     */
    public function addArtistMap(int $artistId, string $objectType, int $objectId): void;

    /**
     * This cleans out unused artists
     */
    public function collectGarbage(): void;

    public function collectGarbageForArtist(int $artistId): void;

    /**
     * Inserts a new artist row and returns its id, or null when the write failed
     */
    public function create(string $name, ?string $prefix, ?string $mbid, ?int $userId): ?int;

    /**
     * Deletes the artist entry
     */
    public function delete(
        Artist $artist,
    ): void;

    /**
     * This finds an artist based on its name
     */
    public function findByName(string $name): ?Artist;

    /**
     * Finds the single artist carrying this MusicBrainz id
     */
    public function findIdByMbid(string $mbid): ?int;

    /**
     * Finds an artist by either form of its name, restricted to rows that do or do not already carry an mbid
     */
    public function findIdByName(string $name, string $fullName, bool $withMbid): ?int;

    /**
     * Finds every mbid-less artist matching either form of the name, so a known mbid can be back-filled onto them
     *
     * @return list<int>
     */
    public function findIdsByNameWithoutMbid(string $name, string $fullName): array;

    /**
     * Reads the album ids credited to an artist through the album half of the artist_map
     *
     * @return list<int>
     */
    public function getAlbumIds(int $artistId): array;

    /**
     * Reads the prefixed display name of an artist, or null when there is no such row
     */
    public function getFullNameById(int $artistId): ?string;

    /**
     * Reads the minimal artist detail Subsonic needs, together with its lowest-numbered catalog
     *
     * @return array{id: int, f_name: string, name: string, album_count: int, song_count: int, catalog_id: int}|null
     */
    public function getIdArray(int $artistId): ?array;

    /**
     * Reads the same minimal detail for every artist, optionally scoped to one catalog
     *
     * @return list<array{id: int, f_name: string, name: string, album_count: int, song_count: int, has_art: int}>
     */
    public function getIdArrayRows(?int $catalogId, bool $albumArtist): array;

    /**
     * Reads the prefix, basename and display name of an artist, or null when there is no such row
     *
     * @return array{id: string, name: string, prefix: string, basename: string}|null
     */
    public function getNameArrayById(int $artistId): ?array;

    /**
     * Reads the ids of artists mapped onto one song or album
     *
     * @return list<int>
     */
    public function getObjectMap(string $objectType, int $objectId): array;

    /**
     * Reads the summed play counts of a set of artists, for the prefetch that feeds the browse display
     *
     * @param array<int|string> $artistIds
     *
     * @return list<array{artist: int, total_count: int}>
     */
    public function getPlayCountsByIds(array $artistIds): array;

    /**
     * This returns a number of random artists.
     *
     * @return int[]
     */
    public function getRandom(
        int $userId,
        ?int $count = 1,
    ): array;

    /**
     * Reads whole artist rows for the in-process cache, in one statement instead of one per object
     *
     * @param array<int|string> $artistIds
     *
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $artistIds): array;

    /**
     * Reads the user an artist was uploaded by, or 0 when it was not an upload
     */
    public function getUploaderId(int $artistId): int;

    /**
     * Moves everything credited to one artist onto another, or clears the credit when there is no replacement
     */
    public function migrate(int $oldArtistId, int $newArtistId): void;

    /**
     * Drops the artist_map row, undoing addArtistMap()
     */
    public function removeArtistMap(int $artistId, string $objectType, int $objectId): void;

    /**
     * Writes the split name of an artist
     */
    public function rename(int $artistId, ?string $prefix, string $name): void;

    /**
     * Writes the split name onto whichever artist carries this MusicBrainz id
     */
    public function renameByMbid(string $mbid, ?string $prefix, string $name): void;

    /**
     * Writes a single artist column, bounded by the enum because the column name goes into the statement
     */
    public function setField(int $artistId, ArtistFieldEnum $field, int|string|null $value): bool;

    /**
     * Recomputes the cached totals on every artist
     */
    public function updateAllCounts(): void;

    /**
     * Recomputes the cached totals on one artist, after something mapped to it changed
     */
    public function updateCounts(int $artistId): void;

    /**
     * Writes the biography fields, stamping the row as manually edited when a person supplied them
     */
    public function updateInfo(
        int $artistId,
        ?string $summary,
        ?string $placeformed,
        ?int $yearformed,
        int $lastUpdate,
        bool $manual,
    ): void;
}
