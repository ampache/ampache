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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\PodcastRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ConfirmDeleteActionTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface */
    private MockInterface $ui;

    /** @var MockInterface|PodcastDeleterInterface */
    private MockInterface $podcastDeleter;

    /** @var MockInterface|PodcastRepositoryInterface */
    private MockInterface $podcastRepository;

    private ConfirmDeleteAction $subject;

    public function setUp(): void
    {
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->ui                = $this->mock(UiInterface::class);
        $this->podcastDeleter    = $this->mock(PodcastDeleterInterface::class);
        $this->podcastRepository = $this->mock(PodcastRepositoryInterface::class);

        $this->subject = new ConfirmDeleteAction(
            $this->configContainer,
            $this->ui,
            $this->podcastDeleter,
            $this->podcastRepository
        );
    }

    public function testRunReturnsNullIfPodcastIsDisabled(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->expectException(AccessDeniedException::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunThrowsExceptionIfDemoMode(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->expectException(AccessDeniedException::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunReturnsNullAndShowsErrorIfDeletionFails(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $podcast    = $this->mock(Podcast::class);

        $podcastId = 666;
        $webPath   = 'some-web-path';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['podcast_id' => (string) $podcastId]);

        $this->podcastRepository->shouldReceive('findById')
            ->with($podcastId)
            ->once()
            ->andReturn($podcast);

        $this->podcastDeleter->shouldReceive('delete')
            ->with($podcast)
            ->once()
            ->andReturnFalse();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'There Was a Problem',
                'Couldn\'t delete this Podcast.',
                sprintf(
                    '%s/browse.php?action=podcast',
                    $webPath
                )
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunReturnsNullAndDeletes(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $podcast    = $this->mock(Podcast::class);

        $podcastId = 666;
        $webPath   = 'some-web-path';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['podcast_id' => (string) $podcastId]);

        $this->podcastRepository->shouldReceive('findById')
            ->with($podcastId)
            ->once()
            ->andReturn($podcast);

        $this->podcastDeleter->shouldReceive('delete')
            ->with($podcast)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'No Problem',
                'Podcast has been deleted',
                sprintf(
                    '%s/browse.php?action=podcast',
                    $webPath
                )
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
