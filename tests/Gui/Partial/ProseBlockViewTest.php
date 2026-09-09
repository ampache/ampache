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

class ProseBlockViewTest extends TestCase
{
    public function testFoldsOnVisibleTextOnly(): void
    {
        $short = str_repeat('a', 600);

        static::assertFalse(new ProseBlockView($short, 'fold')->isFolded());
        static::assertFalse(new ProseBlockView('<b>' . $short . '</b>', 'fold')->isFolded());
        static::assertTrue(new ProseBlockView($short . 'a', 'fold')->isFolded());
    }

    public function testFromTextDecodesThenEscapesAndKeepsLineBreaks(): void
    {
        $view = ProseBlockView::fromText("a &ndash; b\n<script>", 'fold');

        static::assertSame("a – b<br />\n&lt;script&gt;", $view->getHtml());
    }
}
