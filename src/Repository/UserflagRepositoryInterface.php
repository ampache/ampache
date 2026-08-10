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

use Ampache\Repository\Model\User;

/**
 * Provides access to the `user_flag` table
 */
interface UserflagRepositoryInterface
{
    /**
     * Moves the weight counter a flag keeps on the flagged object's own table
     *
     * Only the types carrying a `weight` column are touched; anything else is ignored.
     */
    public function adjustWeight(string $objectType, int $objectId, int $delta): void;

    /**
     * Removes the flags of objects that no longer exist, or of one named object
     */
    public function collectGarbage(?string $objectType = null, ?int $objectId = null): void;

    /**
     * Drops one user's flag from an object
     */
    public function deleteFlag(int $objectId, string $objectType, int $userId): void;

    /**
     * Reads the most recently flagged objects, newest first
     *
     * @return list<int>
     */
    public function findLatestIds(
        string $inputType,
        ?User $user,
        int $count,
        int $offset,
        int $since,
        int $before,
        bool $byUser,
        int $catalogId,
    ): array;

    /**
     * The date one user flagged an object, or `null` when they have not flagged it
     */
    public function getFlagDate(int $objectId, string $objectType, int $userId): ?int;

    /**
     * The dates one user flagged a set of objects, keyed by object id and skipping the unflagged
     *
     * @param list<int|string> $objectIds
     * @return array<int, int>
     */
    public function getFlagDates(string $objectType, array $objectIds, int $userId): array;

    /**
     * Moves the flags of an object onto another one, keeping whatever the target already had
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void;

    /**
     * Stores one user's flag on an object, replacing their previous one
     */
    public function setFlag(int $objectId, string $objectType, int $userId, int $date): void;
}
