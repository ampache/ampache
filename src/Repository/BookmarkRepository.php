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

declare(strict_types=1);

namespace Ampache\Repository;

use Doctrine\DBAL\Connection;

final class BookmarkRepository implements BookmarkRepositoryInterface
{
    private Connection $database;

    public function __construct(
        Connection $database
    ) {
        $this->database = $database;
    }

    /**
     * @return int[]
     */
    public function getBookmarks(int $userId): array
    {
        $db_results = $this->database->executeQuery(
            'SELECT `id` FROM `bookmark` WHERE `user` = ?',
            [$userId]
        );

        $ids = [];
        while ($rowId = $db_results->fetchOne()) {
            $ids[] = (int) $rowId;
        }

        return $ids;
    }

    public function delete(int $bookmarkId): void
    {
        $this->database->executeQuery(
            'DELETE FROM `bookmark` WHERE `id` = ?',
            [$bookmarkId]
        );
    }

    /**
     * Remove bookmark for items that no longer exist.
     */
    public function collectGarbage(): void
    {
        $types = ['song', 'video', 'podcast_episode'];

        foreach ($types as $type) {
            $this->database->executeQuery(
                sprintf(
                    'DELETE FROM `bookmark` USING `bookmark` LEFT JOIN `%s` ON `%s`.`id` = `bookmark`.`object_id` WHERE `bookmark`.`object_type` = \'%s\' AND `%s`.`id` IS NULL',
                    $type,
                    $type,
                    $type,
                    $type
                )
            );
        }
    }

    public function update(
        int $userId,
        int $position,
        int $time
    ): void {
        $this->database->executeQuery(
            'UPDATE `bookmark` SET `position` = ?, `update_date` = ? WHERE `id` = ?',
            [$position, $time, $userId]
        );
    }

    /**
     * Creates a new bookmark entry and returns the id of the new dataset
     */
    public function create(
        int $position,
        string $comment,
        string $objectType,
        int $objectId,
        int $userId,
        int $updateDate
    ): int {
        $this->database->executeQuery(
            'INSERT INTO `bookmark` (`user`, `position`, `comment`, `object_type`, `object_id`, `creation_date`, `update_date`) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$userId, $position, $comment, $objectType, $objectId, time(), $updateDate]
        );

        return (int) $this->database->lastInsertId();
    }

    /**
     * Searches for certain bookmarks
     *
     * @return int[]
     */
    public function lookup(
        string $objectType,
        int $objectId,
        int $userId,
        ?string $comment = null
    ): array {
        $comment_sql = '';
        $bookmarks   = [];
        $params      = [$userId, $objectType, $objectId];

        if ($comment !== null) {
            $comment_sql = ' AND `comment` = ?';
            $params[]    = $comment;
        }

        $db_results = $this->database->executeQuery(
            sprintf(
                'SELECT `id` FROM `bookmark` WHERE `user` = ? AND `object_type` = ? AND `object_id` = ?%s',
                $comment_sql
            ),
            $params
        );

        while ($rowId = $db_results->fetchOne()) {
            $bookmarks[] = (int) $rowId;
        }

        return $bookmarks;
    }

    /**
     * Updates existing items matching the values
     */
    public function edit(
        int $position,
        string $comment,
        string $objectType,
        int $objectId,
        int $userId,
        int $updateDate
    ): void {
        $this->database->executeQuery(
            'UPDATE `bookmark` SET `position` = ?, `update_date` = ? WHERE `user` = ? AND `comment` = ? AND `object_type` = ? AND `object_id` = ?',
            [
                $position,
                $updateDate,
                $userId,
                $comment,
                $objectType,
                $objectId,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getDataById(int $bookmarkId): array
    {
        $dbResults = $this->database->fetchAssociative(
            'SELECT * FROM `bookmark` WHERE `id`= ?',
            [$bookmarkId]
        );

        if ($dbResults === false) {
            return [];
        }

        return $dbResults;
    }
}
