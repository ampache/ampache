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
use Ampache\Module\Util\RecommendationInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class GetSimilarMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var RecommendationInterface|MockInterface|null */
    private MockInterface $recommendation;

    private GetSimilarMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory  = $this->mock(StreamFactoryInterface::class);
        $this->recommendation = $this->mock(RecommendationInterface::class);

        $this->subject = new GetSimilarMethod(
            $this->streamFactory,
            $this->recommendation
        );
    }

    /**
     * @dataProvider requestDataProvider
     */
    public function testHandleThrowsExceptionIfRequestParamMissing(
        array $input,
        string $keyName
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $keyName));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            $input
        );
    }

    public function requestDataProvider(): array
    {
        return [
            [[], 'type'],
            [['type' => 1], 'filter'],
        ];
    }

    public function testHandleThrowsExceptionIfTypeNotAllowed(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: foobar');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'type' => 'foobar',
                'filter' => 666
            ]
        );
    }

    public function testHandleReturnsEmptyResultIfNothingWasFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $result   = 'some-result';
        $objectId = 666;
        $type     = 'song';

        $this->recommendation->shouldReceive('getSongsLike')
            ->with($objectId)
            ->once()
            ->andReturn([]);

        $output->shouldReceive('emptyResult')
            ->with($type)
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
                [
                    'type' => $type,
                    'filter' => (string) $objectId
                ]
            )
        );
    }

    public function testHandleReturnsData(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $result           = 'some-result';
        $objectId         = 666;
        $type             = 'artist';
        $userId           = 42;
        $recommendationId = 33;
        $limit            = 111;
        $offset           = 222;

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->recommendation->shouldReceive('getArtistsLike')
            ->with($objectId)
            ->once()
            ->andReturn([['child' => (string) $recommendationId]]);

        $output->shouldReceive('indexes')
            ->with(
                [$recommendationId],
                $type,
                $userId,
                false,
                false,
                $limit,
                $offset
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

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'type' => $type,
                    'filter' => (string) $objectId,
                    'limit' => $limit,
                    'offset' => $offset,
                ]
            )
        );
    }
}
