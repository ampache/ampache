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

use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\QuickConnect\QuickConnectService;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /QuickConnect/Authorize — the already-signed-in approval step. `userId` (mint a session for someone
 * else) is 403 for anyone but an admin; an invalid/expired/locked-out code is a plain `false`, not a 404.
 */
final class QuickConnectAuthorizeMethod implements JellyfinMethodInterface
{
    public function __construct(private readonly QuickConnectService $service) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        // checked before the auth guard: a disabled feature answers 503 regardless of auth state, like the
        // other four QuickConnect endpoints, rather than leaking whether the caller's token would work
        if (!$this->service->isEnabled()) {
            return JellyfinResponse::serviceUnavailable('QuickConnect is not enabled');
        }
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $query = $request->getQueryParams();
        $code  = (string) ($query['code'] ?? '');
        if ($code === '') {
            return JellyfinResponse::json(['error' => 'code is required'], 400);
        }

        $overrideUserId    = null;
        $userIdParam       = (string) ($query['userId'] ?? '');
        if ($userIdParam !== '') {
            if (!JellyfinId::isType($userIdParam, 'user')) {
                return JellyfinResponse::forbidden();
            }
            $overrideUserId = JellyfinId::decodeId($userIdParam);
        }

        $result = $this->service->authorize($code, $user, $overrideUserId);
        if ($result['forbidden']) {
            return JellyfinResponse::forbidden();
        }

        return JellyfinResponse::json($result['success']);
    }
}
