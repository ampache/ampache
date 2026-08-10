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

/**
 * Provides access to the `tmp_playlist` and `tmp_playlist_data` tables, the per-session play queue
 *
 * Every read is bounded by what its caller needs: the queue has no ceiling, so counting it or listing
 * it in full is never done just to answer "is there anything in it".
 */
interface TmpPlaylistRepositoryInterface
{
    /**
     * Appends one item to a queue
     */
    public function addItem(int $playlistId, int $objectId, string $objectType): void;

    /**
     * Drops the queues whose session is gone, and any data rows left without a queue
     */
    public function collectGarbage(): void;

    /**
     * Counts the items in a queue, for the one caller that prints the number
     */
    public function countItems(int $playlistId): int;

    /**
     * Creates a queue and returns its id, or `null` when the write failed
     */
    public function create(string $sessionId, string $type, string $objectType): ?int;

    /**
     * Drops one item by the id of its data row
     */
    public function deleteItemByRowId(int $rowId): void;

    /**
     * Drops every item from a queue
     */
    public function deleteItems(int $playlistId): void;

    /**
     * Drops the other queues a session left behind, and their items
     */
    public function deleteOtherSessionPlaylists(string $sessionId, int $playlistId): void;

    /**
     * The queue id belonging to a session, or `null` when it has none
     */
    public function findBySession(string $sessionId): ?int;

    /**
     * The newest queue id belonging to a username, or `null` when they have none
     */
    public function findByUsername(string $username): ?int;

    /**
     * Reads the items of a queue in order, optionally cut to the first `$limit` of them
     *
     * @return list<array{object_type: string, id: int, object_id: int}>
     */
    public function getItems(int $playlistId, int $limit = 0): array;

    /**
     * The object id of the first item in a queue, or `null` when it is empty
     */
    public function getNextObjectId(int $playlistId): ?int;

    /**
     * Reads a queue's own row
     *
     * @return array<string, mixed>
     */
    public function getRow(int $playlistId): array;

    /**
     * Whether a queue holds anything at all, which costs the same at three rows and three hundred thousand
     */
    public function hasItems(int $playlistId): bool;
}
