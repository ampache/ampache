<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

namespace Ampache\Repository;

use Ampache\Repository\Model\BroadcastInterface;

interface BroadcastRepositoryInterface
{
    /**
     * @return int[]
     */
    public function getByUser(int $userId): array;

    /**
     * Get broadcast from its key.
     */
    public function findByKey(string $key): ?BroadcastInterface;

    public function delete(BroadcastInterface $broadcast): void;

    /**
     * Create a broadcast
     */
    public function create(
        int $userId,
        string $name,
        string $description = ''
    ): int;

    /**
     * Update broadcast state.
     */
    public function updateState(
        int $broadcastId,
        int $started,
        string $key = ''
    ): void;

    /**
     * Update broadcast listeners
     */
    public function updateListeners(
        int $broadcastId,
        int $listeners
    ): void;


    /**
     * Update broadcast current song.
     */
    public function updateSong(
        int $broadcastId,
        int $songId
    ): void;


    /**
     * Update a broadcast from data array.
     */
    public function update(
        int $broadcastId,
        string $name,
        string $description,
        int $isPrivate
    ): void;

    /**
     * @return array<string, int|string>
     */
    public function getDataById(int $broadcastId): array;
}
