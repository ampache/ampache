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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class LastShoutsMethodTest extends MockeryTestCase
{
    /** @var UserRepositoryInterface|MockInterface|null */
    private MockInterface $userRepository;

    /** @var ShoutRepositoryInterface|MockInterface|null */
    private MockInterface $shoutRepository;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    private ?LastShoutsMethod $subject;

    public function setUp(): void
    {
        $this->userRepository  = $this->mock(UserRepositoryInterface::class);
        $this->shoutRepository = $this->mock(ShoutRepositoryInterface::class);
        $this->streamFactory   = $this->mock(StreamFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new LastShoutsMethod(
            $this->userRepository,
            $this->shoutRepository,
            $this->streamFactory,
            $this->configContainer
        );
    }

    public function testHandleThrowsExceptionIfSocialNotEnabled(): void
    {
        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: sociable');

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfUsernameIsMissing(): void
    {
        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: username');

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleReturnsResultWithConfigLimit(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $shoutId  = 666;
        $username = 'some-username';
        $result   = 'some-result';
        $limit    = 42;
        $userId   = 33;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('getPopularThreshold')
            ->with(10)
            ->once()
            ->andReturn($limit);

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturn($userId);

        $this->shoutRepository->shouldReceive('getTop')
            ->with($limit, $userId)
            ->once()
            ->andReturn([$shoutId]);

        $output->shouldReceive('shouts')
            ->with([$shoutId])
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
                ['username' => $username]
            )
        );
    }
}
