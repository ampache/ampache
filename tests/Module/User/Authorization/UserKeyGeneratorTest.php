<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\User\Authorization;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\User;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\UserRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class UserKeyGeneratorTest extends MockeryTestCase
{
    /** @var UserRepositoryInterface|MockInterface|null */
    private MockInterface $userRepository;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    private ?UserKeyGenerator $subject;

    public function setUp(): void
    {
        $this->userRepository = $this->mock(UserRepositoryInterface::class);
        $this->logger         = $this->mock(LoggerInterface::class);

        $this->subject = new UserKeyGenerator(
            $this->userRepository,
            $this->logger
        );
    }

    public function testGenerateApiKeyGeneratesAndSetsNewKey(): void
    {
        $userId   = 666;
        $userName = 'some-user-name';
        $password = 'some-user-password';

        $user = $this->mock(User::class);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $user->username = $userName;

        $this->userRepository->shouldReceive('updateApiKey')
            ->with(
                $userId,
                Mockery::type('string')
            )
            ->once();
        $this->userRepository->shouldReceive('retrievePasswordFromUser')
            ->with($userId)
            ->once()
            ->andReturn($password);

        $this->logger->shouldReceive('notice')
            ->with(
                sprintf('Updating apikey for %d', $userId),
                [LegacyLogger::CONTEXT_TYPE => UserKeyGenerator::class]
            )
            ->once();

        $this->subject->generateApikey(
            $user
        );
    }

    public function testGenerateRssTokenGeneratesAndSetsToken(): void
    {
        $userId = 666;

        $user = $this->mock(User::class);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->logger->shouldReceive('notice')
            ->with(
                sprintf('Updating rsstoken for %d', $userId),
                [LegacyLogger::CONTEXT_TYPE => UserKeyGenerator::class]
            )
            ->once();

        $this->userRepository->shouldReceive('updateRssToken')
            ->with(
                $userId,
                Mockery::type('string')
            )
            ->once();

        $this->subject->generateRssToken($user);
    }

    public function testGenerateRssTokenDoesNothingOnError(): void
    {
        $userId       = 666;
        $errorMessage = 'some-error-message';

        $user = $this->mock(User::class);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andThrow(new \Exception($errorMessage));

        $this->logger->shouldReceive('error')
            ->with(
                sprintf('Could not generate random_bytes: %s', $errorMessage),
                [LegacyLogger::CONTEXT_TYPE => UserKeyGenerator::class]
            )
            ->once();

        $this->subject->generateRssToken($user);
    }
}
