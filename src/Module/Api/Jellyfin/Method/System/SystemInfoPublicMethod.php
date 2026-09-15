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

namespace Ampache\Module\Api\Jellyfin\Method\System;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\JellyfinServerId;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/** GET /System/Info/Public — unauthenticated capability probe clients use before login. */
final class SystemInfoPublicMethod implements JellyfinMethodInterface
{
    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        return JellyfinResponse::json([
            'LocalAddress' => (string) AmpConfig::get('web_path', ''),
            'ServerName' => (string) AmpConfig::get('site_title', 'Ampache'),
            'Version' => JellyfinServerId::PROTOCOL_VERSION,
            'ProductName' => 'Ampache',
            'OperatingSystem' => PHP_OS,
            'Id' => JellyfinServerId::derive((string) AmpConfig::get('secret_key', '')),
            'StartupWizardCompleted' => true,
        ]);
    }
}
