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
 * Real Jellyfin (ASP.NET Core) binds both JSON request bodies and query strings case-insensitively by
 * default, and real clients rely on that in both directions — confirmed live: gelly POSTs a lowercase
 * `secret` to `AuthenticateWithQuickConnect` where the spec names the property `Secret`, and Finamp's real
 * `GET /QuickConnect/Connect` request sends `Secret` where the vendored spec text (also real, just
 * generated with different casing) names it `secret`. Every JSON-body or query-string field read in this
 * surface that came from a client goes through this rather than a literal `$body['Key']` /
 * `$query['key']`, so it matches real server behavior instead of whichever casing a given doc happened to
 * use. Despite the name, `$body` here is just "the associative array to search" — a decoded JSON body or
 * `$request->getQueryParams()` are both fine.
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
