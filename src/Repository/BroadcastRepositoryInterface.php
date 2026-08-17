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

use Ampache\Repository\Model\Broadcast;

/**
 * Manages broadcast related database access
 *
 * Tables: `broadcast`
 */
interface BroadcastRepositoryInterface
{
    /**
     * Starts or stops the broadcast, resetting the current song and listener count
     */
    /**
     * Clears the started state of broadcasts that cannot be running, leaving live ones alone
     */
    public function collectGarbage(): void;

    /**
     * Creates a new broadcast owned by the given user and returns its id
     */
    public function create(int $userId, string $name, string $description, bool $isPrivate = false): int;

    /**
     * Deletes a single item
     */
    public function delete(Broadcast $broadcast): void;

    /**
     * Loads a single broadcast, or null when the id matches nothing
     */
    public function findById(int $objectId): ?Broadcast;

    /**
     * Finds the broadcast currently published under the given key
     */
    public function findByKey(string $key): ?Broadcast;

    /**
     * Returns the ids of every broadcast owned by the user
     *
     * @return int[]
     */
    public function getIdsByUser(int $userId): array;

    /**
     * Returns the full rows for a set of ids, for the object cache
     *
     * @param array<int|string> $broadcastIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $broadcastIds): array;

    /**
     * Writes the editable properties of an existing broadcast
     */
    /**
     * Writes the broadcast, inserting it when it has no id yet
     *
     * Returns the id a new row was given, or null when an existing one was updated.
     */
    public function persist(Broadcast $broadcast): ?int;

    /**
     * Clears the started state of every broadcast, since none can outlive the websocket server
     *
     * @return int the number of rows that still claimed to be running
     */
    public function resetStartedState(): int;

    public function update(Broadcast $broadcast): void;

    /**
     * Stores the current listener count
     */
    public function updateListeners(Broadcast $broadcast, int $listeners): void;

    /**
     * Stores the song currently being broadcast
     */
    public function updateSong(Broadcast $broadcast, int $songId): void;

    public function updateState(Broadcast $broadcast, int $started, string $key): void;
}
