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
 *
 */

namespace Ampache\Module\Podcast;

use Ampache\Module\Podcast\Exception\PodcastFolderException;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\WebFetcher\WebFetcherInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\PodcastRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PodcastEpisodeDownloaderTest extends TestCase
{
    private PodcastFolderProviderInterface&MockObject $podcastFolderProvider;

    private WebFetcherInterface&MockObject $webFetcher;

    private LoggerInterface&MockObject $logger;

    private PodcastEpisodeDownloader $subject;

    private PodcastRepositoryInterface&MockObject $podcastRepository;

    protected function setUp(): void
    {
        $this->podcastFolderProvider = $this->createMock(PodcastFolderProviderInterface::class);
        $this->webFetcher            = $this->createMock(WebFetcherInterface::class);
        $this->logger                = $this->createMock(LoggerInterface::class);
        $this->podcastRepository     = $this->createMock(PodcastRepositoryInterface::class);

        $this->subject = new PodcastEpisodeDownloader(
            $this->podcastFolderProvider,
            $this->webFetcher,
            $this->podcastRepository,
            $this->logger,
        );
    }

    public function testFetchFailsIfEpisodeHasNoSourceUrl(): void
    {
        $episode = $this->createMock(Podcast_Episode::class);

        $episodeId = 666;

        $episode->expects(static::once())
            ->method('getId')
            ->willReturn($episodeId);

        $this->logger->expects(static::once())
            ->method('warning')
            ->with(
                sprintf('Cannot download podcast episode %d, empty source.', $episodeId),
                [LegacyLogger::CONTEXT_TYPE => PodcastEpisodeDownloader::class]
            );

        $this->subject->fetch($episode);
    }

    public function testFetchFailsIfPodcastBaseUrlDoesNotExist(): void
    {
        $episode = $this->createMock(Podcast_Episode::class);
        $podcast = $this->createMock(Podcast::class);

        $episodeId    = 666;
        $source       = 'some-source';
        $podcastId    = 42;
        $errorMessage = 'some-error-message';

        $this->logger->expects(static::once())
            ->method('error')
            ->with(
                sprintf(
                    'Podcast folder error: %s. Check your catalog directory and permissions',
                    $errorMessage
                ),
                [LegacyLogger::CONTEXT_TYPE => PodcastEpisodeDownloader::class]
            );

        $episode->expects(static::once())
            ->method('getId')
            ->willReturn($episodeId);
        $episode->expects(static::once())
            ->method('getSource')
            ->willReturn($source);
        $episode->expects(static::once())
            ->method('getPodcastId')
            ->willReturn($podcastId);

        $this->podcastRepository->expects(static::once())
            ->method('findById')
            ->with($podcastId)
            ->willReturn($podcast);

        $this->podcastFolderProvider->expects(static::once())
            ->method('getBaseFolder')
            ->with($podcast)
            ->willThrowException(new PodcastFolderException($errorMessage));

        $this->subject->fetch($episode);
    }
}
