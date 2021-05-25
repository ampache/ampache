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
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Mockery\MockInterface;

class PodcastDeleterTest extends MockeryTestCase
{
    /** @var PodcastRepositoryInterface|MockInterface */
    private MockInterface $podcastRepository;

    /** @var PodcastEpisodeRepositoryInterface|MockInterface */
    private MockInterface $podcastEpisodeRepository;

    /** @var PodcastEpisodeDeleterInterface|MockInterface */
    private MockInterface $podcastEpisodeDeleter;

    private PodcastDeleter $subject;

    public function setUp(): void
    {
        $this->podcastRepository        = $this->mock(PodcastRepositoryInterface::class);
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);
        $this->podcastEpisodeDeleter    = $this->mock(PodcastEpisodeDeleterInterface::class);

        $this->subject = new PodcastDeleter(
            $this->podcastRepository,
            $this->podcastEpisodeRepository,
            $this->podcastEpisodeDeleter
        );
    }

    public function testDeleteDeletesData(): void
    {
        $episodeId = 666;

        $podcastEpisode = $this->mock(Podcast_Episode::class);
        $podcast        = $this->mock(PodcastInterface::class);

        $this->podcastEpisodeRepository->shouldReceive('getEpisodeIds')
            ->with($podcast)
            ->once()
            ->andReturn([$episodeId]);
        $this->podcastEpisodeRepository->shouldReceive('findById')
            ->with($episodeId)
            ->once()
            ->andReturn($podcastEpisode);

        $this->podcastEpisodeDeleter->shouldReceive('delete')
            ->with($podcastEpisode)
            ->once();

        $this->podcastRepository->shouldReceive('remove')
            ->with($podcast)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->delete($podcast)
        );
    }
}
