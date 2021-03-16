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
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Util\RecommendationInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class UpdateArtistInfoMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var RecommendationInterface|MockInterface|null */
    private MockInterface $recommendation;

    private UpdateArtistInfoMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory  = $this->mock(StreamFactoryInterface::class);
        $this->modelFactory   = $this->mock(ModelFactoryInterface::class);
        $this->recommendation = $this->mock(RecommendationInterface::class);

        $this->subject = new UpdateArtistInfoMethod(
            $this->streamFactory,
            $this->modelFactory,
            $this->recommendation
        );
    }

    public function testHandleThrowsExceptionIfIdParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: id');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionAccessIsDenied(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 75');

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['id' => 666]
        );
    }

    public function testHandleThrowsExceptionIfArtistDoesNotExist(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);

        $artistId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $artistId));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['id' => (string) $artistId]
        );
    }

    public function testHandleThrowsExceptionIfUpdateFails(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);

        $artistId = 666;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %d', $artistId));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->recommendation->shouldReceive('getArtistInfo')
            ->with($artistId)
            ->once()
            ->andReturn([]);
        $this->recommendation->shouldReceive('getArtistsLike')
            ->with($artistId)
            ->once()
            ->andReturn([]);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['id' => (string) $artistId]
        );
    }

    public function testHandleUpdates(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $artist     = $this->mock(Artist::class);
        $stream     = $this->mock(StreamInterface::class);

        $artistId = 666;
        $result   = 'some-result';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->recommendation->shouldReceive('getArtistInfo')
            ->with($artistId)
            ->once()
            ->andReturn([]);
        $this->recommendation->shouldReceive('getArtistsLike')
            ->with($artistId)
            ->once()
            ->andReturn(['some-info']);

        $output->shouldReceive('success')
            ->with(sprintf('Updated artist info: %d', $artistId))
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
                ['id' => (string) $artistId]
            )
        );
    }
}
