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

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Browse;
use LogicException;
use Override;

/**
 * Shared plumbing for a browse list renderer.
 *
 * Renderers are shared services, so the previous context is put back after each render: a template that
 * renders a second browse of the same type would otherwise leave this one pointing at the wrong data.
 */
abstract class AbstractBrowseListRenderer extends AbstractView implements BrowseListRendererInterface
{
    private ?BrowseListContext $context = null;

    final public function getBrowse(): Browse
    {
        return $this->getContext()->browse;
    }

    /**
     * The grid layout swaps a handful of cell classes, so the two spellings are resolved in one place.
     */
    final public function getCellClass(string $tableClass, string $gridClass): string
    {
        return $this->getBrowse()->is_grid_view() ? $gridClass : $tableClass;
    }

    /**
     * @return list<int>
     */
    final public function getObjectIds(): array
    {
        return array_map(intval(...), array_values($this->getContext()->objectIds));
    }

    final public function getTableClass(): string
    {
        return $this->getBrowse()->is_grid_view() ? ' gridview' : '';
    }

    #[Override]
    final public function renderList(BrowseListContext $context): string
    {
        $previous      = $this->context;
        $this->context = $context;

        try {
            return $this->render();
        } finally {
            $this->context = $previous;
        }
    }

    final protected function getContext(): BrowseListContext
    {
        if ($this->context === null) {
            throw new LogicException(static::class . ' was rendered outside renderList()');
        }

        return $this->context;
    }
}
