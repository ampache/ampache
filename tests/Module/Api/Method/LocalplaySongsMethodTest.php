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

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Localplay\LocalPlayControllerFactoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class LocalplaySongsMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var LocalPlayControllerFactoryInterface|MockInterface|null */
    private MockInterface $localPlayControllerFactory;

    private LocalplaySongsMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory              = $this->mock(StreamFactoryInterface::class);
        $this->configContainer            = $this->mock(ConfigContainerInterface::class);
        $this->localPlayControllerFactory = $this->mock(LocalPlayControllerFactoryInterface::class);

        $this->subject = new LocalplaySongsMethod(
            $this->streamFactory,
            $this->configContainer,
            $this->localPlayControllerFactory
        );
    }

    public function testHandleThrowsExceptionIfLocalplayAccessIsDenied(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $level = 33;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 100');

        $this->configContainer->shouldReceive('getLocalplayLevel')
            ->withNoArgs()
            ->once()
            ->andReturn($level);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_LOCALPLAY, $level)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfLocalplayConnectionFails(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $localPlay  = $this->mock(LocalPlay::class);

        $level = 33;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Unable to connect to localplay controller');

        $this->configContainer->shouldReceive('getLocalplayLevel')
            ->withNoArgs()
            ->once()
            ->andReturn($level);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_LOCALPLAY, $level)
            ->once()
            ->andReturnTrue();

        $this->localPlayControllerFactory->shouldReceive('create')
            ->withNoArgs()
            ->once()
            ->andReturn($localPlay);

        $localPlay->shouldReceive('connect')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleReturnsEmptyResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $localPlay  = $this->mock(LocalPlay::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'some-result';
        $level  = 33;

        $this->configContainer->shouldReceive('getLocalplayLevel')
            ->withNoArgs()
            ->once()
            ->andReturn($level);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_LOCALPLAY, $level)
            ->once()
            ->andReturnTrue();

        $this->localPlayControllerFactory->shouldReceive('create')
            ->withNoArgs()
            ->once()
            ->andReturn($localPlay);

        $localPlay->shouldReceive('connect')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();
        $localPlay->shouldReceive('get')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('emptyResult')
            ->with('localplay_songs')
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

    public function testHandleReturnsSongResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $localPlay  = $this->mock(LocalPlay::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'some-result';
        $level  = 33;
        $songs  = [42, 666];

        $this->configContainer->shouldReceive('getLocalplayLevel')
            ->withNoArgs()
            ->once()
            ->andReturn($level);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_LOCALPLAY, $level)
            ->once()
            ->andReturnTrue();

        $this->localPlayControllerFactory->shouldReceive('create')
            ->withNoArgs()
            ->once()
            ->andReturn($localPlay);

        $localPlay->shouldReceive('connect')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();
        $localPlay->shouldReceive('get')
            ->withNoArgs()
            ->once()
            ->andReturn($songs);

        $output->shouldReceive('object_array')
            ->with($songs, 'localplay_songs')
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
}
