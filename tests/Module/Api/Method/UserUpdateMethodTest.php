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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Preference\UserPreferenceUpdaterInterface;
use Ampache\Module\User\UserStateTogglerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class UserUpdateMethodTest extends MockeryTestCase
{
    /** @var UserStateTogglerInterface|MockInterface|null */
    private MockInterface $userStateToggler;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var UserRepositoryInterface|MockInterface|null */
    private MockInterface $userRepository;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var UserPreferenceUpdaterInterface|MockInterface|null */
    private MockInterface $userPreferenceUpdater;

    private UserUpdateMethod $subject;

    public function setUp(): void
    {
        $this->userStateToggler      = $this->mock(UserStateTogglerInterface::class);
        $this->streamFactory         = $this->mock(StreamFactoryInterface::class);
        $this->userRepository        = $this->mock(UserRepositoryInterface::class);
        $this->modelFactory          = $this->mock(ModelFactoryInterface::class);
        $this->configContainer       = $this->mock(ConfigContainerInterface::class);
        $this->userPreferenceUpdater = $this->mock(UserPreferenceUpdaterInterface::class);

        $this->subject = new UserUpdateMethod(
            $this->userStateToggler,
            $this->streamFactory,
            $this->userRepository,
            $this->modelFactory,
            $this->configContainer,
            $this->userPreferenceUpdater
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

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfUserWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $username = 'some-username';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $username));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturnNull();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['username' => $username]
        );
    }

    public function testHandleThrowsExceptionIfTryingToUpdateAdminPassword(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $username = 'some-username';
        $userId   = 666;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $username));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $user->access = AccessLevelEnum::LEVEL_ADMIN;

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['username' => $username, 'password' => 'some-password']
        );
    }

    public function testHandleEnablesUser(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $username = 'some-username';
        $userId   = 666;
        $password = 'some-password';
        $result   = 'some-result';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $user->access = AccessLevelEnum::LEVEL_USER;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SIMPLE_USER_MODE)
            ->once()
            ->andReturnFalse();

        $user->shouldReceive('update_password')
            ->with('', $password)
            ->once();

        $this->userStateToggler->shouldReceive('enable')
            ->with($user)
            ->once();

        $output->shouldReceive('success')
            ->with(sprintf('successfully updated: %s', $username))
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
                ['username' => $username, 'disable' => '0', 'password' => $password]
            )
        );
    }

    public function testHandleDisablesUser(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $username = 'some-username';
        $userId   = 666;
        $result   = 'some-result';
        $fullname = 'some-fullname';
        $email    = 'some-email@tld.com';
        $website  = 'some-website';
        $state    = 'some-state';
        $city     = 'some-city';
        $bitrate  = 12345;

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->userStateToggler->shouldReceive('disable')
            ->with($user)
            ->once();

        $user->shouldReceive('update_fullname')
            ->with($fullname)
            ->once();
        $user->shouldReceive('update_email')
            ->with($email)
            ->once();
        $user->shouldReceive('update_website')
            ->with($website)
            ->once();
        $user->shouldReceive('update_state')
            ->with($state)
            ->once();
        $user->shouldReceive('update_city')
            ->with($city)
            ->once();

        $this->userPreferenceUpdater->shouldReceive('update')
            ->with('transcode_bitrate', $userId, $bitrate)
            ->once();

        $output->shouldReceive('success')
            ->with(sprintf('successfully updated: %s', $username))
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
                    'username' => $username,
                    'disable' => '1',
                    'fullname' => $fullname,
                    'email' => $email,
                    'website' => $website,
                    'state' => $state,
                    'city' => $city,
                    'maxbitrate' => (string) $bitrate,
                ]
            )
        );
    }
}
