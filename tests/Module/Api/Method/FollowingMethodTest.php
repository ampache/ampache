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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\UserFollowerRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class FollowingMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var UserFollowerRepositoryInterface|MockInterface|null */
    private MockInterface $userFollowerRepository;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var UserRepositoryInterface|MockInterface|null */
    private MockInterface $userRepository;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    private ?FollowingMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory          = $this->mock(StreamFactoryInterface::class);
        $this->userFollowerRepository = $this->mock(UserFollowerRepositoryInterface::class);
        $this->configContainer        = $this->mock(ConfigContainerInterface::class);
        $this->userRepository         = $this->mock(UserRepositoryInterface::class);
        $this->logger                 = $this->mock(LoggerInterface::class);

        $this->subject = new FollowingMethod(
            $this->streamFactory,
            $this->userFollowerRepository,
            $this->configContainer,
            $this->userRepository,
            $this->logger
        );
    }

    public function testHandleThrowsExceptionIfFeatureIsNotEnabled(): void
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

    public function testHandleThrowsExceptionIsUsernameParameterIsMissing(): void
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

    public function testHandleThrowsExceptionIsNoUserWasFound(): void
    {
        $username = 'some-username';

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf(T_('Not Found: %s'), $username));

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturnNull();

        $this->logger->shouldReceive('critical')
            ->with(
                sprintf(
                    'User `%s` cannot be found.',
                    $username
                ),
                [LegacyLogger::CONTEXT_TYPE => FollowingMethod::class]
            )
            ->once();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['username' => $username]
        );
    }

    public function testHandleReturnsEmptyOutput(): void
    {
        $username = 'some-username';
        $result   = 'some-result';
        $userId   = 666;

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturn($userId);

        $this->userFollowerRepository->shouldReceive('getFollowing')
            ->with($userId)
            ->once()
            ->andReturn([]);

        $output->shouldReceive('emptyResult')
            ->with('user')
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

    public function testHandleReturnsUsers(): void
    {
        $username       = 'some-username';
        $result         = 'some-result';
        $userId         = 666;
        $followerUserId = 42;

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturn($userId);

        $this->userFollowerRepository->shouldReceive('getFollowing')
            ->with($userId)
            ->once()
            ->andReturn([$followerUserId]);

        $output->shouldReceive('users')
            ->with([$followerUserId])
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
