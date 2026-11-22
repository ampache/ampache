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

namespace Ampache\Gui\Stats;

use Ampache\Module\Util\Graph;
use PHPUnit\Framework\TestCase;

class GraphsViewTest extends TestCase
{
    /** A date the browser's picker can't read shows an empty field, and posting it graphs from 1970 */
    public function testTheDateFieldsUseTheBrowserPickerFormat(): void
    {
        $start   = (int) mktime(10, 30, 0, 11, 16, 2026);
        $end     = (int) mktime(8, 5, 0, 12, 1, 2026);
        $subject = new GraphsView($this->createMock(Graph::class), '', [], 0, '', 0, $start, $end, 'day', '', '', false);

        self::assertSame('2026-11-16T10:30', $subject->getFormattedStartDate());
        self::assertSame('2026-12-01T08:05', $subject->getFormattedEndDate());
        self::assertSame($start, strtotime($subject->getFormattedStartDate()));
        self::assertSame($end, strtotime($subject->getFormattedEndDate()));
    }
}
