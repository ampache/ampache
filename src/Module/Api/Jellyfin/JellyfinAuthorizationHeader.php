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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Parses the Jellyfin client identification header: `MediaBrowser Client="…", Device="…", DeviceId="…",
 * Version="…", Token="…"` (older clients send the same shape under an `Emby` scheme word).
 */
final class JellyfinAuthorizationHeader
{
    private function __construct(
        public readonly ?string $client,
        public readonly ?string $device,
        public readonly ?string $deviceId,
        public readonly ?string $version,
        public readonly ?string $token,
    ) {}

    /**
     * The single place that resolves the caller's bearer token — from the compound header, its bare
     * fallback, or (since a media element can't set headers) a query-string token, matching real Jellyfin.
     * `JellyfinRequestAuthenticator` and any handler that itself needs the raw token (e.g. `LogoutMethod`,
     * which has to destroy the specific session that called it) both go through this, not their own parse.
     */
    public static function extractToken(ServerRequestInterface $request): string
    {
        $header = self::parse(
            $request->getHeaderLine('Authorization'),
            $request->getHeaderLine('X-Emby-Authorization'),
            $request->getHeaderLine('X-MediaBrowser-Token'),
        );

        $query = $request->getQueryParams();

        return $header->token ?? (string) ($query['api_key'] ?? $query['ApiKey'] ?? $query['Token'] ?? '');
    }

    /**
     * @param string|null $authorization the `Authorization` header value
     * @param string|null $legacyAuthorization the `X-Emby-Authorization` fallback header value
     * @param string|null $bareToken the `X-MediaBrowser-Token` fallback header value (token only, no compound fields)
     */
    public static function parse(
        ?string $authorization,
        ?string $legacyAuthorization = null,
        ?string $bareToken = null,
    ): self {
        if ($authorization !== null && $authorization !== '') {
            return self::parseCompound($authorization);
        }

        if ($legacyAuthorization !== null && $legacyAuthorization !== '') {
            return self::parseCompound($legacyAuthorization);
        }

        return new self(null, null, null, null, ($bareToken !== null && $bareToken !== '') ? $bareToken : null);
    }

    private static function parseCompound(string $headerValue): self
    {
        $fields = [];
        preg_match_all('/(\w+)="([^"]*)"/', $headerValue, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $fields[strtolower($match[1])] = $match[2];
        }

        return new self(
            $fields['client'] ?? null,
            $fields['device'] ?? null,
            $fields['deviceid'] ?? null,
            $fields['version'] ?? null,
            $fields['token'] ?? null,
        );
    }
}
