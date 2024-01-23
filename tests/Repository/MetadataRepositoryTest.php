<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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
 *
 */

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MetadataRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;

    private MetadataRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new MetadataRepository(
            $this->connection
        );
    }

    public function testCollectGarbageExecutesQuery(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `metadata` USING `metadata` LEFT JOIN `song` ON `song`.`id` = `metadata`.`object_id` WHERE `song`.`id` IS NULL;');

        $this->subject->collectGarbage();
    }

    public function testMigrateMigrates(): void
    {
        $objectType  = 'some-object-type';
        $oldObjectId = 666;
        $newObjectId = 42;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE IGNORE `metadata` SET `object_id` = ? WHERE `object_id` = ? AND `type` = ?',
                [
                    $newObjectId,
                    $oldObjectId,
                    ucfirst($objectType),
                ],
            );

        $this->subject->migrate($objectType, $oldObjectId, $newObjectId);
    }
}
