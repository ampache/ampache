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

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Override;

/**
 * A user's listening year in review.
 *
 * Each section is the same shape -- a titled box wrapping a browse -- so they are described rather than
 * written out five times. The flags are per section because the Ratings box really does render
 * differently from the rest, and that stays visible here instead of hiding in a copied block.
 */
final class WrappedView extends AbstractView
{
    /**
     * @param list<array{title: string, type: string, objectIds: array<int>, grid: bool, mashup: bool, store: bool}> $sections
     */
    public function __construct(
        private readonly BrowseFactoryInterface $browseFactory,
        private readonly string $year,
        private readonly int $songCount,
        private readonly string $minutesPlayed,
        private readonly array $sections,
    ) {}

    public function getMinutesPlayed(): string
    {
        return $this->minutesPlayed;
    }

    /**
     * Sections with nothing in them are dropped rather than rendering an empty box.
     *
     * @return list<array{title: string, type: string, objectIds: array<int>, grid: bool, mashup: bool, store: bool}>
     */
    public function getSections(): array
    {
        return array_values(
            array_filter($this->sections, static fn(array $section): bool => $section['objectIds'] !== [])
        );
    }

    public function getSongCount(): int
    {
        return $this->songCount;
    }

    public function getTitle(): string
    {
        return T_('Ampache Wrapped') . ' (' . $this->year . ')';
    }

    /**
     * @param array{title: string, type: string, objectIds: array<int>, grid: bool, mashup: bool, store: bool} $section
     */
    public function renderSection(array $section): string
    {
        // only the section that stores its browse needs a tmp_browse row
        $browse = $this->browseFactory->create(null, $section['store']);
        $browse->set_type($section['type']);
        $browse->set_use_filters(false);
        $browse->set_show_header(false);
        if ($section['grid']) {
            $browse->set_grid_view(true, false);
        }

        if ($section['mashup']) {
            $browse->set_mashup(true);
        }

        ob_start();
        $browse->show_objects($section['objectIds']);
        if ($section['store']) {
            $browse->store();
        }

        return (string) ob_get_clean();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('wrapped.phtml');
    }
}
