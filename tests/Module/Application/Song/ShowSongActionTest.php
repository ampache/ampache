<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Song;

use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\Song\SongViewAdapterInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Gui\TalViewInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ShowSongActionTest extends MockeryTestCase
{
    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var GuiFactoryInterface|MockInterface|null */
    private MockInterface $guiFactory;

    /** @var MockInterface|TalFactoryInterface|null */
    private MockInterface $talFactory;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    private ShowSongAction $subject;

    protected function setUp(): void
    {
        $this->ui           = $this->mock(UiInterface::class);
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);
        $this->guiFactory   = $this->mock(GuiFactoryInterface::class);
        $this->talFactory   = $this->mock(TalFactoryInterface::class);
        $this->logger       = $this->mock(LoggerInterface::class);

        $this->subject = new ShowSongAction(
            $this->ui,
            $this->modelFactory,
            $this->guiFactory,
            $this->talFactory,
            $this->logger
        );
    }

    public function testRunEchoesErrorIfSongDoesNotExist(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $song       = $this->mock(Song::class);
        $user       = $this->mock(User::class);

        $song_id       = 0;
        $song->catalog = 1;

        $user->catalogs['music'] = [1];

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['song_id' => (string) $song_id]);

        $this->modelFactory->shouldReceive('createSong')
            ->with($song_id)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturn(true);

        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->logger->shouldReceive('warning')
            ->with(
                'Requested a song that does not exist',
                [LegacyLogger::CONTEXT_TYPE => ShowSongAction::class]
            )
            ->once();

        $this->expectOutputString('You have requested an object that does not exist');

        $this->assertNull(
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }

    public function testRunRendersSongDetails(): void
    {
        $request         = $this->mock(ServerRequestInterface::class);
        $gatekeeper      = $this->mock(GuiGatekeeperInterface::class);
        $song            = $this->mock(Song::class);
        $user            = $this->mock(User::class);
        $songViewAdapter = $this->mock(SongViewAdapterInterface::class);
        $talView         = $this->mock(TalViewInterface::class);

        $song_id = 666;
        $title   = 'some-song-title';
        $content = 'some-content';

        $song->id      = $song_id;
        $song->catalog = 1;

        $user->catalogs['music'] = [1];

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['song_id' => (string) $song_id]);

        $this->modelFactory->shouldReceive('createSong')
            ->with($song_id)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturn(false);

        $this->ui->shouldReceive('showBoxTop')
            ->with(
                $title,
                'box box_song_details'
            )
            ->once();
        $this->ui->shouldReceive('showBoxBottom')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $song->shouldReceive('get_fullname')
            ->withNoArgs()
            ->once()
            ->andReturn($title);

        $this->guiFactory->shouldReceive('createSongViewAdapter')
            ->with($gatekeeper, $song)
            ->once()
            ->andReturn($songViewAdapter);

        $this->talFactory->shouldReceive('createTalView->setTemplate')
            ->with('song.xhtml')
            ->once()
            ->andReturn($talView);

        $talView->shouldReceive('setContext')
            ->with('SONG', $songViewAdapter)
            ->once()
            ->andReturnSelf();
        $talView->shouldReceive('render')
            ->withNoArgs()
            ->once()
            ->andReturn($content);

        $this->expectOutputString($content);

        $this->assertNull(
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }
}
