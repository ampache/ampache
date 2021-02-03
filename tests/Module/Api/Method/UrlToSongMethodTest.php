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

namespace Ampache\Module\Api\Method;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Stream\Url\StreamUrlParserInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class UrlToSongMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var StreamUrlParserInterface|MockInterface|null */
    private MockInterface $streamUrlParser;

    private ?UrlToSongMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory   = $this->mock(StreamFactoryInterface::class);
        $this->streamUrlParser = $this->mock(StreamUrlParserInterface::class);

        $this->subject = new UrlToSongMethod(
            $this->streamFactory,
            $this->streamUrlParser
        );
    }

    public function testHandleThrowsExceptionIfUrlIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: url');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfUrlIsNotParsable(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $url = 'some-url';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: url');

        $this->streamUrlParser->shouldReceive('parse')
            ->with($url)
            ->once()
            ->andReturn([]);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'url' => $url
            ]
        );
    }

    public function testHandleReturnsFoundSong(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $url    = 'some-url';
        $userId = 666;
        $songId = 42;
        $result = 'some-result';

        $this->streamUrlParser->shouldReceive('parse')
            ->with($url)
            ->once()
            ->andReturn(['id' => (string) $songId]);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $output->shouldReceive('songs')
            ->with(
                [$songId],
                $userId,
                true,
                false
            )
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'url' => $url
            ]
        );
    }
}
