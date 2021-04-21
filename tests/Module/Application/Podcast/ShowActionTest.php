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
use Ampache\Gui\TalFactoryInterface;
use Ampache\Gui\TalViewInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Podcast\Gui\PodcastGuiFactoryInterface;
use Ampache\Module\Podcast\Gui\PodcastViewAdapterInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowActionTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface */
    private MockInterface $ui;

    /** @var PodcastEpisodeRepositoryInterface|MockInterface */
    private MockInterface $podcastEpisodeRepository;

    /** @var MockInterface|TalFactoryInterface */
    private MockInterface $talFactory;

    /** @var MockInterface|PodcastGuiFactoryInterface */
    private MockInterface $podcastGuiFactory;

    /** @var MockInterface|PodcastRepositoryInterface */
    private MockInterface $podcastRepository;

    private ShowAction $subject;

    public function setUp(): void
    {
        $this->configContainer          = $this->mock(ConfigContainerInterface::class);
        $this->ui                       = $this->mock(UiInterface::class);
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);
        $this->talFactory               = $this->mock(TalFactoryInterface::class);
        $this->podcastGuiFactory        = $this->mock(PodcastGuiFactoryInterface::class);
        $this->podcastRepository        = $this->mock(PodcastRepositoryInterface::class);

        $this->subject = new ShowAction(
            $this->configContainer,
            $this->ui,
            $this->podcastEpisodeRepository,
            $this->talFactory,
            $this->podcastGuiFactory,
            $this->podcastRepository
        );
    }

    public function testRunReturnsNullIfPodcastDisabled(): void
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

    public function testRunDoesNothingIfPodcastIdIsMissin(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
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

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunRendersAndReturnsNull(): void
    {
        $request            = $this->mock(ServerRequestInterface::class);
        $gatekeeper         = $this->mock(GuiGatekeeperInterface::class);
        $podcast            = $this->mock(Podcast::class);
        $podcastViewAdapter = $this->mock(PodcastViewAdapterInterface::class);
        $talView            = $this->mock(TalViewInterface::class);

        $podcastId    = 666;
        $webPath      = 'some-web-path';
        $renderResult = 'some-result';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->talFactory->shouldReceive('createTalView->setTemplate')
            ->with('podcast/podcast.xhtml')
            ->once()
            ->andReturn($talView);

        $talView->shouldReceive('setContext')
            ->with('PODCAST', $podcastViewAdapter)
            ->once()
            ->andReturnSelf();
        $talView->shouldReceive('setContext')
            ->with('PODCAST_ID', $podcastId)
            ->once()
            ->andReturnSelf();
        $talView->shouldReceive('setContext')
            ->with('WEB_PATH', $webPath)
            ->once()
            ->andReturnSelf();
        $talView->shouldReceive('render')
            ->withNoArgs()
            ->once()
            ->andReturn($renderResult);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->podcastGuiFactory->shouldReceive('createPodcastViewAdapter')
            ->with($podcast)
            ->once()
            ->andReturn($podcastViewAdapter);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['podcast' => (string) $podcastId]);

        $this->podcastRepository->shouldReceive('findById')
            ->with($podcastId)
            ->once()
            ->andReturn($podcast);

        $this->expectOutputString($renderResult);

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
