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

namespace Ampache\Gui\Mood;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The order toggle above a mood cloud.
 */
final class MoodOrderView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly string $browseType,
        private readonly string $currentSort,
    ) {}

    /**
     * The ordering not currently applied, which is the one worth offering as a link.
     *
     * @return array{sort: string, label: string, symbol: string}
     */
    public function getAlternative(): array
    {
        return ($this->currentSort === 'count')
            ? ['sort' => 'name', 'label' => T_('Name'), 'symbol' => 'sort_by_alpha']
            : ['sort' => 'count', 'label' => T_('# Items'), 'symbol' => 'sort'];
    }

    public function getAlternativeUrl(): string
    {
        $alternative = $this->getAlternative();
        $url         = sprintf('%s/browse.php?action=mood&type=%s', $this->webPath, rawurlencode($this->browseType));

        return ($alternative['sort'] === 'name') ? $url : $url . '&sort=' . $alternative['sort'];
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('mood_order.phtml');
    }
}
