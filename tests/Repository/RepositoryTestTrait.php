<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use PDO;
use PDOStatement;

trait RepositoryTestTrait
{
    public function runFindByIdTrait(
        string $tableName,
        string $className,
        array $parameters
    ): void {
        $itemId = 666;

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                sprintf(
                    'SELECT * FROM `%s` WHERE `id` = ?',
                    $tableName
                ),
                [$itemId]
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, $className, $parameters);
        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        static::assertNull(
            $this->subject->findById($itemId)
        );
    }

    public function testPrototypeYieldsNewItems(): void
    {
        $result = $this->subject->prototype();

        static::assertTrue(
            $result->isNew()
        );

        static::assertNotSame(
            spl_object_hash($result),
            spl_object_hash($this->subject->prototype())
        );
    }
}
