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

use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\ModelFactoryInterface;
use Doctrine\DBAL\Connection;

final class BroadcastRepository implements BroadcastRepositoryInteface
{
    private Connection $database;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        Connection $database,
        ModelFactoryInterface $modelFactory
    ) {
        $this->database     = $database;
        $this->modelFactory = $modelFactory;
    }

    /**
     * @return int[]
     */
    public function getByUser(int $userId): array
    {
        $db_results = $this->database->executeQuery(
            'SELECT `id` FROM `broadcast` WHERE `user` = ?',
            [$userId]
        );

        $broadcasts = [];
        while ($rowId = $db_results->fetchOne()) {
            $broadcasts[] = (int) $rowId;
        }

        return $broadcasts;
    }

    /**
     * Get broadcast from its key.
     */
    public function findByKey(string $key): ?Broadcast
    {
        $rowId = $this->database->fetchOne(
            'SELECT `id` FROM `broadcast` WHERE `key` = ?',
            [$key]
        );

        if ($rowId === false) {
            return null;
        }

        return $this->modelFactory->createBroadcast((int) $rowId);
    }

    public function delete(Broadcast $broadcast): void
    {
        $this->database->executeQuery(
            'DELETE FROM `broadcast` WHERE `id` = ?',
            [$broadcast->getId()]
        );
    }

    /**
     * Create a broadcast
     */
    public function create(
        int $userId,
        string $name,
        string $description = ''
    ): int {
        $this->database->executeQuery(
            'INSERT INTO `broadcast` (`user`, `name`, `description`, `is_private`) VALUES (?, ?, ?, ?)',
            [$userId, $name, $description, 1]
        );

        return (int) $this->database->lastInsertId();
    }

    /**
     * Update broadcast state.
     */
    public function updateState(
        int $broadcastId,
        int $started,
        string $key = ''
    ): void {
        $this->database->executeQuery(
            'UPDATE `broadcast` SET `started` = ?, `key` = ?, `song` = ?, `listeners` = ? WHERE `id` = ?',
            [$started, $key, 0, 0, $broadcastId]
        );
    }

    /**
     * Update broadcast listeners
     */
    public function updateListeners(
        int $broadcastId,
        int $listeners
    ): void {
        $this->database->executeQuery(
            'UPDATE `broadcast` SET `listeners` = ? WHERE `id` = ?',
            [$listeners, $broadcastId]
        );
    }

    /**
     * Update broadcast current song.
     */
    public function updateSong(
        int $broadcastId,
        int $songId
    ): void {
        $this->database->executeQuery(
            'UPDATE `broadcast` SET `song` = ? WHERE `id` = ?',
            [$songId, $broadcastId]
        );
    }

    /**
     * Update a broadcast from data array.
     */
    public function update(
        int $broadcastId,
        string $name,
        string $description,
        int $isPrivate
    ): void {
        $this->database->executeQuery(
            'UPDATE `broadcast` SET `name` = ?, `description` = ?, `is_private` = ? WHERE `id` = ?',
            [$name, $description, $isPrivate, $broadcastId]
        );
    }
}
