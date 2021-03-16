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

namespace Ampache\Module\Api\Gui\Method;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Teapot\StatusCode;

class DownloadMethodTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private DownloadMethod $subject;

    public function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new DownloadMethod(
            $this->modelFactory
        );
    }

    public function testHandleReturn404IfVitalRequestParamsAreFailing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $response->shouldReceive('withStatus')
            ->with(StatusCode::NOT_FOUND)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                []
            )
        );
    }

    public function testHandleReturn404IfTypeIsNotSupported(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $response->shouldReceive('withStatus')
            ->with(StatusCode::NOT_FOUND)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['id' => 666, 'type' => 'foobar']
            )
        );
    }

    public function testHandleReturn404IfPlayUrlIsEmpty(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $objectId   = 666;
        $objectType = 'song';
        $userId     = 42;

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $response->shouldReceive('withStatus')
            ->with(StatusCode::NOT_FOUND)
            ->once()
            ->andReturnSelf();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with('song', $objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('play_url')
            ->with(
                '&action=download&client=api&cache=1',
                'api',
                function_exists('curl_version'),
                $userId
            )
            ->once()
            ->andReturn('');

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['id' => (string) $objectId, 'type' => $objectType]
            )
        );
    }

    public function testHandleRedirectsToPlayUrl(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $objectId   = 666;
        $objectType = 'song';
        $userId     = 42;
        $url        = 'some-url';
        $format     = 'some-format';

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $response->shouldReceive('withStatus')
            ->with(StatusCode::FOUND)
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with(
                'Location',
                '/play' . $url
            )
            ->once()
            ->andReturnSelf();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with('song', $objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('play_url')
            ->with(
                '&action=download&client=api&cache=1&transcode_to=' . $format . '&format=' . $format,
                'api',
                function_exists('curl_version'),
                $userId
            )
            ->once()
            ->andReturn(':443/play' . $url);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['id' => (string) $objectId, 'type' => $objectType, 'format' => $format]
            )
        );
    }
}
