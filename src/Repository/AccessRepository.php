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

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Module\Authorization\Access;
use Ampache\Repository\Model\ModelFactoryInterface;
use Doctrine\DBAL\Connection;

final class AccessRepository implements AccessRepositoryInterface
{
    private ModelFactoryInterface $modelFactory;

    private Connection $connection;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        Connection $connection
    ) {
        $this->modelFactory = $modelFactory;
        $this->connection   = $connection;
    }

    /**
     * Returns a full listing of all access rules on this server
     *
     * @return Access[]
     */
    public function getAccessLists(): array
    {
        $dbResults = $this->connection->executeQuery('SELECT `id` FROM `access_list`');

        $results = [];

        while ($id = $dbResults->fetchOne()) {
            $results[] = $this->modelFactory->createAccess((int) $id);
        }

        return $results;
    }

    /**
     * Searches for certain ip and config. Returns true if a match was found
     */
    public function findByIp(
        string $userIp,
        int $level,
        string $type,
        ?int $userId
    ): bool {
        $sql = 'SELECT `id` FROM `access_list` WHERE `start` <= ? AND `end` >= ? AND `level` >= ? AND `type` = ?';

        $params  = [inet_pton($userIp), inet_pton($userIp), $level, $type];

        if ($userId !== null && $userId != -1) {
            $sql .= ' AND `user` IN (?, -1)';
            $params[] = $userId;
        } else {
            $sql .= ' AND `user` = -1';
        }

        return $this->connection->executeQuery(
            $sql,
            $params
        )->rowCount() > 0;
    }

    /**
     * deletes the specified access_list entry
     */
    public function delete(int $accessId): void
    {
        $this->connection->executeQuery(
            'DELETE FROM `access_list` WHERE `id` = ?',
            [$accessId]
        );
    }

    /**
     * This sees if the ACL that we've specified already exists in order to
     * prevent duplicates. The name is ignored.
     */
    public function exists(
        string $inAddrStart,
        string $inAddrEnd,
        string $type,
        int $userId
    ): bool {
        return $this->connection->executeQuery(
            'SELECT * FROM `access_list` WHERE `start` = ? AND `end` = ? AND `type` = ? AND `user` = ?',
            [$inAddrStart, $inAddrEnd, $type, $userId]
        )->rowCount() > 0;
    }

    /**
     * Creates a new acl item
     *
     * @param string $startIp The startip in in-addr notation
     * @param string $endIp The end ip in in-addr notation
     * @param string $name Name of the acl
     * @param int $userId Designated user id (or -1 if none)
     * @param int $level Access level
     * @param string $type Access type
     */
    public function create(
        string $startIp,
        string $endIp,
        string $name,
        int $userId,
        int $level,
        string $type
    ): void {
        $this->connection->executeQuery(
            'INSERT INTO `access_list` (`name`, `level`, `start`, `end`, `user`, `type`) VALUES (?, ?, ?, ?, ?, ?)',
            [$name, $level, $startIp, $endIp, $userId, $type]
        );
    }

    /**
     * Updates the data of a certain acl item
     *
     * @param int $accessId Id of an existing acl item
     * @param string $startIp The startip in in-addr notation
     * @param string $endIp The end ip in in-addr notation
     * @param string $name Name of the acl
     * @param int $userId Designated user id (or -1 if none)
     * @param int $level Access level
     * @param string $type Access type
     */
    public function update(
        int $accessId,
        string $startIp,
        string $endIp,
        string $name,
        int $userId,
        int $level,
        string $type
    ): void {
        $this->connection->executeQuery(
            'UPDATE `access_list` SET `start` = ?, `end` = ?, `level` = ?, `user` = ?, `name` = ?, `type` = ? WHERE `id` = ?',
            [$startIp, $endIp, $level, $userId, $name, $type, $accessId]
        );
    }
}
