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
 * Derives a stable, UUID-shaped ServerId from this install's `secret_key` config value — deterministic
 * across requests and restarts without needing a dedicated install-identity column.
 *
 * @todo revisit per plan §F: a config-derived UUID vs. one tied to the DB install identity is still open.
 */
final class JellyfinServerId
{
    /**
     * The Jellyfin protocol version this surface emulates — clients gate features (and refuse to connect
     * at all, e.g. Symfonium's "media provider is too old") on this, not on Ampache's own version. Must
     * match `info.version` in the vendored `docs/jellyfin-openapi-stable.json` (currently 12.0.0).
     */
    public const string PROTOCOL_VERSION = '12.0.0';

    public static function derive(string $secretKey): string
    {
        $hex = substr(md5($secretKey), 0, 32);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
