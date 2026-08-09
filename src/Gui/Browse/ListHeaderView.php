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

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Browse;
use Override;

/**
 * The paging, alpha and view-options bar a browse renders above and below its table.
 *
 * Which of the two it is used to be implicit: the template flipped `$is_header` itself, so the bottom
 * render was defined by having already run once, and the album page pre-set the flag to steer it. It is
 * a constructor argument now, which is also what the DOM ids are suffixed with -- the header's controls
 * end `_1` and the footer's end `_`, and the page javascript addresses them by those names.
 */
final class ListHeaderView extends AbstractView
{
    private const array GRID_VIEW_TYPES = [
        'song',
        'album',
        'album_disk',
        'artist',
        'live_stream',
        'playlist',
        'smartplaylist',
        'video',
        'podcast',
        'podcast_episode',
    ];

    public function __construct(
        private readonly Browse $browse,
        private readonly bool $isHeader,
        private readonly string $argumentParam = '',
        private readonly bool $hideView = false,
        private readonly bool $groupRelease = false,
    ) {}

    /**
     * @return list<array{filter: string, label: string, key: int}>
     */
    public function getAlphabet(): array
    {
        $letters   = str_split((string) AmpConfig::get('alpha_string_pattern', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $letters[] = '#';
        $letters[] = '*';

        $entries = [];
        foreach ($letters as $key => $value) {
            $filter = match ($value) {
                '#' => '^[[:digit:]|[:punct:]]',
                '*' => '^.*',
                default => '^' . $value,
            };

            $entries[] = [
                'filter' => $filter,
                // the active letter is bolded in place, so the label carries its own markup
                'label' => ($this->browse->get_filter('regex_match') === $filter) ? '<b>' . scrub_out($value) . '</b>' : scrub_out($value),
                'key' => $key,
            ];
        }

        return $entries;
    }

    public function getArgumentParam(): string
    {
        return $this->argumentParam;
    }

    public function getBrowse(): Browse
    {
        return $this->browse;
    }

    public function getBrowseId(): int
    {
        return (int) $this->browse->id;
    }

    public function getCurrentPage(): int
    {
        $limit = $this->getLimit();

        return ($this->getStart() > 0 && $limit > 0) ? (int) floor($this->getStart() / $limit) : 0;
    }

    /**
     * The suffix every control id carries so the top and bottom bars do not collide.
     */
    public function getIdSuffix(): string
    {
        return ($this->isHeader) ? '1' : '';
    }

    public function getLimit(): int
    {
        return $this->browse->get_offset();
    }

    public function getNextOffset(): int
    {
        $next = $this->getStart() + $this->getLimit();

        return ($next >= $this->getTotal()) ? $this->getStart() : $next;
    }

    public function getPages(): int
    {
        $limit = $this->getLimit();

        return ($limit > 0 && $this->getTotal() > $limit) ? (int) ceil($this->getTotal() / $limit) : 0;
    }

    public function getPrevOffset(): int
    {
        return max(0, $this->getStart() - $this->getLimit());
    }

    public function getStart(): int
    {
        return $this->browse->get_start();
    }

    public function getTotal(): int
    {
        return $this->browse->get_total();
    }

    /**
     * A per-render counter so two bars on one page get distinct ajax observer names.
     */
    public function getUid(): int
    {
        if (array_key_exists('browse_uid', $_REQUEST)) {
            return (int) $_REQUEST['browse_uid']++;
        }

        $uid = (int) AmpConfig::get('list_header_uid');
        AmpConfig::set('list_header_uid', ++$uid, true);

        return $uid;
    }

    public function isGroupRelease(): bool
    {
        return $this->groupRelease;
    }

    public function isHeader(): bool
    {
        return $this->isHeader;
    }

    public function showAlpha(): bool
    {
        return !$this->browse->is_static_content() && $this->browse->is_use_filters();
    }

    /**
     * A type with no grid layout has the option hidden, and a non-mashup browse is forced back to rows.
     */
    public function showGridView(): bool
    {
        if (in_array($this->browse->get_type(), self::GRID_VIEW_TYPES, true)) {
            return true;
        }

        if (!$this->browse->is_mashup()) {
            $this->browse->set_grid_view(false);
        }

        return false;
    }

    public function showPaging(): bool
    {
        return $this->getPages() > 1 && $this->getStart() > -1 && $this->browse->is_use_pages();
    }

    public function showSelect(): bool
    {
        return in_array($this->browse->get_type(), Browse::MULTISELECT_TYPES, true);
    }

    public function showView(): bool
    {
        return !$this->hideView;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/list_header.phtml');
    }
}
