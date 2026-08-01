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
 * Provides access to the `user_playlist` table, the play queue a client keeps for a user
 */
interface UserPlaylistRepositoryInterface
{
    /**
     * Appends items to a client's queue in one statement
     *
     * @param list<array{object_type: string, object_id: int|string, track: int|string}> $items
     */
    public function addItems(int $userId, string $client, int $time, array $items): void;

    /**
     * Drops everything one client queued for a user
     */
    public function clear(int $userId, string $client): void;

    /**
     * Clears the current-track marker across every queue a user has
     */
    public function clearCurrent(int $userId): void;

    /**
     * The highest track position in a client's queue, which is what the queue length is counted by
     */
    public function getCount(int $userId, string $client): int;

    /**
     * The row a user is currently playing, or an empty array when nothing is marked
     *
     * @return array<string, mixed>
     */
    public function getCurrentRow(int $userId): array;

    /**
     * The queue of one client, in track order
     *
     * @return list<array<string, mixed>>
     */
    public function getItems(int $userId, string $client): array;

    /**
     * The client name of the user's most recently stored queue
     */
    public function getLatestClient(int $userId): string;

    /**
     * The time a client's queue was stored, or `null` when it holds nothing
     */
    public function getTime(int $userId, string $client): ?int;

    /**
     * Marks a named object as the one being played
     */
    public function setCurrentByObject(int $userId, string $objectType, int $objectId, int $position): void;

    /**
     * Marks the item at a track position as the one being played
     */
    public function setCurrentByTrack(int $userId, string $objectType, int $track, int $position): void;
}
