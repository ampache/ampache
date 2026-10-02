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

use Ampache\Repository\Model\UpdateInfoEnum;
use Ampache\Repository\UpdateInfoRepositoryInterface;

/**
 * Holds this install's own UUID-shaped ServerId, generated once and kept in `update_info`.
 *
 * It used to be the md5 of `secret_key`, which an unauthenticated route then published: that digest tells
 * an attacker for free whether the install still runs the shipped default key, and confirms any guess at a
 * hand-set one without touching the server again.
 */
final readonly class JellyfinServerId
{
    /**
     * The Jellyfin protocol version this surface emulates — clients gate features (and refuse to connect
     * at all, e.g. Symfonium's "media provider is too old") on this, not on Ampache's own version. Must
     * match `info.version` in the vendored `docs/jellyfin-openapi-stable.json` (currently 12.0.0).
     */
    public const string PROTOCOL_VERSION = '12.0.0';

    public function __construct(private UpdateInfoRepositoryInterface $updateInfoRepository) {}

    private static function shape(string $hex): string
    {
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    public function get(): string
    {
        $stored = $this->updateInfoRepository->getValueByKey(UpdateInfoEnum::JELLYFIN_SERVER_ID);
        if ($stored !== null && $stored !== '') {
            return $stored;
        }

        $serverId = self::shape(bin2hex(random_bytes(16)));
        $this->updateInfoRepository->setValue(UpdateInfoEnum::JELLYFIN_SERVER_ID, $serverId);

        return $serverId;
    }
}
