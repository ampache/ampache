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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\User;
use DateTimeInterface;
use Psr\Log\LoggerInterface;

final readonly class BookmarkRepository implements BookmarkRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    /**
     * Remove bookmark for items that no longer exist.
     */
    public function collectGarbage(): void
    {
        $types = [
            'song',
            'video',
            'podcast_episode',
        ];
        foreach ($types as $type) {
            try {
                $this->connection->query(
                    sprintf(
                        "DELETE FROM `bookmark` USING `bookmark` LEFT JOIN `%s` ON `%s`.`id` = `bookmark`.`object_id` WHERE `bookmark`.`object_type` = '%s' AND `%s`.`id` IS NULL;",
                        $type,
                        $type,
                        $type,
                        $type
                    )
                );
            } catch (DatabaseException) {
                $this->logger->debug(
                    'collectGarbage error',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

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
    ): void {
        if ($latestOnly) {
            $this->connection->query(
                'DELETE FROM `bookmark` WHERE `user` = ? AND `comment` = ? AND `object_type` = ? AND `object_id` = ?;',
                [$userId, $comment, $objectType, $objectId]
            );
        }

        $this->connection->query(
            'INSERT INTO `bookmark` (`user`, `position`, `comment`, `object_type`, `object_id`, `creation_date`, `update_date`) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$userId, $position, $comment, $objectType, $objectId, $updateDate, $updateDate]
        );
    }

    public function delete(int $bookmarkId): void
    {
        $this->connection->query(
            'DELETE FROM `bookmark` WHERE `id` = ?',
            [$bookmarkId]
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

    /**
     * Reads one of a user's bookmarks by its own id, which is how a `bookmark` object type addresses it
     *
     * @return list<int>
     */
    public function findIdsByBookmarkId(int $userId, int $bookmarkId): array
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `bookmark` WHERE `user` = ? AND `id` = ?;',
            [$userId, $bookmarkId]
        );

        $bookmarkIds = [];
        while ($rowId = $result->fetchColumn()) {
            $bookmarkIds[] = (int) $rowId;
        }

        return $bookmarkIds;
    }

    /**
     * Reads the ids a user has bookmarked against one object, newest first
     *
     * @return list<int>
     */
    public function findIdsByObject(int $userId, string $objectType, int $objectId, ?string $comment): array
    {
        $sql    = 'SELECT `id` FROM `bookmark` WHERE `user` = ? AND `object_type` = ? AND `object_id` = ? ';
        $params = [$userId, $objectType, $objectId];
        if ($comment !== null && $comment !== '') {
            $sql .= 'AND `comment` = ? ';
            $params[] = $comment;
        }

        $result = $this->connection->query($sql . 'ORDER BY `update_date` DESC;', $params);

        $bookmarkIds = [];
        while ($bookmarkId = $result->fetchColumn()) {
            $bookmarkIds[] = (int) $bookmarkId;
        }

        return $bookmarkIds;
    }

    /**
     * @return int[]
     */
    public function getByUser(User $user): array
    {
        $ids = [];

        $result = $this->connection->query(
            'SELECT `id` FROM `bookmark` WHERE `user` = ?',
            [$user->getId()]
        );

        while ($rowId = $result->fetchColumn()) {
            $ids[] = (int) $rowId;
        }

        return $ids;
    }

    /**
     * @return int[]
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

    /**
     * Reads the bookmark a user holds against one object
     *
     * @return array<string, mixed>
     */
    public function getRowByObject(string $objectType, int $objectId, int $userId): array
    {
        $row = $this->connection->fetchRow(
            'SELECT * FROM `bookmark` WHERE `object_type` = ? AND `object_id` = ? AND `user` = ?',
            [$objectType, $objectId, $userId]
        );

        return ($row === false)
            ? []
            : $row;
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

    public function update(int $bookmarkId, int $position, DateTimeInterface $date): void
    {
        $this->connection->query(
            'UPDATE `bookmark` SET `position` = ?, `update_date` = ? WHERE `id` = ?',
            [$position, $date->getTimestamp(), $bookmarkId]
        );
    }

    /**
     * Updates the position and the comment of a bookmark, which is what the edit dialog sends
     */
    public function updateWithComment(int $bookmarkId, int $position, string $comment, int $updateDate): void
    {
        $this->connection->query(
            'UPDATE `bookmark` SET `position` = ?, `comment` = ?, `update_date` = ? WHERE `id` = ?',
            [$position, $comment, $updateDate, $bookmarkId]
        );
    }
}
