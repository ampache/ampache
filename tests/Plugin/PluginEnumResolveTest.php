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
 * Finding a plugin from a name that was stored rather than typed.
 *
 * A preference files itself under the plugin's own `$name`, which is what an operator reads and carries
 * spaces and dots. Twelve of the thirty-nine differ from their key, so a lookup by key alone answers
 * nothing for them, and the preferences screen then shows their settings with no help and no error.
 */
class PluginEnumResolveTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: class-string}>
     */
    public static function displayNames(): array
    {
        $names = [];
        foreach (PluginEnum::LIST as $class) {
            $declared = new ReflectionClass($class)->getDefaultProperties()['name'] ?? null;
            if (is_string($declared) && $declared !== '') {
                $names[] = [$declared, $class];
            }
        }

        return $names;
    }

    /**
     * @return list<array{0: string, 1: class-string}>
     */
    public static function keys(): array
    {
        $keys = [];
        foreach (PluginEnum::LIST as $key => $class) {
            $keys[] = [(string) $key, $class];
        }

        return $keys;
    }

    public function testANameNoPluginCarriesResolvesToNothing(): void
    {
        $this->assertNull(PluginEnum::resolve('not a plugin'));
        $this->assertNull(PluginEnum::resolve(''));
    }

    /**
     * @param class-string $class
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('displayNames')]
    public function testEveryPluginIsFoundByTheNameItsPreferencesAreFiledUnder(string $name, string $class): void
    {
        $this->assertSame($class, PluginEnum::resolve($name));
    }

    /**
     * @param class-string $class
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('keys')]
    public function testEveryPluginIsStillFoundByItsKey(string $key, string $class): void
    {
        $this->assertSame($class, PluginEnum::resolve($key));
    }

    public function testTheLookupIgnoresCase(): void
    {
        $this->assertSame(AmpacheCatalogFavorites::class, PluginEnum::resolve('CATALOG FAVORITES'));
        $this->assertSame(AmpacheCatalogFavorites::class, PluginEnum::resolve('CatalogFavorites'));
    }
}
