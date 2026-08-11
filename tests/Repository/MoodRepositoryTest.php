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
use Ampache\Repository\Model\Mood;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MoodRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private MoodRepository $subject;

    public function testAddMapReturnsTheNewMapId(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT IGNORE INTO `mood_map` (`mood_id`, `user`, `object_type`, `object_id`) VALUES (?, ?, ?, ?)',
                [666, 0, 'song', 42]
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(7);

        static::assertSame(7, $this->subject->addMap(666, 'song', 42, 0));
    }

    public function testCollectGarbageSweepsEveryMappableTypeAndDropsTheEmptyMoods(): void
    {
        $statements = [];
        $this->connection->method('query')
            ->willReturnCallback(function (string $sql) use (&$statements): PDOStatement {
                $statements[] = $sql;

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->collectGarbage();

        // a map is orphaned by whichever object it names, and a type with no sweep keeps the mood alive for ever
        foreach (Mood::OBJECT_TYPES as $objectType) {
            static::assertNotEmpty(
                array_filter(
                    $statements,
                    static fn(string $sql): bool => str_starts_with($sql, 'DELETE FROM `mood_map`') && str_contains($sql, sprintf("`mood_map`.`object_type`='%s'", $objectType))
                ),
                sprintf('no orphaned map sweep for %s', $objectType)
            );
        }

        // a mood nothing points at any more goes, whoever created it
        static::assertNotEmpty(
            array_filter($statements, static fn(string $sql): bool => str_starts_with($sql, 'DELETE FROM `mood` USING `mood`'))
        );

        // the owner is part of `unique_mood_map`, so the duplicate sweep must not take the map a user set beside the one from the file
        static::assertNotEmpty(
            array_filter(
                $statements,
                static fn(string $sql): bool => str_starts_with($sql, 'DELETE `b` FROM `mood_map`') && str_contains($sql, '`a`.`user` <=> `b`.`user`')
            )
        );
    }

    public function testGetTopMoodsGroupsTheMapsOfOneMoodIntoASingleRow(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                static::callback(
                    static fn(string $sql): bool => str_contains($sql, 'MAX(`mood_map`.`user`) AS `user`') && str_contains($sql, 'GROUP BY `mood`.`id`')
                ),
                ['song', 42]
            )
            ->willReturn($result);

        $result->method('fetch')->willReturn(false);

        static::assertSame([], $this->subject->getTopMoods('song', 42, 0));
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new MoodRepository($this->connection);
    }
}
