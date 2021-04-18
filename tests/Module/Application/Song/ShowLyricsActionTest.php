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

namespace Ampache\Module\Application\Song;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowLyricsActionTest extends MockeryTestCase
{
    /** @var UiInterface|MockInterface */
    private MockInterface $ui;

    /** @var ModelFactoryInterface|MockInterface */
    private MockInterface $modelFactory;

    private ShowLyricsAction $subject;

    public function setUp(): void
    {
        $this->ui           = $this->mock(UiInterface::class);
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new ShowLyricsAction(
            $this->ui,
            $this->modelFactory
        );
    }

    public function testRunRendersLyrics(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $song       = $this->mock(Song::class);

        $songId = 666;
        $lyrics = ['some' => 'lyrics'];

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['song_id' => $songId]);

        $this->modelFactory->shouldReceive('createSong')
            ->with($songId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $song->shouldReceive('fill_ext_info')
            ->withNoArgs()
            ->once();
        $song->shouldReceive('get_lyrics')
            ->withNoArgs()
            ->once()
            ->andReturn($lyrics);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_lyrics.inc.php',
                [
                    'song' => $song,
                    'lyrics' => $lyrics
                ]
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showfooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
