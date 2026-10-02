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
use Override;

class HttpAuthenticatorTest extends MockeryTestCase
{
    private const string USERNAME = 'some-username';

    private ?HttpAuthenticator $subject = null;

    public function testAuthFailsWithNoServerIdentity(): void
    {
        unset($_SERVER['REMOTE_USER'], $_SERVER['HTTP_REMOTE_USER']);

        self::assertSame(
            ['success' => false, 'error' => 'HTTP auth login attempt failed'],
            $this->subject->auth(self::USERNAME, 'irrelevant')
        );
    }

    /**
     * GHSA-v58p-jrqf-qxj2: HTTP_REMOTE_USER is PHP's mapping of a client-supplied `Remote-User:` request
     * header and must never be accepted as proof of identity, or any unauthenticated request can log in as
     * an arbitrary existing user simply by naming it in both the username and this header.
     */
    public function testAuthFailsWithSpoofedHttpRemoteUserHeader(): void
    {
        unset($_SERVER['REMOTE_USER']);
        $_SERVER['HTTP_REMOTE_USER'] = self::USERNAME;

        self::assertSame(
            ['success' => false, 'error' => 'HTTP auth login attempt failed'],
            $this->subject->auth(self::USERNAME, 'irrelevant')
        );
    }

    /**
     * REMOTE_USER is only ever set by the web server or an authenticating module (mod_auth*, SSO), never
     * derived from a client request header, so it is the only value this authenticator may trust.
     */
    public function testAuthSucceedsWithRemoteUser(): void
    {
        $_SERVER['REMOTE_USER'] = self::USERNAME;

        self::assertSame(
            [
                'success' => true,
                'type' => 'http',
                'username' => self::USERNAME,
                'name' => self::USERNAME,
                'email' => '',
            ],
            $this->subject->auth(self::USERNAME, 'irrelevant')
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->subject = new HttpAuthenticator();
    }

    #[Override]
    protected function tearDown(): void
    {
        unset($_SERVER['REMOTE_USER'], $_SERVER['HTTP_REMOTE_USER']);

        parent::tearDown();
    }
}
