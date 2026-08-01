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
 * Counts the rows of a table and maintains the cached totals in `update_info`
 *
 * It owns the statement, the `update_info` write and the in-process read cache together, so a caller
 * cannot leave the cache disagreeing with the row it just wrote.
 */
interface CatalogCounterInterface
{
    /**
     * Applies a known change to a table's totals without reading anything
     */
    public function adjust(CountableTableEnum $table, int $items, int $time = 0, float $size = 0.0): void;

    /**
     * Counts the whole table and refreshes its cached total
     */
    public function count(CountableTableEnum $table): int;

    /**
     * Counts the items, playing time and megabytes one catalog holds of its own media type
     *
     * @return array{items: int, time: int, size: int}
     */
    public function countCatalog(int $catalogId, ?string $gatherTypes): array;

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

    /**
     * Counts the genres that are not hidden
     */
    public function countTags(): int;

    /**
     * Counts the videos of the whole server, or of one catalog
     */
    public function countVideos(int $catalogId = 0): int;

    /**
     * Reads one cached total, from `user_data` for a real user and from `update_info` otherwise
     */
    public function getStoredCount(string $key, int $userId): int;

    /**
     * Reads every cached total, from `user_data` for a real user and from `update_info` otherwise
     *
     * @return array<string, int>
     */
    public function getStoredCounts(int $userId): array;

    /**
     * Recounts every media table and stores its own contribution, then the `items`, `time` and `size` totals
     */
    public function refreshMediaTotals(bool $skipDisabledCatalogs): void;

    /**
     * Recounts every media table and every list table, and stores the totals the server reports
     */
    public function refreshServerCounts(bool $skipDisabledCatalogs): void;

    /**
     * Stores one cached total, keeping the in-process read cache in step with it
     */
    public function setStoredCount(string $key, float|int $value): void;
}
