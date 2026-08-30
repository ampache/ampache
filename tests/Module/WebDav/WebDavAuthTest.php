<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\WebDav;

use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Override;
use ReflectionMethod;

class WebDavAuthTest extends MockeryTestCase
{
    private AuthenticationManagerInterface&MockInterface $authenticationManager;
    private WebDavAuth $subject;
    private UserRepositoryInterface&MockInterface $userRepository;

    public function testValidateUserPassBindsTheEnabledUser(): void
    {
        unset($GLOBALS['user']);

        $user           = Mockery::mock(User::class);
        $user->disabled = false;
        $user->id       = 42;

        $this->authenticationManager->shouldReceive('login')
            ->once()
            ->andReturn(['success' => true]);

        $this->userRepository->shouldReceive('findByUsername')
            ->with('some-user')
            ->once()
            ->andReturn($user);

        self::assertTrue($this->validate('some-user', 'some-pass'));
        self::assertSame($user, $GLOBALS['user'] ?? null);
    }

    public function testValidateUserPassReturnsFalseForDisabledUser(): void
    {
        $user           = Mockery::mock(User::class);
        $user->disabled = true;

        $this->authenticationManager->shouldReceive('login')
            ->once()
            ->andReturn(['success' => true]);

        $this->userRepository->shouldReceive('findByUsername')
            ->with('some-user')
            ->once()
            ->andReturn($user);

        self::assertFalse($this->validate('some-user', 'some-pass'));
    }

    public function testValidateUserPassReturnsFalseForUnknownUser(): void
    {
        $this->authenticationManager->shouldReceive('login')
            ->once()
            ->andReturn(['success' => true]);

        $this->userRepository->shouldReceive('findByUsername')
            ->with('some-user')
            ->once()
            ->andReturnNull();

        self::assertFalse($this->validate('some-user', 'some-pass'));
    }

    public function testValidateUserPassReturnsFalseWhenLoginFails(): void
    {
        $this->authenticationManager->shouldReceive('login')
            ->with('some-user', 'some-pass', true)
            ->once()
            ->andReturn(['success' => false]);

        $this->userRepository->shouldNotReceive('findByUsername');

        self::assertFalse($this->validate('some-user', 'some-pass'));
    }

    #[Override]
    protected function setUp(): void
    {
        unset($GLOBALS['user']);

        $this->authenticationManager = Mockery::mock(AuthenticationManagerInterface::class);
        $this->userRepository        = Mockery::mock(UserRepositoryInterface::class);

        $this->subject = new WebDavAuth(
            $this->authenticationManager,
            $this->userRepository
        );
    }

    #[Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['user']);
    }

    private function validate(string $username, string $password): bool
    {
        return (bool) (new ReflectionMethod($this->subject, 'validateUserPass'))
            ->invoke($this->subject, $username, $password);
    }
}
