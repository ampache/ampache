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

namespace Ampache\Plugin;

use Ampache\Repository\Model\LibraryItemEnum;

interface PluginStreamControlInterface extends AmpachePluginInterface
{
    /**
     * Check stream control
     * @param array<int, array{object_type: string|LibraryItemEnum, object_id: int}> $media_ids
     * @param string|null $ip the client's source IP; only meaningful (and only meant to be enforced) when $isGuest is true, since a real account is already identified by its own id
     * @param bool $isGuest whether the request is attributed to a surrogate account (eg. a public share) rather than its own logged-in user
     */
    public function stream_control(array $media_ids, ?string $ip = null, bool $isGuest = false): bool;

    /**
     * Whether this plugin's current configuration reads the shared per-IP guest activity history,
     * so `User::stream_control()` can skip writing to it when nothing would ever read the write back.
     */
    public function tracksIp(): bool;
}
