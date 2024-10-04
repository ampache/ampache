<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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
 */

namespace Ampache\Module\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PodcastSyncerTest extends TestCase
{
    private PodcastRepositoryInterface&MockObject $podcastRepository;

    private ModelFactoryInterface&MockObject $modelFactory;

    private PodcastEpisodeDownloaderInterface&MockObject $podcastEpisodeDownloader;

    private PodcastDeleterInterface&MockObject $podcastDeleter;

    private PodcastEpisodeRepositoryInterface&MockObject $podcastEpisodeRepository;

    private ConfigContainerInterface&MockObject $configContainer;

    private PodcastSyncer $subject;

    protected function setUp(): void
    {
        $this->podcastRepository        = $this->createMock(PodcastRepositoryInterface::class);
        $this->modelFactory             = $this->createMock(ModelFactoryInterface::class);
        $this->podcastEpisodeDownloader = $this->createMock(PodcastEpisodeDownloaderInterface::class);
        $this->podcastDeleter           = $this->createMock(PodcastDeleterInterface::class);
        $this->podcastEpisodeRepository = $this->createMock(PodcastEpisodeRepositoryInterface::class);
        $this->configContainer          = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new PodcastSyncer(
            $this->podcastRepository,
            $this->modelFactory,
            $this->podcastEpisodeDownloader,
            $this->podcastDeleter,
            $this->podcastEpisodeRepository,
            $this->configContainer
        );
    }

    public function testSyncEpisodeFetches(): void
    {
        $episode = $this->createMock(Podcast_Episode::class);

        $this->podcastEpisodeDownloader->expects(static::once())
            ->method('fetch')
            ->with($episode);

        $this->subject->syncEpisode($episode);
    }
}
