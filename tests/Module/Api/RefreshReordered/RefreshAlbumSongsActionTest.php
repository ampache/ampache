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

namespace Ampache\Module\Api\RefreshReordered;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class RefreshAlbumSongsActionTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface */
    private MockInterface $modelFactory;

    private RefreshAlbumSongsAction $subject;

    public function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new RefreshAlbumSongsAction(
            $this->modelFactory
        );
    }

    public function testRunPrintsObjects(): void
    {
        $objectId = 666;

        $browse     = $this->mock(Browse::class);
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['id' => $objectId]);

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_show_header')
            ->with(true)
            ->once();
        $browse->shouldReceive('set_type')
            ->with('song')
            ->once();
        $browse->shouldReceive('set_simple_browse')
            ->with(true)
            ->once();
        $browse->shouldReceive('set_filter')
            ->withSomeOfArgs('album', $objectId)
            ->once();
        $browse->shouldReceive('set_sort')
            ->with('track', 'ASC')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once();
        $browse->shouldReceive('show_objects')
            ->with(null, true)
            ->once();
        $browse->shouldReceive('store')
            ->withNoArgs()
            ->once();

        $this->expectOutputString('<div id=\'browse_content_song\' class=\'browse_content\'></div>');

        $this->assertNull(
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }
}
