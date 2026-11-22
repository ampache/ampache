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

namespace Ampache\Module\Util;

/**
 * Decides whether a url may be fetched by the server
 */
interface UrlValidatorInterface
{
    /**
     * Whether the url is http(s) and resolves only to public addresses, or also private ones with $allowLan
     *
     * Loopback, link-local and the cloud metadata addresses are refused either way.
     */
    public function isPublicHttpUrl(string $url, bool $allowLan = false): bool;

    /**
     * The host, port and one address a fetch may pin its connection to, or null when the url may not be fetched
     *
     * @return array{host: string, port: int, address: string}|null
     */
    public function resolvePinnedTarget(string $url, bool $allowLan = false): ?array;
}
