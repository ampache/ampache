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

use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\SongDataFieldEnum;
use Ampache\Repository\Model\SongFieldEnum;
use Ampache\Repository\Model\SongMbidSourceEnum;
use Ampache\Repository\Model\Tag;
use Iterator;
use Traversable;

interface SongRepositoryInterface
{
    public function collectGarbage(Song $song): void;

    /**
     * @param int[] $songIds
     */
    public function collectGarbageForSongs(array $songIds): void;

    /**
     * Removes the songs and song rows the scanner has orphaned, across the whole table
     */
    public function collectOrphanedGarbage(?string $ignorePattern): void;

    /**
     * Counts the songs of one album still held by a catalog, which decides whether the album moves with them
     */
    public function countByAlbumAndCatalog(int $albumId, int $catalogId): int;

    public function delete(int $songId): bool;

    /**
     * Removes every songs of one catalog, for a catalog that is being deleted
     */
    public function deleteByCatalog(int $catalogId): bool;

    /**
     * Removes a set of songs by id, without recording them in the `deleted_song` archive
     *
     * @param list<int> $songIds
     */
    public function deleteByIds(array $songIds): void;

    /**
     * Records a set of songs in the `deleted_song` archive and removes them
     *
     * @param list<int> $songIds
     */
    public function deleteByIdsWithArchive(array $songIds): void;

    /**
     * Reads the id of the song holding this file
     */
    public function findIdByFile(string $file): ?int;

    /**
     * Reads the id of the song whose file matches a pattern, for a remote url that carries its own id
     */
    public function findIdByFilePattern(string $pattern): ?int;

    /**
     * Reads the id of the song carrying this MusicBrainz id
     */
    public function findIdByMbid(string $mbid): ?int;

    /**
     * Reads the id of the song matching a set of tags, by mbid where the tags carry one and by name where they do not
     *
     * @param array<string, mixed> $data
     */
    public function findIdByTags(array $data): ?int;

    /**
     * Reads a song id from a last.fm style match on title, artist and album
     */
    public function findIdForScrobble(
        string $songName,
        string $artistName,
        string $albumName,
        string $songMbid,
        string $artistMbid,
        string $albumMbid,
    ): ?int;

    /**
     * Reads the songs whose album row has gone, which have to be re-read from their tags to be fixed
     *
     * @return list<int>
     */
    public function findIdsWithMissingAlbum(): array;

    /**
     * The uploader of the song: an id, null when it was not user-uploaded, false when there is no
     * such song
     */
    public function findOwnerId(int $songId): int|false|null;

    /**
     * Reads the MusicBrainz id of an album, an artist or an album artist
     */
    public function findRelatedMbid(SongMbidSourceEnum $source, int $objectId): ?string;

    /**
     * gets the songs (including songs where they are the album artist) for this artist
     *
     * @return list<int>
     */
    public function getAllByArtist(
        int $artistId,
    ): array;

    /**
     * gets the songs for an album takes an optional limit
     *
     * @return list<int>
     */
    public function getByAlbum(int $albumId, int $limit = 0): array;

    /**
     * gets the songs for an album for a single disk takes an optional limit
     *
     * @return list<int>
     */
    public function getByAlbumDisk(int $albumDiskId, int $limit = 0): array;

    /**
     * gets the songs for this artist
     *
     * @return list<int>
     */
    public function getByArtist(
        int $artistId,
    ): array;

    /**
     * Returns all song ids linked to the provided catalog (or all)
     *
     * @return Traversable<int>
     */
    public function getByCatalog(?Catalog $catalog = null): Traversable;

    /**
     * gets the songs for a label, based on label name
     *
     * @return list<int>
     */
    public function getByFolder(
        string $folderName,
    ): array;

    /**
     * gets the songs for a label, based on label name
     *
     * @return list<int>
     */
    public function getByLabel(
        string $labelName,
    ): array;

    /**
     * Returns a list of song ID's attached to a license ID.
     *
     * @return list<int>
     */
    public function getByLicense(int $licenseId): array;

    /**
     * Reads the extended row of a song
     *
     * @return array<string, mixed>
     */
    public function getDataRow(int $songId): array;

    /**
     * Reads the extended rows of a set of songs, for the in-request cache
     *
     * @param list<int|string> $songIds
     * @return list<array<string, mixed>>
     */
    public function getDataRowsByIds(array $songIds): array;

    /**
     * Reads the rows the `deleted_song` archive holds
     *
     * @return list<array<string, mixed>>
     */
    public function getDeletedRows(): array;

    /**
     * Gets a list of the disabled songs for and returns an array of Songs
     *
     * @return Iterator<Song>
     */
    public function getDisabled(): Iterator;

    /**
     * Reads a page of the enabled songs across every catalog, or the given ones
     *
     * @param array<int|string>|null $catalogIds every catalog when null or empty
     * @return list<int>
     */
    public function getEnabledIds(?array $catalogIds, int $size = 0, int $offset = 0, bool $catalogDisable = false): array;

    /**
     * Reads a page of the enabled songs of one catalog, optionally ordered by album
     *
     * @return list<int>
     */
    public function getEnabledIdsByCatalog(int $catalogId, int $size = 0, int $offset = 0, bool $byAlbum = false): array;

    /**
     * Reads the file and title of every song of one catalog, which a remote verify compares against
     *
     * @return list<array{id: int, file: string, title: string}>
     */
    public function getFileRowsByCatalog(int $catalogId): array;

