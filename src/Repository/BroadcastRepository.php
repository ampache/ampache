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
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\ModelFactoryInterface;

/**
 * Manages broadcast related database access
 *
 * Tables: `broadcast`
 */
final readonly class BroadcastRepository implements BroadcastRepositoryInterface
{
    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private DatabaseConnectionInterface $connection,
    ) {}

    /**
     * Creates a new broadcast owned by the given user and returns its id
     */
    public function create(int $userId, string $name, string $description): int
    {
        $this->connection->query(
            'INSERT INTO `broadcast` (`user`, `name`, `description`, `is_private`) VALUES (?, ?, ?, \'1\')',
            [$userId, $name, $description]
        );

        return $this->connection->getLastInsertedId();
    }

    /**
     * Deletes a single item
     */
    public function delete(Broadcast $broadcast): void
    {
        $this->connection->query(
            'DELETE FROM `broadcast` WHERE `id` = ?',
            [$broadcast->getId()]
        );
    }

    /**
     * Finds the broadcast currently published under the given key
     */
    public function findByKey(string $key): ?Broadcast
    {
        $objectId = $this->connection->fetchOne(
            'SELECT `id` FROM `broadcast` WHERE `key` = ?',
            [$key]
        );

        if ($objectId === false) {
            return null;
        }

        return $this->modelFactory->createBroadcast((int) $objectId);
    }

    /**
     * Returns the ids of every broadcast owned by the user
     *
     * @return int[]
     */
    public function getIdsByUser(int $userId): array
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `broadcast` WHERE `user` = ?',
            [$userId]
        );

        $broadcasts = [];

        while ($rowId = $result->fetchColumn()) {
            $broadcasts[] = (int) $rowId;
        }

        return $broadcasts;
    }

    /**
     * Writes the editable properties of an existing broadcast
     */
    public function update(Broadcast $broadcast): void
    {
        $this->connection->query(
            'UPDATE `broadcast` SET `name` = ?, `description` = ?, `is_private` = ? WHERE `id` = ?',
            [
                $broadcast->name,
                $broadcast->description,
                ($broadcast->is_private) ? 1 : 0,
                $broadcast->getId(),
            ]
        );
    }

    /**
     * Stores the current listener count
     */
    public function updateListeners(Broadcast $broadcast, int $listeners): void
    {
        $this->connection->query(
            'UPDATE `broadcast` SET `listeners` = ? WHERE `id` = ?',
            [$listeners, $broadcast->getId()]
        );
    }

    /**
     * Stores the song currently being broadcast
     */
    public function updateSong(Broadcast $broadcast, int $songId): void
    {
        $this->connection->query(
            'UPDATE `broadcast` SET `song` = ? WHERE `id` = ?',
            [$songId, $broadcast->getId()]
        );
    }

    /**
     * Starts or stops the broadcast, resetting the current song and listener count
     */
    public function updateState(Broadcast $broadcast, int $started, string $key): void
    {
        $this->connection->query(
            'UPDATE `broadcast` SET `started` = ?, `key` = ?, `song` = \'0\', `listeners` = \'0\' WHERE `id` = ?',
            [$started, $key, $broadcast->getId()]
        );
    }
}
