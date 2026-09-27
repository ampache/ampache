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

namespace Ampache\Module\Api\Jellyfin;

use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class JellyfinRequestAuthenticatorTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;

    public function testADisabledUserIsRefused(): void
    {
        $user           = $this->createMock(User::class);
        $user->disabled = true;

        $this->userRepository->method('findByApiSessionToken')->willReturn($user);

        self::assertNull($this->subject()->authenticate($this->requestWithToken('some-token')));
    }

    public function testAnEmptyTokenNeverReachesTheRepository(): void
    {
        $this->userRepository->expects(static::never())->method('findByApiSessionToken');

        self::assertNull($this->subject()->authenticate($this->requestWithToken('')));
    }

    public function testTheTokenIsResolvedThroughTheApiTypedSessionLookup(): void
    {
        $user           = $this->createMock(User::class);
        $user->disabled = false;

        // the username path accepted a stream session, so it must not come back
        $this->userRepository->expects(static::never())->method('findByUsername');
        $this->userRepository->expects(static::once())
            ->method('findByApiSessionToken')
            ->with('some-token')
            ->willReturn($user);

        self::assertSame($user, $this->subject()->authenticate($this->requestWithToken('some-token')));
    }

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
    }

    private function requestWithToken(string $token): ServerRequestInterface&MockObject
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->willReturn('');
        $request->method('getQueryParams')->willReturn(['ApiKey' => $token]);

        return $request;
    }

    private function subject(): JellyfinRequestAuthenticator
    {
        return new JellyfinRequestAuthenticator($this->userRepository);
    }
}
