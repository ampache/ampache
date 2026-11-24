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

namespace Ampache\Gui\Art;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArtSelectionViewTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function objectTypeProvider(): iterable
    {
        yield 'album' => ['album'];
        yield 'folder' => ['folder'];
    }

    // a candidate that cannot be measured shows the placeholder, which must be a file the server has
    #[DataProvider('objectTypeProvider')]
    public function testThePlaceholderIsAnImageThatExists(string $objectType): void
    {
        $subject = new ArtSelectionView('', 1, $objectType, '', [0 => []]);

        self::assertFileExists(__DIR__ . '/../../../public' . $subject->getRows()[0][0]['url']);
    }
}
