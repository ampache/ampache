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
 */

namespace Ampache\Module\System\Update;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Update\Migration\MigrationInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use ArrayIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class UpdateRunnerTest extends TestCase
{
    use ConsecutiveParams;

    private DatabaseConnectionInterface&MockObject $connection;

    private LoggerInterface&MockObject $logger;

    private UpdateInfoRepositoryInterface&MockObject $updateInfoRepository;

    private ConfigContainerInterface&MockObject $configContainer;

    private UpdateRunner $subject;

    protected function setUp(): void
    {
        $this->connection           = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger               = $this->createMock(LoggerInterface::class);
        $this->updateInfoRepository = $this->createMock(UpdateInfoRepositoryInterface::class);
        $this->configContainer      = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new UpdateRunner(
            $this->connection,
            $this->logger,
            $this->updateInfoRepository,
            $this->configContainer
        );
    }

    public function testRunTableCheckReturnsTableWithoutAction(): void
    {
        $tableName = 'some-table';
        $sql       = 'some-sql';

        $migration = $this->createMock(MigrationInterface::class);
        $updates   = new ArrayIterator([[
            'migration' => $migration,
        ]]);

        $migration->expects(static::once())
            ->method('getTableMigrations')
            ->with('utf8mb4_unicode_ci', 'utf8mb4', 'InnoDB')
            ->willReturn(new ArrayIterator([$tableName => $sql]));

        $this->connection->expects(static::once())
            ->method('query')
            ->with(sprintf('DESCRIBE `%s`', $tableName))
            ->willThrowException(new QueryFailedException());

        $this->logger->expects(static::once())
            ->method('warning')
            ->with(
                'Missing table: ' . $tableName,
                [
                    LegacyLogger::CONTEXT_TYPE => UpdateRunner::class
                ]
            );

        static::assertSame(
            $tableName,
            $this->subject->runTableCheck($updates)->current()
        );
    }

    public function testRunTableCheckReturnsNullIfEverythingIsAlright(): void
    {
        $tableName = 'some-table';
        $sql       = 'some-sql';

        $migration = $this->createMock(MigrationInterface::class);
        $updates   = new ArrayIterator([[
            'migration' => $migration,
        ]]);

        $migration->expects(static::once())
            ->method('getTableMigrations')
            ->with('utf8mb4_unicode_ci', 'utf8mb4', 'InnoDB')
            ->willReturn(new ArrayIterator([$tableName => $sql]));

        $this->connection->expects(static::once())
            ->method('query')
            ->with(sprintf('DESCRIBE `%s`', $tableName));

        static::assertNull(
            $this->subject->runTableCheck($updates, true)->current(),
        );
    }
}
