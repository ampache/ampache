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
 *
 */

namespace Ampache\Module\Application\Label;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\DeletionUrlResolverInterface;
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

class DeleteActionTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private DeletionUrlResolverInterface|MockInterface|null $deletionUrlResolver;
    private ?DeleteAction $subject;
    private UiInterface|MockInterface|null $ui;

    public function testDoesNothingAndReturnsNullInDemoMode(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
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

    public function testShowConfirmationAndReturnsNull(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $labelId    = 666;
        $webPath    = 'some-web-path';
        $burlParam  = 'aA+b/c=';
        $originPage = 'some-web-path/browse.php?action=label';

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['label_id' => $labelId, 'burl' => $burlParam]);

        $this->deletionUrlResolver->shouldReceive('resolveBurl')
            ->with($burlParam)
            ->once()
            ->andReturn($originPage);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::CONTENT_MANAGER
            )
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::LABEL)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmationWithReturn')
            ->with(
                'Are You Sure?',
                'This Label will be deleted',
                sprintf(
                    '%s/labels.php?action=confirm_delete&label_id=%s&burl=aA%%2Bb%%2Fc%%3D',
                    $webPath,
                    $labelId
                ),
                $originPage,
                'delete_label'
            )
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testShowConfirmationCancelsToTheLabelItselfWithoutAnOriginPage(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $labelId = 666;
        $webPath = 'some-web-path';

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['label_id' => $labelId]);

        $this->deletionUrlResolver->shouldReceive('resolveBurl')
            ->with('')
            ->once()
            ->andReturn('');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::LABEL)
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::CONTENT_MANAGER
            )
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmationWithReturn')
            ->with(
                'Are You Sure?',
                'This Label will be deleted',
                sprintf(
                    '%s/labels.php?action=confirm_delete&label_id=%s&burl=',
                    $webPath,
                    $labelId
                ),
                sprintf('%s/labels.php?action=show&label=%d', $webPath, $labelId),
                'delete_label'
            )
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer     = $this->mock(ConfigContainerInterface::class);
        $this->ui                  = $this->mock(UiInterface::class);
        $this->deletionUrlResolver = $this->mock(DeletionUrlResolverInterface::class);

        $this->subject = new DeleteAction(
            $this->configContainer,
            $this->ui,
            $this->deletionUrlResolver
        );
    }
}
