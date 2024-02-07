<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Repository\Model\MetadataField;
use Traversable;

interface MetadataFieldRepositoryInterface
{
    /**
     * Remove metadata for songs which don't exist anymore
     */
    public function collectGarbage(): void;

    /**
     * Returns the list of available fields
     *
     * Key is the primary key, value the name
     *
     * @return Traversable<int, string>
     */
    public function getPropertyList(): Traversable;

    /**
     * Finds a single `metadata-field` item by its id
     */
    public function findById(int $fieldId): ?MetadataField;

    /**
     * Finds a single `metadata-field` item by its name
     */
    public function findByName(string $name): ?MetadataField;

    /**
     * Saves the item
     *
     * @return null|int The id of the item if the item was new
     */
    public function persist(MetadataField $field): ?int;

    /**
     * Creates a new `metadata-field` item
     */
    public function prototype(): MetadataField;
}
