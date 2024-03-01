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
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\ModelFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LiveStreamRepositoryTest extends TestCase
{
    private ModelFactoryInterface&MockObject $modelFactory;

    private DatabaseConnectionInterface&MockObject $connection;

    private LiveStreamRepository $subject;

    protected function setUp(): void
    {
        $this->modelFactory = $this->createMock(ModelFactoryInterface::class);
        $this->connection   = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new LiveStreamRepository(
            $this->modelFactory,
            $this->connection
        );
    }

    public function testFindByIdReturnsNullIfTheObjectDoesNotExist(): void
    {
        $objectId = 666;

        $item = $this->createMock(Live_Stream::class);

        $this->modelFactory->expects(static::once())
            ->method('createLiveStream')
            ->with($objectId)
            ->willReturn($item);

        $item->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        static::assertNull(
            $this->subject->findById($objectId)
        );
    }

    public function testFindByIdReturnsFoundObject(): void
    {
        $objectId = 666;

        $item = $this->createMock(Live_Stream::class);

        $this->modelFactory->expects(static::once())
            ->method('createLiveStream')
            ->with($objectId)
            ->willReturn($item);

        $item->expects(static::once())
            ->method('isNew')
            ->willReturn(false);

        static::assertSame(
            $item,
            $this->subject->findById($objectId)
        );
    }
}
