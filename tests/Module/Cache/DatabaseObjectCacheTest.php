<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Cache;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Mockery\MockInterface;

class DatabaseObjectCacheTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface|null */
    private MockInterface $configcontainer;

    /** @var MockInterface|ObjectCacheAdapter\ObjectCacheAdapterInterface|null */
    private MockInterface $adapter;

    private ?DatabaseObjectCache $subject;

    private string $adapterName = 'some-adapter';

    public function setUp(): void
    {
        $this->configcontainer = $this->mock(ConfigContainerInterface::class);
        $this->adapter         = $this->mock(ObjectCacheAdapter\ObjectCacheAdapterInterface::class);

        $this->subject = new DatabaseObjectCache(
            $this->configcontainer,
            [
                $this->adapterName => $this->adapter
            ]
        );
    }

    public function testAddReturnsFalseIfCacheIsNotActive(): void
    {
        $this->configcontainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::MEMORY_CACHE)
            ->once()
            ->andReturnFalse();

        $this->assertFalse(
            $this->subject->add('index', 'object-id', [])
        );
    }

    public function testAddAdds(): void
    {
        $index    = 'some-index';
        $objectId = 'some-object-id';
        $data     = ['some-data'];

        $this->createAdapterExpectations();

        $this->configcontainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::MEMORY_CACHE)
            ->once()
            ->andReturnTrue();

        $this->adapter->shouldReceive('add')
            ->with($index, $objectId, $data)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->add($index, $objectId, $data)
        );
    }

    public function testRemoveRemoves(): void
    {
        $index    = 'some-index';
        $objectId = 'some-object-id';

        $this->createAdapterExpectations();

        $this->adapter->shouldReceive('remove')
            ->with($index, $objectId)
            ->once();

        $this->subject->remove($index, $objectId);
    }

    public function testClearClears(): void
    {
        $this->createAdapterExpectations();

        $this->adapter->shouldReceive('clear')
            ->withNoArgs()
            ->once();

        $this->subject->clear();
    }

    public function testExistsReturnsValue(): void
    {
        $index    = 'some-index';
        $objectId = 'some-object-id';

        $this->createAdapterExpectations();

        $this->adapter->shouldReceive('exists')
            ->with($index, $objectId)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->exists($index, $objectId)
        );
    }

    public function testRetrieveReturnsDataFromCacheIfAvailable(): void
    {
        $index    = 'some-index';
        $objectId = 'some-object-id';
        $data     = ['some-data'];

        $this->createAdapterExpectations();

        $this->adapter->shouldReceive('exists')
            ->with($index, $objectId)
            ->once()
            ->andReturnTrue();
        $this->adapter->shouldReceive('retrieve')
            ->with($index, $objectId)
            ->once()
            ->andReturn($data);

        $this->assertSame(
            $data,
            $this->subject->retrieve($index, $objectId)
        );
        $this->assertSame(
            1,
            $this->subject->getCacheHitAmount()
        );
    }

    public function testRetrieveReturnsEmptyArrayIfNoEntryExists(): void
    {
        $index    = 'some-index';
        $objectId = 'some-object-id';

        $this->createAdapterExpectations();

        $this->adapter->shouldReceive('exists')
            ->with($index, $objectId)
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->retrieve($index, $objectId)
        );
        $this->assertSame(
            0,
            $this->subject->getCacheHitAmount()
        );
    }

    private function createAdapterExpectations(): void
    {
        $this->configcontainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::MEMORY_CACHE_ADAPTER)
            ->once()
            ->andReturn($this->adapterName);
    }
}
