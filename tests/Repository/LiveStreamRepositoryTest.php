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

use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\ModelFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LiveStreamRepositoryTest extends TestCase
{
    private CatalogCounterInterface&MockObject $catalogCounter;
    private DatabaseConnectionInterface&MockObject $connection;
    private ModelFactoryInterface&MockObject $modelFactory;
    private LiveStreamRepository $subject;

    public function testDeleteRefreshesTheCachedTotal(): void
    {
        $liveStream = $this->createMock(Live_Stream::class);
        $liveStream->method('getId')->willReturn(666);

        // this is the assertion the static made impossible - it needed a real database to run at all
        $this->catalogCounter->expects(static::once())
            ->method('count')
            ->with(CountableTableEnum::LIVE_STREAM);

        $this->subject->delete($liveStream);
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

        self::assertSame(
            $item,
            $this->subject->findById($objectId)
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

        self::assertNull(
            $this->subject->findById($objectId)
        );
    }

    public function testPersistInsertsANewItemAndReturnsTheId(): void
    {
        $item = new Live_Stream();

        $item->name     = 'some-name';
        $item->site_url = 'https://some-site';
        $item->url      = 'https://some-url';
        $item->catalog  = 42;
        $item->codec    = 'mp3';

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `live_stream` (`name`, `site_url`, `url`, `catalog`, `codec`) VALUES (?, ?, ?, ?, ?)',
                ['some-name', 'https://some-site', 'https://some-url', 42, 'mp3']
            );

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(666);

        self::assertSame(
            666,
            $this->subject->persist($item)
        );
    }

    public function testPersistReturnsNullIfTheInsertYieldedNoId(): void
    {
        $item = new Live_Stream();

        $item->catalog = 42;

        $this->connection->expects(static::once())
            ->method('query');

        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn(0);

        self::assertNull(
            $this->subject->persist($item)
        );
    }

    public function testPersistUpdatesAnExistingItemAndReturnsNull(): void
    {
        $item = new Live_Stream();

        $item->id       = 666;
        $item->name     = 'some-name';
        $item->site_url = 'https://some-site';
        $item->url      = 'https://some-url';
        $item->codec    = 'mp3';

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `live_stream` SET `name` = ?, `site_url` = ?, `url` = ?, `codec` = ? WHERE `id` = ?',
                ['some-name', 'https://some-site', 'https://some-url', 'mp3', 666]
            );

        $this->connection->expects(static::never())
            ->method('getLastInsertedId');

        self::assertNull(
            $this->subject->persist($item)
        );
    }

    protected function setUp(): void
    {
        $this->modelFactory   = $this->createMock(ModelFactoryInterface::class);
        $this->connection     = $this->createMock(DatabaseConnectionInterface::class);
        $this->catalogCounter = $this->createMock(CatalogCounterInterface::class);

        $this->subject = new LiveStreamRepository(
            $this->modelFactory,
            $this->connection,
            $this->catalogCounter
        );
    }
}
