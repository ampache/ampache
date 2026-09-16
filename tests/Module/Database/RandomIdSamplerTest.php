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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RandomIdSamplerTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private RandomIdSampler $subject;

    public function testBoundParametersAreForwardedToBothQueries(): void
    {
        $this->connection->expects(self::once())
            ->method('fetchRow')
            ->with(self::anything(), [42])
            ->willReturn(['min_id' => 5, 'max_id' => 5]);

        $this->connection->expects(self::once())
            ->method('fetchOne')
            ->with(self::anything(), [42, 5])
            ->willReturn(5);

        self::assertSame([5], $this->subject->sample('song', 'id', 'WHERE `catalog` = ?', [42], 1));
    }

    public function testDuplicateProbeHitsAreNotDoubleCounted(): void
    {
        $this->connection->method('fetchRow')->willReturn(['min_id' => 1, 'max_id' => 1]);
        $this->connection->method('fetchOne')->willReturn(1);

        self::assertSame([1], $this->subject->sample('song', 'id', 'WHERE 1=1', [], 5));
    }

    public function testGivesUpAndReturnsFewerThanTheLimitWhenNothingElseMatches(): void
    {
        $this->connection->method('fetchRow')->willReturn(['min_id' => 1, 'max_id' => 1000000]);
        $this->connection->method('fetchOne')->willReturn(false);

        self::assertSame([], $this->subject->sample('song', 'id', 'WHERE 1=1', [], 5));
    }

    public function testLimitOfZeroReturnsNothingWithoutQuerying(): void
    {
        $this->connection->expects(self::never())->method('fetchRow');
        $this->connection->expects(self::never())->method('fetchOne');

        self::assertSame([], $this->subject->sample('song', 'id', 'WHERE 1=1', [], 0));
    }

    public function testNoMatchingRowsReturnsEmpty(): void
    {
        $this->connection->method('fetchRow')->willReturn(false);

        self::assertSame([], $this->subject->sample('song', 'id', 'WHERE 1=1', [], 5));
    }

    public function testProbesTheRangeReturnedByMinMax(): void
    {
        $this->connection->method('fetchRow')
            ->with(self::stringContains('SELECT MIN(`id`) AS `min_id`, MAX(`id`) AS `max_id` FROM `song` WHERE 1=1'), [])
            ->willReturn(['min_id' => 10, 'max_id' => 12]);

        $this->connection->method('fetchOne')
            ->with(self::stringContains('SELECT `id` FROM `song` WHERE 1=1 AND `id` >= ? ORDER BY `id` LIMIT 1'))
            ->willReturnOnConsecutiveCalls(10, 11, 12);

        $result = $this->subject->sample('song', 'id', 'WHERE 1=1', [], 3);

        sort($result);
        self::assertSame([10, 11, 12], $result);
    }

    protected function setUp(): void
    {
        $this->connection  = $this->createMock(DatabaseConnectionInterface::class);
        $this->subject     = new RandomIdSampler($this->connection);
    }
}
