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
use Ampache\Gui\FormVerificatorInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\PodcastInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateActionTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface */
    private MockInterface $ui;

    /** @var PodcastCreatorInterface|MockInterface */
    private MockInterface $podcastCreator;

    /** @var FormVerificatorInterface|MockInterface */
    private MockInterface $formVerificator;

    private CreateAction $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->ui              = $this->mock(UiInterface::class);
        $this->podcastCreator  = $this->mock(PodcastCreatorInterface::class);
        $this->formVerificator = $this->mock(FormVerificatorInterface::class);

        $this->subject = new CreateAction(
            $this->configContainer,
            $this->ui,
            $this->podcastCreator,
            $this->formVerificator
        );
    }

    public function testRunReturnsNullIfNotEnabled(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->run(
                $request,
                $gatekeeper
            )
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

        $this->subject->run(
            $request,
            $gatekeeper
        );
    }

    public function testRunThrowsExceptionIfDemoModeIsEnabled(): void
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

        $this->subject->run(
            $request,
            $gatekeeper
        );
    }

    public function testRunThrowsExceptionIfFormVerificationFails(): void
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
            ->andReturnFalse();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->formVerificator->shouldReceive('verify')
            ->with($request, 'add_podcast')
            ->once()
            ->andReturnFalse();

        $this->subject->run(
            $request,
            $gatekeeper
        );
    }

    public function testRunShowsErrorIfCreationFails(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $snippet = 'some-snippet';

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
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
            ->with($request, 'add_podcast')
            ->once()
            ->andReturnTrue();
        $this->formVerificator->shouldReceive('register')
            ->with('add_podcast')
            ->once()
            ->andReturn($snippet);

        $this->podcastCreator->shouldReceive('create')
            ->with('https://', 0)
            ->once()
            ->andReturnNull();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_add_podcast.inc.php',
                [
                    'feedUrl' => 'https://',
                    'catalogId' => 0,
                    'verificationSnippet' => $snippet,
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
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }

    public function testRunCreatesAndShowsConfirmation(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $feedUrl   = 'some-url';
        $catalogId = 666;
        $webPath   = 'some-webpath';

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'feed' => $feedUrl,
                'catalog' => (string) $catalogId,
            ]);

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

        $this->formVerificator->shouldReceive('verify')
            ->with($request, 'add_podcast')
            ->once()
            ->andReturnTrue();

        $this->podcastCreator->shouldReceive('create')
            ->with($feedUrl, $catalogId)
            ->once()
            ->andReturn($this->mock(PodcastInterface::class));

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'No Problem',
                'Subscribed to the Podcast',
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
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }
}
