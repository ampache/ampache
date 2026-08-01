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
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use PDO;
use Psr\Log\LoggerInterface;

final readonly class UserflagRepository implements UserflagRepositoryInterface
{
    /** @var list<string> the object types a flag may point at, and the tables the sweep reads them from */
    private const array GARBAGE_TYPES = [
        'album_disk',
        'album',
        'artist',
        'catalog',
        'folder',
        'label',
        'live_stream',
        'playlist',
        'podcast_episode',
        'podcast',
        'search',
        'song',
        'tag',
        'user',
        'video',
    ];

    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    public function adjustWeight(string $objectType, int $objectId, int $delta): void
    {
        // the table name goes into the statement, so only the types known to carry the column may reach it
        if (!in_array($objectType, Stats::WEIGHT_TYPES, true)) {
            return;
        }

        $this->connection->query(
            sprintf(
                'UPDATE `%s` SET `weight` = `weight` %s 1 WHERE `id` = ?;',
                $objectType,
                ($delta < 0) ? '-' : '+'
            ),
            [$objectId]
        );
    }

    public function collectGarbage(?string $objectType = null, ?int $objectId = null): void
    {
        $statements = [];
        if ($objectType !== null) {
            if (!in_array($objectType, self::GARBAGE_TYPES, true)) {
                $this->logger->critical(
                    'Garbage collect on type `' . $objectType . '` is not supported.',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return;
            }

            $statements[] = ['DELETE FROM `user_flag` WHERE `object_type` = ? AND `object_id` = ?', [$objectType, $objectId]];
        } else {
            foreach (self::GARBAGE_TYPES as $type) {
                $statements[] = [
                    sprintf(
                        'DELETE FROM `user_flag` WHERE `object_type` = ? AND `user_flag`.`object_id` NOT IN (SELECT `%s`.`id` FROM `%s`);',
                        $type,
                        $type
                    ),
                    [$type],
                ];
            }
        }

        // one type that cannot be swept must not take the rest of the sweep down with it
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

    public function deleteFlag(int $objectId, string $objectType, int $userId): void
    {
        $this->connection->query(
            'DELETE FROM `user_flag` WHERE `object_id` = ? AND `object_type` = ? AND `user` = ?',
            [$objectId, $objectType, $userId]
        );
    }

    /**
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
    ): array {
        $sql   = $this->getLatestSql($inputType, $user, $since, $before, $byUser, $catalogId);
        $limit = ($offset < 1)
            ? (string) $count
            : $offset . ',' . $count;
        if ($count > 0) {
            $sql .= 'LIMIT ' . $limit;
        }

        $result = $this->connection->query($sql);

        $objectIds = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $objectIds[] = (int) $row['id'];
        }

        return $objectIds;
    }

    public function getFlagDate(int $objectId, string $objectType, int $userId): ?int
    {
        $date = $this->connection->fetchOne(
            'SELECT `date` FROM `user_flag` WHERE `user` = ? AND `object_id` = ? AND `object_type` = ?',
            [$userId, $objectId, $objectType]
        );

        return ($date === false || $date === null)
            ? null
            : (int) $date;
    }

    /**
     * @param list<int|string> $objectIds
     * @return array<int, int>
     */
    public function getFlagDates(string $objectType, array $objectIds, int $userId): array
    {
        if ($objectIds === []) {
            return [];
        }

        $result = $this->connection->query(
            sprintf(
                'SELECT `object_id`, `date` FROM `user_flag` WHERE `user` = ? AND `object_id` IN (%s) AND `object_type` = ?',
                implode(',', array_map(intval(...), $objectIds))
            ),
            [$userId, $objectType]
        );

        $dates = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $dates[(int) $row['object_id']] = (int) $row['date'];
        }

        return $dates;
    }

    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->query(
            'UPDATE IGNORE `user_flag` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }

    public function setFlag(int $objectId, string $objectType, int $userId, int $date): void
    {
        $this->connection->query(
            'REPLACE INTO `user_flag` (`object_id`, `object_type`, `user`, `date`) VALUES (?, ?, ?, ?)',
            [$objectId, $objectType, $userId, $date]
        );
    }

    private function getLatestSql(
        string $inputType,
        ?User $user,
        int $since,
        int $before,
        bool $byUser,
        int $catalogId,
    ): string {
        $type = Stats::validate_type($inputType);
        $sql  = "SELECT DISTINCT(`user_flag`.`object_id`) AS `id`, COUNT(DISTINCT(`user_flag`.`user`)) AS `count`, `user_flag`.`object_type` AS `type`, MAX(`user_flag`.`user`) AS `user`, MAX(`user_flag`.`date`) AS `date` FROM `user_flag`";
        if ($inputType == 'album_artist' || $inputType == 'song_artist') {
            $sql .= " LEFT JOIN `artist` ON `artist`.`id` = `user_flag`.`object_id` AND `user_flag`.`object_type` = 'artist'";
        }

        $sql .= " WHERE `user_flag`.`object_type` = '" . $type . "'";
        if ($byUser && $user?->id > 0) {
            $sql .= sprintf(' AND `user_flag`.`user` = \'%s\'', $user->id);
        }

        if (AmpConfig::get('catalog_disable') && in_array($type, ['artist', 'album', 'album_disk', 'song', 'video'])) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }

        if (AmpConfig::get('catalog_filter')) {
            $sql .= " AND" . Catalog::get_user_filter('user_flag_' . $type, $user?->getId() ?? -1);
        }

        $catalog_sql = Catalog::get_catalog_id_filter($inputType, '`user_flag`.`object_id`', $catalogId);
        if ($catalog_sql !== '') {
            $sql .= " AND " . $catalog_sql;
        }

        if ($inputType == 'album_artist') {
            $sql .= " AND `artist`.`album_count` > 0";
        }

        if ($inputType == 'song_artist') {
            $sql .= " AND `artist`.`song_count` > 0";
        }

        if ($since > 0) {
            $sql .= " AND `user_flag`.`date` >= '" . $since . "'";
            if ($before > 0) {
                $sql .= " AND `user_flag`.`date` <= '" . $before . "'";
            }
        }

        return $sql . " GROUP BY `user_flag`.`object_id`, `type` ORDER BY `date` DESC ";
    }
}
