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

namespace Ampache\Module\Application\TvShowSeason;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\TvShowSeasonInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowActionTest extends MockeryTestCase
{
    /** @var MockInterface|UiInterface */
    private MockInterface $ui;

    /** @var ModelFactoryInterface|MockInterface */
    private MockInterface $modelFactory;

    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    private ShowAction $subject;

    public function setUp(): void
    {
        $this->ui              = $this->mock(UiInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new ShowAction(
            $this->ui,
            $this->modelFactory,
            $this->configContainer
        );
    }

    public function testRunRenders(): void
    {
        $request          = $this->mock(ServerRequestInterface::class);
        $gatekeeper       = $this->mock(GuiGatekeeperInterface::class);
        $tvShowSeason     = $this->mock(TvShowSeasonInterface::class);

        $tvShowSeasonId = 666;
        $episodes       = [111, 222];
        $webPath        = 'some-web-path';

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['season' => (string) $tvShowSeasonId]);

        $this->modelFactory->shouldReceive('createTvShowSeason')
            ->with($tvShowSeasonId)
            ->once()
            ->andReturn($tvShowSeason);

        $tvShowSeason->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $tvShowSeason->shouldReceive('getEpisodeIds')
            ->withNoArgs()
            ->once()
            ->andReturn($episodes);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_tvshow_season.inc.php',
                [
                    'season' => $tvShowSeason,
                    'object_ids' => $episodes,
                    'object_type' => 'tvshow_episode',
                    'web_path' => $webPath,
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
}
