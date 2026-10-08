<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class Artist3MethodTest extends MockeryTestCase
{
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private StreamFactoryInterface|MockInterface|null $streamFactory;
    private ?Artist3Method $subject;

    /**
     * A withdrawn artist is refused exactly like an id that was never there, so nothing in the response
     * tells a listener that the artist exists at all.
     */
    public function testHandleRefusesAnArtistTheCallerCannotSee(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);
        $user       = $this->mock(User::class);

        $artistId = 666;

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $artist->shouldReceive('isVisible')
            ->with($user)
            ->once()
            ->andReturnFalse();

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $artistId);

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $artistId],
            $user,
            3
        );
    }

    public function testHandleReturnsOutput(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $artistId = 666;
        $result   = 'some-result';

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $artist->shouldReceive('isVisible')
            ->with($user)
            ->once()
            ->andReturnTrue();

        $output->shouldReceive('artists')
            ->with(
                3,
                [(string) $artistId],
                [],
                $user,
                'stringauth',
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

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'filter' => (string) $artistId,
                    'auth' => 'stringauth',
                ],
                $user,
                3
            )
        );
    }

    public function testHandleThrowsExceptionIfArtistDoesNotExist(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);
        $user       = $this->mock(User::class);

        $artistId = 666;

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $artistId);

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $artistId],
            $user,
            3
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->modelFactory  = $this->mock(ModelFactoryInterface::class);
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);

        $this->subject = new Artist3Method(
            $this->modelFactory,
            $this->streamFactory
        );
    }
}
