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

use Ampache\Module\Authentication\Oidc\OidcAuthenticationServiceInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Override;

class OidcAuthenticatorTest extends MockeryTestCase
{
    private MockInterface|OidcAuthenticationServiceInterface|null $oidcAuthenticationService;
    private ?OidcAuthenticator $subject;

    public function testAuthFailsAndDoesNotClaimTheLogin(): void
    {
        $this->oidcAuthenticationService->shouldNotReceive('handleCallback');
        $this->oidcAuthenticationService->shouldNotReceive('redirectToProvider');

        $result = $this->subject->auth('some-username', 'some-password');

        self::assertFalse($result['success']);
        self::assertArrayNotHasKey('ui_required', $result);
    }

    public function testPostAuthReturnsTheCallbackResult(): void
    {
        $result = [
            'success' => true,
            'type' => 'oidc',
            'username' => 'some-username',
        ];

        $this->oidcAuthenticationService->shouldReceive('handleCallback')
            ->withNoArgs()
            ->once()
            ->andReturn($result);

        self::assertSame(
            $result,
            $this->subject->postAuth()
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->oidcAuthenticationService = Mockery::mock(OidcAuthenticationServiceInterface::class);

        $this->subject = new OidcAuthenticator(
            $this->oidcAuthenticationService
        );
    }
}
