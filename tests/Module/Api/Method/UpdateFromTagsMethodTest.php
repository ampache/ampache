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

namespace Ampache\Module\Api\Method;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Catalog\SingleItemUpdaterInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class UpdateFromTagsMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var SingleItemUpdaterInterface|MockInterface|null */
    private MockInterface $singleItemUpdater;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private UpdateFromTagsMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory     = $this->mock(StreamFactoryInterface::class);
        $this->singleItemUpdater = $this->mock(SingleItemUpdaterInterface::class);
        $this->modelFactory      = $this->mock(ModelFactoryInterface::class);

        $this->subject = new UpdateFromTagsMethod(
            $this->streamFactory,
            $this->singleItemUpdater,
            $this->modelFactory
        );
    }

    public function testHandleThrowsExceptionIfTypeParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: type');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
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
            ['type' => 'some-type']
        );
    }

    public function testHandleThrowsExceptionIfTypeIsNotSupported(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $type = 'some-type';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: ' . $type);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => $type, 'id' => 666]
        );
    }

    public function testHandleThrowsExceptionIfItemWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $type     = 'song';
        $songId   = 666;
        $song->id = 0;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage('Not Found: ' . $songId);

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $songId)
            ->once()
            ->andReturn($song);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => $type, 'id' => (string) $songId]
        );
    }

    public function testHandleUpdates(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);

        $type     = 'song';
        $songId   = 666;
        $song->id = $songId;
        $result   = 'some-result';

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $songId)
            ->once()
            ->andReturn($song);

        $this->singleItemUpdater->shouldReceive('update')
            ->with($type, $songId, true)
            ->once();

        $output->shouldReceive('success')
            ->with(sprintf('Updated tags for: %d (%s)', $songId, $type))
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
                ['type' => $type, 'id' => (string) $songId]
            )
        );
    }
}
