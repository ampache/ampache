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

namespace Ampache\Module\Api\Authentication;

use Ampache\MockeryTestCase;
use Ampache\Model\ModelFactoryInterface;
use Ampache\Model\User;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\UserRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class HandshakeTest extends MockeryTestCase
{
    /** @var UserRepositoryInterface|MockInterface|null */
    private MockInterface $userRepository;

    /** @var NetworkCheckerInterface|MockInterface|null */
    private MockInterface $networkChecker;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private ?Handshake $subject;

    public function setUp(): void
    {
        $this->userRepository = $this->mock(UserRepositoryInterface::class);
        $this->networkChecker = $this->mock(NetworkCheckerInterface::class);
        $this->logger         = $this->mock(LoggerInterface::class);
        $this->modelFactory   = $this->mock(ModelFactoryInterface::class);

        $this->subject = new Handshake(
            $this->userRepository,
            $this->networkChecker,
            $this->logger,
            $this->modelFactory
        );
    }

    public function testHandshakeThrowsExceptionIfVersionIsTooOld(): void
    {
        $username   = 'some-name';
        $passphrase = 'some-pass';
        $timestamp  = time();
        $version    = '1.2.3';
        $userIp     = '111.222.333.444';

        $this->expectException(Exception\HandshakeException::class);
        $this->expectExceptionMessage('Login failed, API version is too old');

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Handshake Attempt, IP:%s User:%s Version:%s', $userIp, $username, $version),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                'Login Failed: Version too old',
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();

        $this->subject->handshake(
            $username,
            $passphrase,
            $timestamp,
            $version,
            $userIp
        );
    }

    public function testHandshakeThrowsExceptionIfNoUserWasFound(): void
    {
        $username   = 'some-name';
        $passphrase = 'some-pass';
        $timestamp  = time();
        $version    = '5.0.0';
        $userIp     = '111.222.333.444';

        $this->expectException(Exception\HandshakeException::class);
        $this->expectExceptionMessage('Incorrect username or password');

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Handshake Attempt, IP:%s User:%s Version:%s', $userIp, $username, $version),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                sprintf('Login Attempt, IP:%s Time: %s User:%s (%s) Auth:%s', $userIp, $timestamp, $username, null, $passphrase),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                'Login Failed, unable to match passphrase',
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturnNull();

        $this->subject->handshake(
            $username,
            $passphrase,
            $timestamp,
            $version,
            $userIp
        );
    }

    public function testHandshakeThrowsExceptionIfAccessFromNetworkWasDenied(): void
    {
        $username   = 'some-name';
        $passphrase = 'some-pass';
        $timestamp  = time();
        $version    = '5.0.0';
        $userIp     = '111.222.333.444';
        $userId     = 666;

        $user = $this->mock(User::class);

        $this->expectException(Exception\HandshakeException::class);
        $this->expectExceptionMessage('Incorrect username or password');

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Handshake Attempt, IP:%s User:%s Version:%s', $userIp, $username, $version),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                sprintf('Login Attempt, IP:%s Time: %s User:%s (%s) Auth:%s', $userIp, $timestamp, $username, $userId, $passphrase),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                'Login Failed, unable to match passphrase',
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturn($userId);

        $this->networkChecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_API, $userId, AccessLevelEnum::LEVEL_GUEST)
            ->once()
            ->andReturnFalse();

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->subject->handshake(
            $username,
            $passphrase,
            $timestamp,
            $version,
            $userIp
        );
    }

    public function testHandshakeWithApiKeyReturnsUser(): void
    {
        $username   = '';
        $passphrase = 'some-pass';
        $timestamp  = time();
        $version    = '5.0.0';
        $userIp     = '111.222.333.444';
        $userId     = 666;

        $user = $this->mock(User::class);

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Handshake Attempt, IP:%s User:%s Version:%s', $userIp, $username, $version),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                sprintf('Login Attempt, IP:%s Time: %s User:%s (%s) Auth:%s', $userIp, $timestamp, $username, $userId, $passphrase),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                'Login Success, passphrase matched',
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();

        $this->userRepository->shouldReceive('findByApiKey')
            ->with($passphrase)
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->networkChecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_API, $userId, AccessLevelEnum::LEVEL_GUEST)
            ->once()
            ->andReturnTrue();

        $this->assertSame(
            $user,
            $this->subject->handshake(
                $username,
                $passphrase,
                $timestamp,
                $version,
                $userIp
            )
        );
    }

    public function testHandshakeThrowsExceptionIfTimestampIsBelowGracePeriod(): void
    {
        $username   = 'some-name';
        $passphrase = 'some-pass';
        $timestamp  = 12345;
        $version    = '5.0.0';
        $userIp     = '111.222.333.444';
        $userId     = 666;

        $user = $this->mock(User::class);

        $this->expectException(Exception\HandshakeException::class);
        $this->expectExceptionMessage('Login failed, timestamp is out of range');

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Handshake Attempt, IP:%s User:%s Version:%s', $userIp, $username, $version),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                sprintf('Login Attempt, IP:%s Time: %s User:%s (%s) Auth:%s', $userIp, $timestamp, $username, $userId, $passphrase),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                Mockery::pattern('/Login failed, timestamp is out/'),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturn($userId);

        $this->networkChecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_API, $userId, AccessLevelEnum::LEVEL_GUEST)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->subject->handshake(
            $username,
            $passphrase,
            $timestamp,
            $version,
            $userIp
        );
    }

    public function testHandshakeThrowsExceptionIfPasswordWasNotFound(): void
    {
        $username   = 'some-name';
        $passphrase = 'some-pass';
        $timestamp  = time();
        $version    = '5.0.0';
        $userIp     = '111.222.333.444';
        $userId     = 666;

        $user = $this->mock(User::class);

        $this->expectException(Exception\HandshakeException::class);
        $this->expectExceptionMessage('Login failed, timestamp is out of range');

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Handshake Attempt, IP:%s User:%s Version:%s', $userIp, $username, $version),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                sprintf('Login Attempt, IP:%s Time: %s User:%s (%s) Auth:%s', $userIp, $timestamp, $username, $userId, $passphrase),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                sprintf('Unable to find user with userid of %d', $userId),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturn($userId);
        $this->userRepository->shouldReceive('retrievePasswordFromUser')
            ->with($userId)
            ->once()
            ->andReturn('');

        $this->networkChecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_API, $userId, AccessLevelEnum::LEVEL_GUEST)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->subject->handshake(
            $username,
            $passphrase,
            $timestamp,
            $version,
            $userIp
        );
    }

    public function testHandshakeThrowsExceptionIfPassphrasesDontMatch(): void
    {
        $username     = 'some-name';
        $userPassword = 'some-user-password';
        $timestamp    = time();
        $passphrase   = hash('sha256', $timestamp . 'something');
        $version      = '5.0.0';
        $userIp       = '111.222.333.444';
        $userId       = 666;

        $user = $this->mock(User::class);

        $this->expectException(Exception\HandshakeException::class);
        $this->expectExceptionMessage('Incorrect username or password');

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf('Handshake Attempt, IP:%s User:%s Version:%s', $userIp, $username, $version),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                sprintf('Login Attempt, IP:%s Time: %s User:%s (%s) Auth:%s', $userIp, $timestamp, $username, $userId, $passphrase),
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                'Login Failed, unable to match passphrase',
                [LegacyLogger::CONTEXT_TYPE => Handshake::class]
            )
            ->once();

        $this->userRepository->shouldReceive('findByUsername')
            ->with($username)
            ->once()
            ->andReturn($userId);
        $this->userRepository->shouldReceive('retrievePasswordFromUser')
            ->with($userId)
            ->once()
            ->andReturn($userPassword);

        $this->networkChecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_API, $userId, AccessLevelEnum::LEVEL_GUEST)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->subject->handshake(
            $username,
            $passphrase,
            $timestamp,
            $version,
            $userIp
        );
    }
}
