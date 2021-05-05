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

interface ShoutRepositoryInterface
{
    /**
     * @return int[]
     */
    public function getBy(
        string $objectType,
        int $objectId
    ): array;

    /**
     * Cleans out orphaned shoutbox items
     */
    public function collectGarbage(?string $object_type = null, ?int $object_id = null): void;

    /**
     * This function deletes the shoutbox entry
     */
    public function delete(int $shoutId): void;

    /**
     * This returns the top user_shouts, shoutbox objects are always shown regardless and count against the total
     * number of objects shown
     *
     * @return int[]
     */
    public function getTop(int $limit, ?int $userId = null): array;

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void;

    /**
     * Inserts a new shout item and returns the created id
     */
    public function insert(
        int $userId,
        int $date,
        string $comment,
        int $sticky,
        int $objectId,
        string $objectType,
        string $data
    ): int;


    /**
     * This updates a shoutbox entry
     */
    public function update(int $shoutId, string $comment, bool $isSticky): void;

    /**
     * @return array{
     *  id: int,
     *  user: int,
     *  text: string,
     *  date: int,
     *  sticky:int,
     *  object_id: int,
     *  object_type: string,
     *  data: string
     * }
     */
    public function getDataById(
        int $shoutId
    ): array;
}