    /**
     * Reads every song file of one catalog keyed by song id, for the scanner's in-process cache
     *
     * @return array<int, string>
     */
    public function getFilesByCatalog(int $catalogId, int $limit = 0, int $offset = 0): array;

    /**
     * Reads the ids of every song of one catalog, enabled or not
     *
     * @return list<int>
     */
    public function getIdsByCatalog(int $catalogId): array;

    /**
     * Reads the ids of the songs of one catalog whose file carries one of the given extensions
     *
     * @param list<string> $extensions without the dot, as the cache preferences name them
     * @return list<int>
     */
    public function getIdsByCatalogAndExtension(int $catalogId, array $extensions): array;

    /**
     * Reads the songs whose file sits under a base folder path
     *
     * @return list<int>
     */
    public function getIdsByFilePrefix(string $folderPath): array;

    /**
     * Reads the artists mapped onto a song, or the artists mapped onto an album
     *
     * @return list<int>
     */
    public function getParentIds(int $objectId, bool $forAlbum): array;

    /**
     * The parent artists of many songs (or albums) in one statement, keyed by the object they belong to
     *
     * @param list<int> $objectIds
     * @return array<int, list<int>>
     */
    public function getParentIdsBulk(array $objectIds, bool $forAlbum): array;

    /**
     * Reads the playback columns of the extended row, the one partial read the callers ask for
     *
     * These are the scalars a player or an api response needs, leaving the comment, lyrics and waveform behind.
     *
     * @return array<string, mixed>
     */
    public function getPartialDataRow(int $songId): array;

    /**
     * Gets the songs from the artist in a random order
     *
     * @return list<int>
     */
    public function getRandomByArtist(
        Artist $artist,
    ): array;

    /**
     * Gets the songs from a genre in a random order
     *
     * @return list<int>
     */
    public function getRandomByGenre(
        Tag $genre,
    ): array;

    /**
     * Reads one song row with the album and artist identity a `Song` object is built from
     *
     * @return array<string, mixed>
     */
    public function getRow(int $songId): array;

    /**
     * Reads song rows for the in-request cache, dropping the disabled catalogs when they are hidden
     *
     * @param list<int|string> $songIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $songIds, bool $catalogDisable): array;

    /**
     * Reads the values mapped onto a song, ISRCs being the only kind so far
     *
     * @return list<string>
     */
    public function getSongMapValues(int $songId, string $objectType): array;

    /**
     * gets the songs for this artist
     *
     * @return list<int>
     */
    public function getTopSongsByArtist(
        Artist $artist,
        int $count = 50,
    ): array;

    /**
     * Reads a page of the songs a verify pass walks, newest path first
     *
     * @return list<array{id: int, file: string, min_update_time: int}>
     */
    public function getVerifyRowsByCatalog(int $catalogId, int $limit, bool $onlyStale): array;

    /**
     * Reads the stored waveform, which is a blob every other read deliberately leaves behind
     *
     * @return array<string, mixed>
     */
    public function getWaveformRow(int $songId): array;

    /**
     * Whether a song row exists
     */
    public function hasId(int $songId): bool;

    /**
     * Inserts a song row and returns its id, or `null` when the write failed
     *
     * @param list<mixed> $values in the column order of the statement
     */
    public function insert(array $values): ?int;

    /**
     * Inserts the extended row that belongs with a new song
     *
     * @param list<mixed> $values in the column order of the statement
     */
    public function insertData(array $values): void;

    /**
     * Points the maps of one album at another and drops what the old one left behind
     */
    public function migrateAlbum(int $newAlbumId, int $oldAlbumId, int $songId): bool;

    /**
     * Points the maps of one artist at another and drops what the old one left behind
     */
    public function migrateArtist(int $newArtistId, int $oldArtistId): bool;

    /**
     * Rewrites the leading path of every file of one catalog, for a catalog that moved on disk
     */
    public function replaceFilePathForCatalog(int $catalogId, string $oldPath, string $newPath): void;

    /**
     * Zeroes the play counters of songs that have no history left, which a rebuild cannot reach
     */
    public function resetCountsWithoutHistory(): void;

    /**
     * Writes a single `song_data` column, returning false when the write failed
     */
    public function setDataField(int $songId, SongDataFieldEnum $field, string $value): bool;

    /**
     * Writes a single `song` column, returning false when the write failed
     */
    public function setField(int $songId, SongFieldEnum $field, int|string|null $value): bool;

    /**
     * Moves a song to another catalog and to the file it now lives in
     */
    public function setFileAndCatalog(int $songId, string $file, int $catalogId): bool;

    /**
     * Rebuilds every song's play and skip totals from `object_count`, and the played flag that follows them
     */
    public function updateAllCounts(): void;

    /**
     * Rewrites a song row and its extended row from a freshly read file
     *
     * @param list<mixed> $songValues in the column order of the statement
     * @param list<mixed> $dataValues in the column order of the statement
     */
    public function updateSong(int $songId, array $songValues, array $dataValues): void;

    /**
     * Replaces the mapped values of one kind for a song, dropping whatever is no longer in the list
     *
     * @param list<int|string> $objectIds
     */
    public function updateSongMap(array $objectIds, string $objectType, int $songId): void;

    /**
     * Stamps a song as read from its file
     */
    public function updateUpdateTime(int $songId, int $time): void;
}
