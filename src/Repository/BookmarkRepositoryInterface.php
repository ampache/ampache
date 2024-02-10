<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\User;
use DateTimeInterface;

interface BookmarkRepositoryInterface
{
    /**
     * @return list<int>
     */
    public function getByUser(User $user): array;

    /**
     * @return list<int>
     */
    public function getByUserAndComment(User $user, string $comment): array;

    public function delete(int $bookmarkId): void;

    /**
     * Remove bookmark for items that no longer exist.
     */
    public function collectGarbage(): void;

    public function update(int $bookmarkId, int $position, DateTimeInterface $date): void;

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void;

    /**
     * Finds a single item by id
     */
    public function findById(int $itemId): ?Bookmark;
}
