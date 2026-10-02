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

namespace Ampache\Gui\Preferences;

use PHPUnit\Framework\TestCase;

class PreferenceTypeTest extends TestCase
{
    public function testEveryTypeDeclaredInTheDefaultsCatalogueIsMapped(): void
    {
        foreach (\Ampache\Module\System\Preference::DEFAULTS as $name => $row) {
            $this->assertSame(
                $row[3] === 'bool' ? 'boolean' : $row[3],
                PreferenceType::fromDatabase($row[3])->value,
                sprintf('preference `%s` declares type `%s`', $name, $row[3])
            );
        }
    }

    public function testFromDatabaseFallsBackToStringForAnUnknownType(): void
    {
        $this->assertSame(PreferenceType::STRING, PreferenceType::fromDatabase('wat'));
        $this->assertSame(PreferenceType::STRING, PreferenceType::fromDatabase(''));
    }

    public function testFromDatabaseMapsTheDeclaredTypes(): void
    {
        $this->assertSame(PreferenceType::BOOLEAN, PreferenceType::fromDatabase('boolean'));
        $this->assertSame(PreferenceType::INTEGER, PreferenceType::fromDatabase('integer'));
        $this->assertSame(PreferenceType::STRING, PreferenceType::fromDatabase('string'));
        $this->assertSame(PreferenceType::SPECIAL, PreferenceType::fromDatabase('special'));
        $this->assertSame(PreferenceType::TRANSCODING, PreferenceType::fromDatabase('transcoding'));
    }

    public function testFromDatabaseTreatsTheBoolTypoAsABoolean(): void
    {
        $this->assertSame(PreferenceType::BOOLEAN, PreferenceType::fromDatabase('bool'));
    }
}
