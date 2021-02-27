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
use Ampache\Module\Api\Method\Lib\DemocraticControlMapperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\DemocraticRepositoryInterface;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class DemocraticMethodTest extends MockeryTestCase
{
    /** @var DemocraticControlMapperInterface|MockInterface|null */
    private MockInterface $democraticControlMapper;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var DemocraticRepositoryInterface|MockInterface|null */
    private MockInterface $democraticRepository;

    private DemocraticMethod $subject;

    public function setUp(): void
    {
        $this->democraticControlMapper = $this->mock(DemocraticControlMapperInterface::class);
        $this->streamFactory           = $this->mock(StreamFactoryInterface::class);
        $this->democraticRepository    = $this->mock(DemocraticRepositoryInterface::class);

        $this->subject = new DemocraticMethod(
            $this->democraticControlMapper,
            $this->streamFactory,
            $this->democraticRepository
        );
    }

    public function testHandleThrowsExceptionIfMethodParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: method');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfMethodIsNotKnown(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Invalid Request');

        $method = 'foobar';

        $this->democraticControlMapper->shouldReceive('map')
            ->with($method)
            ->once()
            ->andReturnNull();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['method' => $method]
        );
    }

    public function testHandleReturnsActionResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);
        $user       = $this->mock(User::class);
        $democratic = $this->mock(Democratic::class);

        $method      = 'foobar';
        $result      = 'some-result';
        $objectId    = 666;
        $accessLevel = 42;

        $this->democraticControlMapper->shouldReceive('map')
            ->with($method)
            ->once()
            ->andReturn(function (
                $democraticParam,
                $outputParam,
                $userParam,
                $objectIdParam
            ) use ($result, $democratic, $output, $objectId, $user): string {
                $this->assertSame($democraticParam, $democratic);
                $this->assertSame($outputParam, $output);
                $this->assertSame($userParam, $user);
                $this->assertSame($objectIdParam, $objectId);

                return $result;
            });

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->access = (string) $accessLevel;

        $this->democraticRepository->shouldReceive('getCurrent')
            ->with($accessLevel)
            ->once()
            ->andReturn($democratic);

        $democratic->shouldReceive('set_parent')
            ->withNoArgs()
            ->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'method' => $method,
                    'oid' => (string) $objectId
                ]
            )
        );
    }
}
