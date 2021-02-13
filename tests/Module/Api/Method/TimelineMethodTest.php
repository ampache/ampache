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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Preference\UserPreferenceRetrieverInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class TimelineMethodTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var UserRepositoryInterface|MockInterface|null */
    private MockInterface $userRepository;

    /** @var UserActivityRepositoryInterface|MockInterface|null */
    private MockInterface $userActivityRepository;

    /** @var UserPreferenceRetrieverInterface|MockInterface|null */
    private MockInterface $userPreferenceRetriever;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    private ?TimelineMethod $subject;

    public function setUp(): void
    {
        $this->configContainer         = $this->mock(ConfigContainerInterface::class);
        $this->userRepository          = $this->mock(UserRepositoryInterface::class);
        $this->userActivityRepository  = $this->mock(UserActivityRepositoryInterface::class);
        $this->userPreferenceRetriever = $this->mock(UserPreferenceRetrieverInterface::class);
        $this->streamFactory           = $this->mock(StreamFactoryInterface::class);

        $this->subject = new TimelineMethod(
            $this->configContainer,
            $this->userRepository,
            $this->userActivityRepository,
            $this->userPreferenceRetriever,
            $this->streamFactory
        );
    }

    public function testHandleThrowsExceptionIfSociableIsDisabled(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: sociable');

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
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: username');

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

    public function testHandleThrowsExceptionIfUsernameWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $userName = 'some-name';

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage($userName);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($userName)
            ->once()
            ->andReturnNull();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['username' => $userName]
        );
    }

    public function testHandleThrowsExceptionIfUserDoesntAllowPersonalInfo(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $userName = 'some-name';
        $userId   = 666;

        $this->expectException(AccessDeniedException::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($userName)
            ->once()
            ->andReturn($userId);

        $this->userPreferenceRetriever->shouldReceive('retrieve')
            ->with($userId, 'allow_personal_info_recent')
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['username' => $userName]
        );
    }

    public function testHandleReturnsData(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $userName   = 'some-name';
        $userId     = 666;
        $limit      = 42;
        $since      = 33;
        $result     = 'some-result';
        $activityId = 21;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($userName)
            ->once()
            ->andReturn($userId);

        $this->userPreferenceRetriever->shouldReceive('retrieve')
            ->with($userId, 'allow_personal_info_recent')
            ->once()
            ->andReturnTrue();

        $this->userActivityRepository->shouldReceive('getActivities')
            ->with($userId, $limit, $since)
            ->once()
            ->andReturn([$activityId]);

        $output->shouldReceive('timeline')
            ->with([$activityId])
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
                ['username' => $userName, 'limit' => $limit, 'since' => $since]
            )
        );
    }
}
