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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Podcast\Exception\PodcastFeedLoadingException;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Mockery\MockInterface;

class PodcastSyncerTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var PodcastEpisodeRepositoryInterface|MockInterface */
    private MockInterface $podcastEpisodeRepository;

    /** @var PodcastEpisodeCreatorInterface|MockInterface */
    private MockInterface $podcastEpisodeCreator;

    /** @var PodcastRepositoryInterface|MockInterface */
    private MockInterface $podcastRepository;

    /** @var PodcastEpisodeDeleterInterface|MockInterface */
    private MockInterface $podcastEpisodeDeleter;

    /** @var PodcastEpisodeDownloaderInterface|MockInterface */
    private MockInterface $podcastEpisodeDownloader;

    /** @var MockInterface|PodcastFeedLoaderInterface */
    private MockInterface $podcastFeedLoader;

    private PodcastSyncer $subject;

    public function setUp(): void
    {
        $this->configContainer          = $this->mock(ConfigContainerInterface::class);
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);
        $this->podcastEpisodeCreator    = $this->mock(PodcastEpisodeCreatorInterface::class);
        $this->podcastRepository        = $this->mock(PodcastRepositoryInterface::class);
        $this->podcastEpisodeDeleter    = $this->mock(PodcastEpisodeDeleterInterface::class);
        $this->podcastEpisodeDownloader = $this->mock(PodcastEpisodeDownloaderInterface::class);
        $this->podcastFeedLoader        = $this->mock(PodcastFeedLoaderInterface::class);

        $this->subject = new PodcastSyncer(
            $this->configContainer,
            $this->podcastEpisodeRepository,
            $this->podcastEpisodeCreator,
            $this->podcastRepository,
            $this->podcastEpisodeDeleter,
            $this->podcastEpisodeDownloader,
            $this->podcastFeedLoader
        );
    }

    public function testSyncReturnsFalseIfLoadingFails(): void
    {
        $podcast = $this->mock(PodcastInterface::class);

        $feedUrl = 'some-feed-url';

        $podcast->shouldReceive('getFeed')
            ->withNoArgs()
            ->once()
            ->andReturn($feedUrl);

        $this->podcastFeedLoader->shouldReceive('load')
            ->with($feedUrl)
            ->once()
            ->andThrow(new PodcastFeedLoadingException());

        $this->assertFalse(
            $this->subject->sync($podcast, false)
        );
    }

    public function testSyncTriesToAddEpisodesAndReturnsTrue(): void
    {
        $podcast = $this->mock(PodcastInterface::class);

        $feedUrl   = 'some-feed-url';
        $afterDate = 666;

        $xml = <<<XML
        <rss>
        <channel>
        <item>
           <id>some-id</id>
        </item>    
        </channel>
        </rss>
        XML;

        $xmlChannel = simplexml_load_string($xml);

        $podcast->shouldReceive('getFeed')
            ->withNoArgs()
            ->once()
            ->andReturn($feedUrl);
        $podcast->shouldReceive('getLastSync')
            ->withNoArgs()
            ->once()
            ->andReturn($afterDate);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD)
            ->once()
            ->andReturn('0');
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::PODCAST_KEEP)
            ->once()
            ->andReturn('0');

        $this->podcastFeedLoader->shouldReceive('load')
            ->with($feedUrl)
            ->once()
            ->andReturn($xmlChannel);

        $this->podcastEpisodeCreator->shouldReceive('create')
            ->with(
                $podcast,
                \Mockery::on(function ($value) {
                    $this->assertSame(
                        (string) $value->id,
                        'some-id'
                    );

                    return true;
                }),
                $afterDate
            )
            ->once();

        $this->podcastRepository->shouldReceive('updateLastSync')
            ->with(
                $podcast,
                \Mockery::on(function ($value) {
                    return $value > time() - 5 && $value < time() + 5;
                })
            )
            ->once();

        $this->assertTrue(
            $this->subject->sync(
                $podcast,
                false
            )
        );
    }

    public function testAddEpisodesCleansUpAndDownloads(): void
    {
        $podcast        = $this->mock(PodcastInterface::class);
        $podcastEpisode = $this->mock(Podcast_Episode::class);

        $afterdate      = 666;
        $downloadAmount = 42;
        $keepAmount     = 33;

        $xml = <<<XML
        <rss>
        <channel>
        </channel>
        </rss>
        XML;

        $xmlChannel = simplexml_load_string($xml);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD)
            ->once()
            ->andReturn((string) $downloadAmount);
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::PODCAST_KEEP)
            ->once()
            ->andReturn((string) $keepAmount);

        $this->podcastRepository->shouldReceive('updateLastSync')
            ->with(
                $podcast,
                \Mockery::on(function ($value) {
                    return $value > time() - 5 && $value < time() + 5;
                })
            )
            ->once();

        $this->podcastEpisodeRepository->shouldReceive('getDownloadableEpisodes')
            ->with($podcast, $downloadAmount)
            ->once()
            ->andReturn($this->arrayAsGenerator([$podcastEpisode]));
        $this->podcastEpisodeRepository->shouldReceive('changeState')
            ->with($podcastEpisode, PodcastStateEnum::PENDING)
            ->once();
        $this->podcastEpisodeRepository->shouldReceive('getDeletableEpisodes')
            ->with($podcast, $keepAmount)
            ->once()
            ->andReturn($this->arrayAsGenerator([$podcastEpisode]));

        $this->podcastEpisodeDownloader->shouldReceive('download')
            ->with($podcastEpisode)
            ->once();

        $this->podcastEpisodeDeleter->shouldReceive('delete')
            ->with($podcastEpisode)
            ->once();

        $this->subject->addEpisodes(
            $podcast,
            $xmlChannel->channel->item,
            $afterdate,
            true
        );
    }
}
