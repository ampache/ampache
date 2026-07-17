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
 */

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FolderRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;
    private FolderRepository $subject;

    public function testCollectGarbageRunsCleanupQueries(): void
    {
        $this->connection->expects(static::atLeast(7))
            ->method('query');

        $this->subject->collectGarbage();
    }

    public function testCollectGarbageSwallowsDatabaseException(): void
    {
        $this->connection->method('query')
            ->willThrowException(new QueryFailedException('boom'));

        $this->subject->collectGarbage();

        $this->addToAssertionCount(1);
    }

    public function testGetAllReturnsFoldersKeyedById(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id`, `name` FROM `folder`')
            ->willReturn($result);

        $result->expects(static::exactly(3))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                ['id' => '1', 'name' => 'Music'],
                ['id' => '2', 'name' => 'Podcasts'],
                false,
            );

        static::assertSame(
            [1 => 'Music', 2 => 'Podcasts'],
            $this->subject->getAll(),
        );
    }

    public function testGetItemCountReturnsCount(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT COUNT(*) AS `count` FROM `folder`;')
            ->willReturn($result);

        $result->expects(static::once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['count' => '5']);

        static::assertSame(5, $this->subject->getItemCount());
    }

    public function testGetItemCountReturnsZeroWhenNoRowFound(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $this->connection->method('query')
            ->willReturn($result);

        $result->method('fetch')
            ->willReturn(false);

        static::assertSame(0, $this->subject->getItemCount());
    }

    public function testLookupByPathNameReturnsIdWhenFound(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `folder` WHERE `path_name` = ? AND `catalog` = ?',
                ['/music', 3],
            )
            ->willReturn('21');

        static::assertSame(21, $this->subject->lookupByPathName('/music', 3));
    }

    public function testLookupByPathNameReturnsMinusOneForBlankPath(): void
    {
        $this->connection->expects(static::never())
            ->method('fetchOne');

        static::assertSame(-1, $this->subject->lookupByPathName(''));
    }

    public function testLookupReturnsIdWhenFound(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `folder` WHERE `name` = ? AND `catalog` = ? AND `parent` = ?',
                ['Music', 3, 7],
            )
            ->willReturn('21');

        static::assertSame(21, $this->subject->lookup('Music', 3, 7));
    }

    public function testLookupReturnsMinusOneForBlankName(): void
    {
        $this->connection->expects(static::never())
            ->method('fetchOne');

        static::assertSame(-1, $this->subject->lookup(''));
    }

    public function testLookupReturnsZeroWhenNotFound(): void
    {
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `folder` WHERE `name` = ? AND `catalog` = ?',
                ['Music', 3],
            )
            ->willReturn(false);

        static::assertSame(0, $this->subject->lookup('Music', 3));
    }

    public function testUpdateUtimeUsesProvidedTime(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `folder` SET `update_time` = ? WHERE `id` = ?;',
                [12345, 21],
            );

        $this->subject->update_utime(21, 12345);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new FolderRepository($this->connection);
    }
}
