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

namespace Ampache\Module\System;

use Ampache\Config\AmpConfig;

/**
 * Named database lock (MySQL GET_LOCK) serializing check-then-insert sections
 * across concurrent requests, e.g. parallel uploads creating the same artist.
 * Best effort: a failed acquisition does not block the caller.
 */
final class DbLock
{
    private const TIMEOUT = 10;

    public static function acquire(string $key): bool
    {
        $statement = Dba::read('SELECT GET_LOCK(?, ?);', [self::name($key), self::TIMEOUT]);

        return ($statement !== null && $statement->fetchColumn() == 1);
    }

    public static function release(string $key): void
    {
        Dba::read('SELECT RELEASE_LOCK(?);', [self::name($key)]);
    }

    private static function name(string $key): string
    {
        // GET_LOCK names are limited to 64 characters and shared server-wide
        return 'ampache_' . md5(AmpConfig::get('database_name', '') . '|' . $key);
    }
}
