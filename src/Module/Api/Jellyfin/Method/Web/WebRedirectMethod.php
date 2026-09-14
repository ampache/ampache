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

namespace Ampache\Module\Api\Jellyfin\Method\Web;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /web[/*] — real Jellyfin serves its bundled web client here. A client with no native QuickConnect UI
 * (confirmed live: one opened `/web/#/quickconnect` in a browser after a failed code) opens this expecting
 * a page to approve the device from. Ampache doesn't bundle the real Jellyfin webapp, so this redirects to
 * Ampache's own equivalent page instead of 404ing. The `#/quickconnect` fragment never reaches the server
 * (the browser strips it before the request is sent), so every `/web` path is handled identically here —
 * there is no way to tell `#/quickconnect` apart from any other client-side route server-side.
 */
final class WebRedirectMethod implements JellyfinMethodInterface
{
    public function __construct(private readonly ConfigContainerInterface $configContainer) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        return JellyfinResponse::redirect($this->configContainer->getWebPath('/client') . '/preferences.php?tab=quickconnect');
    }
}
