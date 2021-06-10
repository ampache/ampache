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

interface UserPlaylistRepositoryInterface
{
    /**
     * Returns an array of all object_ids currently in this User_Playlist.
     * @return array<array{
     *  id: int,
     *  object_type: string,
     *  object_id: int,
     *  track: int,
     *  current_track: int,
     *  current_time: int
     * }>
     */
    public function getItemsByUser(int $userId): array;

    /**
     * This returns the next object in the user_playlist.
     * @return null|array{
     *  id: int,
     *  object_type: string,
     *  object_id: int,
     *  track: int,
     *  current_track: int,
     *  current_time: int
     * }
     */
    public function getCurrentObjectByUser(int $userId): ?array;

    /**
     * This clears all the objects out of a user's playlist
     */
    public function clear(int $userId): void;

    /**
     * This adds a new song to the playlist
     */
    public function addItem(
        int $userId,
        string $objectType,
        int $objectId,
        int $track
    ): void;

    /**
     * set the active object in the user_playlist.
     */
    public function setCurrentObjectByUser(
        int $userId,
        string $objectType,
        int $objectId,
        int $position
    ): void;
}
