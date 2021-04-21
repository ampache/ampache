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

namespace Ampache\Module\Podcast;

use Ampache\MockeryTestCase;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\PodcastRepositoryInterface;
use Mockery\MockInterface;

class PodcastByCatalogLoaderTest extends MockeryTestCase
{
    /** @var CatalogRepositoryInterface|MockInterface */
    private MockInterface $catalogRepository;

    /** @var PodcastRepositoryInterface|MockInterface */
    private MockInterface $podcastRepository;

    private PodcastByCatalogLoader $subject;

    public function setUp(): void
    {
        $this->catalogRepository = $this->mock(CatalogRepositoryInterface::class);
        $this->podcastRepository = $this->mock(PodcastRepositoryInterface::class);

        $this->subject = new PodcastByCatalogLoader(
            $this->catalogRepository,
            $this->podcastRepository
        );
    }

    public function testLoadLoadsPodcastsForAllCatalogs(): void
    {
        $catalogId = 666;
        $podcastId = 42;

        $podcast = $this->mock(Podcast::class);

        $this->catalogRepository->shouldReceive('getList')
            ->with('podcast')
            ->once()
            ->andReturn([$catalogId]);

        $this->podcastRepository->shouldReceive('getPodcastIds')
            ->with($catalogId)
            ->once()
            ->andReturn([$podcastId]);

        $this->podcastRepository->shouldReceive('findById')
            ->with($podcastId)
            ->once()
            ->andReturn($podcast);

        $this->assertSame(
            [$podcast],
            $this->subject->load()
        );
    }

    public function testLoadLoadsPodcastsForSpecificCatalog(): void
    {
        $catalogId = 666;
        $podcastId = 42;

        $podcast = $this->mock(Podcast::class);

        $this->podcastRepository->shouldReceive('getPodcastIds')
            ->with($catalogId)
            ->once()
            ->andReturn([$podcastId]);
        $this->podcastRepository->shouldReceive('findById')
            ->with($podcastId)
            ->once()
            ->andReturn($podcast);

        $this->assertSame(
            [$podcast],
            $this->subject->load([(string) $catalogId])
        );
    }
}
