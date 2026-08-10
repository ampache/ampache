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

namespace Ampache\Module\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Util\WebFetcher\Exception\FetchFailedException;
use Ampache\Module\Util\WebFetcher\WebFetcherInterface;
use Ampache\Repository\CatalogMapRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use ArrayIterator;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

class PodcastSyncerTest extends TestCase
{
    private CatalogMapRepositoryInterface&MockObject $catalogMapRepository;
    private ConfigContainerInterface&MockObject $configContainer;
    private ModelFactoryInterface&MockObject $modelFactory;
    private PodcastDeleterInterface&MockObject $podcastDeleter;
    private PodcastEpisodeDownloaderInterface&MockObject $podcastEpisodeDownloader;
    private PodcastEpisodeRepositoryInterface&MockObject $podcastEpisodeRepository;
    private PodcastRepositoryInterface&MockObject $podcastRepository;
    private PodcastSyncer $subject;
    private WebFetcherInterface&MockObject $webFetcher;

    public function testAddEpisodesDownloadsAndCleansUpEligibleEpisodes(): void
    {
        $podcast          = $this->createMock(Podcast::class);
        $episodes         = new SimpleXMLElement('<items></items>');
        $episode          = $this->createMock(Podcast_Episode::class);
        $eligibleDeletion = new ArrayIterator([]);

        $this->configContainer->method('get')
            ->with(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD)
            ->willReturn(5);

        $this->podcastEpisodeRepository->expects(static::once())
            ->method('getEpisodesEligibleForDownload')
            ->with($podcast, 5)
            ->willReturn(new ArrayIterator([$episode]));

        $episode->expects(static::once())
            ->method('change_state')
            ->with(PodcastEpisodeStateEnum::PENDING);

        $this->podcastEpisodeDownloader->expects(static::once())
            ->method('fetch')
            ->with($episode);

        $this->podcastEpisodeRepository->expects(static::once())
            ->method('getEpisodesEligibleForDeletion')
            ->with($podcast)
            ->willReturn($eligibleDeletion);

        $this->podcastDeleter->expects(static::once())
            ->method('deleteEpisode')
            ->with($eligibleDeletion);

        $this->podcastEpisodeRepository->expects(static::once())
            ->method('getEpisodeCount')
            ->with($podcast)
            ->willReturn(3);

        $podcast->expects(static::once())
            ->method('setEpisodeCount')
            ->with(3);
        $podcast->expects(static::once())
            ->method('setLastSyncDate');
        $podcast->expects(static::once())
            ->method('save');

        $this->subject->addEpisodes($podcast, $episodes, null, true);
    }

    /**
     * The episode rows are inserted whether or not anything downloads, so a subscribe with downloads off
     * still has to leave the podcast with the right episode count.
     */
    public function testAddEpisodesRefreshesTheCountWhenNothingIsDownloaded(): void
    {
        $podcast  = $this->createMock(Podcast::class);
        $episodes = new SimpleXMLElement('<items></items>');

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD)
            ->willReturn(-1);

        $this->podcastEpisodeRepository->expects(static::once())
            ->method('getEpisodeCount')
            ->with($podcast)
            ->willReturn(504);

        $this->podcastDeleter->expects(static::never())
            ->method('deleteEpisode');

        $podcast->expects(static::once())
            ->method('setEpisodeCount')
            ->with(504);
        $podcast->expects(static::once())
            ->method('save');

