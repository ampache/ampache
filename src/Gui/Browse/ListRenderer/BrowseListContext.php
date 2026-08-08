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

use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Playlist;

/**
 * Everything a browse list needs that is decided per render.
 *
 * This carries data only. A renderer gets its services through its own constructor, which is the whole
 * point of the interface: `Browse` used to lend fifteen of them to templates through local scope.
 */
final readonly class BrowseListContext
{
    /**
     * @param array<mixed> $objectIds
     * @param array<mixed> $hideColumns
     * @param array<string, Collection|Folder|Playlist|Search> $supplementalObjects
     */
    public function __construct(
        public Browse $browse,
        public array $objectIds,
        public array $hideColumns,
        public string $argumentParam,
        public string $limitThreshold,
        public bool $prefetched,
        public bool $groupRelease,
        public bool $reorder = false,
        public array $supplementalObjects = [],
    ) {}
}
