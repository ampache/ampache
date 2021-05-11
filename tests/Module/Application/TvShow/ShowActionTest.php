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

namespace Ampache\Module\Application\TvShow;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\TvShowInterface;
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
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $tvShow     = $this->mock(TvShowInterface::class);

        $tvShowId = 666;
        $seasons  = [111, 222];
        $webPath  = 'some-web-path';

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['tvshow' => (string) $tvShowId]);

        $this->modelFactory->shouldReceive('createTvShow')
            ->with($tvShowId)
            ->once()
            ->andReturn($tvShow);

        $tvShow->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $tvShow->shouldReceive('get_seasons')
            ->withNoArgs()
            ->once()
            ->andReturn($seasons);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_tvshow.inc.php',
                [
                    'web_path' => $webPath,
                    'object_ids' => $seasons,
                    'object_type' => 'tvshow_season',
                    'tvshow' => $tvShow
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
