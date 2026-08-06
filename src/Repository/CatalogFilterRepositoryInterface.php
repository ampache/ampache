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

use Traversable;

/**
 * Provides access to `catalog_filter_group` and `catalog_filter_group_map`, the per-user catalog visibility
 */
interface CatalogFilterRepositoryInterface
{
    /**
     * Gives every existing group a row for a newly added catalog, enabled only for the DEFAULT group
     */
    public function addCatalogToGroups(int $catalogId): void;

    /**
     * Adds every catalog the DEFAULT group is missing, so a new catalog is visible without a manual edit
     */
    public function addMissingCatalogsToDefaultGroup(): void;

    /**
     * Removes the mappings whose group or catalog has gone, and puts DEFAULT back at id 0
     */
    public function collectGarbage(): void;

    /**
     * Counts the catalogs a group has enabled
     */
    public function countCatalogs(int $groupId): int;

    /**
     * Creates a filter group and returns its id
     */
    public function createGroup(string $name): int;

    /**
     * Creates a filter group and maps the given catalogs onto it
     *
     * @param array<int, int> $enabledByCatalogId catalog id => 1 to enable, 0 to hide
     */
    public function createGroupWithCatalogs(string $name, array $enabledByCatalogId): bool;

    /**
     * Removes a group and every catalog mapping it holds
     */
    public function deleteGroup(int $groupId): bool;

    /**
     * Reads every filter group, by name
     *
     * @return Traversable<array{id: int, name: string}>
     */
    public function findGroups(): Traversable;

    /**
     * Whether another group already carries this name, optionally ignoring one group id
     */
    public function groupNameExists(string $name, int $excludeId = 0): bool;

    /**
     * Whether a user's group has this catalog enabled; a user id of -1 asks the DEFAULT group
     */
    public function hasAccess(int $catalogId, int $userId): bool;

    /**
     * Maps a set of catalogs onto a group in one statement, for a group that has none yet
     *
     * @param array<int, int> $enabledByCatalogId catalog id => 1 to enable, 0 to hide
     */
    public function insertCatalogsForGroup(int $groupId, array $enabledByCatalogId): bool;

    /**
     * Whether a group has this catalog enabled
     */
    public function isCatalogEnabled(int $groupId, int $catalogId): bool;

    /**
     * Renames a filter group
     */
    public function renameGroup(int $groupId, string $name): void;

    /**
     * Puts the DEFAULT group back at id 0, where the rest of the schema assumes it lives
     *
     * Autoincrement starts at 1, so a group inserted normally lands in the wrong place and every catalog
     * filter silently stops matching. Returns whether the repair had to run.
     */
    public function repairDefaultGroup(): bool;

    /**
     * Enables or hides one catalog for a group, adding the mapping when it has none
     */
    public function setCatalogEnabled(int $groupId, int $catalogId, int $enabled): bool;

    /**
     * Renames a group and applies the enabled state of every catalog it was given
     *
     * @param array<int, int> $enabledByCatalogId catalog id => 1 to enable, 0 to hide
     */
    public function updateGroupCatalogs(int $groupId, string $name, array $enabledByCatalogId): bool;
}
