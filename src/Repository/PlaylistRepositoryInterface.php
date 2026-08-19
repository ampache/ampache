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

use Ampache\Repository\Model\Playlist;

/**
 * Manages playlist related database access
 *
 * Tables: `playlist`
 */
interface PlaylistRepositoryInterface extends PlaylistObjectRepositoryInterface
{
    /**
     * Appends entries, each row being [object_id, object_type, track]
     *
     * @param list<array{0: int, 1: ?string, 2: int}> $rows
     */
    public function addTracks(Playlist $playlist, array $rows): void;

    /**
     * Removes the playlist, its entries and the stats recorded against it
     */
    public function delete(Playlist $playlist): void;

    /**
     * Empties the playlist
     */
    public function deleteAllTracks(Playlist $playlist): void;

    /**
     * Removes one entry by its own `playlist_data` id
     */
    public function deleteTrackById(Playlist $playlist, int $trackId): void;

    /**
     * Removes one entry by the position it holds in the list
     */
    public function deleteTrackByNumber(Playlist $playlist, int $track): void;

    /**
     * Removes one entry by the id of the object it points at
     */
    public function deleteTrackByObjectId(Playlist $playlist, int $objectId): void;

    /**
     * Reads the id of a user's playlist with this exact name and type, or `null` when they have none
     */
    public function findIdByName(string $name, int $userId, string $type): ?int;

    /**
     * Reads the ids a user may see, optionally narrowed by name and by the names they hide
     *
     * @return list<int>
     */
    public function findIds(
        int $userId,
        bool $isAdmin,
        bool $includePublic,
        string $playlistName,
        bool $like,
        ?string $hiddenPrefix,
    ): array;

    /**
     * Reads the id and display name of every playlist a user may see, keyed by id
     *
     * @return array<int, string>
     */
    public function findNames(int $userId, bool $isAdmin): array;

    /**
     * Reads the saved smartlists a user can reach, as id => name
     *
     * @return array<int, string>
     */
    public function findSearchNames(int $userId, bool $ownedOnly): array;

    /**
     * Reads the playlists holding media of one catalog, optionally only the ones with no original-size art
     *
     * @return list<int>
     */
    public function getIdsByCatalog(int $catalogId, bool $missingArtOnly = false): array;

    /**
     * Reads the entries of one media type in a playlist, in track order or at random
     *
     * @return list<array<string, mixed>>
     */
    public function getItemsOfType(
        int $playlistId,
        string $objectType,
        int $userId,
        bool $catalogFilter,
        bool $withTime,
        bool $random,
        string $limit = '',
    ): array;

    /**
     * The highest position currently used, so appended entries carry on from there
     */
    public function getLastTrackNumber(Playlist $playlist): int;

    /**
     * Counts the entries of a playlist, honouring the catalog filter the user browses under
     */
    public function getMediaCount(int $playlistId, string $type, int $userId, bool $catalogFilter): int;

    /**
     * Reads the media types a playlist holds
     *
     * @return list<string>
     */
    public function getObjectTypes(int $playlistId): array;

    /**
     * Reads whole playlist rows for the in-request cache
     *
     * @param list<int|string> $playlistIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $playlistIds): array;

    /**
     * Entry ids in their stored order, for renumbering
     *
     * @return int[]
     */
    public function getTrackIdsInOrder(Playlist $playlist): array;

    /**
     * Entry ids sorted by artist then album then track
     *
     * @return int[]
     */
    public function getTrackIdsSorted(Playlist $playlist): array;

    /**
     * Whether a playlist holds an object, a track position, or that object at or before that position
     */
    public function hasItem(int $playlistId, ?int $objectId, ?int $track, string $objectType): bool;

    /**
     * Inserts a playlist and returns its id, or `null` when the write failed
     */
    public function insert(string $name, int $userId, string $username, string $type, int $date): ?int;

    /**
     * Moves every entry pointing at one object onto another
     */
    public function migrateObject(string $objectType, int $oldObjectId, int $newObjectId): void;

    /**
     * Puts an object at a position, displacing whatever held it
     */
    public function replaceTrackAtNumber(Playlist $playlist, int $objectId, int $track): void;

    /**
     * Stores the position of one entry
     */
    public function setTrackNumber(int $trackId, int $track): void;

    /**
     * Writes new positions for a set of entries in one statement
     *
     * @param array<int, int> $tracksById
     */
    public function setTrackNumbers(array $tracksById): void;
}
