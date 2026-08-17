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

namespace Ampache\Repository\Model;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public static function validTypeDataProvider(): Generator
    {
        yield 'folder' => ['folder', true];
        yield 'genre in the spelling a collection stores' => ['genre', true];
        // `tag` is the table name; anything arriving from a template has to be denormalized before it is checked
        yield 'genre named after its table' => ['tag', false];
        // a smartlist is never stored as itself, it contributes the songs it resolves to, so it is not valid here
        yield 'search' => ['search', false];
        yield 'collection' => ['collection', false];
    }

    /**
     * A pinned collection takes only its own type, whatever the caller sends
     */
    public function testAcceptsTypeRefusesAnythingButThePinnedType(): void
    {
        $subject = new Collection();

        $subject->object_type = 'folder';

        self::assertTrue($subject->acceptsType('folder'));
        self::assertFalse($subject->acceptsType('song'));
    }

    public function testAcceptsTypeTakesAnythingValidWhenMixed(): void
    {
        $subject = new Collection();

        self::assertTrue($subject->acceptsType('folder'));
        self::assertTrue($subject->acceptsType('song'));
        self::assertFalse($subject->acceptsType('search'));
    }

    #[DataProvider('validTypeDataProvider')]
    public function testIsValidTypeAnswersForTheType(string $objectType, bool $expected): void
    {
        self::assertSame($expected, Collection::isValidType($objectType));
    }
}
