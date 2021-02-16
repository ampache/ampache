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

namespace Ampache\Module\Application\SmartPlaylist;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Search;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowPlaylistActionTest extends MockeryTestCase
{
    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private ?ShowPlaylistAction $subject;

    public function setUp(): void
    {
        $this->ui           = $this->mock(UiInterface::class);
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new ShowPlaylistAction(
            $this->ui,
            $this->modelFactory
        );
    }

    public function testRunDisplaysPlaylistSearchView(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $search     = $this->mock(Search::class);

        $playlistId = 666;
        $objectIds  = [1, 2, 3];

        $this->modelFactory->shouldReceive('createSearch')
            ->with($playlistId)
            ->once()
            ->andReturn($search);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['playlist_id' => (string) $playlistId]);

        $search->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $search->shouldReceive('get_items')
            ->withNoArgs()
            ->once()
            ->andReturn($objectIds);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_search.inc.php',
                [
                    'playlist' => $search,
                    'object_ids' => $objectIds
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
