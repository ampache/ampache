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
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\PlaylistRepositoryInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class PlaylistCreateMethodTest extends MockeryTestCase
{
    /** @var UpdateInfoRepositoryInterface|MockInterface|null */
    private MockInterface $updateInfoRepository;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var PlaylistRepositoryInterface|MockInterface|null */
    private MockInterface $playlistRepository;

    private PlaylistCreateMethod $subject;

    public function setUp(): void
    {
        $this->updateInfoRepository = $this->mock(UpdateInfoRepositoryInterface::class);
        $this->streamFactory        = $this->mock(StreamFactoryInterface::class);
        $this->playlistRepository   = $this->mock(PlaylistRepositoryInterface::class);

        $this->subject = new PlaylistCreateMethod(
            $this->updateInfoRepository,
            $this->streamFactory,
            $this->playlistRepository
        );
    }

    public function testHandleThrowsExceptionIfNameIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: name');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionifCreationFails(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $name   = 'some-name';
        $userId = 666;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->playlistRepository->shouldReceive('create')
            ->with($name, 'public', $userId)
            ->once()
            ->andReturn(0);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['name' => $name, 'type' => 'foobar']
        );
    }

    public function testHandleReturnsData(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $name       = 'some-name';
        $userId     = 666;
        $result     = 'some-result';
        $playlistId = 42;

        $this->playlistRepository->shouldReceive('create')
            ->with($name, 'private', $userId)
            ->once()
            ->andReturn($playlistId);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->updateInfoRepository->shouldReceive('updateCountByTableName')
            ->with('playlist')
            ->once();

        $output->shouldReceive('playlists')
            ->with([$playlistId], $userId, false, false)
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
                ['name' => $name]
            )
        );
    }
}
