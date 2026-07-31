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

namespace Ampache\Module\Application\Video;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\DeletionUrlResolverInterface;
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class DeleteActionTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private DeletionUrlResolverInterface|MockInterface|null $deletionUrlResolver;
    private LoggerInterface|MockInterface|null $logger;
    private ?DeleteAction $subject;
    private UiInterface|MockInterface|null $ui;

    public function testRunCancelsToTheVideoItselfWithoutAnOriginPage(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $videoId = 666;
        $webPath = 'some-path';

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
            ->andReturn(['video_id' => (string) $videoId]);

        $this->deletionUrlResolver->shouldReceive('resolveBurl')
            ->with('')
            ->once()
            ->andReturn('');

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmationWithReturn')
            ->with(
                'Are You Sure?',
                'The Video will be deleted',
                sprintf(
                    '%s/video.php?action=confirm_delete&video_id=%d&burl=',
                    $webPath,
                    $videoId
                ),
                sprintf('%s/video.php?action=show_video&video_id=%d', $webPath, $videoId),
                'delete_video'
            )
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunDeletesAndReturnsNull(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $videoId    = 666;
        $webPath    = 'some-path';
        $burlParam  = 'aA+b/c=';
        $originPage = 'some-path/browse.php?action=video';

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
            ->andReturn(['video_id' => (string) $videoId, 'burl' => $burlParam]);

        $this->deletionUrlResolver->shouldReceive('resolveBurl')
            ->with($burlParam)
            ->once()
            ->andReturn($originPage);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmationWithReturn')
            ->with(
                'Are You Sure?',
                'The Video will be deleted',
                sprintf(
                    '%s/video.php?action=confirm_delete&video_id=%d&burl=aA%%2Bb%%2Fc%%3D',
                    $webPath,
                    $videoId
                ),
                $originPage,
                'delete_video'
            )
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunReturnsNullInDemoMode(): void
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

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer     = $this->mock(ConfigContainerInterface::class);
        $this->ui                  = $this->mock(UiInterface::class);
        $this->logger              = $this->mock(LoggerInterface::class);
        $this->deletionUrlResolver = $this->mock(DeletionUrlResolverInterface::class);

        $this->subject = new DeleteAction(
            $this->configContainer,
            $this->ui,
            $this->logger,
            $this->deletionUrlResolver
        );
    }
}
