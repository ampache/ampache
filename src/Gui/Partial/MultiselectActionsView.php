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

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The action bar for a multi-select browse.

 * Rendered inside a `[data-multiselect-scope]` element beside the table whose rows carry the checkboxes;
 * `src/js/multiselect.js` fills the placeholders in each url.
 */
final class MultiselectActionsView extends AbstractView
{
    /**
     * @param list<array{action: string, url: string, icon: string, text: string, confirm?: string}> $actions
     */
    public function __construct(
        private readonly array $actions,
    ) {}

    /**
     * @return list<array{action: string, url: string, icon: string, text: string, confirm?: string}>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('partial/multiselect_actions.phtml');
    }
}
