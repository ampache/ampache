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

namespace Ampache\Module\Api\Authentication;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class GatekeeperTest extends MockeryTestCase
{
    private MockInterface&LoggerInterface $logger;
    private MockInterface&ServerRequestInterface $request;
    private Gatekeeper $subject;
    private MockInterface&UserRepositoryInterface $userRepository;

    /**
     * Regression: a non-matching key must never fall back to a bare `user` lookup, or any string plus
     * `user=admin` would authenticate as that account (unauthenticated Subsonic/native API takeover).
     */
    public function testGetUserDoesNotFallBackToTheUsernameParameter(): void
    {
        $this->expectAuthTokenFromQuery('apiKey', 'wrong-key', ['user' => 'admin']);

        $this->userRepository->shouldReceive('findByApiKey')
            ->with('wrong-key')
            ->once()
            ->andReturnNull();
        $this->userRepository->shouldNotReceive('findByUsername');

        $this->assertNull($this->subject->getUser('apiKey'));
    }

    public function testGetUserResolvesTheUserFromTheApiKey(): void
    {
        $user = $this->mock(User::class);

        $this->expectAuthTokenFromQuery('apiKey', 'valid-key');

        $this->userRepository->shouldReceive('findByApiKey')
            ->with('valid-key')
            ->once()
            ->andReturn($user);
        $this->userRepository->shouldNotReceive('findByUsername');

        $this->assertSame($user, $this->subject->getUser('apiKey'));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->userRepository = $this->mock(UserRepositoryInterface::class);
        $this->request        = $this->mock(ServerRequestInterface::class);
        $this->logger         = $this->mock(LoggerInterface::class);

        $this->logger->shouldReceive('notice')->andReturnNull();

        $this->subject = new Gatekeeper(
            $this->userRepository,
            $this->request,
            $this->logger
        );
    }

    /**
     * Configure the request so that getAuth() resolves the given token from the legacy query parameter,
     * with no Authorization/auth header present.
     *
     * @param array<string, string> $extraQuery
     */
    private function expectAuthTokenFromQuery(string $requestKey, string $token, array $extraQuery = []): void
    {
        $this->request->shouldReceive('getHeaderLine')
            ->with('Authorization')
            ->andReturn('');
        $this->request->shouldReceive('getHeaderLine')
            ->with('auth')
            ->andReturn('');
        $this->request->shouldReceive('getMethod')
            ->andReturn('GET');
        $this->request->shouldReceive('getQueryParams')
            ->andReturn(array_merge([$requestKey => $token], $extraQuery));
    }
}
