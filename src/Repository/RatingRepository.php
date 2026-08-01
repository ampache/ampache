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
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\User;
use PDO;
use Psr\Log\LoggerInterface;

final readonly class RatingRepository implements RatingRepositoryInterface
{
    /** @var list<string> the object types a rating row may point at, and the tables the sweep reads them from */
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
        if ($objectType !== null && $objectType !== '') {
            if (!in_array($objectType, self::GARBAGE_TYPES, true)) {
                $this->logger->critical(
                    'Garbage collect on type `' . $objectType . '` is not supported.',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return;
            }

            $statements[] = ['DELETE FROM `rating` WHERE `object_type` = ? AND `object_id` = ?', [$objectType, $objectId]];
        } else {
            foreach (self::GARBAGE_TYPES as $type) {
                $statements[] = [
                    sprintf(
                        'DELETE FROM `rating` WHERE `object_type` = ? AND `rating`.`object_id` NOT IN (SELECT `%s`.`id` FROM `%s`);',
                        $type,
                        $type
                    ),
                    [$type],
                ];
            }
        }

        // delete 'empty' ratings
        $statements[] = ['DELETE FROM `rating` WHERE `rating`.`rating` = 0;', []];

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

    public function deleteRating(int $objectId, string $objectType, int $userId): void
    {
        $this->connection->query(
            'DELETE FROM `rating` WHERE `object_id` = ? AND `object_type` = ? AND `user` = ?',
            [$objectId, $objectType, $userId]
        );
    }

    /**
     * @return list<int>
     */
    public function findHighestIds(
        string $inputType,
        int $count,
        int $offset,
        ?int $userId,
        bool $byUser,
        int $catalogId,
    ): array {
        return $this->findIds(
            $this->getHighestSql($inputType, $userId, $byUser, $catalogId),
            $count,
            $offset
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
    ): array {
        return $this->findIds(
            $this->getLatestSql($inputType, $user, $since, $before),
            $count,
            $offset
        );
    }

    public function getAverageRating(int $objectId, string $objectType): ?float
    {
        $rating = $this->connection->fetchOne(
            'SELECT ROUND(AVG(`rating`), 2) AS `rating` FROM `rating` WHERE `object_id` = ? AND `object_type` = ? HAVING COUNT(object_id) > 1',
            [$objectId, $objectType]
        );

        return ($rating === false || $rating === null)
            ? null
            : (float) $rating;
    }

    /**
     * @param list<int|string> $objectIds
     * @return array<int, float>
     */
    public function getAverageRatings(string $objectType, array $objectIds): array
    {
        if ($objectIds === []) {
            return [];
        }

        $result = $this->connection->query(
            sprintf(
                'SELECT ROUND(AVG(`rating`), 2) AS `rating`, `object_id` FROM `rating` WHERE `object_id` IN (%s) AND `object_type` = ? GROUP BY `object_id`',
                implode(',', array_map(intval(...), $objectIds))
            ),
            [$objectType]
        );

        $ratings = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $ratings[(int) $row['object_id']] = (float) $row['rating'];
        }

        return $ratings;
    }

    public function getUserRating(int $objectId, string $objectType, int $userId): ?int
    {
        $rating = $this->connection->fetchOne(
            'SELECT `rating` FROM `rating` WHERE `user` = ? AND `object_id` = ? AND `object_type` = ? AND `rating` > 0;',
            [$userId, $objectId, $objectType]
        );

        return ($rating === false || $rating === null)
            ? null
            : (int) $rating;
    }

    /**
     * @param list<int|string> $objectIds
     * @return array<int, int>
     */
    public function getUserRatings(string $objectType, array $objectIds, int $userId): array
    {
        if ($objectIds === []) {
            return [];
        }

        $result = $this->connection->query(
            sprintf(
                'SELECT `rating`, `object_id` FROM `rating` WHERE `user` = ? AND `object_id` IN (%s) AND `object_type` = ?',
                implode(',', array_map(intval(...), $objectIds))
            ),
            [$userId, $objectType]
        );

        $ratings = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $ratings[(int) $row['object_id']] = (int) $row['rating'];
        }

        return $ratings;
    }

    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->query(
            'UPDATE IGNORE `rating` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }

    public function setRating(int $objectId, string $objectType, int $rating, int $userId, int $date): void
    {
        $this->connection->query(
            'REPLACE INTO `rating` (`object_id`, `object_type`, `rating`, `user`, `date`) VALUES (?, ?, ?, ?, ?)',
            [$objectId, $objectType, $rating, $userId, $date]
        );
    }

    /**
     * @return list<int>
     */
    private function findIds(string $sql, int $count, int $offset): array
    {
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

    private function getHighestSql(string $inputType, ?int $userId, bool $byUser, int $catalogId): string
    {
        $type   = Stats::validate_type($inputType);
        $userId = $userId ?? -1;
        $sql    = "SELECT MAX(`rating`.`id`) AS `table_id`, MIN(`rating`.`object_id`) AS `id`, ROUND(AVG(`rating`.`rating`), 2) AS `rating`, COUNT(DISTINCT(`rating`.`user`)) AS `count`, MAX(`rating`.`date`) AS `date` FROM `rating`";
        if ($inputType == 'album_artist' || $inputType == 'song_artist') {
            $sql .= " LEFT JOIN `artist` ON `artist`.`id` = `rating`.`object_id` AND `rating`.`object_type` = 'artist'";
        }

        $sql .= sprintf(' WHERE `object_type` = \'%s\'', $type);
        if ($byUser && $userId > 0) {
            $sql .= sprintf(' AND `rating`.`user` = \'%s\'', $userId);
        }

        if (AmpConfig::get('catalog_disable') && in_array($inputType, ['artist', 'album', 'album_disk', 'song', 'video'])) {
            $sql .= " AND " . Catalog::get_enable_filter($inputType, '`object_id`');
        }

        if (AmpConfig::get('catalog_filter')) {
            $sql .= " AND" . Catalog::get_user_filter('rating_' . $type, $userId);
        }

        $catalog_sql = Catalog::get_catalog_id_filter($inputType, '`rating`.`object_id`', $catalogId);
        if ($catalog_sql !== '') {
            $sql .= " AND " . $catalog_sql;
        }

        if ($inputType == 'album_artist') {
            $sql .= " AND `artist`.`album_count` > 0";
        }

        if ($inputType == 'song_artist') {
            $sql .= " AND `artist`.`song_count` > 0";
        }

        return $sql . " GROUP BY `rating`.`object_id` ORDER BY `rating` DESC, `date` DESC, `count` DESC, `table_id` DESC ";
    }

    private function getLatestSql(string $inputType, ?User $user, int $since, int $before): string
    {
        $type = Stats::validate_type($inputType);
        $sql  = "SELECT DISTINCT(`rating`.`object_id`) AS `id`, `rating`.`rating`, `rating`.`object_type` AS `type`, MAX(`rating`.`user`) AS `user`, MAX(`rating`.`date`) AS `date` FROM `rating`";
        if ($inputType == 'album_artist' || $inputType == 'song_artist') {
            $sql .= " LEFT JOIN `artist` ON `artist`.`id` = `rating`.`object_id` AND `rating`.`object_type` = 'artist'";
        }

        $sql .= ($user instanceof User)
            ? " WHERE `rating`.`object_type` = '" . $type . "' AND `rating`.`user` = '" . $user->getId() . "'"
            : " WHERE `rating`.`object_type` = '" . $type . "'";
        if (AmpConfig::get('catalog_disable') && in_array($type, ['artist', 'album', 'album_disk', 'song', 'video'])) {
            $sql .= " AND " . Catalog::get_enable_filter($type, '`object_id`');
        }

        if (AmpConfig::get('catalog_filter')) {
            $sql .= " AND" . Catalog::get_user_filter('rating_' . $type, $user?->getId() ?? -1);
        }

        if ($inputType == 'album_artist') {
            $sql .= " AND `artist`.`album_count` > 0";
        }

        if ($inputType == 'song_artist') {
            $sql .= " AND `artist`.`song_count` > 0";
        }

        if ($since > 0) {
            $sql .= " AND `rating`.`date` >= '" . $since . "'";
            if ($before > 0) {
                $sql .= " AND `rating`.`date` <= '" . $before . "'";
            }
        }

        return $sql . " GROUP BY `rating`.`object_id`, `type` ORDER BY `rating` DESC, `date` DESC ";
    }
}
