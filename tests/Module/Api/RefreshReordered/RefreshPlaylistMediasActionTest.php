<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api\RefreshReordered;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class RefreshPlaylistMediasActionTest extends MockeryTestCase
{
    /** @var MockInterface|RequestParserInterface|null */
    private MockInterface $requestParser;

    /** @var MockInterface|ModelFactoryInterface|null */
    private MockInterface $modelFactory;

    private ?RefreshPlaylistMediasAction $subject;

    protected function setUp(): void
    {
        $this->requestParser = $this->mock(RequestParserInterface::class);
        $this->modelFactory  = $this->mock(ModelFactoryInterface::class);

        $this->subject = new RefreshPlaylistMediasAction(
            $this->requestParser,
            $this->modelFactory
        );
    }

    public function testRunRendersAndReturnsNull(): void
    {
        $objectId          = '666';
        $playlistObjectIds = [1, 2, 42];

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $playlist   = $this->mock(Playlist::class);
        $browse     = $this->mock(Browse::class);

        $this->requestParser->shouldReceive('getFromRequest')
            ->with('id')
            ->once()
            ->andReturn($objectId);

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);
        $this->modelFactory->shouldReceive('createPlaylist')
            ->with((int) $objectId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturn(false);
        $playlist->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $playlist->shouldReceive('get_items')
            ->withNoArgs()
            ->once()
            ->andReturn($playlistObjectIds);
        $playlist->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn((int) $objectId);

        $browse->shouldReceive('set_type')
            ->with('playlist_media')
            ->once();
        $browse->shouldReceive('add_supplemental_object')
            ->with('playlist', (int) $objectId)
            ->once();
        $browse->shouldReceive('set_static_content')
            ->with(true)
            ->once();
        $browse->shouldReceive('show_objects')
            ->with($playlistObjectIds)
            ->once();
        $browse->shouldReceive('store')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