        $this->subject->addEpisodes($podcast, $episodes);
    }

    public function testAddEpisodesSkipsDownloadWhenDownloadLimitIsNegative(): void
    {
        $podcast  = $this->createMock(Podcast::class);
        $episodes = new SimpleXMLElement('<items></items>');

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD)
            ->willReturn(-1);

        $this->podcastEpisodeRepository->expects(static::never())
            ->method('getEpisodesEligibleForDownload');

        $this->podcastDeleter->expects(static::never())
            ->method('deleteEpisode');

        $podcast->expects(static::once())
            ->method('setLastSyncDate');
        $podcast->expects(static::once())
            ->method('save');

        $this->subject->addEpisodes($podcast, $episodes);
    }

    public function testSyncEpisodeFetches(): void
    {
        $episode = $this->createMock(Podcast_Episode::class);

        $this->podcastEpisodeDownloader->expects(static::once())
            ->method('fetch')
            ->with($episode);

        $this->subject->syncEpisode($episode);
    }

    public function testSyncForCatalogsDownloadsUpToTheConfiguredLimit(): void
    {
        $catalog = $this->createMock(Catalog::class);
        $catalog->method('get_podcast_ids')->willReturn([30]);

        $podcast = $this->createMock(Podcast::class);
        $podcast->method('getFeedUrl')->willReturn('');
        $podcast->method('getEpisodeIds')
            ->with(PodcastEpisodeStateEnum::PENDING)
            ->willReturn([100, 101, 102]);

        $this->podcastRepository->method('findById')
            ->with(30)
            ->willReturn($podcast);

        $this->configContainer->method('get')
            ->with(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD)
            ->willReturn(2);

        $episode100 = $this->createMock(Podcast_Episode::class);
        $episode101 = $this->createMock(Podcast_Episode::class);

        $this->modelFactory->expects(static::exactly(2))
            ->method('createPodcastEpisode')
            ->willReturnMap([
                [100, $episode100],
                [101, $episode101],
            ]);

        $this->podcastEpisodeDownloader->expects(static::exactly(2))
            ->method('fetch');

        static::assertSame(3, $this->subject->syncForCatalogs([$catalog]));
    }

    public function testSyncForCatalogsSkipsMissingPodcastsAndCountsPendingEpisodes(): void
    {
        $catalog = $this->createMock(Catalog::class);
        $catalog->method('get_podcast_ids')->willReturn([10, 20]);

        $podcast = $this->createMock(Podcast::class);
        $podcast->method('getFeedUrl')->willReturn('');
        $podcast->method('getEpisodeIds')
            ->with(PodcastEpisodeStateEnum::PENDING)
            ->willReturn([1, 2, 3]);

        $this->podcastRepository->method('findById')
            ->willReturnMap([
                [10, null],
                [20, $podcast],
            ]);

        $this->configContainer->method('get')
            ->with(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD)
            ->willReturn(-1);

        $this->podcastEpisodeDownloader->expects(static::never())
            ->method('fetch');

        static::assertSame(3, $this->subject->syncForCatalogs([$catalog]));
    }

    public function testSyncParsesFeedAndDelegatesToAddEpisodes(): void
    {
        $feedUrl = 'https://feed.example/rss';

        $podcast = $this->createMock(Podcast::class);
        $podcast->method('getFeedUrl')->willReturn($feedUrl);
        $podcast->method('getLastSyncDate')->willReturn(new DateTimeImmutable());

        $this->webFetcher->expects(static::once())
            ->method('fetch')
            ->with($feedUrl)
            ->willReturn('<rss><channel></channel></rss>');

        $this->configContainer->method('get')
            ->with(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD)
            ->willReturn(-1);

        $podcast->expects(static::once())
            ->method('setLastSyncDate');
        $podcast->expects(static::once())
            ->method('save');

        static::assertTrue($this->subject->sync($podcast));
    }

    public function testSyncReturnsFalseForEmptyFeedUrl(): void
    {
        $podcast = $this->createMock(Podcast::class);
        $podcast->method('getFeedUrl')->willReturn('');

        $this->webFetcher->expects(static::never())
            ->method('fetch');
        $this->configContainer->expects(static::never())
            ->method('get');

        static::assertFalse($this->subject->sync($podcast));
    }

    public function testSyncReturnsFalseWhenTheFeedIsRefused(): void
    {
        $feedUrl = 'http://169.254.169.254/latest/meta-data/';

        $podcast = $this->createMock(Podcast::class);
        $podcast->method('getFeedUrl')->willReturn($feedUrl);

        $this->webFetcher->expects(static::once())
            ->method('fetch')
            ->with($feedUrl)
            ->willThrowException(new FetchFailedException('Refusing to fetch url'));

        $podcast->expects(static::never())
            ->method('save');

        static::assertFalse($this->subject->sync($podcast));
    }

    protected function setUp(): void
    {
        $this->podcastRepository        = $this->createMock(PodcastRepositoryInterface::class);
        $this->modelFactory             = $this->createMock(ModelFactoryInterface::class);
        $this->podcastEpisodeDownloader = $this->createMock(PodcastEpisodeDownloaderInterface::class);
        $this->podcastDeleter           = $this->createMock(PodcastDeleterInterface::class);
        $this->podcastEpisodeRepository = $this->createMock(PodcastEpisodeRepositoryInterface::class);
        $this->configContainer          = $this->createMock(ConfigContainerInterface::class);
        $this->catalogMapRepository     = $this->createMock(CatalogMapRepositoryInterface::class);
        $this->webFetcher               = $this->createMock(WebFetcherInterface::class);

        // Catalog::update_mapping() reaches the container, and debug_event() pulls the logger off the same one
        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            CatalogMapRepositoryInterface::class => $this->catalogMapRepository,
            default => $this->createMock(LoggerInterface::class),
        });
        $GLOBALS['dic'] = $dic;

        $this->subject = new PodcastSyncer(
            $this->podcastRepository,
            $this->modelFactory,
            $this->podcastEpisodeDownloader,
            $this->podcastDeleter,
            $this->podcastEpisodeRepository,
            $this->configContainer,
            $this->webFetcher
        );
    }
}
