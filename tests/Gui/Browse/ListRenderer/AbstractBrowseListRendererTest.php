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

namespace Ampache\Gui\Browse\ListRenderer;

use Ampache\MockeryTestCase;
use Ampache\Module\Database\Query\Browse;
use Override;

/**
 * A renderer whose only per-render value is the id list it was handed.
 */
final class RowsListRenderer extends AbstractBrowseListRenderer
{
    public ?BrowseListContext $nested = null;

    /**
     * Prints its rows, renders the nested context once, then prints its rows again.
     */
    public function body(): string
    {
        $output = implode(',', $this->getRows());

        $nested = $this->nested;
        if ($nested !== null) {
            $this->nested = null;
            $output .= ' [' . $this->renderList($nested) . '] ' . implode(',', $this->getRows());
        }

        return $output;
    }

    /**
     * @return list<int>
     */
    public function getRows(): array
    {
        /** @var list<int> */
        return $this->cachePerRender('rows', $this->getObjectIds(...));
    }

    #[Override]
    protected function templateFile(): string
    {
        return __DIR__ . '/fixtures/rows.phtml';
    }
}

class AbstractBrowseListRendererTest extends MockeryTestCase
{
    /**
     * Two disk sections on one album page render the same shared renderer twice, so the second must not
     * inherit the first one's rows.
     */
    public function testRenderCacheIsRebuiltForEachRender(): void
    {
        $subject = new RowsListRenderer();

        $this->assertSame('1,3,2', $subject->renderList($this->createContext([1, 3, 2])));
        $this->assertSame('4,5', $subject->renderList($this->createContext([4, 5])));
    }

    /**
     * A template rendering a second browse of the same type must leave the outer render's rows intact.
     */
    public function testRenderCacheIsRestoredAfterANestedRender(): void
    {
        $subject         = new RowsListRenderer();
        $subject->nested = $this->createContext([4, 5]);

        $this->assertSame('1,3,2 [4,5] 1,3,2', $subject->renderList($this->createContext([1, 3, 2])));
    }

    /**
     * @param list<int> $objectIds
     */
    private function createContext(array $objectIds): BrowseListContext
    {
        return new BrowseListContext(
            $this->mock(Browse::class),
            $objectIds,
            [],
            '',
            '',
            false,
            false
        );
    }
}
