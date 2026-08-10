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

namespace Ampache\Gui\Browse;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Browse;
use Override;

/**
 * The filter box in the sidebar, showing only the filters the current browse supports.
 */
final class BrowseFiltersView extends AbstractView
{
    /**
     * Only these are rendered here; a browse offering none of them gets no filter box at all.
     */
    private const array SUPPORTED = [
        'starts_with',
        'minimum_count',
        'rated',
        'unplayed',
        'playlist_type',
        'catalog',
        'show_art',
    ];

    /**
     * @param array<array-key, string> $availableFilters
     * @param array<int, string> $catalogs
     */
    public function __construct(
        private readonly Browse $browse,
        private readonly array $availableFilters,
        private readonly array $catalogs,
        private readonly int $selectedCatalogId,
        private readonly string $argumentParam,
    ) {}

    public function getArgumentParam(): string
    {
        return $this->argumentParam;
    }

    public function getBrowse(): Browse
    {
        return $this->browse;
    }

    /**
     * @return array<int, string>
     */
    public function getCatalogs(): array
    {
        return $this->catalogs;
    }

    /**
     * The name filter can match from the start or anywhere, when the browse offers both.
     *
     * @return array<string, string>
     */
    public function getMatchModes(): array
    {
        $labels = ['starts_with' => T_('Starts With'), 'like' => T_('Contains')];

        return array_intersect_key($labels, array_flip($this->availableFilters));
    }

    public function getMatchValue(): string
    {
        return (string) $this->browse->get_filter($this->getSelectedMatchMode());
    }

    public function getSelectedCatalogId(): int
    {
        return $this->selectedCatalogId;
    }

    public function getSelectedMatchMode(): string
    {
        $mode = $this->browse->get_match_mode();

        return array_key_exists($mode, $this->getMatchModes()) ? $mode : 'starts_with';
    }

    public function hasFilter(string $filter): bool
    {
        return in_array($filter, $this->availableFilters, true);
    }

    /**
     * A browse that supports none of the rendered filters shows nothing rather than an empty box.
     */
    public function isEmpty(): bool
    {
        return array_intersect($this->availableFilters, self::SUPPORTED) === [];
    }

    public function isFilterActive(string $filter): bool
    {
        return (bool) $this->browse->get_filter($filter);
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse_filters.phtml');
    }
}
