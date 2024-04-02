<?php

declare(strict_types=1);

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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\User;
use DateTimeInterface;

final readonly class BookmarkRepository implements BookmarkRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection)
    {
    }

    /**
     * @return list<int>
     */
    public function getByUser(User $user): array
    {
        $ids = [];

        $result = $this->connection->query(
            'SELECT `id` FROM `bookmark` WHERE `user` = ?',
            [
                $user->getId()
            ]
        );

        while ($rowId = $result->fetchColumn()) {
            $ids[] = (int) $rowId;
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    public function getByUserAndComment(User $user, string $comment): array
    {
        $ids = [];

        $result = $this->connection->query(
            'SELECT `id` FROM `bookmark` WHERE `user` = ? AND `comment` = ?',
            [
                $user->getId(),
                $comment
            ]
        );

        while ($rowId = $result->fetchColumn()) {
            $ids[] = (int) $rowId;
        }

        return $ids;
    }

    public function delete(int $bookmarkId): void
    {
        $this->connection->query(
            'DELETE FROM `bookmark` WHERE `id` = ?',
            [
                $bookmarkId
            ]
        );
    }

    /**
     * Remove bookmark for items that no longer exist.
     */
    public function collectGarbage(): void
    {
        $types = ['song', 'video', 'podcast_episode'];
        foreach ($types as $type) {
            $this->connection->query(
                sprintf(
                    'DELETE FROM `bookmark` USING `bookmark` LEFT JOIN `%s` ON `%s`.`id` = `bookmark`.`object_id` WHERE `bookmark`.`object_type` = \'%s\' AND `%s`.`id` IS NULL;',
                    $type,
                    $type,
                    $type,
                    $type
                )
            );
        }
    }

    public function update(int $bookmarkId, int $position, DateTimeInterface $date): void
    {
        $this->connection->query(
            'UPDATE `bookmark` SET `position` = ?, `update_date` = ? WHERE `id` = ?',
            [$position, $date->getTimestamp(), $bookmarkId]
        );
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->query(
            'UPDATE IGNORE `bookmark` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = ?',
            [$newObjectId, $oldObjectId, ucfirst($objectType)]
        );
    }

    /**
     * Finds a single item by id
     */
    public function findById(int $itemId): ?Bookmark
    {
        $bookmark = new Bookmark($itemId);

        if ($bookmark->isNew()) {
            return null;
        }

        return $bookmark;
    }
}
