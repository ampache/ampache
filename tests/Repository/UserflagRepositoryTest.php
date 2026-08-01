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
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserflagRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private UserflagRepository $subject;

    public function testAdjustWeightIgnoresATypeWithoutTheColumn(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->subject->adjustWeight('playlist', 666, 1);
    }

    public function testAdjustWeightMovesTheCounterOnTheRatedTable(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `song` SET `weight` = `weight` - 1 WHERE `id` = ?;',
                [666]
            );

        $this->subject->adjustWeight('song', 666, -1);
    }

    public function testCollectGarbageRefusesAnUnsupportedType(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->logger->expects(static::once())
            ->method('critical');

        $this->subject->collectGarbage('some-type', 666);
    }

    public function testCollectGarbageSweepsOneNamedObject(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `user_flag` WHERE `object_type` = ? AND `object_id` = ?',
                ['song', 666]
            );

        $this->subject->collectGarbage('song', 666);
    }

    public function testGetFlagDateReturnsNullWhenTheUserHasNotFlaggedIt(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `date` FROM `user_flag` WHERE `user` = ? AND `object_id` = ? AND `object_type` = ?',
                [42, 666, 'song']
            )
            ->willReturn(false);

        static::assertNull($this->subject->getFlagDate(666, 'song', 42));
    }

    public function testGetFlagDateReturnsTheStoredDate(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `date` FROM `user_flag` WHERE `user` = ? AND `object_id` = ? AND `object_type` = ?',
                [42, 666, 'song']
            )
            ->willReturn('123456');

        static::assertSame(123456, $this->subject->getFlagDate(666, 'song', 42));
    }

    public function testGetFlagDatesKeysTheRowsByObjectAndCastsTheIdList(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `object_id`, `date` FROM `user_flag` WHERE `user` = ? AND `object_id` IN (1,2) AND `object_type` = ?',
                [42, 'song']
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['object_id' => '2', 'date' => '123456'], false);

        static::assertSame(
            [2 => 123456],
            $this->subject->getFlagDates('song', [1, '2'], 42)
        );
    }

    public function testGetFlagDatesSkipsTheQueryForAnEmptyList(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        static::assertSame([], $this->subject->getFlagDates('song', [], 42));
    }

    public function testMigrateMovesTheFlagsKeepingWhatTheTargetHad(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE IGNORE `user_flag` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
                [42, 'song', 666]
            );

        $this->subject->migrate('song', 666, 42);
    }

    public function testSetFlagReplacesThePreviousOne(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'REPLACE INTO `user_flag` (`object_id`, `object_type`, `user`, `date`) VALUES (?, ?, ?, ?)',
                [666, 'song', 42, 123456]
            );

        $this->subject->setFlag(666, 'song', 42, 123456);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new UserflagRepository(
            $this->connection,
            $this->logger
        );
    }
}
