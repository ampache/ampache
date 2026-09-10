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

namespace Ampache\Gui\Partial;

use PHPUnit\Framework\TestCase;

class HeaderChipTest extends TestCase
{
    public function testDecodesEntitiesInText(): void
    {
        self::assertSame(
            'Mixgalaxy – Records & Co',
            new HeaderChip('Mixgalaxy &ndash; Records &amp; Co')->text
        );
    }

    public function testGenresLinksEachTagAndSkipsNamelessOnes(): void
    {
        $chips = HeaderChip::genres(
            [
                ['id' => 3, 'name' => 'Electro'],
                ['id' => 4, 'name' => ''],
                ['id' => '5', 'name' => 'Rock'],
            ],
            '/browse.php?action=tag&type=artist&show_tag='
        );

        self::assertCount(2, $chips);
        self::assertSame('/browse.php?action=tag&type=artist&show_tag=3', $chips[0]->url);
        self::assertSame('Rock', $chips[1]->text);
        self::assertTrue($chips[0]->genre);
    }

    public function testListOfFlattensSkipsEmptyAndWrapsScalars(): void
    {
        $kept = new HeaderChip('kept');

        $chips = HeaderChip::listOf(
            '',
            null,
            ' 2007 ',
            [$kept],
            new HeaderChip(''),
        );

        self::assertSame(['2007', 'kept'], array_map(static fn(HeaderChip $chip): string => $chip->text, $chips));
    }
}
