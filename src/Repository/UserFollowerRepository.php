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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\User;

/**
 * Manages database-access to the `user_follower`-table
 */
final readonly class UserFollowerRepository implements UserFollowerRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
    ) {
    }

    /**
     * Get users following the user
     *
     * @return list<int>
     */
    public function getFollowers(User $user): array
    {
        $result = $this->connection->query(
            'SELECT `user` FROM `user_follower` WHERE `follow_user` = ?',
            [$user->getId()]
        );

        $results = [];
        while ($userId = $result->fetchColumn()) {
            $results[] = (int) $userId;
        }

        return $results;
    }

    /**
     * Get users followed by this user
     *
     * @return list<int>
     */
    public function getFollowing(User $user): array
    {
        $result = $this->connection->query(
            'SELECT `follow_user` FROM `user_follower` WHERE `user` = ?',
            [$user->getId()]
        );

        $results = [];
        while ($userId = $result->fetchColumn()) {
            $results[] = (int) $userId;
        }

        return $results;
    }

    /**
     * Get if a user is followed by another user
     */
    public function isFollowedBy(
        User $user,
        User $followingUser,
    ): bool {
        return $this->connection->fetchOne(
            'SELECT count(`id`) FROM `user_follower` WHERE `user` = ? AND `follow_user` = ?',
            [$followingUser->getId(), $user->getId()]
        ) > 0;
    }

    /**
     * Adds an entry for a user following another user
     */
    public function add(
        User $user,
        User $followingUser,
    ): void {
        $this->connection->query(
            'INSERT INTO `user_follower` (`user`, `follow_user`, `follow_date`) VALUES (?, ?, UNIX_TIMESTAMP())',
            [
                $followingUser->getId(),
                $user->getId(),
            ]
        );
    }

    /**
     * Deletes a user follow-entry
     */
    public function delete(
        User $user,
        User $followingUser
    ): void {
        $this->connection->query(
            'DELETE FROM `user_follower` WHERE `user` = ? AND `follow_user` = ?',
            [
                $followingUser->getId(),
                $user->getId(),
            ]
        );
    }
}
