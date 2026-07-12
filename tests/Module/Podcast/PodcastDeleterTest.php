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
 */

namespace Ampache\Module\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PodcastDeleterTest extends TestCase
{
    private ConfigContainerInterface&MockObject $config;
    private LoggerInterface&MockObject $logger;
    private ModelFactoryInterface&MockObject $modelFactory;
    private PodcastEpisodeRepositoryInterface&MockObject $podcastEpisodeRepository;
    private PodcastRepositoryInterface&MockObject $podcastRepository;
    private PodcastDeleter $subject;

    public function testDeleteEpisodeDoesNotTouchDiskWhenFeatureDisabled(): void
    {
        $episode = $this->createMock(Podcast_Episode::class);

        $episode->method('getId')
            ->willReturn(42);
        $episode->file = '/some/path.mp3';

        $this->config->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::DELETE_FROM_DISK)
            ->willReturn(false);

        $this->podcastEpisodeRepository->expects(static::once())
            ->method('deleteEpisode')
            ->with($episode);

        $this->subject->deleteEpisode([$episode]);
    }

    public function testDeleteRemovesPodcastAndItsEpisodes(): void
    {
        $podcast   = $this->createMock(Podcast::class);
        $episode   = $this->createMock(Podcast_Episode::class);
        $episodeId = 42;

        $podcast->method('getId')
            ->willReturn(21);

        $this->podcastEpisodeRepository->expects(static::once())
            ->method('getEpisodes')
            ->with($podcast)
            ->willReturn([$episodeId]);

        $this->modelFactory->expects(static::once())
            ->method('createPodcastEpisode')
            ->with($episodeId)
            ->willReturn($episode);

        $episode->method('getId')
            ->willReturn($episodeId);
        $episode->file = null;

        $this->config->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::DELETE_FROM_DISK)
            ->willReturn(false);

        $this->podcastEpisodeRepository->expects(static::once())
            ->method('deleteEpisode')
            ->with($episode);

        $this->podcastRepository->expects(static::once())
            ->method('delete')
            ->with($podcast);

        $this->subject->delete($podcast);
    }

    protected function setUp(): void
    {
        $this->podcastRepository         = $this->createMock(PodcastRepositoryInterface::class);
        $this->modelFactory              = $this->createMock(ModelFactoryInterface::class);
        $this->config                    = $this->createMock(ConfigContainerInterface::class);
        $this->podcastEpisodeRepository  = $this->createMock(PodcastEpisodeRepositoryInterface::class);
        $this->logger                    = $this->createMock(LoggerInterface::class);

        $this->subject = new PodcastDeleter(
            $this->podcastRepository,
            $this->modelFactory,
            $this->config,
            $this->podcastEpisodeRepository,
            $this->logger,
        );
    }
}
