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
use Ampache\Gui\TalFactoryInterface;
use Ampache\Gui\TalViewInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Podcast\Gui\PodcastEpisodeViewAdapterInterface;
use Ampache\Module\Podcast\Gui\PodcastGuiFactoryInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowActionTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface */
    private MockInterface $ui;

    /** @var MockInterface|TalFactoryInterface */
    private MockInterface $talFactory;

    /** @var MockInterface|PodcastGuiFactoryInterface */
    private MockInterface $podcastGuiFactory;

    /** @var MockInterface|PodcastEpisodeRepositoryInterface */
    private MockInterface $podcastEpisodeRepository;

    private ShowAction $subject;

    public function setUp(): void
    {
        $this->configContainer          = $this->mock(ConfigContainerInterface::class);
        $this->ui                       = $this->mock(UiInterface::class);
        $this->talFactory               = $this->mock(TalFactoryInterface::class);
        $this->podcastGuiFactory        = $this->mock(PodcastGuiFactoryInterface::class);
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);

        $this->subject = new ShowAction(
            $this->configContainer,
            $this->ui,
            $this->talFactory,
            $this->podcastGuiFactory,
            $this->podcastEpisodeRepository
        );
    }

    public function testRunRendersAndReturnsNull(): void
    {
        $request     = $this->mock(ServerRequestInterface::class);
        $gatekeeper  = $this->mock(GuiGatekeeperInterface::class);
        $episode     = $this->mock(Podcast_Episode::class);
        $viewAdapter = $this->mock(PodcastEpisodeViewAdapterInterface::class);
        $talView     = $this->mock(TalViewInterface::class);

        $podcastEpisodeId = 666;
        $result           = 'some-result';
        $webPath          = 'some-path';

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $episode->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($podcastEpisodeId);

        $this->podcastGuiFactory->shouldReceive('createPodcastEpisodeViewAdapter')
            ->with($episode)
            ->once()
            ->andReturn($viewAdapter);

        $this->talFactory->shouldReceive('createTalView->setTemplate')
            ->with('podcast/podcast_episode.xhtml')
            ->once()
            ->andReturn($talView);

        $talView->shouldReceive('setContext')
            ->with('EPISODE', $viewAdapter)
            ->once()
            ->andReturnSelf();
        $talView->shouldReceive('setContext')
            ->with('EPISODE_ID', $podcastEpisodeId)
            ->once()
            ->andReturnSelf();
        $talView->shouldReceive('setContext')
            ->with('WEB_PATH', $webPath)
            ->once()
            ->andReturnSelf();
        $talView->shouldReceive('render')
            ->withNoArgs()
            ->once()
            ->andReturn($result);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['podcast_episode' => (string) $podcastEpisodeId]);

        $this->podcastEpisodeRepository->shouldReceive('findById')
            ->with($podcastEpisodeId)
            ->once()
            ->andReturn($episode);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->expectOutputString($result);

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
