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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Application\Playlist;

use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playlist\PlaylistSongSorterInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class SortTrackActionTest extends MockeryTestCase
{
    private MockInterface $modelFactory;

    private MockInterface $ui;

    private MockInterface $playlistSongSorter;

    private SortTrackAction $subject;

    public function setUp(): void
    {
        $this->modelFactory       = $this->mock(ModelFactoryInterface::class);
        $this->ui                 = $this->mock(UiInterface::class);
        $this->playlistSongSorter = $this->mock(PlaylistSongSorterInterface::class);

        $this->subject = new SortTrackAction(
            $this->modelFactory,
            $this->ui,
            $this->playlistSongSorter
        );
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $playlist   = $this->mock(Playlist::class);

        $playlistId = 666;

        $this->expectException(AccessDeniedException::class);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['playlist_id' => (string) $playlistId]);

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($playlistId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('has_access')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunSortsAndRenders(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $playlist   = $this->mock(Playlist::class);

        $playlistId = 666;
        $items      = [1, 2, 3];

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['playlist_id' => (string) $playlistId]);

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($playlistId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('has_access')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();
        $playlist->shouldReceive('get_items')
            ->withNoArgs()
            ->once()
            ->andReturn($items);

        $this->playlistSongSorter->shouldReceive('sort')
            ->with($playlist)
            ->once();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_playlist.inc.php',
                [
                    'playlist' => $playlist,
                    'object_ids' => $items,
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
