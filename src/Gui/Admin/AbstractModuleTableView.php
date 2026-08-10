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

namespace Ampache\Gui\Admin;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The install/enable table the three module admin pages share.
 *
 * The columns and the rows are declared together so the empty-state cell can span the real column count
 * rather than a hand-written number.
 */
abstract class AbstractModuleTableView extends AbstractView
{
    /** @var list<list<string>>|null */
    private ?array $rows = null;

    public function __construct(
        private readonly string $adminPath,
    ) {}

    /**
     * @return list<array{class: string, label: string}>
     */
    abstract public function getColumns(): array;

    /**
     * Cells are html, because the action column is a link (sometimes two).
     *
     * @return list<list<string>>
     */
    final public function getRows(): array
    {
        return $this->rows ??= $this->buildRows();
    }

    /**
     * A row per module that loaded; anything that failed to load is skipped rather than half-rendered.
     *
     * @return list<list<string>>
     */
    abstract protected function buildRows(): array;

    final protected function getAdminPath(): string
    {
        return $this->adminPath;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('module_table.phtml');
    }
}
