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
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Api\Jellyfin\QuickConnect\QuickConnectResultMapper;
use Ampache\Module\QuickConnect\QuickConnectService;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /QuickConnect/Connect — no auth; the device polls with its own `Secret` until `Authenticated` turns
 * true. An unknown or expired secret is an identical 404, so this can't be used to probe which ones exist.
 */
final class QuickConnectConnectMethod implements JellyfinMethodInterface
{
    public function __construct(
        private readonly QuickConnectResultMapper $mapper,
        private readonly QuickConnectService $service,
    ) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if (!$this->service->isEnabled()) {
            return JellyfinResponse::serviceUnavailable('QuickConnect is not enabled');
        }

        // real Finamp traffic sends `Secret`; the vendored spec text names it lowercase `secret`
        $secret = (string) (JellyfinRequestBody::field($request->getQueryParams(), 'secret') ?? '');
        if ($secret === '') {
            return JellyfinResponse::notFound();
        }

        $row = $this->service->findBySecret($secret);
        if ($row === null) {
            return JellyfinResponse::notFound();
        }

        return JellyfinResponse::json($this->mapper->map($row));
    }
}
