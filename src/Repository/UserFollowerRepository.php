<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Repository;

use Doctrine\DBAL\Connection;

final class UserFollowerRepository implements UserFollowerRepositoryInterface
{
    private Connection $database;

    public function __construct(
        Connection $database
    ) {
        $this->database = $database;
    }

    /**
     * Get users following the user
     *
     * @return int[]
     */
    public function getFollowers(int $userId): array
    {
        $dbResults = $this->database->executeQuery(
            'SELECT `user` FROM `user_follower` WHERE `follow_user` = ?',
            [$userId]
        );

        $results = [];

        while ($followerId = $dbResults->fetchOne()) {
            $results[] = (int) $followerId;
        }

        return $results;
    }

    /**
     * Get users followed by this user
     *
     * @return int[]
     */
    public function getFollowing(int $userId): array
    {
        $db_results = $this->database->executeQuery(
            'SELECT `follow_user` FROM `user_follower` WHERE `user` = ?',
            [$userId]
        );

        $results = [];

        while ($userId = $db_results->fetchOne()) {
            $results[] = (int) $userId;
        }

        return $results;
    }

    /**
     * Get if an user is followed by another user
     */
    public function isFollowedBy(int $userId, int $followingUserId): bool
    {
        return $this->database->executeQuery(
            'SELECT `id` FROM `user_follower` WHERE `user` = ? AND `follow_user` = ?',
            [$followingUserId, $userId]
        )->rowCount() > 0;
    }

    public function add(
        int $userId,
        int $followingUserId,
        int $time
    ): void {
        $this->database->executeQuery(
            'INSERT INTO `user_follower` (`user`, `follow_user`, `follow_date`) VALUES (?, ?, ?)',
            [
                $followingUserId,
                $userId,
                $time
            ]
        );
    }

    public function delete(
        int $userId,
        int $followingUserId
    ): void {
        $this->database->executeQuery(
            'DELETE FROM `user_follower` WHERE `user` = ? AND `follow_user` = ?',
            [
                $followingUserId,
                $userId,
            ]
        );
    }
}
