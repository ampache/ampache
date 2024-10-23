<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Shoutbox;
use Traversable;

/**
 * @extends BaseRepositoryInterface<Shoutbox>
 */
interface ShoutRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Returns all shout-box items for the provided object-type and -id
     *
     * @return Traversable<Shoutbox>
     */
    public function getBy(
        LibraryItemEnum $objectType,
        int $objectId
    ): Traversable;

    /**
     * Cleans out orphaned shout-box items
     */
    public function collectGarbage(?string $objectType = null, ?int $objectId = null): void;

    /**
     * This returns the top user_shouts, shout-box objects are always shown regardless and count against the total
     * number of objects shown
     *
     * @return Traversable<Shoutbox>
     */
    public function getTop(int $limit, ?string $username = null): Traversable;

    /**
     * Persists the shout-item in the database
     *
     * If the item is new, it will be created. Otherwise, an update will happen
     *
     * @return null|non-negative-int
     */
    public function persist(Shoutbox $shout): ?int;

    /**
     * Migrates an object associate shouts to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void;
}
