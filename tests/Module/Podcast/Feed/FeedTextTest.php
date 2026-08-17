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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FeedTextTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function cleanDataProvider(): array
    {
        return [
            ['', ''],
            ['just text', 'just text'],
            // every break is a single one, however many tags produced it
            ['<p>first</p><p>second</p>', "first\nsecond"],
            ['<p>first<p>second', "first\nsecond"],
            ['one<br />two<br>three<br/>four', "one\ntwo\nthree\nfour"],
            // the shape real feeds ship: a <br> pair with a newline between them
            ["a.<br>\n<br>\nb.<br>\n<br>\nc.", "a.\nb.\nc."],
            ['<ul><li>one</li><li>two</li></ul>', "one\ntwo"],
            // inline markup is dropped without leaving a break
            ['a <strong>bold</strong> claim', 'a bold claim'],
            ['<a href="https://ampache.org">the site</a>', 'the site'],
            // entities are decoded
            ['Bell &amp; Sebastian', 'Bell & Sebastian'],
            ['spaced&nbsp;out', 'spaced out'],
            ['&#8220;quoted&#8221;', '“quoted”'],
            // feeds that encoded their markup a second time
            ['&lt;p&gt;first&lt;/p&gt;&lt;p&gt;second&lt;/p&gt;', "first\nsecond"],
            // ... but a lone escaped bracket is text the feed meant to write
            ['5 &lt; 6', '5 < 6'],
            // whitespace is tidied up
            ["  padded  \n\n\n\n  text  ", "padded\ntext"],
            ['<p></p><p>only one</p>', 'only one'],
        ];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function cleanLineDataProvider(): array
    {
        return [
            ['<p>a title</p>', 'a title'],
            ["broken<br />over<br />lines", 'broken over lines'],
            ['Episode 1: &quot;The Start&quot;', 'Episode 1: "The Start"'],
        ];
    }

    #[DataProvider('cleanLineDataProvider')]
    public function testCleanLineKeepsTheValueOnOneLine(string $input, string $expected): void
    {
        self::assertSame($expected, FeedText::cleanLine($input));
    }

    #[DataProvider('cleanDataProvider')]
    public function testCleanReturnsPlainText(string $input, string $expected): void
    {
        self::assertSame($expected, FeedText::clean($input));
    }
}
