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

namespace Ampache\Application\Api;

use Ampache\MockeryTestCase;
use Ampache\Model\Browse;
use Ampache\Model\ModelFactoryInterface;
use Ampache\Model\Playlist;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\RequestParserInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class RefreshReorderedApplicationTest extends MockeryTestCase
{

    /** @var MockInterface|LoggerInterface|null */
    private MockInterface $logger;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var RequestParserInterface|MockInterface|null */
    private MockInterface $requestParser;

    /** @var RefreshReorderedApplication|null */
    private RefreshReorderedApplication $subject;

    public function setUp(): void
    {
        $this->logger        = $this->mock(LoggerInterface::class);
        $this->modelFactory  = $this->mock(ModelFactoryInterface::class);
        $this->requestParser = $this->mock(RequestParserInterface::class);

        $this->subject = new RefreshReorderedApplication(
            $this->logger,
            $this->modelFactory,
            $this->requestParser
        );
    }

    public function testRunSortPlaylistMedias(): void
    {
        $action     = RefreshReorderedApplication::ACTION_REFRESH_PLAYLIST_MEDIAS;
        $id         = 666;
        $object_ids = [1, 2, 3];

        $browse   = $this->mock(Browse::class);
        $playlist = $this->mock(Playlist::class);

        $playlist->id = $id;

        $this->requestParser->shouldReceive('getFromRequest')
            ->with('action')
            ->once()
            ->andReturn($action);
        $this->requestParser->shouldReceive('getFromRequest')
            ->with('id')
            ->once()
            ->andReturn((string) $id);

        $this->logger->shouldReceive('debug')
            ->with(
                'Called for action: {' . $action . '}',
                [LegacyLogger::CONTEXT_TYPE => RefreshReorderedApplication::class]
            )
            ->once();

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);
        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($id)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $playlist->shouldReceive('get_items')
            ->withNoArgs()
            ->once()
            ->andReturn($object_ids);

        $browse->shouldReceive('set_type')
            ->with('playlist_media')
            ->once();
        $browse->shouldReceive('add_supplemental_object')
            ->with('playlist', $id)
            ->once();
        $browse->shouldReceive('set_static_content')
            ->with(true)
            ->once();
        $browse->shouldReceive('show_objects')
            ->with($object_ids)
            ->once();
        $browse->shouldReceive('store')
            ->withNoArgs()
            ->once();

        $this->subject->run();
    }

    public function testRunSortAlbumSongs(): void
    {
        $action = RefreshReorderedApplication::ACTION_REFRESH_ALBUM_SONGS;
        $id     = 666;

        $browse = $this->mock(Browse::class);

        $this->requestParser->shouldReceive('getFromRequest')
            ->with('action')
            ->once()
            ->andReturn($action);
        $this->requestParser->shouldReceive('getFromRequest')
            ->with('id')
            ->once()
            ->andReturn((string) $id);

        $this->logger->shouldReceive('debug')
            ->with(
                'Called for action: {' . $action . '}',
                [LegacyLogger::CONTEXT_TYPE => RefreshReorderedApplication::class]
            )
            ->once();

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_type')
            ->with('song')
            ->once();
        $browse->shouldReceive('set_show_header')
            ->with(true)
            ->once();
        $browse->shouldReceive('set_simple_browse')
            ->with(true)
            ->once();
        $browse->shouldReceive('set_filter')
            ->with('album', $id)
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

        $this->expectOutputString(
            '<div id=\'browse_content_song\' class=\'browse_content\'></div>'
        );

        $this->subject->run();
    }
}
