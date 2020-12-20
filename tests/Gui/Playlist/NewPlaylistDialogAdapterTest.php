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

namespace Ampache\Gui\Playlist;

use Ampache\MockeryTestCase;
use Ampache\Model\Playlist;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Mockery\MockInterface;

class NewPlaylistDialogAdapterTest extends MockeryTestCase
{
    /** @var MockInterface|PlaylistLoaderInterface|null */
    private MockInterface $playlistLoader;

    /** @var MockInterface|AjaxUriRetrieverInterface|null */
    private MockInterface $ajaxUriRetriever;

    /** @var MockInterface|GuiGatekeeperInterface|null */
    private MockInterface $gatekeeper;

    private string $object_type = 'some-object-type';

    private int $object_id = 666;

    private ?NewPlaylistDialogAdapter $subject;

    public function setUp(): void
    {
        $this->playlistLoader   = $this->mock(PlaylistLoaderInterface::class);
        $this->ajaxUriRetriever = $this->mock(AjaxUriRetrieverInterface::class);
        $this->gatekeeper       = $this->mock(GuiGatekeeperInterface::class);

        $this->subject = new NewPlaylistDialogAdapter(
            $this->playlistLoader,
            $this->ajaxUriRetriever,
            $this->gatekeeper,
            $this->object_type,
            $this->object_id
        );
    }

    public function testGetPlaylistsReturnsList(): void
    {
        $playlist = $this->mock(Playlist::class);

        $userId = 666;

        $this->gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->playlistLoader->shouldReceive('loadByUserId')
            ->with($userId)
            ->once()
            ->andReturn([$playlist]);

        $this->assertSame(
            [$playlist],
            $this->subject->getPlaylists()
        );
    }

    public function testAjaxUriReturnsUri(): void
    {
        $uri = 'some-uri';

        $this->ajaxUriRetriever->shouldReceive('getAjaxUri')
            ->withNoArgs()
            ->once()
            ->andReturn($uri);

        $this->assertSame(
            $uri,
            $this->subject->getAjaxUri()
        );
    }

    public function testGetObjectTypeReturnsValue(): void
    {
        $this->assertSame(
            $this->object_type,
            $this->subject->getObjectType()
        );
    }

    public function testGetObjectIdReturnsValue(): void
    {
        $this->assertSame(
            $this->object_id,
            $this->subject->getObjectId()
        );
    }

    public function testGetNewPlaylistTitleReturnsValue(): void
    {
        $this->assertSame(
            'Playlist Name',
            $this->subject->getNewPlaylistTitle()
        );
    }
}
