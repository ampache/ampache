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
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\ModelFactoryInterface;
use PDO;
use Psr\Log\LoggerInterface;

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
        private LoggerInterface $logger,
    ) {}

    /**
     * Starts or stops the broadcast, resetting the current song and listener count
     */
    /**
     * Clears the started state of broadcasts that cannot be running
     *
     * Only the rows that are provably dead are touched: a broadcast with no `key` has nothing a listener
     * could register with, so it is a leftover whatever `started` claims. A row that still holds its key
     * is left alone, because this runs while the server may be up and that one may be a live broadcast.
     */
    public function collectGarbage(): void
    {
        try {
            $this->connection->query(
                'UPDATE `broadcast` SET `started` = 0, `song` = 0, `listeners` = 0 WHERE `started` = 1 AND (`key` IS NULL OR `key` = \'\')'
            );
        } catch (DatabaseException) {
            $this->logger->debug('collectGarbage error', [LegacyLogger::CONTEXT_TYPE => self::class]);
        }
    }

    /**
     * Creates a new broadcast owned by the given user and returns its id
     */
    public function create(int $userId, string $name, string $description, bool $isPrivate = false): int
    {
        $this->connection->query(
            'INSERT INTO `broadcast` (`user`, `name`, `description`, `is_private`) VALUES (?, ?, ?, ?)',
            [$userId, $name, $description, ($isPrivate) ? 1 : 0]
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
     * Loads a single broadcast, or null when the id matches nothing
     */
    public function findById(int $objectId): ?Broadcast
    {
        $broadcast = $this->modelFactory->createBroadcast($objectId);

        if ($broadcast->isNew()) {
            return null;
        }

        return $broadcast;
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
     * Returns the full rows for a set of ids, for the object cache
     *
     * @param array<int|string> $broadcastIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $broadcastIds): array
    {
        if ($broadcastIds === []) {
            return [];
        }

        $result = $this->connection->query(
            'SELECT * FROM `broadcast` WHERE `id` IN (' . implode(',', array_map(intval(...), $broadcastIds)) . ')'
        );

        $results = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * Writes the editable properties of an existing broadcast
     */
    /**
     * Writes the broadcast, inserting it when it has no id yet
     *
     * Returns the id a new row was given, or null when an existing one was updated.
     */
    public function persist(Broadcast $broadcast): ?int
    {
        if (!$broadcast->isNew()) {
            $this->update($broadcast);

            return null;
        }

        return $this->create($broadcast->user, (string) $broadcast->name, (string) $broadcast->description, $broadcast->is_private);
    }

    /**
     * Clears the started state of every broadcast
     *
     * A broadcast only exists for as long as its websocket connection does, so nothing can still be
     * running once the server that held those connections has gone. Called when the server starts, which
     * is what stops a crash leaving rows that claim to be live forever.
     */
    public function resetStartedState(): int
    {
        $result = $this->connection->query(
            'UPDATE `broadcast` SET `started` = 0, `key` = \'\', `song` = 0, `listeners` = 0 WHERE `started` = 1'
        );

        return $result->rowCount();
    }

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

    public function updateState(Broadcast $broadcast, int $started, string $key): void
    {
        $this->connection->query(
            'UPDATE `broadcast` SET `started` = ?, `key` = ?, `song` = \'0\', `listeners` = \'0\' WHERE `id` = ?',
            [$started, $key, $broadcast->getId()]
        );
    }
}
