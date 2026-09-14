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

/**
 * Real Jellyfin (ASP.NET Core) binds JSON request bodies case-insensitively by default, and at least one
 * real client relies on that — confirmed live: gelly POSTs a lowercase `secret` to
 * `AuthenticateWithQuickConnect`, where the spec's own schema names the property `Secret`. Every JSON-body
 * field read in this surface goes through this rather than a literal `$body['Key']`, so it matches real
 * server behavior instead of just the documented casing.
 */
final class JellyfinRequestBody
{
    /** @param array<array-key, mixed> $body */
    public static function field(array $body, string $key): mixed
    {
        foreach ($body as $bodyKey => $value) {
            if (is_string($bodyKey) && strcasecmp($bodyKey, $key) === 0) {
                return $value;
            }
        }

        return null;
    }
}
