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

namespace Ampache\Module\Catalog;

/**
 * Counts the rows of a table and maintains the cached total in `update_info`
 *
 * This is a seam over `Catalog::count_table()`, not a reimplementation — the statement, the
 * `update_info` write and its in-process read cache all stay in `Catalog`, so a caller here cannot
 * leave the cache disagreeing with the row it just wrote.
 */
interface CatalogCounterInterface
{
    /**
     * Counts the whole table and refreshes its cached total
     */
    public function count(CountableTableEnum $table): int;

    /**
     * Counts one catalog's share of the table, without touching the cached total
     *
     * Used by the scanner for progress reporting, where the number is per-catalog and the cached
     * whole-table total must not be overwritten with it.
     */
    public function countForCatalog(
        CountableTableEnum $table,
        int $catalogId,
        int $updateTime = 0,
        int $limit = 0,
    ): int;
}
