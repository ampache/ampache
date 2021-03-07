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

namespace Ampache\Module\Playback\MediaUrlListGenerator;

use Ampache\MockeryTestCase;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Util\XmlWriterInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class XspfMediaUrlListGeneratorTypeTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface */
    private MockInterface $streamFactory;

    /** @var XmlWriterInterface|MockInterface */
    private MockInterface $xmlWriter;

    private XspfMediaUrlListGeneratorType $subject;

    public function setUp(): void
    {
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);
        $this->xmlWriter     = $this->mock(XmlWriterInterface::class);

        $this->subject = new XspfMediaUrlListGeneratorType(
            $this->streamFactory,
            $this->xmlWriter
        );
    }

    public function testGenerateReturnsWrappedList(): void
    {
        $playlist   = $this->mock(Stream_Playlist::class);
        $response   = $this->mock(ResponseInterface::class);
        $stream_url = $this->mock(Stream_Url::class);
        $stream     = $this->mock(StreamInterface::class);

        $playlistTitle = 'some-playlist-title';
        $title         = 'some-title';
        $creator       = 'some-creator';
        $duration      = 666;
        $url           = 'some-url';
        $result        = 'some-result';
        $type          = 'video';
        $infoUrl       = 'some-info-url';
        $imageUrl      = 'some-image-url';
        $album         = 'some-album';
        $xmlResult     = 'some-xml-result';

        $playlist->title = $playlistTitle;
        $playlist->urls  = [$stream_url];

        $stream_url->title     = $title;
        $stream_url->author    = $creator;
        $stream_url->time      = $duration;
        $stream_url->url       = $url;
        $stream_url->type      = $type;
        $stream_url->info_url  = $infoUrl;
        $stream_url->image_url = $imageUrl;
        $stream_url->album     = $album;

        $this->xmlWriter->shouldReceive('buildKeyedArray')
            ->with([
                'track' => [
                    'title' => $title,
                    'creator' => $creator,
                    'duration' => $duration * 1000,
                    'location' => $url,
                    'identifier' => $url,
                    'meta' => [
                        'attribute' => 'rel="provider"',
                        'value' => 'video'
                    ],
                    'info' => $infoUrl,
                    'image' => $imageUrl,
                    'album' => $album
                ]
            ], true)
            ->once()
            ->andReturn($result);
        $this->xmlWriter->shouldReceive('writePlainXml')
            ->with($result, 'xspf', $playlistTitle)
            ->once()
            ->andReturn($xmlResult);

        $this->streamFactory->shouldReceive('createStream')
            ->with($xmlResult)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withHeader')
            ->with('Cache-Control', 'public')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Content-Disposition', 'filename=ampache_playlist.xspf')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Content-Type', 'application/xspf+xml')
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
