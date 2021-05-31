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
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;

class PodcastEpisodeDeleterTest extends MockeryTestCase
{
    /** @var PodcastEpisodeRepositoryInterface|MockInterface */
    private MockInterface $podcastEpisodeRepository;

    /** @var LoggerInterface|MockInterface */
    private MockInterface $logger;

    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    private PodcastEpisodeDeleter $subject;

    public function setUp(): void
    {
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);
        $this->logger                   = $this->mock(LoggerInterface::class);
        $this->configContainer          = $this->mock(ConfigContainerInterface::class);

        $this->subject = new PodcastEpisodeDeleter(
            $this->podcastEpisodeRepository,
            $this->logger,
            $this->configContainer
        );
    }

    public function testDeleteLogsError(): void
    {
        $podcastEpisodeId = 666;

        $podcastEpisode = $this->mock(Podcast_Episode::class);

        $dir = vfsStream::setup();

        $fileName = $dir->url() . '/foobar';

        $podcastEpisode->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($podcastEpisodeId);
        $podcastEpisode->shouldReceive('getFile')
            ->withNoArgs()
            ->once()
            ->andReturn($fileName);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DELETE_FROM_DISK)
            ->once()
            ->andReturnTrue();

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Removing podcast episode %d', $podcastEpisodeId),
                [LegacyLogger::CONTEXT_TYPE => PodcastEpisodeDeleter::class]
            )
            ->once();
        $this->logger->shouldReceive('error')
            ->with(
                sprintf('Cannot delete file %s', $fileName),
                [LegacyLogger::CONTEXT_TYPE => PodcastEpisodeDeleter::class]
            )
            ->once();

        $this->podcastEpisodeRepository->shouldReceive('remove')
            ->with($podcastEpisode)
            ->once()
            ->andReturnFalse();

        $this->assertFalse(
            $this->subject->delete($podcastEpisode)
        );
    }

    public function testDeleteDeletes(): void
    {
        $podcastEpisodeId = 666;
        $fileName         = 'some-file-name';

        $podcastEpisode = $this->mock(Podcast_Episode::class);

        $dir  = vfsStream::setup();
        $file = vfsStream::newFile($fileName);
        $dir->addChild($file);

        $podcastEpisode->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($podcastEpisodeId);
        $podcastEpisode->shouldReceive('getFile')
            ->withNoArgs()
            ->once()
            ->andReturn($file->url());

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DELETE_FROM_DISK)
            ->once()
            ->andReturnTrue();

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Removing podcast episode %d', $podcastEpisodeId),
                [LegacyLogger::CONTEXT_TYPE => PodcastEpisodeDeleter::class]
            )
            ->once();

        $this->podcastEpisodeRepository->shouldReceive('remove')
            ->with($podcastEpisode)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->delete($podcastEpisode)
        );
    }
}
