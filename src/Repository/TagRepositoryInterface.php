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
 *
 */

namespace Ampache\Repository;

interface TagRepositoryInterface
{
    /**
     * This gets the objects from a specified tag and returns an array of object ids, nothing more
     *
     * @return int[]
     */
    public function getTagObjectIds(
        string $type,
        int $tagId,
        ?int $limit = null,
        int $offset = 0
    ): array;

    /**
     * Get all tags from all Songs from [type] (artist, album, ...)
     *
     * @return string[]
     */
    public function getSongTags(string $type, int $objectId): array;

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void;

    /**
     * This checks to see if a tag exists, this has nothing to do with objects or maps
     */
    public function findByName(string $value): ?int;

    /**
     * This is a non-object non type dependent function that just returns tags
     * we've got, it can take filters (this is used by the tag cloud)
     *
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function getByType(string $type = '', string $order = 'count'): array;

    /**
     * This gets the top tags for the specified object using limit
     *
     * @return array<int, array{
     *  user: int,
     *  id: int,
     *  name: string
     * }>
     */
    public function getTopTags(string $type, int $object_id, int $limit = 10): array;
}
