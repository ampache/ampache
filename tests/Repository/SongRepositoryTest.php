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

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Catalog;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SongRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;

    private SongRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new SongRepository(
            $this->connection,
        );
    }

    public function testGetByCatalogReturnsValuesForCatalog(): void
    {
        $songId    = 666;
        $catalogId = 42;

        $result  = $this->createMock(PDOStatement::class);
        $catalog = $this->createMock(Catalog::class);

        $catalog->expects(static::once())
            ->method('getId')
            ->willReturn($catalogId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `song` WHERE `catalog` = ? ORDER BY `album`, `track`',
                [$catalogId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $songId, false);

        static::assertSame(
            [$songId],
            iterator_to_array($this->subject->getByCatalog($catalog))
        );
    }

    public function testGetByCatalogReturnsAllItems(): void
    {
        $songId = 666;

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `song` ORDER BY `album`, `track`',
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $songId, false);

        static::assertSame(
            [$songId],
            iterator_to_array($this->subject->getByCatalog())
        );
    }
}
