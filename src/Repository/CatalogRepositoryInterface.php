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

namespace Ampache\Repository;

use Ampache\Module\Catalog\CatalogTypeEnum;
use Ampache\Repository\Model\CatalogFieldEnum;

/**
 * Provides access to the `catalog` table and to each backend's own `catalog_*` settings table
 */
interface CatalogRepositoryInterface
{
    /**
     * Creates a backend's own settings table, wrapping its columns in the id and catalog_id every one has
     *
     * @param array<string, string> $columns column => its SQL type, e.g. `VARCHAR(255)`
     */
    public function createSubTypeTable(CatalogTypeEnum $type, array $columns): void;

    /**
     * Removes every catalog of one backend, used when that backend is uninstalled
     */
    public function deleteByType(CatalogTypeEnum $type): void;

    /**
     * Removes the catalog row itself, leaving everything that pointed at it to the caller
     */
    public function deleteRow(int $catalogId): bool;

    /**
     * Removes a catalog's row from its backend's settings table
     */
    public function deleteSubTypeRow(CatalogTypeEnum $type, int $catalogId): bool;

    /**
     * Drops a backend's settings table, used when that backend is uninstalled
     *
     * A table that is already gone is the outcome asked for, so it is not an error.
     */
    public function dropSubTypeTable(CatalogTypeEnum $type): void;

    /**
     * The catalog whose configured path this file sits under, or null when no catalog claims it
     */
    public function findCatalogIdByPathPrefix(CatalogTypeEnum $type, string $filePath): ?int;

    /**
     * Whether a catalog is enabled, or null when there is no such catalog
     */
    public function findEnabled(int $catalogId): ?bool;

    /**
     * The display name of a catalog, or an empty string when there is no such catalog
     */
    public function findName(int $catalogId): string;

    /**
     * The id of a catalog's row in its backend's settings table, or null when it has none
     *
     * Also null when that backend has been uninstalled and its table no longer exists.
     */
    public function findSubTypeId(CatalogTypeEnum $type, int $catalogId): ?int;

    /**
     * The backend a catalog is served by, or null when there is no such catalog
     */
    public function findType(int $catalogId): ?string;

    /**
     * Reads catalog ids by name, narrowed by gather type, by the enabled flag and by a user's filter
     *
     * `$filterUserId` is `null` for no catalog filtering at all, `-1` for the DEFAULT filter group that
     * the system and guest users are held to, and a user id for that user's own group.
     *
     * @return list<int>
     */
    public function getIds(
        ?string $gatherType = null,
        bool $enabledOnly = false,
        ?int $filterUserId = null,
    ): array;

    /**
     * Reads the names of the given catalogs, keyed by id and ordered by name
     *
     * @param array<int> $catalogIds
     * @return array<int, string>
     */
    public function getNamesByIds(array $catalogIds): array;

    /**
     * The configured path of every catalog of one backend, keyed by catalog id
     *
     * @return array<int, string>
     */
    public function getSubTypePaths(CatalogTypeEnum $type): array;

    /**
     * Inserts a catalog row and returns its id, or 0 when the write produced none
     */
    public function insert(
        string $name,
        string $type,
        string $renamePattern,
        string $sortPattern,
        string $gatherTypes,
    ): int;

    /**
     * Adds a catalog's row to its backend's settings table
     *
     * @param array<string, mixed> $values column => value, without `catalog_id`
     */
    public function insertSubType(CatalogTypeEnum $type, array $values, int $catalogId): bool;

    /**
     * Releases the processing lock taken by `tryAcquireProcessingLock()`
     */
    public function releaseProcessingLock(int $catalogId): void;

    /**
     * Writes one bounded column of a catalog
     */
    public function setField(int $catalogId, CatalogFieldEnum $field, int|string $value): bool;

    /**
     * Whether a backend's settings table has been created yet
     */
    public function subTypeTableExists(CatalogTypeEnum $type): bool;

    /**
     * Whether a backend already has a catalog holding this value, which is what stops a duplicate
     */
    public function subTypeValueExists(CatalogTypeEnum $type, string $column, string $value): bool;

    /**
     * Takes an exclusive, session-scoped lock for one catalog so overlapping scans can't race each other
     *
     * Backed by `GET_LOCK()`, so a crashed process releases it automatically rather than leaving it stuck.
     */
    public function tryAcquireProcessingLock(int $catalogId): bool;

    /**
     * Writes the three settings the catalog edit form exposes
     */
    public function updateSettings(int $catalogId, string $name, string $renamePattern, string $sortPattern): void;

    /**
     * Points a catalog at another path on disk
     */
    public function updateSubTypePath(CatalogTypeEnum $type, int $catalogId, string $path): void;
}
