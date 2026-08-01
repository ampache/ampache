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
use Ampache\Repository\Model\Catalog;
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

    public function delete(int $songId): bool;

    /**
     * Reads the id of the song holding this file
     */
    public function findIdByFile(string $file): ?int;

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
     * Reads the artists mapped onto a song, or the artists mapped onto an album
     *
     * @return list<int>
     */
    public function getParentIds(int $objectId, bool $forAlbum): array;

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
     * Reads the replaygain columns of the extended row, the one partial read the callers ask for
     *
     * @return array<string, mixed>
     */
    public function getReplaygainRow(int $songId): array;

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
     * Writes a single `song_data` column, returning false when the write failed
     */
    public function setDataField(int $songId, SongDataFieldEnum $field, string $value): bool;

    /**
     * Writes a single `song` column, returning false when the write failed
     */
    public function setField(int $songId, SongFieldEnum $field, int|string|null $value): bool;

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
