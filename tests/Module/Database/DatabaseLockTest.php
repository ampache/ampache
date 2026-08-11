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
use Ampache\Module\Database\Exception\QueryFailedException;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DatabaseLockTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private DatabaseLock $subject;

    public function testAcquirePassesTheGivenTimeout(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT GET_LOCK(?, ?)', [$this->lockName('test_db', 'artist|Various'), 42])
            ->willReturn('1');

        static::assertTrue($this->subject->acquire('artist|Various', 42));
    }

    public function testAcquireReturnsFalseIfTheLockTimedOut(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn('0');

        static::assertFalse($this->subject->acquire('artist|Various'));
    }

    public function testAcquireReturnsFalseIfTheQueryFailed(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willThrowException(new QueryFailedException());

        static::assertFalse($this->subject->acquire('artist|Various'));
    }

    public function testAcquireReturnsTrueIfTheLockWasTaken(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT GET_LOCK(?, ?)', [$this->lockName('test_db', 'artist|Various'), 10])
            ->willReturn('1');

        static::assertTrue($this->subject->acquire('artist|Various'));
    }

    public function testAcquireScopesTheLockNameToTheDatabase(): void
    {
        $configContainer = $this->createMock(ConfigContainerInterface::class);
        $configContainer->method('get')
            ->with('database_name')
            ->willReturn('other_db');

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT GET_LOCK(?, ?)', [$this->lockName('other_db', 'artist|Various'), 10])
            ->willReturn('1');

        $subject = new DatabaseLock($this->connection, $configContainer, $this->logger);

        static::assertTrue($subject->acquire('artist|Various'));
    }

    public function testReleaseReleasesTheLock(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT RELEASE_LOCK(?)', [$this->lockName('test_db', 'artist|Various')])
            ->willReturn($this->createMock(PDOStatement::class));

        $this->subject->release('artist|Various');
    }

    public function testReleaseSwallowsAFailedQuery(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willThrowException(new QueryFailedException());

        $this->subject->release('artist|Various');

        $this->addToAssertionCount(1);
    }

    protected function setUp(): void
    {
        $this->connection      = $this->createMock(DatabaseConnectionInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);

        $this->configContainer->method('get')
            ->with('database_name')
            ->willReturn('test_db');

        $this->subject = new DatabaseLock($this->connection, $this->configContainer, $this->logger);
    }

    private function lockName(string $database, string $name): string
    {
        return 'ampache_' . md5($database . '|' . $name);
    }
}
