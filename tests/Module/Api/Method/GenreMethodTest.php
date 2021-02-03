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
use Ampache\Model\ModelFactoryInterface;
use Ampache\Model\Tag;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class GenreMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var MockInterface|ModelFactoryInterface|null */
    private MockInterface $modelFactory;

    private ?GenreMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);
        $this->modelFactory  = $this->mock(ModelFactoryInterface::class);

        $this->subject = new GenreMethod(
            $this->streamFactory,
            $this->modelFactory
        );
    }

    public function testHandleThrowsExceptionIfFilterIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: filter');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfTagDoesNotExist(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $tag        = $this->mock(Tag::class);

        $objectId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $objectId);

        $this->modelFactory->shouldReceive('createTag')
            ->with($objectId)
            ->once()
            ->andReturn($tag);

        $tag->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => $objectId]
        );
    }

    public function testHandleReturnsResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $tag        = $this->mock(Tag::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId = 666;
        $result   = 'some-result';

        $this->modelFactory->shouldReceive('createTag')
            ->with($objectId)
            ->once()
            ->andReturn($tag);

        $tag->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $output->shouldReceive('genres')
            ->with(
                [$objectId],
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
            ['filter' => $objectId]
        );
    }
}
