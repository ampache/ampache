<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Authentication;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authentication\Authenticator\AuthenticatorInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class AuthenticationManagerTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface|null */
    private MockInterface $configContainer;

    /** @var MockInterface|AuthenticatorInterface|null */
    private MockInterface $authenticator;

    private string $authenticatorName = 'some-authenticator';

    /** @var AuthenticationManager|null */
    private ?AuthenticationManager $subject;

    public function setUp(): void
    {
        $this->configContainer = Mockery::mock(ConfigContainerInterface::class);
        $this->authenticator   = Mockery::mock(AuthenticatorInterface::class);

        $this->subject = new AuthenticationManager(
            $this->configContainer,
            [$this->authenticatorName => $this->authenticator]
        );
    }

    public function testLoginFailsIfAuthenticatorNotAvailable(): void
    {
        $this->configContainer->shouldReceive('get')
            ->with('auth_methods')
            ->once()
            ->andReturn(['roedlbroem']);

        static::assertSame(
            [],
            $this->subject->login('foo', 'bar')
        );
    }

    public function testLoginFailsIfNotSuccesful(): void
    {
        $username = 'some-username';
        $password = 'some-password';

        $this->authenticator->shouldReceive('auth')
            ->with($username, $password)
            ->once()
            ->andReturn(['success' => false]);

        $this->configContainer->shouldReceive('get')
            ->with('auth_methods')
            ->once()
            ->andReturn([$this->authenticatorName]);

        static::assertSame(
            ['success' => false],
            $this->subject->login($username, $password)
        );
    }

    public function testLoginSucceedsIfUiNotRequired(): void
    {
        $username = 'some-username';
        $password = 'some-password';
        $result   = ['success' => false, 'ui_required' => true];

        $this->authenticator->shouldReceive('auth')
            ->with($username, $password)
            ->once()
            ->andReturn($result);

        $this->configContainer->shouldReceive('get')
            ->with('auth_methods')
            ->once()
            ->andReturn([$this->authenticatorName]);

        static::assertSame(
            $result,
            $this->subject->login($username, $password)
        );
    }

    public function testLoginSucceeds(): void
    {
        $username = 'some-username';
        $password = 'some-password';
        $result   = ['success' => true];

        $this->authenticator->shouldReceive('auth')
            ->with($username, $password)
            ->once()
            ->andReturn($result);

        $this->configContainer->shouldReceive('get')
            ->with('auth_methods')
            ->once()
            ->andReturn([$this->authenticatorName]);

        static::assertSame(
            $result,
            $this->subject->login($username, $password)
        );
    }

    public function testPostAuthDoesNothingIfMethodNotAllowed(): void
    {
        $this->configContainer->shouldReceive('get')
            ->with('auth_methods')
            ->once()
            ->andReturn([$this->authenticatorName]);

        static::assertSame(
            [],
            $this->subject->postAuth('roedlbroem')
        );
    }

    public function testPostAuthDoesNothingIfAuthenticatorWasNotFound(): void
    {
        $method = 'roedlbroem';

        $this->configContainer->shouldReceive('get')
            ->with('auth_methods')
            ->once()
            ->andReturn([$method]);

        static::assertSame(
            [],
            $this->subject->postAuth($method)
        );
    }

    public function testPostAuthReturnsResult(): void
    {
        $result = ['some' => 'result'];

        $this->configContainer->shouldReceive('get')
            ->with('auth_methods')
            ->once()
            ->andReturn([$this->authenticatorName]);

        $this->authenticator->shouldReceive('postAuth')
            ->withNoArgs()
            ->once()
            ->andReturn($result);

        static::assertSame(
            $result,
            $this->subject->postAuth($this->authenticatorName)
        );
    }
}
