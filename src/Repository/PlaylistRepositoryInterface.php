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
     * The highest position currently used, so appended entries carry on from there
     */
    public function getLastTrackNumber(Playlist $playlist): int;

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
