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

namespace Ampache\Repository\Model;

use Ampache\Repository\Model\library_item as TITemType;

interface LibraryItemLoaderInterface
{
    /**
     * Loads a generic library-item
     *
     * Will try to load an item with the given object-type and -id.
     * Supports the specification of a list of allowed classes/interfaces to check against.
     *
     * @template TITemType of library_item
     *
     * @param list<class-string<TITemType>> $allowedItems List of all possible class-/interface-names
     *
     * @return null|TITemType
     */
    public function load(
        LibraryItemEnum $objectType,
        int $objectId,
        array $allowedItems = [library_item::class]
    ): ?library_item;
}
