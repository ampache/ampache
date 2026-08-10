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

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\LegacyLogger;
use PDO;
use Psr\Log\LoggerInterface;

final readonly class UserActivityRepository implements UserActivityRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    /**
     * Remove activities for items that no longer exist.
     */
    public function collectGarbage(
        ?string $object_type = null,
        ?int $object_id = null,
    ): void {
        $types = [
            'album_disk',
            'album',
            'artist',
            'catalog',
            'folder',
            'live_stream',
            'playlist',
            'podcast_episode',
            'podcast',
            'song',
            'video',
        ];

        if ($object_type !== null) {
            if (in_array($object_type, $types, true)) {
                $this->connection->query(
                    'DELETE FROM `user_activity` WHERE `object_type` = ? AND `object_id` = ?',
                    [$object_type, $object_id]
                );
            } else {
                $this->logger->critical(
                    'Garbage collect on type `' . $object_type . '` is not supported.',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        } else {
            $statements = [];
            foreach ($types as $type) {
                $statements[] = [sprintf('DELETE FROM `user_activity` WHERE `object_type` = ? AND `user_activity`.`object_id` NOT IN (SELECT `%s`.`id` FROM `%s`);', $type, $type), [$type]];
            }

            // accidental plays
            $statements[] = ["DELETE FROM `user_activity` WHERE `object_type` IN ('album', 'artist') AND `action` = 'play';", []];
            // deleted users
            $statements[] = ['DELETE FROM `user_activity` WHERE `user` NOT IN (SELECT `id` FROM `user`);', []];

            // one missing table must not take the rest of the sweep down with it
            foreach ($statements as $statement) {
                try {
                    $this->connection->query($statement[0], $statement[1]);
                } catch (DatabaseException) {
                    $this->logger->debug(
                        'collectGarbage error: ' . $statement[0],
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                }
            }
        }
    }

    /**
     * Delete activity by date
     */
    public function deleteByDate(
        int $date,
        string $action,
        int $user_id = 0,
    ): void {
        $this->connection->query(
            'DELETE FROM `user_activity` WHERE `activity_date` = ? AND `action` = ? AND `user` = ?',
            [$date, $action, $user_id]
        );
    }

    /**
     * @return int[]
     */
    public function getActivities(
        int $user_id,
        int $limit = 0,
        int $since = 0,
    ): array {
        if ($limit < 1) {
            $limit = (int) AmpConfig::get('popular_threshold', 10);
        }

        $params = [$user_id];
        $sql    = "SELECT `id` FROM `user_activity` WHERE `user` = ? ";
        if ($since > 0) {
            $sql .= "AND `activity_date` <= ? ";
            $params[] = $since;
        }

        $sql .= "ORDER BY `activity_date` DESC LIMIT " . $limit;
        $dbResults = $this->connection->query($sql, $params);
        $results   = [];
        while ($row = $dbResults->fetch(PDO::FETCH_ASSOC)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * @return int[]
     */
    public function getFriendsActivities(int $user_id, int $limit = 0, int $since = 0): array
    {
        if ($limit < 1) {
            $limit = (int) AmpConfig::get('popular_threshold', 10);
        }

        $params = [$user_id];
        $sql    = "SELECT `user_activity`.`id` FROM `user_activity` INNER JOIN `user_follower` ON `user_follower`.`follow_user` = `user_activity`.`user` WHERE `user_follower`.`user` = ? ";
        if ($since > 0) {
            $sql .= "AND `user_activity`.`activity_date` <= ? ";
            $params[] = $since;
        }

        $sql .= "ORDER BY `user_activity`.`activity_date` DESC LIMIT " . $limit;
        $dbResults = $this->connection->query($sql, $params);
        $results   = [];
        while ($row = $dbResults->fetch(PDO::FETCH_ASSOC)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Reads whole activity rows for the in-process cache, in one statement instead of one per object
     *
     * @param list<int|string> $activityIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $activityIds): array
    {
        if ($activityIds === []) {
            return [];
        }

        $result = $this->connection->query(
            sprintf(
                'SELECT * FROM `user_activity` WHERE `id` IN (%s)',
                implode(',', array_map(intval(...), $activityIds))
            )
        );

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Moves the activity of an object onto another one
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->query(
            'UPDATE `user_activity` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }

    /**
     * Inserts the necessary data to register a generic action on an object
     *
     * @todo Replace when active record models are available
     */
    public function registerGenericEntry(
        int $userId,
        string $action,
        string $object_type,
        int $objectId,
        int $date,
    ): void {
        $this->connection->query(
            'INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`) VALUES (?, ?, ?, ?, ?)',
            [$userId, $action, $object_type, $objectId, $date]
        );
    }
}
