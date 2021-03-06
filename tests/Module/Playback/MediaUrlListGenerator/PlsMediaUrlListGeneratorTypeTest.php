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

namespace Ampache\Module\Playback\MediaUrlListGenerator;

use Ampache\MockeryTestCase;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\Stream_Url;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class PlsMediaUrlListGeneratorTypeTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface */
    private MockInterface $streamFactory;

    private PlsMediaUrlListGeneratorType $subject;

    public function setUp(): void
    {
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);

        $this->subject = new PlsMediaUrlListGeneratorType(
            $this->streamFactory
        );
    }

    public function testGenerateReturnsData(): void
    {
        $stream    = $this->mock(StreamInterface::class);
        $playlist  = $this->mock(Stream_Playlist::class);
        $response  = $this->mock(ResponseInterface::class);
        $streamUrl = $this->mock(Stream_Url::class);

        $url    = 'some-url';
        $author = 'some-author';
        $title  = 'some-title';
        $time   = 123456;

        $playlist->urls = [$streamUrl];

        $streamUrl->url    = $url;
        $streamUrl->author = $author;
        $streamUrl->title  = $title;
        $streamUrl->time   = $time;

        $result = "[playlist]\n";
        $result .= "NumberOfEntries=1\n";
        $result .= sprintf("File1=%s\n", $url);
        $result .= sprintf("Title1=%s - %s\n", $author, $title);
        $result .= sprintf("Length1=%s\n", $time);
        $result .= "Version=2\n";

        $response->shouldReceive('withHeader')
            ->with('Cache-Control', 'public')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Content-Disposition', 'filename=ampache_playlist.pls')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Content-Type', 'audio/x-scpls')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $this->assertSame(
            $response,
            $this->subject->generate($playlist, $response)
        );
    }
}
