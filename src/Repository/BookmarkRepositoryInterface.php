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

use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\User;
use DateTimeInterface;

interface BookmarkRepositoryInterface
{
    /**
     * Remove bookmark for items that no longer exist.
     */
    public function collectGarbage(): void;

    /**
     * Stores a new bookmark, dropping the user's previous one for that object when only the latest is kept
     */
    public function create(
        int $userId,
        int $position,
        string $comment,
        string $objectType,
        int $objectId,
        int $updateDate,
        bool $latestOnly,
    ): void;

    public function delete(int $bookmarkId): void;

    /**
     * Finds a single item by id
     */
    public function findById(int $itemId): ?Bookmark;

    /**
     * Reads one of a user's bookmarks by its own id, which is how a `bookmark` object type addresses it
     *
     * @return list<int>
     */
    public function findIdsByBookmarkId(int $userId, int $bookmarkId): array;

    /**
     * Reads the ids a user has bookmarked against one object, newest first
     *
     * @return list<int>
     */
    public function findIdsByObject(int $userId, string $objectType, int $objectId, ?string $comment): array;

    /**
     * @return int[]
     */
    public function getByUser(User $user): array;

    /**
     * @return int[]
     */
    public function getByUserAndComment(User $user, string $comment): array;

    /**
     * Reads the bookmark a user holds against one object
     *
     * @return array<string, mixed>
     */
    public function getRowByObject(string $objectType, int $objectId, int $userId): array;

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void;

    public function update(int $bookmarkId, int $position, DateTimeInterface $date): void;

    /**
     * Updates the position and the comment of a bookmark, which is what the edit dialog sends
     */
    public function updateWithComment(int $bookmarkId, int $position, string $comment, int $updateDate): void;
}
