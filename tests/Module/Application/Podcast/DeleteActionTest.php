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
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class DeleteActionTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface */
    private MockInterface $ui;

    private DeleteAction $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->ui              = $this->mock(UiInterface::class);

        $this->subject = new DeleteAction(
            $this->configContainer,
            $this->ui
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

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunThrowsExceptionIfDemoModeIsActive(): void
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

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunRendersAndReturnsNull(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $webPath   = 'some-web-path';
        $podcastId = 666;

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

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['podcast_id' => (string) $podcastId]);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'Are You Sure?',
                'The Podcast will be removed from the database',
                sprintf(
                    '%s/podcast.php?action=confirm_delete&podcast_id=%s',
                    $webPath,
                    $podcastId
                ),
                1,
                'delete_podcast'
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
