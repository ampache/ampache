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

namespace Ampache\Module\Podcast\Feed;

/**
 * Reads the `itunes:duration` of an episode: `HH:MM:SS`, `MM:SS` or a count of seconds.
 */
final class FeedDuration
{
    public static function toSeconds(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        if (preg_match('/^(\d+):([0-5]?\d):([0-5]?\d)$/', $value, $parts) === 1) {
            return ((int) $parts[1] * 3600) + ((int) $parts[2] * 60) + (int) $parts[3];
        }

        if (preg_match('/^(\d+):([0-5]?\d)$/', $value, $parts) === 1) {
            return ((int) $parts[1] * 60) + (int) $parts[2];
        }

        // anything else is worth nothing rather than a wrong number
        return (preg_match('/^\d+$/', $value) === 1)
            ? (int) $value
            : 0;
    }
}
