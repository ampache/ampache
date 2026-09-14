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

use Ampache\Module\Api\Jellyfin\JellyfinAuthorizationHeader;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Api\Jellyfin\QuickConnect\JellyfinQuickConnectService;
use Ampache\Module\Api\Jellyfin\QuickConnect\QuickConnectResultMapper;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /QuickConnect/Initiate — no prior auth; the device identifies itself via the same `Authorization`
 * header every other request carries. A rate-limited device gets 503 too, the spec's only other code here.
 */
final class QuickConnectInitiateMethod implements JellyfinMethodInterface
{
    public function __construct(
        private readonly QuickConnectResultMapper $mapper,
        private readonly JellyfinQuickConnectService $service,
    ) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if (!$this->service->isEnabled()) {
            return JellyfinResponse::serviceUnavailable('QuickConnect is not enabled');
        }

        $header = JellyfinAuthorizationHeader::parse(
            $request->getHeaderLine('Authorization'),
            $request->getHeaderLine('X-Emby-Authorization'),
        );

        $row = $this->service->initiate(
            (string) $header->deviceId,
            (string) $header->device,
            (string) $header->client,
            (string) $header->version,
        );
        if ($row === null) {
            return JellyfinResponse::serviceUnavailable('Too many requests');
        }

        return JellyfinResponse::json($this->mapper->map($row));
    }
}
