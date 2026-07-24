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

namespace Ampache\Module\Application\PodcastEpisode;

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
    private ConfigContainerInterface&MockInterface $configContainer;
    private DeletionUrlResolverInterface&MockInterface $deletionUrlResolver;
    private DeleteAction $subject;
    private UiInterface&MockInterface $ui;

    public function testRunCancelsToTheEpisodeItselfWithoutAnOriginPage(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $episodeId = 666;
        $webPath   = 'some-path';

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
            ->andReturn(['podcast_episode_id' => (string) $episodeId]);

        $this->deletionUrlResolver->shouldReceive('resolveBurl')
            ->with('')
            ->once()
            ->andReturn('');

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
                'The Podcast Episode will be deleted',
                sprintf(
                    '%s/podcast_episode.php?action=confirm_delete&podcast_episode_id=%d&burl=',
                    $webPath,
                    $episodeId
                ),
                sprintf('%s/podcast_episode.php?action=show&podcast_episode=%d', $webPath, $episodeId),
                'delete_podcast_episode'
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

    public function testRunShowsConfirmationWithTheOriginPage(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $episodeId  = 666;
        $webPath    = 'some-path';
        $burlParam  = 'aA+b/c=';
        $originPage = 'some-path/podcast.php?action=show&podcast=7';

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
            ->andReturn(['podcast_episode_id' => (string) $episodeId, 'burl' => $burlParam]);

        $this->deletionUrlResolver->shouldReceive('resolveBurl')
            ->with($burlParam)
            ->once()
            ->andReturn($originPage);

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
                'The Podcast Episode will be deleted',
                sprintf(
                    '%s/podcast_episode.php?action=confirm_delete&podcast_episode_id=%d&burl=aA%%2Bb%%2Fc%%3D',
                    $webPath,
                    $episodeId
                ),
                $originPage,
                'delete_podcast_episode'
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
