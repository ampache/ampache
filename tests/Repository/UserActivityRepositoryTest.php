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
use Ampache\Module\Database\Exception\QueryFailedException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserActivityRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private UserActivityRepository $subject;

    public function testCollectGarbageDeletesTheActivitiesOfASingleObject(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `user_activity` WHERE `object_type` = ? AND `object_id` = ?',
                ['song', 666]
            );

        $this->subject->collectGarbage('song', 666);
    }

    public function testCollectGarbageIgnoresAnUnsupportedType(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->subject->collectGarbage('some-type', 666);
    }

    public function testCollectGarbageRunsTheRestOfTheSweepAfterAFailedStatement(): void
    {
        $calls = 0;

        // the first statement blowing up must not stop the ones behind it
        $this->connection->expects(static::atLeast(2))
            ->method('query')
            ->willReturnCallback(function () use (&$calls): PDOStatement {
                $calls++;

                if ($calls === 1) {
                    throw new QueryFailedException();
                }

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->collectGarbage();

        static::assertSame(13, $calls);
    }

    public function testDeleteByDateDeletesTheEntry(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `user_activity` WHERE `activity_date` = ? AND `action` = ? AND `user` = ?',
                [123456, 'play', 42]
            );

        $this->subject->deleteByDate(123456, 'play', 42);
    }

    public function testGetActivitiesReturnsIds(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `user_activity` WHERE `user` = ? AND `activity_date` <= ? ORDER BY `activity_date` DESC LIMIT 5',
                [42, 123456]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => '666'], false);

        static::assertSame(
            [666],
            $this->subject->getActivities(42, 5, 123456)
        );
    }

    public function testRegisterGenericEntryInsertsTheActivity(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`) VALUES (?, ?, ?, ?, ?)',
                [42, 'play', 'song', 666, 123456]
            );

        $this->subject->registerGenericEntry(42, 'play', 'song', 666, 123456);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new UserActivityRepository(
            $this->connection
        );
    }
}
