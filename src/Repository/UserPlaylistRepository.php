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
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Doctrine\DBAL\Connection;

final class UserPlaylistRepository implements UserPlaylistRepositoryInterface
{
    private Connection $database;

    public function __construct(
        Connection $database
    ) {
        $this->database = $database;
    }

    /**
     * Returns an array of all object_ids currently in this User_Playlist.
     * @return array<array{
     *  id: int,
     *  object_type: string,
     *  object_id: int,
     *  track: int,
     *  current_track: int,
     *  current_time: int
     * }>
     */
    public function getItemsByUser(int $userId): array
    {
        // Select all objects from this user
        $dbResults = $this->database->executeQuery(
            'SELECT `id`, `object_type`, `object_id`, `track`, `current_track`, `current_time`  FROM `user_playlist` WHERE `user` = ? ORDER BY `track`, `id`',
            [$userId]
        );

        $result = [];

        while ($results = $dbResults->fetchAssociative()) {
            $result[] = [
                'id' => (int) $results['id'],
                'object_type' => $results['object_type'],
                'object_id' => (int) $results['object_id'],
                'track' => (int) $results['track'],
                'current_track' => (int) $results['current_track'],
                'current_time' => (int) $results['current_time']
            ];
        }

        return $result;
    }

    /**
     * This returns the next object in the user_playlist.
     * @return null|array{
     *  id: int,
     *  object_type: string,
     *  object_id: int,
     *  track: int,
     *  current_track: int,
     *  current_time: int
     * }
     */
    public function getCurrentObjectByUser(int $userId): ?array
    {
        // Select the current object for this user
        $dbResults = $this->database->fetchAssociative(
            'SELECT `id`, `object_type`, `object_id`, `track`, `current_track`, `current_time` FROM `user_playlist` WHERE `user`= ? AND `current_track` = 1 LIMIT 1',
            [$userId]
        );

        if ($dbResults === false) {
            return null;
        }

        return [
            'id' => (int) $dbResults['id'],
            'object_type' => $dbResults['object_type'],
            'object_id' => (int) $dbResults['object_id'],
            'track' => (int) $dbResults['track'],
            'current_track' => (int) $dbResults['current_track'],
            'current_time' => (int) $dbResults['current_time']
        ];
    }

    /**
     * This clears all the objects out of a user's playlist
     */
    public function clear(int $userId): void
    {
        $this->database->executeQuery(
            'DELETE FROM `user_playlist` WHERE `user` = ?',
            [$userId]
        );
    }

    /**
     * This adds a new song to the playlist
     */
    public function addItem(
        int $userId,
        string $objectType,
        int $objectId,
        int $track
    ): void {
        $this->database->executeQuery(
            'INSERT INTO `user_playlist` (`user`, `object_type`, `object_id`, `track`) VALUES (?, ?, ?, ?)',
            [$userId, $objectType, $objectId, $track]
        );
    }

    /**
     * set the active object in the user_playlist.
     */
    public function setCurrentObjectByUser(
        int $userId,
        string $objectType,
        int $objectId,
        int $position
    ): void {
        // remove the old current
        $this->database->executeQuery(
            'UPDATE `user_playlist` SET `current_track` = 0, `current_time` = 0 WHERE `user` = ?',
            [$userId]
        );

        // set the new one
        $this->database->executeQuery(
            'UPDATE `user_playlist` SET `current_track` = 1, `current_time` = ? WHERE `object_type` = ? AND `object_id` = ? AND `user` = ? LIMIT 1',
            [$position, $objectType, $objectId, $userId]
        );
    }
}
