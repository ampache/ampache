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
 */

namespace Ampache\Module\Database\Query;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class SmartlistTest extends TestCase
{
    public function testInheritsTheSearchTableThatTheSharedWritesInterpolate(): void
    {
        self::assertSame(
            'search',
            new ReflectionClass(Smartlist::class)->getConstant('DB_TABLENAME')
        );
    }

    public function testIsASearchSoEveryExistingTypeCheckStillPasses(): void
    {
        self::assertTrue(is_subclass_of(Smartlist::class, Search::class));
    }

    public function testTakesNoObjectTypeSoTheCallerCannotPickOne(): void
    {
        $parameters = new ReflectionMethod(Smartlist::class, '__construct')->getParameters();

        self::assertSame(
            ['search_id', 'user'],
            array_map(static fn($parameter): string => $parameter->getName(), $parameters)
        );
    }
}
