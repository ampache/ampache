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
 * Provides access to the `rating` table
 */
interface RatingRepositoryInterface
{
    /**
     * Moves the weight counter a rating keeps on the rated object's own table
     *
     * Only the types carrying a `weight` column are touched; anything else is ignored.
     */
    public function adjustWeight(string $objectType, int $objectId, int $delta): void;

    /**
     * Removes the ratings of objects that no longer exist, or of one named object
     */
    public function collectGarbage(?string $objectType = null, ?int $objectId = null): void;

    /**
     * Drops one user's rating of an object
     */
    public function deleteRating(int $objectId, string $objectType, int $userId): void;

    /**
     * Reads the objects carrying the highest average rating, best first
     *
     * @return list<int>
     */
    public function findHighestIds(
        string $inputType,
        int $count,
        int $offset,
        ?int $userId,
        bool $byUser,
        int $catalogId,
    ): array;

    /**
     * Reads the most recently rated objects, newest first
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
    ): array;

    /**
     * The average rating of one object, or `null` while fewer than two users have rated it
     */
    public function getAverageRating(int $objectId, string $objectType): ?float;

    /**
     * The average rating of a set of objects, keyed by object id and skipping the unrated
     *
     * @param list<int|string> $objectIds
     * @return array<int, float>
     */
    public function getAverageRatings(string $objectType, array $objectIds): array;

    /**
     * One user's rating of an object, or `null` when they have not rated it
     */
    public function getUserRating(int $objectId, string $objectType, int $userId): ?int;

    /**
     * One user's ratings of a set of objects, keyed by object id and skipping the unrated
     *
     * @param list<int|string> $objectIds
     * @return array<int, int>
     */
    public function getUserRatings(string $objectType, array $objectIds, int $userId): array;

    /**
     * Moves the ratings of an object onto another one, keeping whatever the target already had
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void;

    /**
     * Stores one user's rating of an object, replacing their previous one
     */
    public function setRating(int $objectId, string $objectType, int $rating, int $userId, int $date): void;
}
