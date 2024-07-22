<?php

declare(strict_types=1);

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

use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use DateTimeInterface;

/**
 * Manages share related database access
 *
 * Tables: `share`
 */
interface ShareRepositoryInterface
{
    /**
     * Migrate a share associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void;

    /**
     * Finds a single item by its id
     */
    public function findById(int $itemId): ?Share;

    /**
     * Cleanup old shares
     */
    public function collectGarbage(): void;

    /**
     * Returns the ids of all items the user has access to
     *
     * @return list<int>
     */
    public function getIdsByUser(User $user): array;

    /**
     * Deletes a single item
     */
    public function delete(Share $item): void;

    /**
     * Sets the last access-date and raises the counter
     */
    public function registerAccess(Share $share, DateTimeInterface $date): void;
}
