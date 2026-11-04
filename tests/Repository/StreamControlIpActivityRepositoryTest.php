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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StreamControlIpActivityRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private StreamControlIpActivityRepository $subject;

    public function testCollectGarbageDeletesOldRows(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `tmp_stream_control` WHERE `date` < ?',
                static::callback(static fn(array $params): bool => count($params) === 1 && is_int($params[0]))
            );

        $this->subject->collectGarbage();
    }

    public function testRecordInsertsARow(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `tmp_stream_control` (`ip`, `date`, `hits`, `size`, `time`) VALUES (?, ?, ?, ?, ?)',
                ['198.51.100.1', 12345, 1, 4096, 180]
            );

        $this->subject->record('198.51.100.1', 12345, 1, 4096, 180);
    }

    public function testSumBandwidthSumsTheSizeColumn(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT IFNULL(SUM(`size`), 0) FROM `tmp_stream_control` WHERE `ip` = ? AND `date` >= ? AND `date` <= ?',
                ['198.51.100.1', 100, 200]
            )
            ->willReturn('4096');

        self::assertSame(4096, $this->subject->sumBandwidth('198.51.100.1', 100, 200));
    }

    public function testSumHitsSumsTheHitsColumn(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT IFNULL(SUM(`hits`), 0) FROM `tmp_stream_control` WHERE `ip` = ? AND `date` >= ? AND `date` <= ?',
                ['198.51.100.1', 100, 200]
            )
            ->willReturn('3');

        self::assertSame(3, $this->subject->sumHits('198.51.100.1', 100, 200));
    }

    public function testSumTimeSumsTheTimeColumn(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT IFNULL(SUM(`time`), 0) FROM `tmp_stream_control` WHERE `ip` = ? AND `date` >= ? AND `date` <= ?',
                ['198.51.100.1', 100, 200]
            )
            ->willReturn('180');

        self::assertSame(180, $this->subject->sumTime('198.51.100.1', 100, 200));
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new StreamControlIpActivityRepository(
            $this->connection,
            $this->logger,
        );
    }
}
