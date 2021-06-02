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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\UseractivityInterface;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final class UserActivityRepository implements UserActivityRepositoryInterface
{
    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private Connection $connection;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
        $this->connection      = $connection;
        $this->logger          = $logger;
    }

    /**
     * @return UseractivityInterface[]
     */
    public function getFriendsActivities(int $userId, int $limit = 0, int $since = 0): array
    {
        if ($limit < 1) {
            $limit = $this->configContainer->getPopularThreshold(10);
        }

        $params = [$userId];
        $sql    = 'SELECT `user_activity`.`id` FROM `user_activity` INNER JOIN `user_follower` ON `user_follower`.`follow_user` = `user_activity`.`user` WHERE `user_follower`.`user` = ?';
        if ($since > 0) {
            $sql .= ' AND `user_activity`.`activity_date` <= ?';
            $params[] = $since;
        }

        $dbResult = $this->connection->executeQuery(
            sprintf('%s ORDER BY `user_activity`.`activity_date` DESC LIMIT %d', $sql, $limit),
            $params
        );

        $results = [];

        while ($id = $dbResult->fetchOne()) {
            $results[] = $this->modelFactory->createUseractivity((int) $id);
        }

        return $results;
    }

    /**
     * @return UseractivityInterface[]
     */
    public function getActivities(
        int $userId,
        int $limit = 0,
        int $since = 0
    ): array {
        if ($limit < 1) {
            $limit = $this->configContainer->getPopularThreshold(10);
        }

        $params = [$userId];
        $sql    = 'SELECT `id` FROM `user_activity` WHERE `user` = ?';
        if ($since > 0) {
            $sql .= ' AND `activity_date` <= ?';
            $params[] = $since;
        }

        $dbResult = $this->connection->executeQuery(
            sprintf(
                '%s ORDER BY `activity_date` DESC LIMIT %d',
                $sql,
                $limit
            ),
            $params
        );

        $results = [];

        while ($id = $dbResult->fetchOne()) {
            $results[] = $this->modelFactory->createUseractivity((int) $id);
        }

        return $results;
    }

    /**
     * Delete activity by date
     */
    public function deleteByDate(
        int $date,
        string $action,
        int $userId = 0
    ): void {
        $this->connection->executeQuery(
            'DELETE FROM `user_activity` WHERE `activity_date` = ? AND `action` = ? AND `user` = ?',
            [$date, $action, $userId]
        );
    }

    /**
     * Remove activities for items that no longer exist.
     */
    public function collectGarbage(
        ?string $objectType = null,
        ?int $objectId = null
    ): void {
        $types = ['song', 'album', 'artist', 'video', 'tvshow', 'tvshow_season'];

        if ($objectType !== null) {
            if (in_array($objectType, $types)) {
                $this->connection->executeQuery(
                    'DELETE FROM `user_activity` WHERE `object_type` = ? AND `object_id` = ?',
                    [$objectType, $objectId]
                );
            } else {
                $this->logger->critical(
                    sprintf('Garbage collect on type `%s` is not supported.', $objectType),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }
        } else {
            foreach ($types as $type) {
                $this->connection->executeQuery(
                    sprintf(
                        'DELETE FROM `user_activity` WHERE `object_type` = ? AND `user_activity`.`object_id` NOT IN (SELECT `%s`.`id` FROM `%s`)',
                        $type,
                        $type
                    ),
                    [
                        $type
                    ]
                );
            }
            // accidental plays
            $this->connection->executeQuery(
                'DELETE FROM `user_activity` WHERE `object_type` IN (\'album\', \'artist\')'
            );
        }
    }

    /**
     * Inserts the necessary data to register the playback of a song
     *
     * @todo Replace when active record models are available
     */
    public function registerSongEntry(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        int $date,
        ?string $songName,
        ?string $artistName,
        ?string $albumName,
        ?string $songMbId,
        ?string $artistMbId,
        ?string $albumMbId
    ): void {
        $sql = <<<SQL
            INSERT INTO `user_activity`
            (`user`, `action`, `object_type`, `object_id`, `activity_date`, `name_track`, `name_artist`, `name_album`, `mbid_track`, `mbid_artist`, `mbid_album`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        SQL;

        $this->connection->executeQuery(
            $sql,
            [
                $userId,
                $action,
                $objectType,
                $objectId,
                $date,
                $songName,
                $artistName,
                $albumName,
                $songMbId,
                $artistMbId,
                $albumMbId
            ]
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
        string $objectType,
        int $objectId,
        int $date
    ): void {
        $this->connection->executeQuery(
            'INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`) VALUES (?, ?, ?, ?, ?)',
            [$userId, $action, $objectType, $objectId, $date]
        );
    }

    /**
     * Inserts the necessary data to register an artist related action
     *
     * @todo Replace when active record models are available
     */
    public function registerArtistEntry(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        int $date,
        ?string $artistName,
        ?string $artistMbId
    ): void {
        $sql = <<<SQL
        INSERT INTO `user_activity`
        (`user`, `action`, `object_type`, `object_id`, `activity_date`, `name_artist`, `mbid_artist`)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        SQL;

        $this->connection->executeQuery(
            $sql,
            [
                $userId,
                $action,
                $objectType,
                $objectId,
                $date,
                $artistMbId,
            ]
        );
    }

    /**
     * Inserts the necessary data to register the playback of a song
     *
     * @todo Replace when active record models are available
     */
    public function registerAlbumEntry(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        int $date,
        ?string $artistName,
        ?string $albumName,
        ?string $artistMbId,
        ?string $albumMbId
    ): void {
        $sql = <<<SQL
        INSERT INTO `user_activity`
        (`user`, `action`, `object_type`, `object_id`, `activity_date`, `name_artist`, `name_album`, `mbid_artist`, `mbid_album`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        SQL;

        $this->connection->executeQuery(
            $sql,
            [
                $userId,
                $action,
                $objectType,
                $objectId,
                $date,
                $artistName,
                $albumName,
                $artistMbId,
                $albumMbId
            ]
        );
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->executeQuery(
            'UPDATE `user_activity` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }
}
