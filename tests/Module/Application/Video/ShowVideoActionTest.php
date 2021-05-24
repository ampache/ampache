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

namespace Ampache\Module\Application\Video;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Video\VideoLoaderInterface;
use Ampache\Repository\Model\Video;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowVideoActionTest extends MockeryTestCase
{
    private MockInterface $ui;

    private MockInterface $videoLoader;

    private ShowVideoAction $subject;

    public function setUp(): void
    {
        $this->ui          = $this->mock(UiInterface::class);
        $this->videoLoader = $this->mock(VideoLoaderInterface::class);

        $this->subject = new ShowVideoAction(
            $this->ui,
            $this->videoLoader
        );
    }

    public function testRunRenders(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $video      = $this->mock(Video::class);

        $videoId = 666;

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['video_id' => (string) $videoId]);

        $this->videoLoader->shouldReceive('load')
            ->with($videoId)
            ->once()
            ->andReturn($video);

        $video->shouldReceive('format')
            ->withNoArgs()
            ->once();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_video.inc.php',
                [
                    'video' => $video,
                ]
            )
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
