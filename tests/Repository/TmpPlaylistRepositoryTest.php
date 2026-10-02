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
 */

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TmpPlaylistRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private TmpPlaylistRepository $subject;

    public function testAddItemsBindsOnePlaceholderGroupPerRow(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `tmp_playlist_data` (`object_id`, `tmp_playlist`, `object_type`) VALUES (?, ?, ?), (?, ?, ?)',
                [21, 666, 'song', 33, 666, 'song']
            );

        $this->subject->addItems(666, [21, 33], 'song');
    }

    public function testAddItemsDoesNothingForAnEmptySelection(): void
    {
        $this->connection->expects(static::never())
            ->method('query');

        $this->subject->addItems(666, [], 'song');
    }

    public function testAddItemsSplitsALongSelectionIntoBatches(): void
    {
        // a single statement carrying every row would be as long as the selection
        $this->connection->expects(static::exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params): PDOStatement {
                static $call = 0;
                $expected    = (++$call === 1) ? 500 : 1;

                self::assertSame($expected, substr_count($sql, '(?, ?, ?)'));
                self::assertCount($expected * 3, $params);

                return $this->createMock(PDOStatement::class);
            });

        $this->subject->addItems(666, range(1, 501), 'song');
    }

    public function testDeleteItemByRowIdIsScopedToTheOwnQueue(): void
    {
        // the row id alone used to be enough to drop another user's track, so the queue id is part of the where
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `tmp_playlist_data` WHERE `id` = ? AND `tmp_playlist` = ?',
                [601, 7]
            );

        $this->subject->deleteItemByRowId(601, 7);
    }

    public function testShuffleItemsDeletesAndReinsertsTheSameRows(): void
    {
        $rows = [
            ['object_type' => 'song', 'id' => 1, 'object_id' => 21],
            ['object_type' => 'song', 'id' => 2, 'object_id' => 33],
            ['object_type' => 'album', 'id' => 3, 'object_id' => 5],
        ];

        $calls = [];
        $this->connection->expects(static::exactly(3))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use (&$calls, $rows): PDOStatement {
                $calls[] = [$sql, $params];

                return str_starts_with($sql, 'SELECT')
                    ? $this->makeResultRows($rows)
                    : $this->createMock(PDOStatement::class);
            });

        $this->subject->shuffleItems(666);

        [, $deleteCall, $insertCall] = $calls;

        self::assertSame('DELETE FROM `tmp_playlist_data` WHERE `tmp_playlist` = ?', $deleteCall[0]);
        self::assertSame([666], $deleteCall[1]);

        self::assertSame(
            'INSERT INTO `tmp_playlist_data` (`object_id`, `tmp_playlist`, `object_type`) VALUES (?, ?, ?), (?, ?, ?), (?, ?, ?)',
            $insertCall[0]
        );

        // shuffle() randomizes the order, so compare the rows as a set rather than position by position
        self::assertEqualsCanonicalizing(
            [[21, 666, 'song'], [33, 666, 'song'], [5, 666, 'album']],
            array_chunk($insertCall[1], 3)
        );
    }

    public function testShuffleItemsDoesNothingForFewerThanTwoItems(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->willReturn($this->makeResultRows([
                ['object_type' => 'song', 'id' => 1, 'object_id' => 21],
            ]));

        $this->subject->shuffleItems(666);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new TmpPlaylistRepository(
            $this->connection,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * @param list<array{object_type: string, id: int, object_id: int}> $rows
     */
    private function makeResultRows(array $rows): PDOStatement&MockObject
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')
            ->willReturnOnConsecutiveCalls(...[...$rows, false]);

        return $statement;
    }
}
