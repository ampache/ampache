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

use Ampache\Module\Catalog\CatalogMapTableEnum;

/**
 * Provides access to the `catalog_map` table, which records the catalog every object belongs to
 */
interface CatalogMapRepositoryInterface
{
    /**
     * Maps one object onto a catalog, ignoring a mapping that is already there
     */
    public function add(int $catalogId, string $objectType, int $objectId): void;

    /**
     * Maps one artist onto every catalog its songs and albums live in, under each of its three roles
     */
    public function addForArtist(int $artistId): void;

    /**
     * Removes the mappings whose object no longer exists, plus everything left pointing at catalog 0
     *
     * @param list<CatalogMapTableEnum> $tables
     */
    public function collectGarbage(array $tables): void;

    /**
     * Removes every mapping of one object
     */
    public function deleteForObject(string $objectType, int $objectId): void;

    /**
     * Moves the mappings of an object onto another one, keeping whatever the target already had
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): bool;

    /**
     * Rebuilds the mappings of one table from the catalog each of its rows carries
     */
    public function rebuild(CatalogMapTableEnum $table): void;

    /**
     * Points an object's mapping at another catalog, for media that moved between them
     */
    public function setCatalog(string $objectType, int $objectId, int $catalogId): bool;
}
