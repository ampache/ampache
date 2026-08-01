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

namespace Ampache\Repository\Model;

use PHPUnit\Framework\TestCase;

class PreferenceTest extends TestCase
{
    /**
     * Every row is bound to a seven column insert, so a short row means the preference is never written
     */
    public function testEveryDefaultRowCarriesEveryColumn(): void
    {
        foreach (Preference::DEFAULTS as $name => $row) {
            static::assertCount(6, $row, sprintf('%s does not carry all six columns', $name));
            static::assertIsString($row[0], sprintf('%s has a non-string value', $name));
            static::assertIsString($row[1], sprintf('%s has a non-string description', $name));
            static::assertIsInt($row[2], sprintf('%s has a non-integer level', $name));
        }
    }

    /**
     * A name in one list and not the other is the failure `set_defaults()` used to report at runtime as
     * "missing preference insert code", by which point the preference simply does not exist.
     */
    public function testEverySystemPreferenceHasADefaultRow(): void
    {
        static::assertSame(
            [],
            array_values(array_diff(Preference::SYSTEM_LIST, array_keys(Preference::DEFAULTS))),
            'SYSTEM_LIST entries with no row in DEFAULTS'
        );

        static::assertSame(
            [],
            array_values(array_diff(array_keys(Preference::DEFAULTS), Preference::SYSTEM_LIST)),
            'DEFAULTS rows that SYSTEM_LIST never asks for'
        );
    }
}
