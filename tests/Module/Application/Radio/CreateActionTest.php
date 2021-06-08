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

namespace Ampache\Module\Application\Radio;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\FormVerificatorInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\LiveStream\LiveStreamCreatorInterface;
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateActionTest extends MockeryTestCase
{
    private MockInterface $configContainer;

    private MockInterface $ui;

    private MockInterface $formVerificator;

    private MockInterface $liveStreamCreator;

    private CreateAction $subject;

    public function setUp(): void
    {
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->ui                = $this->mock(UiInterface::class);
        $this->formVerificator   = $this->mock(FormVerificatorInterface::class);
        $this->liveStreamCreator = $this->mock(LiveStreamCreatorInterface::class);

        $this->subject = new CreateAction(
            $this->configContainer,
            $this->ui,
            $this->formVerificator,
            $this->liveStreamCreator
        );
    }

    public function testRunThrowsExceptionIfRadioIsDisabled(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RADIO)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RADIO)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunThrowsExceptionIfDemoModeIsEnabled(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RADIO)
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

    public function testRunThrowsExceptionIfFormVerificationFails(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RADIO)
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->formVerificator->shouldReceive('verify')
            ->with($request, 'add_radio')
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunShowsErrorIfCreationFails(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $body = ['some' => 'body'];

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RADIO)
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn($body);

        $this->formVerificator->shouldReceive('verify')
            ->with($request, 'add_radio')
            ->once()
            ->andReturnTrue();

        $this->liveStreamCreator->shouldReceive('create')
            ->with($body)
            ->once()
            ->andReturnFalse();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_add_live_stream.inc.php',
                [
                    'ui' => $this->ui,
                ]
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

    public function testRunCreates(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $body    = ['some' => 'body'];
        $webPath = 'some-path';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RADIO)
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

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn($body);

        $this->formVerificator->shouldReceive('verify')
            ->with($request, 'add_radio')
            ->once()
            ->andReturnTrue();

        $this->liveStreamCreator->shouldReceive('create')
            ->with($body)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                '',
                'Radio Station created',
                sprintf(
                    '%s/browse.php?action=live_stream',
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
