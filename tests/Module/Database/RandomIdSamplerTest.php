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

use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RandomIdSamplerTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private RandomIdSampler $subject;

    public function testBoundParametersAreForwardedToEveryQuery(): void
    {
        $this->connection->expects(self::once())
            ->method('fetchRow')
            ->with(self::anything(), [42])
            ->willReturn(['min_id' => 5, 'max_id' => 5]);

        $this->connection->expects(self::exactly(2))
            ->method('fetchOne')
            ->willReturnCallback(function (string $sql, array $params) {
                if (str_starts_with($sql, 'SELECT COUNT(*)')) {
                    self::assertSame([42], $params);

                    return 5000;
                }

                self::assertSame([42, 5], $params);

                return 5;
            });

        self::assertSame([5], $this->subject->sample('song', 'id', 'WHERE `catalog` = ?', [42], 1));
    }

    public function testDuplicateProbeHitsAreNotDoubleCounted(): void
    {
        $this->connection->method('fetchRow')->willReturn(['min_id' => 1, 'max_id' => 1]);
        $this->connection->method('fetchOne')
            ->willReturnCallback(fn(string $sql) => str_starts_with($sql, 'SELECT COUNT(*)') ? 5 : 1);

        self::assertSame([1], $this->subject->sample('song', 'id', 'WHERE 1=1', [], 1));
    }

    public function testGivesUpAndReturnsFewerThanTheLimitWhenNothingElseMatches(): void
    {
        $this->connection->method('fetchRow')->willReturn(['min_id' => 1, 'max_id' => 1000000]);
        $this->connection->method('fetchOne')
            ->willReturnCallback(fn(string $sql) => str_starts_with($sql, 'SELECT COUNT(*)') ? 1000000 : false);

        self::assertSame([], $this->subject->sample('song', 'id', 'WHERE 1=1', [], 5));
    }

    public function testLimitOfZeroReturnsNothingWithoutQuerying(): void
    {
        $this->connection->expects(self::never())->method('fetchRow');
        $this->connection->expects(self::never())->method('fetchOne');
        $this->connection->expects(self::never())->method('query');

        self::assertSame([], $this->subject->sample('song', 'id', 'WHERE 1=1', [], 0));
    }

    public function testNoMatchingRowsReturnsEmpty(): void
    {
        $this->connection->method('fetchRow')->willReturn(false);

        self::assertSame([], $this->subject->sample('song', 'id', 'WHERE 1=1', [], 5));
    }

    /**
     * The pool being no bigger than what was asked for is exactly the case that used to burn the whole
     * probe budget: every id that exists is already found, so every further probe re-hits a duplicate.
     */
    public function testPoolNoBiggerThanTheLimitIsReturnedDirectlyWithoutProbing(): void
    {
        $this->connection->method('fetchRow')->willReturn(['min_id' => 1, 'max_id' => 16]);
        $this->connection->method('fetchOne')
            ->with(self::stringContains('SELECT COUNT(*) FROM `song`'))
            ->willReturn(16);

        $result = $this->createMock(PDOStatement::class);
        $result->method('fetchColumn')->willReturnOnConsecutiveCalls(...[...range(1, 16), false]);

        $this->connection->expects(self::once())
            ->method('query')
            ->with('SELECT `id` FROM `song` WHERE 1=1')
            ->willReturn($result);

        $ids = $this->subject->sample('song', 'id', 'WHERE 1=1', [], 25);

        sort($ids);
        self::assertSame(range(1, 16), $ids);
    }

    public function testProbesTheRangeReturnedByMinMaxWhenThePoolIsLargerThanTheLimit(): void
    {
        $this->connection->method('fetchRow')
            ->with(self::stringContains('SELECT MIN(`id`) AS `min_id`, MAX(`id`) AS `max_id` FROM `song` WHERE 1=1'), [])
            ->willReturn(['min_id' => 10, 'max_id' => 12]);

        $probeResults = [10, 11, 12];
        $this->connection->method('fetchOne')
            ->willReturnCallback(function (string $sql) use (&$probeResults) {
                if (str_starts_with($sql, 'SELECT COUNT(*)')) {
                    return 100;
                }

                self::assertStringContainsString('SELECT `id` FROM `song` WHERE 1=1 AND `id` >= ? ORDER BY `id` LIMIT 1', $sql);

                return array_shift($probeResults);
            });

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
