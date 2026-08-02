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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\Database\Exception\InsertIdInvalidException;
use Ampache\Module\System\LegacyLogger;
use PDO;
use Psr\Log\LoggerInterface;

final readonly class DemocraticRepository implements DemocraticRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    public function addVote(int $rowId, ?int $userId, string $sessionId, int $date): void
    {
        // `user_vote`.`user` is NOT NULL, so a voter with no account has nothing that can be stored
        try {
            $this->connection->query(
                'INSERT INTO `user_vote` (`user`, `object_id`, `date`, `sid`) VALUES (?, ?, ?, ?)',
                [$userId, $rowId, $date, $sessionId]
            );
        } catch (DatabaseException) {
            $this->logger->warning(
                'addVote failed for row ' . $rowId,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }
    }

    public function delete(int $democraticId): void
    {
        $this->connection->query('DELETE FROM `democratic` WHERE `id` = ?;', [$democraticId]);
        $this->connection->query('DELETE FROM `tmp_playlist` WHERE `session` = ?;', [$democraticId]);
    }

    public function deleteRow(int $rowId): void
    {
        $this->connection->query('DELETE FROM `user_vote` WHERE `object_id` = ?', [$rowId]);
        $this->connection->query('DELETE FROM `tmp_playlist_data` WHERE `id` = ?', [$rowId]);
    }

    public function deleteUnconnectedVotes(): void
    {
        $this->connection->query(
            'DELETE FROM `user_vote` WHERE `user_vote`.`sid` NOT IN (SELECT `session`.`id` FROM `session`)'
        );
    }

    public function deleteVote(int|string $rowId, ?int $userId, string $sessionId): void
    {
        // a logged out voter is only known by their session, so that is what identifies their vote
        $sql    = 'DELETE FROM `user_vote` WHERE `object_id` = ? ';
        $params = [$rowId];
        if ($userId !== null && $userId > 0) {
            $sql .= 'AND `user` = ?';
            $params[] = $userId;
        } else {
            $sql .= 'AND `user_vote`.`sid` = ? ';
            $params[] = $sessionId;
        }

        $this->connection->query($sql, $params);
    }

    public function deleteVotesForPlaylist(int $tmpPlaylistId): void
    {
        $this->connection->query(
            'DELETE FROM `user_vote` USING `user_vote` LEFT JOIN `tmp_playlist_data` ON `user_vote`.`object_id` = `tmp_playlist_data`.`id` WHERE `tmp_playlist_data`.`tmp_playlist` = ?;',
            [$tmpPlaylistId]
        );
    }

    public function findByAccessLevel(int $accessLevel): ?int
    {
        $democraticId = $this->connection->fetchOne(
            'SELECT `id` FROM `democratic` WHERE `level` <= ? ORDER BY `level` DESC, `primary` DESC',
            [$accessLevel]
        );

        return ($democraticId === false)
            ? null
            : (int) $democraticId;
    }

    public function findRandomSongId(string $catalogFilter): ?int
    {
        $songId = $this->connection->fetchOne(
            "SELECT `id` FROM `song` WHERE `enabled`='1' " . $catalogFilter . ' ORDER BY RAND() LIMIT 1'
        );

        return ($songId === false)
            ? null
            : (int) $songId;
    }

    public function findRowId(string $objectType, int $tmpPlaylistId, int $objectId): ?int
    {
        $rowId = $this->connection->fetchOne(
            'SELECT `id` FROM `tmp_playlist_data` WHERE `object_type` = ? AND `tmp_playlist` = ? AND `object_id` = ?;',
            [$objectType, $tmpPlaylistId, $objectId]
        );

        return ($rowId === false)
            ? null
            : (int) $rowId;
    }

    /**
     * @return list<int>
     */
    public function getAllIds(): array
    {
        $result = $this->connection->query('SELECT `id` FROM `democratic` ORDER BY `name`');

        $ids = [];
        while ($id = $result->fetchColumn()) {
            $ids[] = (int) $id;
        }

        return $ids;
    }

    /**
     * @return list<array{object_type: string, object_id: int, id: int}>
     */
    public function getItems(int $tmpPlaylistId, ?int $limit = null): array
    {
        $sql = 'SELECT `tmp_playlist_data`.`object_type`, `tmp_playlist_data`.`object_id`, `tmp_playlist_data`.`id` FROM `tmp_playlist_data` INNER JOIN `user_vote` ON `user_vote`.`object_id` = `tmp_playlist_data`.`id` WHERE `tmp_playlist_data`.`tmp_playlist` = ? GROUP BY 1, 2, 3 ORDER BY COUNT(*) DESC, MAX(`user_vote`.`date`), MAX(`tmp_playlist_data`.`id`) ';
        if ($limit !== null) {
            $sql .= 'LIMIT ' . $limit;
        }

        $result = $this->connection->query($sql, [$tmpPlaylistId]);

        $items = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $items[] = [
                'object_type' => (string) $row['object_type'],
                'object_id' => (int) $row['object_id'],
                'id' => (int) $row['id'],
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTmpPlaylistRow(int $democraticId): array
    {
        $row = $this->connection->fetchRow('SELECT * FROM `tmp_playlist` WHERE `session` = ?', [$democraticId]);

        return ($row === false)
            ? []
            : $row;
    }

    public function getVoteCount(int $rowId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(`user`) AS `count` FROM `user_vote` WHERE `object_id` = ?',
            [$rowId]
        );
    }

    /**
     * @param list<int|string> $rowIds
     * @return array<int, int>
     */
    public function getVoteCounts(array $rowIds): array
    {
        if ($rowIds === []) {
            return [];
        }

        $result = $this->connection->query(
            sprintf(
                'SELECT `object_id`, COUNT(`user`) AS `count` FROM `user_vote` WHERE `object_id` IN (%s) GROUP BY `object_id`',
                implode(',', array_map(intval(...), $rowIds))
            )
        );

        $counts = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $counts[(int) $row['object_id']] = (int) $row['count'];
        }

        return $counts;
    }

    public function hasVoted(string $objectType, int $objectId, int $tmpPlaylistId, ?int $userId, string $sessionId): bool
    {
        $sql    = 'SELECT `tmp_playlist_data`.`object_id` FROM `user_vote` INNER JOIN `tmp_playlist_data` ON `tmp_playlist_data`.`id`=`user_vote`.`object_id` WHERE `tmp_playlist_data`.`object_type` = ? AND `tmp_playlist_data`.`object_id` = ? AND `tmp_playlist_data`.`tmp_playlist` = ? ';
        $params = [$objectType, $objectId, $tmpPlaylistId];
        if ($userId !== null && $userId > 0) {
            $sql .= 'AND `user_vote`.`user` = ? ';
            $params[] = $userId;
        } else {
            $sql .= 'AND `user_vote`.`sid` = ? ';
            $params[] = $sessionId;
        }

        return $this->connection->fetchOne($sql, $params) !== false;
    }

    public function insert(string $name, int $basePlaylist, int $cooldown, int $level, int $userId, int $isDefault): ?int
    {
        try {
            $this->connection->query(
                'INSERT INTO `democratic` (`name`, `base_playlist`, `cooldown`, `level`, `user`, `primary`) VALUES (?, ?, ?, ?, ?, ?)',
                [$name, $basePlaylist, $cooldown, $level, $userId, $isDefault]
            );

            return $this->connection->getLastInsertedId();
        } catch (DatabaseException|InsertIdInvalidException) {
            return null;
        }
    }

    public function insertRow(int $tmpPlaylistId, int $objectId, string $objectType, int $track): ?int
    {
        try {
            $this->connection->query(
                'INSERT INTO `tmp_playlist_data` (`tmp_playlist`, `object_id`, `object_type`, `track`) VALUES (?, ?, ?, ?)',
                [$tmpPlaylistId, $objectId, $objectType, $track]
            );

            return $this->connection->getLastInsertedId();
        } catch (DatabaseException|InsertIdInvalidException) {
            return null;
        }
    }

    public function pruneTracks(): void
    {
        // this deletes data without votes, if it's a voting democratic playlist
        $this->connection->query(
            "DELETE FROM `tmp_playlist_data` USING `tmp_playlist_data` LEFT JOIN `user_vote` ON `tmp_playlist_data`.`id`=`user_vote`.`object_id` LEFT JOIN `tmp_playlist` ON `tmp_playlist`.`id`=`tmp_playlist_data`.`tmp_playlist` WHERE `user_vote`.`object_id` IS NULL AND `tmp_playlist`.`type` = 'vote'"
        );
    }

    public function pruneVotes(): void
    {
        $this->connection->query(
            'DELETE FROM `user_vote` USING `user_vote` LEFT JOIN `tmp_playlist_data` ON `user_vote`.`object_id`=`tmp_playlist_data`.`id` WHERE `tmp_playlist_data`.`id` IS NULL'
        );
    }

    public function update(int $democraticId, string $name, int $basePlaylist, int $cooldown, int $isDefault, int $level): void
    {
        $this->connection->query(
            'UPDATE `democratic` SET `name` = ?, `base_playlist` = ?, `cooldown` = ?, `primary` = ?, `level` = ? WHERE `id` = ?',
            [$name, $basePlaylist, $cooldown, $isDefault, $level, $democraticId]
        );
    }
}
