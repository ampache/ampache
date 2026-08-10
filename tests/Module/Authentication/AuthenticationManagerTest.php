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

namespace Ampache\Module\Authentication;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authentication\Authenticator\AuthenticatorInterface;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\System\Crypto\SymmetricEncrypterInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Override;

class AuthenticationManagerTest extends MockeryTestCase
{
    private const string TOKEN_SQL = 'SELECT `apikey`, `subsonic_secret`, `username` FROM `user` WHERE `username` = ?';

    private const string USERNAME = 'some-username';

    private MockInterface|AuthenticatorInterface|null $authenticator;
    private string $authenticatorName = 'some-authenticator';
    private MockInterface|ConfigContainerInterface|null $configContainer;
    private DatabaseConnectionInterface|MockInterface|null $databaseConnection;
    private ?AuthenticationManager $subject;
    private MockInterface|SymmetricEncrypterInterface|null $symmetricEncrypter;

    public function testLoginFailsIfAuthenticatorNotAvailable(): void
    {
        $this->configContainer->shouldReceive('getArray')
            ->with('auth_methods')
            ->once()
            ->andReturn(['roedlbroem']);

        self::assertSame(
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

        $this->configContainer->shouldReceive('getArray')
            ->with('auth_methods')
            ->once()
            ->andReturn([$this->authenticatorName]);

        self::assertSame(
            ['success' => false],
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

        $this->configContainer->shouldReceive('getArray')
            ->with('auth_methods')
            ->once()
            ->andReturn([$this->authenticatorName]);

        self::assertSame(
            $result,
            $this->subject->login($username, $password)
        );
    }

    public function testLoginSucceedsIfUiNotRequired(): void
    {
        $username = 'some-username';
        $password = 'some-password';
        $result   = [
            'success' => false,
            'ui_required' => true
        ];

        $this->authenticator->shouldReceive('auth')
            ->with($username, $password)
            ->once()
            ->andReturn($result);

        $this->configContainer->shouldReceive('getArray')
            ->with('auth_methods')
            ->once()
            ->andReturn([$this->authenticatorName]);

        self::assertSame(
            $result,
            $this->subject->login($username, $password)
        );
    }

    public function testPostAuthDoesNothingIfAuthenticatorWasNotFound(): void
    {
        $method = 'roedlbroem';

        $this->configContainer->shouldReceive('getArray')
            ->with('auth_methods')
            ->once()
            ->andReturn([$method]);

        self::assertSame(
            [],
            $this->subject->postAuth($method)
        );
    }

    public function testPostAuthDoesNothingIfMethodNotAllowed(): void
    {
        $this->configContainer->shouldReceive('getArray')
            ->with('auth_methods')
            ->once()
            ->andReturn([$this->authenticatorName]);

        self::assertSame(
            [],
            $this->subject->postAuth('roedlbroem')
        );
    }

    public function testPostAuthReturnsResult(): void
    {
        $result = ['some' => 'result'];

        $this->configContainer->shouldReceive('getArray')
            ->with('auth_methods')
            ->once()
            ->andReturn([$this->authenticatorName]);

        $this->authenticator->shouldReceive('postAuth')
            ->withNoArgs()
            ->once()
            ->andReturn($result);

        self::assertSame(
            $result,
            $this->subject->postAuth($this->authenticatorName)
        );
    }

    public function testTokenLoginFailsForAnUnknownUser(): void
    {
        $this->expectTokenRow(false);

        self::assertSame(
            [],
            $this->subject->tokenLogin(self::USERNAME, 'some-token', 'some-salt')
        );
    }

    public function testTokenLoginFailsWhenNoCredentialIsStored(): void
    {
        $this->expectTokenRow([
            'apikey' => null,
            'subsonic_secret' => null,
            'username' => self::USERNAME,
        ]);

        $this->symmetricEncrypter->shouldNotReceive('decrypt');

        self::assertSame(
            [],
            $this->subject->tokenLogin(self::USERNAME, hash('md5', 'some-salt'), 'some-salt')
        );
    }

    public function testTokenLoginFailsWhenTheSubsonicSecretCannotBeDecrypted(): void
    {
        $this->expectTokenRow([
            'apikey' => null,
            'subsonic_secret' => 'some-payload',
            'username' => self::USERNAME,
        ]);

        // rotating secret_key leaves payloads that no longer decrypt. The token here is the one an attacker would send
        // if a failed decrypt were coerced to an empty string, so this fails the moment the null guard is dropped.
        $this->symmetricEncrypter->shouldReceive('decrypt')
            ->with('some-payload')
            ->once()
            ->andReturn(null);

        self::assertSame(
            [],
            $this->subject->tokenLogin(self::USERNAME, hash('md5', 'some-salt'), 'some-salt')
        );
    }

    public function testTokenLoginFailsWithIncompleteParameters(): void
    {
        $this->databaseConnection->shouldNotReceive('fetchRow');

        self::assertSame([], $this->subject->tokenLogin(self::USERNAME, '', 'some-salt'));
        self::assertSame([], $this->subject->tokenLogin(self::USERNAME, 'some-token', ''));
        self::assertSame([], $this->subject->tokenLogin('', 'some-token', 'some-salt'));
    }

    public function testTokenLoginFailsWithTheWrongSubsonicSecret(): void
    {
        $this->expectTokenRow([
            'apikey' => null,
            'subsonic_secret' => 'some-payload',
            'username' => self::USERNAME,
        ]);

        $this->symmetricEncrypter->shouldReceive('decrypt')
            ->with('some-payload')
            ->once()
            ->andReturn('the-real-password');

        self::assertSame(
            [],
            $this->subject->tokenLogin(
                self::USERNAME,
                hash('md5', 'not-the-real-password' . 'some-salt'),
                'some-salt'
            )
        );
    }

    public function testTokenLoginStillAcceptsTheApiKeyWhenASecretIsAlsoSet(): void
    {
        $salt   = 'some-salt';
        $apiKey = 'some-api-key';

        $this->expectTokenRow([
            'apikey' => $apiKey,
            'subsonic_secret' => 'some-payload',
            'username' => self::USERNAME,
        ]);

        $this->symmetricEncrypter->shouldReceive('decrypt')
            ->with('some-payload')
            ->once()
            ->andReturn('some-subsonic-password');

        self::assertSame(
            ['success' => true, 'type' => 'api', 'username' => self::USERNAME],
            $this->subject->tokenLogin(self::USERNAME, hash('md5', $apiKey . $salt), $salt)
        );
    }

    public function testTokenLoginSucceedsWithTheLegacyApiKey(): void
    {
        $salt   = 'some-salt';
        $apiKey = 'some-api-key';

        $this->expectTokenRow([
            'apikey' => $apiKey,
            'subsonic_secret' => null,
            'username' => self::USERNAME,
        ]);

        $this->symmetricEncrypter->shouldNotReceive('decrypt');

        self::assertSame(
            ['success' => true, 'type' => 'api', 'username' => self::USERNAME],
            $this->subject->tokenLogin(self::USERNAME, hash('md5', $apiKey . $salt), $salt)
        );
    }

    public function testTokenLoginSucceedsWithTheSubsonicSecret(): void
    {
        $salt   = 'some-salt';
        $secret = 'some-subsonic-password';

        $this->expectTokenRow([
            'apikey' => null,
            'subsonic_secret' => 'some-payload',
            'username' => self::USERNAME,
        ]);

        $this->symmetricEncrypter->shouldReceive('decrypt')
            ->with('some-payload')
            ->once()
            ->andReturn($secret);

        self::assertSame(
            ['success' => true, 'type' => 'api', 'username' => self::USERNAME],
            $this->subject->tokenLogin(self::USERNAME, hash('md5', $secret . $salt), $salt)
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer    = Mockery::mock(ConfigContainerInterface::class);
        $this->authenticator      = Mockery::mock(AuthenticatorInterface::class);
        $this->symmetricEncrypter = Mockery::mock(SymmetricEncrypterInterface::class);
        $this->databaseConnection = Mockery::mock(DatabaseConnectionInterface::class);

        $this->subject = new AuthenticationManager(
            $this->configContainer,
            [$this->authenticatorName => $this->authenticator],
            $this->symmetricEncrypter,
            $this->databaseConnection
        );
    }

    /**
     * @param array<string, mixed>|false $row
     */
    private function expectTokenRow(array|bool $row): void
    {
        $this->databaseConnection->shouldReceive('fetchRow')
            ->with(self::TOKEN_SQL, [self::USERNAME])
            ->once()
            ->andReturn($row);
    }
}
