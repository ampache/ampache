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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\PreferenceRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class SystemPreferenceMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var PreferenceRepositoryInterface|MockInterface|null */
    private MockInterface $preferenceRepository;

    private ?SystemPreferenceMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory        = $this->mock(StreamFactoryInterface::class);
        $this->preferenceRepository = $this->mock(PreferenceRepositoryInterface::class);

        $this->subject = new SystemPreferenceMethod(
            $this->streamFactory,
            $this->preferenceRepository
        );
    }

    public function testHandleThrowsExceptionIfFilterParamIsMissing(): void
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

    public function testHandleThrowsExceptionIfAccessIsDenied(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 100');

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => 'some-filter']
        );
    }

    public function testHandleThrowsExceptionIfPreferenceDoesNotExist(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $preferenceName = 'some-name';

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %s', $preferenceName));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->preferenceRepository->shouldReceive('get')
            ->with($preferenceName, -1)
            ->once()
            ->andReturn([]);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => $preferenceName]
        );
    }

    public function testHandleReturnsData(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $preferenceName = 'some-name';
        $preference     = ['some-preference'];
        $result         = 'some-result';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->preferenceRepository->shouldReceive('get')
            ->with($preferenceName, -1)
            ->once()
            ->andReturn($preference);

        $output->shouldReceive('object_array')
            ->with($preference, 'preference')
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
                ['filter' => $preferenceName]
            )
        );
    }
}
