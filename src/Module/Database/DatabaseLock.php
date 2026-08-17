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

namespace Ampache\Module\Database;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\LegacyLogger;
use Psr\Log\LoggerInterface;

/**
 * Wraps the server's GET_LOCK/RELEASE_LOCK functions
 *
 * The lock is held by the connection, so it is only useful within a single request.
 */
final readonly class DatabaseLock implements DatabaseLockInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private ConfigContainerInterface $configContainer,
        private LoggerInterface $logger,
    ) {}

    public function acquire(string $name, int $timeout = self::DEFAULT_TIMEOUT): bool
    {
        try {
            $result = $this->connection->fetchOne(
                'SELECT GET_LOCK(?, ?)',
                [$this->lockName($name), $timeout]
            );
        } catch (DatabaseException) {
            $result = false;
        }

        if ((int) $result === 1) {
            return true;
        }

        $this->logger->warning(
            sprintf('Could not acquire lock {%s}, continuing without it', $name),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        return false;
    }

    public function release(string $name): void
    {
        try {
            $this->connection->query('SELECT RELEASE_LOCK(?)', [$this->lockName($name)]);
        } catch (DatabaseException) {
            $this->logger->warning(
                sprintf('Could not release lock {%s}', $name),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }
    }

    /**
     * Lock names are global to the database server, so the database name scopes them to this install
     */
    private function lockName(string $name): string
    {
        return 'ampache_' . md5($this->configContainer->get('database_name') . '|' . $name);
    }
}
