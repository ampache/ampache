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

class RatingRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private RatingRepository $subject;

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

    public function testCollectGarbageSweepsOneObjectAndTheEmptyRatings(): void
    {
        $calls = [];

        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$calls): PDOStatement {
                $calls[] = $sql;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->collectGarbage('song', 666);

        static::assertSame(
            [
                'DELETE FROM `rating` WHERE `object_type` = ? AND `object_id` = ?',
                'DELETE FROM `rating` WHERE `rating`.`rating` = 0;',
            ],
            $calls
        );
    }

    public function testGetAverageRatingReturnsNullWhileOnlyOneUserHasRated(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT ROUND(AVG(`rating`), 2) AS `rating` FROM `rating` WHERE `object_id` = ? AND `object_type` = ? HAVING COUNT(object_id) > 1',
                [666, 'song']
            )
            ->willReturn(false);

        static::assertNull($this->subject->getAverageRating(666, 'song'));
    }

    public function testGetUserRatingReturnsTheStoredValue(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `rating` FROM `rating` WHERE `user` = ? AND `object_id` = ? AND `object_type` = ? AND `rating` > 0;',
                [42, 666, 'song']
            )
            ->willReturn('4');

        static::assertSame(4, $this->subject->getUserRating(666, 'song', 42));
    }

    public function testGetUserRatingsKeysTheRowsByObjectAndCastsTheIdList(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `rating`, `object_id` FROM `rating` WHERE `user` = ? AND `object_id` IN (1,2) AND `object_type` = ?',
                [42, 'song']
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['object_id' => '2', 'rating' => '5'], false);

        static::assertSame(
            [2 => 5],
            $this->subject->getUserRatings('song', [1, '2'], 42)
        );
    }

    public function testGetUserRatingsSkipsTheQueryForAnEmptyList(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        static::assertSame([], $this->subject->getUserRatings('song', [], 42));
    }

    public function testMigrateMovesTheRatingsKeepingWhatTheTargetHad(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE IGNORE `rating` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
                [42, 'song', 666]
            );

        $this->subject->migrate('song', 666, 42);
    }

    public function testSetRatingReplacesThePreviousOne(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'REPLACE INTO `rating` (`object_id`, `object_type`, `rating`, `user`, `date`) VALUES (?, ?, ?, ?, ?)',
                [666, 'song', 4, 42, 123456]
            );

        $this->subject->setRating(666, 'song', 4, 42, 123456);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new RatingRepository(
            $this->connection,
            $this->logger
        );
    }
}
