<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

interface UserFollowerRepositoryInterface
{
    /**
     * Get users following the user
     *
     * @return int[]
     */
    public function getFollowers(int $userId): array;

    /**
     * Get users followed by this user
     *
     * @return int[]
     */
    public function getFollowing(int $userId): array;

    /**
     * Get if a user is followed by another user
     */
    public function isFollowedBy(int $userId, int $followingUserId): bool;

    public function add(
        int $userId,
        int $followingUserId
    ): void;

    public function delete(
        int $userId,
        int $followingUserId
    ): void;
}
