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

namespace Ampache\Module\Api\Jellyfin\Method\Auth;

use Ampache\Module\Api\Jellyfin\JellyfinRequestBody;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\JellyfinSessionMinter;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /Users/AuthenticateByName — mints a fresh API session token via `Session::create()`, rather than
 * reusing the user's permanent apikey as the bearer credential (that's Gatekeeper's separate flow).
 */
final class AuthenticateByNameMethod implements JellyfinMethodInterface
{
    public function __construct(
        private readonly AuthenticationManagerInterface $authenticationManager,
        private readonly JellyfinSessionMinter $sessionMinter,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        $body = json_decode((string) $request->getBody(), true);
        if (!is_array($body)) {
            $body = [];
        }

        $username = trim((string) (JellyfinRequestBody::field($body, 'Username') ?? ''));
        $password = (string) (JellyfinRequestBody::field($body, 'Pw') ?? '');
        if ($username === '') {
            return JellyfinResponse::json(['error' => 'Username is required'], 400);
        }

        $result = $this->authenticationManager->login($username, $password);
        if (empty($result['success'])) {
            return JellyfinResponse::unauthorized();
        }

        $authenticatedUser = $this->userRepository->findByUsername($username);
        if ($authenticatedUser === null || $authenticatedUser->disabled) {
            return JellyfinResponse::unauthorized();
        }

        $session = $this->sessionMinter->mint($authenticatedUser);
        if ($session === null) {
            return JellyfinResponse::json(['error' => 'Could not create a session'], 500);
        }

        return JellyfinResponse::json($session);
    }
}
