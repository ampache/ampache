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

namespace Ampache\Module\Application\Artist;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\SongRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowAllSongsActionTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    /** @var SongRepositoryInterface|MockInterface|null */
    private MockInterface $songRepository;

    private ?ShowAllSongsAction $subject;

    public function setUp(): void
    {
        $this->modelFactory   = $this->mock(ModelFactoryInterface::class);
        $this->ui             = $this->mock(UiInterface::class);
        $this->songRepository = $this->mock(SongRepositoryInterface::class);

        $this->subject = new ShowAllSongsAction(
            $this->modelFactory,
            $this->ui,
            $this->songRepository
        );
    }

    public function testRunRenders(): void
    {
        $artistId = 666;
        $songList = [42, 666];

        $artist     = $this->mock(Artist::class);
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['artist' => $artistId]);

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('format')
            ->withNoArgs()
            ->once();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_artist.inc.php',
                [
                    'artist' => $artist,
                    'object_type' => 'song',
                    'object_ids' => $songList,
                    'gatekeeper' => $gatekeeper,
                ]
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->songRepository->shouldReceive('getByArtist')
            ->with($artistId)
            ->once()
            ->andReturn($songList);

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
