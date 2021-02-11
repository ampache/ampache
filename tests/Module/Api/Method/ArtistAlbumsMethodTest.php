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
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class ArtistAlbumsMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var AlbumRepositoryInterface|MockInterface|null */
    private MockInterface $albumRepository;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private ?ArtistAlbumsMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory   = $this->mock(StreamFactoryInterface::class);
        $this->albumRepository = $this->mock(AlbumRepositoryInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);

        $this->subject = new ArtistAlbumsMethod(
            $this->streamFactory,
            $this->albumRepository,
            $this->modelFactory
        );
    }

    public function testHandleThrowsExceptionIfFilterIsMissing(): void
    {
        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: filter');

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->subject->handle($gatekeeper, $response, $output, []);
    }

    public function testHandleThrowsExceptionIfArtistDoesNotExist(): void
    {
        $objectId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $objectId);

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);

        $this->modelFactory->shouldReceive('createArtist')
            ->with($objectId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->subject->handle($gatekeeper, $response, $output, ['filter' => $objectId]);
    }

    public function testHandleReturnsEmptyOutputIfNoAlbumsExist(): void
    {
        $objectId = 666;
        $result   = 'some-result';

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);
        $stream     = $this->mock(StreamInterface::class);

        $this->modelFactory->shouldReceive('createArtist')
            ->with($objectId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->albumRepository->shouldReceive('getByArtist')
            ->with($artist)
            ->once()
            ->andReturn([]);

        $output->shouldReceive('emptyResult')
            ->with('album')
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
            $this->subject->handle($gatekeeper, $response, $output, ['filter' => $objectId])
        );
    }

    public function testHandleReturnsOutput(): void
    {
        $objectId = 666;
        $result   = 'some-result';
        $userId   = 42;
        $albums   = [1, 2];

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);
        $stream     = $this->mock(StreamInterface::class);

        $this->modelFactory->shouldReceive('createArtist')
            ->with($objectId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->albumRepository->shouldReceive('getByArtist')
            ->with($artist)
            ->once()
            ->andReturn($albums);

        $output->shouldReceive('albums')
            ->with(
                $albums,
                [],
                $userId,
                true,
                0,
                0
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

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->assertSame(
            $response,
            $this->subject->handle($gatekeeper, $response, $output, ['filter' => $objectId])
        );
    }
}
