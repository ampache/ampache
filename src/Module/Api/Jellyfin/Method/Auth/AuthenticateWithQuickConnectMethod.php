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
use Ampache\Module\QuickConnect\QuickConnectService;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /Users/AuthenticateWithQuickConnect — consumes the secret atomically (a replay always fails, even
 * within the TTL) and mints a session through the same routine `AuthenticateByNameMethod` uses.
 */
final class AuthenticateWithQuickConnectMethod implements JellyfinMethodInterface
{
    public function __construct(
        private readonly JellyfinSessionMinter $sessionMinter,
        private readonly QuickConnectService $service,
    ) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if (!$this->service->isEnabled()) {
            return JellyfinResponse::serviceUnavailable('QuickConnect is not enabled');
        }

        $body = json_decode((string) $request->getBody(), true);
        if (!is_array($body)) {
            $body = [];
        }

        $secret = (string) (JellyfinRequestBody::field($body, 'Secret') ?? '');
        if ($secret === '') {
            return JellyfinResponse::json(['error' => 'Secret is required'], 400);
        }

        $result = $this->service->consume($secret);
        if (!$result['ok'] || $result['user'] === null) {
            return JellyfinResponse::json(['error' => 'Unknown, expired, or unauthorized secret'], 400);
        }

        $session = $this->sessionMinter->mint($result['user']);
        if ($session === null) {
            return JellyfinResponse::json(['error' => 'Could not create a session'], 500);
        }

        return JellyfinResponse::json($session);
    }
}
