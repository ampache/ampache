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

namespace Ampache\Module\Application\Stats;

use Ampache\Module\Util\Graph;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Override;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class GraphActionTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $savedRequest = [];

    public function testAZoomChangeResetsTheStartToTheNewWindow(): void
    {
        $_REQUEST = ['zoom' => 'month', 'rendered_zoom' => 'day', 'start_date' => '2026-11-01T10:30', 'end_date' => '2026-11-16T08:05'];

        $end = (int) mktime(8, 5, 0, 11, 16, 2026);
        self::assertSame([$end - 31536000, $end], $this->getDateRange('month'));
    }

    /** Clearing a date field with the picker's clear button graphed from 1970 */
    public function testClearedDatesFallBackToTheDefaultWindow(): void
    {
        $_REQUEST = ['zoom' => 'day', 'rendered_zoom' => 'day', 'start_date' => '', 'end_date' => ''];

        [$start, $end] = $this->getDateRange('day');

        self::assertEqualsWithDelta(time(), $end, 5);
        self::assertSame($end - 864000, $start);
    }

    public function testPostedDatesAreKept(): void
    {
        $_REQUEST = ['zoom' => 'day', 'rendered_zoom' => 'day', 'start_date' => '2026-11-01T10:30', 'end_date' => '2026-11-16T08:05'];

        self::assertSame([(int) mktime(10, 30, 0, 11, 1, 2026), (int) mktime(8, 5, 0, 11, 16, 2026)], $this->getDateRange('day'));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->savedRequest = $_REQUEST;
    }

    #[Override]
    protected function tearDown(): void
    {
        $_REQUEST = $this->savedRequest;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function getDateRange(string $zoom): array
    {
        $subject = new GraphAction(
            $this->createMock(UiInterface::class),
            $this->createMock(LibraryItemLoaderInterface::class),
            $this->createMock(Graph::class),
        );

        /** @var array{0: int, 1: int} */
        return new ReflectionMethod(AbstractGraphRendererAction::class, 'getDateRange')->invoke($subject, $zoom);
    }
}
