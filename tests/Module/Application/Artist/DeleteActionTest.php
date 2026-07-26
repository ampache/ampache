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

namespace Ampache\Module\Application\Artist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
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

    public function testRunCancelsToTheArtistItselfWithoutAnOriginPage(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $artistId = 42;
        $webPath  = 'some-web-path';

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
                'The Artist and all files will be deleted',
                sprintf(
                    '%s/artists.php?action=confirm_delete&artist_id=%d&burl=',
                    $webPath,
                    $artistId
                ),
                sprintf('%s/artists.php?action=show&artist=%d', $webPath, $artistId),
                'delete_artist'
            )
            ->once();

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
            ->andReturn(['artist_id' => (string) $artistId]);

        $this->deletionUrlResolver->shouldReceive('resolveBurl')
            ->with('')
            ->once()
            ->andReturn('');

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunDoesNothingInDemoMode(): void
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

    public function testRunRenders(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $artistId   = 42;
        $webPath    = 'some-web-path';
        $burlParam  = 'aA+b/c=';
        $originPage = 'some-web-path/browse.php?action=artist';

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
                'The Artist and all files will be deleted',
                sprintf(
                    '%s/artists.php?action=confirm_delete&artist_id=%d&burl=aA%%2Bb%%2Fc%%3D',
                    $webPath,
                    $artistId
                ),
                $originPage,
                'delete_artist'
            )
            ->once();

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
            ->andReturn(['artist_id' => (string) $artistId, 'burl' => $burlParam]);

        $this->deletionUrlResolver->shouldReceive('resolveBurl')
            ->with($burlParam)
            ->once()
            ->andReturn($originPage);

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
