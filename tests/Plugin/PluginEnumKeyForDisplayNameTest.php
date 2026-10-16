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

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Finding the key a plugin's stored display name belongs to.
 *
 * A preference files itself under the plugin's own `$name`, which is what an operator reads and carries
 * spaces and dots. Twelve of the thirty-nine differ from their key, so the preferences screen's lookup by
 * key alone answered nothing for them, showing their settings with no help and no error.
 */
class PluginEnumKeyForDisplayNameTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function displayNames(): array
    {
        $names = [];
        foreach (PluginEnum::LIST as $key => $class) {
            $declared = new ReflectionClass($class)->getDefaultProperties()['name'] ?? null;
            if (is_string($declared) && $declared !== '') {
                $names[] = [$declared, (string) $key];
            }
        }

        return $names;
    }

    public function testANameNoPluginCarriesResolvesToNothing(): void
    {
        $this->assertNull(PluginEnum::keyForDisplayName('not a plugin'));
        $this->assertNull(PluginEnum::keyForDisplayName(''));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('displayNames')]
    public function testEveryPluginIsFoundByTheNameItsPreferencesAreFiledUnder(string $name, string $key): void
    {
        $this->assertSame($key, PluginEnum::keyForDisplayName($name));
    }

    public function testTheLookupIgnoresCase(): void
    {
        $this->assertSame('catalogfavorites', PluginEnum::keyForDisplayName('CATALOG FAVORITES'));
        $this->assertSame('catalogfavorites', PluginEnum::keyForDisplayName('catalog favorites'));
    }
}
