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

namespace Ampache\Module\Api\Jellyfin\QuickConnect;

/**
 * Builds the `QuickConnectResult` shape from either `QuickConnectService::initiate()`'s return value or a
 * stored row — both carry the same field names, so one mapper covers `/Initiate` and `/Connect`.
 */
final class QuickConnectResultMapper
{
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function map(array $row): array
    {
        return [
            'Authenticated' => (bool) $row['authorized'],
            'Secret' => (string) $row['secret'],
            'Code' => (string) $row['code'],
            'DeviceId' => (string) ($row['device_id'] ?? ''),
            'DeviceName' => (string) ($row['device_name'] ?? ''),
            'AppName' => (string) ($row['app_name'] ?? ''),
            'AppVersion' => (string) ($row['app_version'] ?? ''),
            'DateAdded' => gmdate('Y-m-d\TH:i:s.000000\Z', (int) $row['date_added']),
        ];
    }
}
