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

namespace Ampache\Module\Video;

use Ampache\MockeryTestCase;
use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Catalog\Loader\Exception\CatalogNotFoundException;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\database_object;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Video;
use Mockery\MockInterface;

class VideoLoaderTest extends MockeryTestCase
{
    private MockInterface $modelFactory;

    private MockInterface $catalogRepository;

    private MockInterface $catalogLoader;

    private VideoLoader $subject;

    public function setUp(): void
    {
        $this->modelFactory      = $this->mock(ModelFactoryInterface::class);
        $this->catalogRepository = $this->mock(CatalogRepositoryInterface::class);
        $this->catalogLoader     = $this->mock(CatalogLoaderInterface::class);

        $this->subject = new VideoLoader(
            $this->modelFactory,
            $this->catalogRepository,
            $this->catalogLoader
        );
    }

    public function testLoadReturnsMappedObjectType(): void
    {
        $videoId = 666;

        $result = $this->mock(database_object::class);

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with('tvshow_episode', $videoId)
            ->once()
            ->andReturn($result);

        $result->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            $result,
            $this->subject->load($videoId)
        );
    }

    public function testLoadReturnsFallback(): void
    {
        $videoId = 666;

        $mappedType = $this->mock(database_object::class);
        $result     = $this->mock(Video::class);

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with('tvshow_episode', $videoId)
            ->once()
            ->andReturn($mappedType);
        $this->modelFactory->shouldReceive('mapObjectType')
            ->with('movie', $videoId)
            ->once()
            ->andReturn($mappedType);
        $this->modelFactory->shouldReceive('mapObjectType')
            ->with('clip', $videoId)
            ->once()
            ->andReturn($mappedType);
        $this->modelFactory->shouldReceive('mapObjectType')
            ->with('personal_video', $videoId)
            ->once()
            ->andReturn($mappedType);
        $this->modelFactory->shouldReceive('mapObjectType')
            ->with('video', $videoId)
            ->once()
            ->andReturn($mappedType);

        $mappedType->shouldReceive('isNew')
            ->withNoArgs()
            ->times(5)
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('createVideo')
            ->with($videoId)
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            $this->subject->load($videoId)
        );
    }

    public function testLoadByCatalogsReturnsList(): void
    {
        $catalogId      = 666;
        $wrongCatalogId = 42;
        $videoId        = 33;

        $catalog = $this->mock(Catalog::class);
        $result  = $this->mock(database_object::class);

        $this->catalogRepository->shouldReceive('getList')
            ->withNoArgs()
            ->once()
            ->andReturn([$catalogId, $wrongCatalogId]);

        $this->catalogLoader->shouldReceive('byId')
            ->with($catalogId)
            ->once()
            ->andReturn($catalog);
        $this->catalogLoader->shouldReceive('byId')
            ->with($wrongCatalogId)
            ->once()
            ->andThrow(new CatalogNotFoundException());

        $catalog->shouldReceive('get_video_ids')
            ->with('')
            ->once()
            ->andReturn([$videoId]);

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with('tvshow_episode', $videoId)
            ->once()
            ->andReturn($result);

        $result->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [$result],
            $this->subject->loadByCatalogs([], '')
        );
    }
}
