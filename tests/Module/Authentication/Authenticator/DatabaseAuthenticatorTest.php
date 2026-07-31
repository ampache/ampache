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

namespace Ampache\Module\Authentication\Authenticator;

use Ampache\MockeryTestCase;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\System\Crypto\SymmetricEncrypterInterface;
use Mockery\MockInterface;
use Override;

class DatabaseAuthenticatorTest extends MockeryTestCase
{
    private const string PASSWORD_SQL = 'SELECT `password` FROM `user` WHERE `username` = ?';

    private const string SECRET_SQL = 'SELECT `apikey`, `subsonic_secret` FROM `user` WHERE `username` = ?';

    private const string USERNAME = 'some-username';

    private DatabaseConnectionInterface|MockInterface|null $databaseConnection = null;
    private ?DatabaseAuthenticator $subject                                    = null;
    private MockInterface|SymmetricEncrypterInterface|null $symmetricEncrypter = null;

    public function testAuthFailsForAnUnknownUser(): void
    {
        $this->expectPasswordRow(false);
        $this->expectSecretRow(false);

        self::assertSame(
            ['success' => false, 'error' => 'MySQL login attempt failed'],
            $this->subject->auth(self::USERNAME, 'some-password')
        );
    }

    public function testAuthFailsWhenNoCredentialIsStored(): void
    {
        $this->expectPasswordRow(false);
        $this->expectSecretRow(['apikey' => null, 'subsonic_secret' => null]);

        // an empty apikey column must never be matched by an empty-ish password
        self::assertSame(
            ['success' => false, 'error' => 'MySQL login attempt failed'],
            $this->subject->auth(self::USERNAME, '0')
        );
    }

    public function testAuthFailsWhenTheSubsonicSecretCannotBeDecrypted(): void
    {
        $this->expectPasswordRow(false);
        $this->expectSecretRow(['apikey' => null, 'subsonic_secret' => 'some-payload']);

        // a rotated secret_key leaves an undecryptable payload behind; it must not authenticate anything
        $this->symmetricEncrypter->shouldReceive('decrypt')
            ->with('some-payload')
            ->once()
            ->andReturn(null);

        self::assertSame(
            ['success' => false, 'error' => 'MySQL login attempt failed'],
            $this->subject->auth(self::USERNAME, 'some-subsonic-password')
        );
    }

    public function testAuthFailsWithEmptyCredentials(): void
    {
        $this->databaseConnection->shouldNotReceive('fetchRow');

        self::assertSame(
            ['success' => false, 'error' => 'MySQL login attempt failed'],
            $this->subject->auth(self::USERNAME, '')
        );
        self::assertSame(
            ['success' => false, 'error' => 'MySQL login attempt failed'],
            $this->subject->auth('', 'some-password')
        );
    }

    public function testAuthFailsWithTheWrongSubsonicSecret(): void
    {
        $this->expectPasswordRow(false);
        $this->expectSecretRow(['apikey' => null, 'subsonic_secret' => 'some-payload']);

        $this->symmetricEncrypter->shouldReceive('decrypt')
            ->with('some-payload')
            ->once()
            ->andReturn('the-real-password');

        self::assertSame(
            ['success' => false, 'error' => 'MySQL login attempt failed'],
            $this->subject->auth(self::USERNAME, 'not-the-real-password')
        );
    }

    public function testAuthSucceedsWithTheLegacyApiKey(): void
    {
        $apiKey = 'some-api-key';

        $this->expectPasswordRow(false);
        $this->expectSecretRow(['apikey' => $apiKey, 'subsonic_secret' => null]);

        $this->symmetricEncrypter->shouldNotReceive('decrypt');

        self::assertSame(
            ['success' => true, 'type' => 'mysql', 'username' => self::USERNAME],
            $this->subject->auth(self::USERNAME, $apiKey)
        );
    }

    public function testAuthSucceedsWithTheLoginPassword(): void
    {
        $password = 'some-password';

        $this->expectPasswordRow(['password' => hash('sha256', $password)]);

        self::assertSame(
            ['success' => true, 'type' => 'mysql', 'username' => self::USERNAME],
            $this->subject->auth(self::USERNAME, $password)
        );
    }

    public function testAuthSucceedsWithTheSubsonicSecret(): void
    {
        $password = 'some-subsonic-password';

        $this->expectPasswordRow(false);
        $this->expectSecretRow(['apikey' => null, 'subsonic_secret' => 'some-payload']);

        $this->symmetricEncrypter->shouldReceive('decrypt')
            ->with('some-payload')
            ->once()
            ->andReturn($password);

        self::assertSame(
            ['success' => true, 'type' => 'mysql', 'username' => self::USERNAME],
            $this->subject->auth(self::USERNAME, $password)
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->databaseConnection = $this->mock(DatabaseConnectionInterface::class);
        $this->symmetricEncrypter = $this->mock(SymmetricEncrypterInterface::class);

        $this->subject = new DatabaseAuthenticator(
            $this->symmetricEncrypter,
            $this->databaseConnection
        );
    }

    /**
     * @param array<string, mixed>|false $row
     */
    private function expectPasswordRow(array|bool $row): void
    {
        $this->databaseConnection->shouldReceive('fetchRow')
            ->with(self::PASSWORD_SQL, [self::USERNAME])
            ->once()
            ->andReturn($row);
    }

    /**
     * @param array<string, mixed>|false $row
     */
    private function expectSecretRow(array|bool $row): void
    {
        $this->databaseConnection->shouldReceive('fetchRow')
            ->with(self::SECRET_SQL, [self::USERNAME])
            ->once()
            ->andReturn($row);
    }
}
