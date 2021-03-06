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

namespace Ampache\Module\Playback\MediaUrlListGenerator;

use Ampache\MockeryTestCase;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\Stream_Url;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class M3uMediaUrlListGeneratorTypeTest extends MockeryTestCase
{
    /** @var MockInterface|StreamFactoryInterface */
    private MockInterface $streamFactory;

    private M3uMediaUrlListGeneratorType $subject;

    public function setUp(): void
    {
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);

        $this->subject = new M3uMediaUrlListGeneratorType(
            $this->streamFactory
        );
    }

    public function testGenerateReturnsResponse(): void
    {
        $playlist = $this->mock(Stream_Playlist::class);
        $response = $this->mock(ResponseInterface::class);
        $stream   = $this->mock(StreamInterface::class);
        $url      = $this->mock(Stream_Url::class);

        $url_path = 'some-path';
        $author   = 'some-author';
        $title    = 'some-title';
        $time     = 123456;

        $playlist->urls = [$url];

        $url->time   = $time;
        $url->author = $author;
        $url->title  = $title;
        $url->url    = $url_path;

        $playlistData = sprintf('#EXTM3U%s', PHP_EOL);
        $playlistData .= sprintf(
            '#EXTINF:%s, %s - %s%s',
            $time,
            $author,
            $title,
            PHP_EOL
        );
        $playlistData .= $url_path . PHP_EOL;

        $this->streamFactory->shouldReceive('createStream')
            ->with($playlistData)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withHeader')
            ->with('Cache-Control', 'public')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Content-Disposition', 'filename=ampache_playlist.m3u')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Content-Type', 'audio/x-mpegurl')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->generate($playlist, $response)
        );
    }
}
