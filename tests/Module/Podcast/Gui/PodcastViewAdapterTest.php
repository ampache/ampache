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

namespace Ampache\Module\Podcast\Gui;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Mockery\MockInterface;

class PodcastViewAdapterTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface */
    private MockInterface $modelFactory;

    /** @var PodcastEpisodeRepositoryInterface|MockInterface */
    private MockInterface $podcastEpisodeRepository;

    /** @var MockInterface|PodcastInterface */
    private MockInterface $podcast;

    private PodcastViewAdapter $subject;

    public function setUp(): void
    {
        $this->modelFactory             = $this->mock(ModelFactoryInterface::class);
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);
        $this->podcast                  = $this->mock(PodcastInterface::class);

        $this->subject = new PodcastViewAdapter(
            $this->podcastEpisodeRepository,
            $this->modelFactory,
            $this->podcast
        );
    }

    public function testGetDescriptionReturnsData(): void
    {
        $value = 'some-value';

        $this->podcast->shouldReceive('getDescription')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getDescription()
        );
    }

    public function testGetWebsiteReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcast->shouldReceive('getWebsite')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getWebsite()
        );
    }

    public function testGetEpisodeListReturnsTheRenderedList(): void
    {
        $episodeIds = [666, 42];

        $browse = $this->mock(Browse::class);

        $this->podcastEpisodeRepository->shouldReceive('getEpisodeIds')
            ->with($this->podcast)
            ->once()
            ->andReturn($episodeIds);

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_type')
            ->with('podcast_episode')
            ->once();
        $browse->shouldReceive('show_objects')
            ->with($episodeIds, true)
            ->once();
        $browse->shouldReceive('store')
            ->withNoArgs()
            ->once();

        $this->assertSame(
            '',
            $this->subject->getEpisodeList()
        );
    }

    public function testGetTitleFormattedReturnsValue(): void
    {
        $value = 'some-value';

        $this->podcast->shouldReceive('getTitleFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getTitleFormatted()
        );
    }
}
