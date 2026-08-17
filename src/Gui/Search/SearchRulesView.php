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

namespace Ampache\Gui\Search;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\System\Core;
use Override;

/**
 * The rule editor shared by the smartlist, search and random pages.
 */
final class SearchRulesView extends AbstractView
{
    public function __construct(
        private readonly ?Search $playlist,
        private readonly string $currentType,
        private readonly string $webPath,
    ) {}

    public function getCurrentType(): string
    {
        return $this->currentType;
    }

    /**
     * The request wins, then the list being edited, then `and`.
     *
     * `search.js` reads the operator back out of the request when it rebuilds the rows, so the resolved
     * value is put back there rather than only being rendered.
     */
    public function getLogicOperator(): string
    {
        $operator = Core::get_request('operator');
        if ($operator === '') {
            $operator             = ($this->playlist instanceof Search) ? (string) $this->playlist->logic_operator : 'and';
            $_REQUEST['operator'] = $operator;
        }

        return strtolower($operator);
    }

    /**
     * The saved rules as javascript, or an empty row when there are none to show.
     */
    public function getRuleScript(): string
    {
        if ($this->playlist instanceof Search) {
            $out = $this->playlist->to_js();
        } else {
            $search = new Search(0, $this->currentType);
            $search->set_rules($_REQUEST);
            $out = $search->to_js();
        }

        // @see search.js SearchRow.add(ruleType, operator, input, subtype)
        return ($out) ?: '<script>SearchRow.add();</script>';
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('search/rules.phtml');
    }
}
