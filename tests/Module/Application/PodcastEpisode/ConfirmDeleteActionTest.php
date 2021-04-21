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

namespace Ampache\Module\Application\PodcastEpisode;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\Podcast\PodcastEpisodeDeleterInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ConfirmDeleteActionTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface */
    private MockInterface $ui;

    /** @var LoggerInterface|MockInterface */
    private MockInterface $logger;

    /** @var MediaDeletionCheckerInterface|MockInterface */
    private MockInterface $mediaDeletionChecker;

    /** @var MockInterface|PodcastEpisodeDeleterInterface */
    private MockInterface $podcastEpisodeDeleter;

    /** @var MockInterface|PodcastEpisodeRepositoryInterface */
    private MockInterface $podcastEpisodeRepository;

    private ConfirmDeleteAction $subject;

    public function setUp(): void
    {
        $this->configContainer          = $this->mock(ConfigContainerInterface::class);
        $this->ui                       = $this->mock(UiInterface::class);
        $this->logger                   = $this->mock(LoggerInterface::class);
        $this->mediaDeletionChecker     = $this->mock(MediaDeletionCheckerInterface::class);
        $this->podcastEpisodeDeleter    = $this->mock(PodcastEpisodeDeleterInterface::class);
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);

        $this->subject = new ConfirmDeleteAction(
            $this->configContainer,
            $this->ui,
            $this->logger,
            $this->mediaDeletionChecker,
            $this->podcastEpisodeDeleter,
            $this->podcastEpisodeRepository
        );
    }

    public function testRunJustReturnsNullInDemoMode(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnTrue();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $episode    = $this->mock(Podcast_Episode::class);

        $episodeId = 666;
        $userId    = 42;

        $this->expectException(AccessDeniedException::class);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['podcast_episode_id' => (string) $episodeId]);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();

        $this->podcastEpisodeRepository->shouldReceive('findById')
            ->with($episodeId)
            ->once()
            ->andReturn($episode);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($episode, $userId)
            ->once()
            ->andReturnFalse();

        $this->logger->shouldReceive('warning')
            ->with(
                sprintf('Unauthorized to remove the episode `%s`', $episodeId),
                [LegacyLogger::CONTEXT_TYPE => ConfirmDeleteAction::class]
            )
            ->once();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunShowErrorIfDeletionFails(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $episode    = $this->mock(Podcast_Episode::class);

        $episodeId = 666;
        $userId    = 42;
        $webPath   = 'some-path';

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['podcast_episode_id' => (string) $episodeId]);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->podcastEpisodeRepository->shouldReceive('findById')
            ->with($episodeId)
            ->once()
            ->andReturn($episode);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($episode, $userId)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'There Was a Problem',
                'Couldn\'t delete this Podcast Episode',
                $webPath
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->podcastEpisodeDeleter->shouldReceive('delete')
            ->with($episode)
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunShowsConfirmation(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $episode    = $this->mock(Podcast_Episode::class);

        $episodeId = 666;
        $userId    = 42;
        $webPath   = 'some-path';

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['podcast_episode_id' => (string) $episodeId]);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->podcastEpisodeRepository->shouldReceive('findById')
            ->with($episodeId)
            ->once()
            ->andReturn($episode);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($episode, $userId)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'No Problem',
                'Podcast Episode has been deleted',
                $webPath
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->podcastEpisodeDeleter->shouldReceive('delete')
            ->with($episode)
            ->once()
            ->andReturnTrue();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
