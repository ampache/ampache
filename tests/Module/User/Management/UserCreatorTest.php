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

namespace Ampache\Module\User\Management;

use Ampache\MockeryTestCase;
use Ampache\Module\User\Management\Exception\UserCreationFailedException;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;

class UserCreatorTest extends MockeryTestCase
{
    /** @var UserRepositoryInterface|MockInterface|null */
    private MockInterface $userRepository;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private UserCreator $subject;

    public function setUp(): void
    {
        $this->userRepository = $this->mock(UserRepositoryInterface::class);
        $this->modelFactory   = $this->mock(ModelFactoryInterface::class);

        $this->subject = new UserCreator(
            $this->userRepository,
            $this->modelFactory
        );
    }

    public function testCreateCreatesWithUnencryptedPassword(): void
    {
        $username = 'some-username';
        $fullname = 'some-full-name';
        $email    = 'some-email';
        $website  = 'some-website';
        $password = 'some-password';
        $access   = 666;
        $state    = 'some-state';
        $city     = 'some-city';
        $disabled = true;
        $userId   = 42;

        $user = $this->mock(User::class);

        $this->userRepository->shouldReceive('create')
            ->with(
                $username,
                $fullname,
                $email,
                $website,
                hash('sha256', $password),
                $access,
                $state,
                $city,
                $disabled
            )
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $user->shouldReceive('fixPreferences')
            ->withNoArgs()
            ->once();

        $this->assertSame(
            $user,
            $this->subject->create(
                $username,
                $fullname,
                $email,
                $website,
                $password,
                $access,
                $state,
                $city,
                $disabled
            )
        );
    }

    public function testCreateThrowsExceptionIfCreationFails(): void
    {
        $this->expectException(UserCreationFailedException::class);

        $username = 'some-username';
        $fullname = 'some-full-name';
        $email    = 'some-email';
        $website  = 'some-website';
        $password = 'some-password';
        $access   = 666;
        $state    = 'some-state';
        $city     = 'some-city';
        $disabled = false;

        $this->userRepository->shouldReceive('create')
            ->with(
                $username,
                $fullname,
                $email,
                $website,
                $password,
                $access,
                $state,
                $city,
                $disabled
            )
            ->once()
            ->andReturnNull();

        $this->subject->create(
            $username,
            $fullname,
            $email,
            $website,
            $password,
            $access,
            $state,
            $city,
            $disabled,
            true
        );
    }
}
