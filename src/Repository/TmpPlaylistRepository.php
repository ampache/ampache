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

final readonly class TmpPlaylistRepository implements TmpPlaylistRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    public function addItem(int $playlistId, int $objectId, string $objectType): void
    {
        $this->connection->query(
            'INSERT INTO `tmp_playlist_data` (`object_id`, `tmp_playlist`, `object_type`) VALUES (?, ?, ?)',
            [$objectId, $playlistId, $objectType]
        );
    }

    public function collectGarbage(): void
    {
        $statements = [
            "DELETE FROM `tmp_playlist` USING `tmp_playlist` LEFT JOIN `session` ON `session`.`id`=`tmp_playlist`.`session` WHERE `session`.`id` IS NULL AND `tmp_playlist`.`type` != 'vote'",
            'DELETE FROM `tmp_playlist_data` USING `tmp_playlist_data` LEFT JOIN `tmp_playlist` ON `tmp_playlist_data`.`tmp_playlist`=`tmp_playlist`.`id` WHERE `tmp_playlist`.`id` IS NULL',
        ];

        foreach ($statements as $sql) {
            try {
                $this->connection->query($sql);
            } catch (DatabaseException) {
                $this->logger->debug(
                    'collectGarbage error: ' . $sql,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    public function countItems(int $playlistId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(`tmp_playlist_data`.`id`) FROM `tmp_playlist_data` WHERE `tmp_playlist_data`.`tmp_playlist` = ?',
            [$playlistId]
        );
    }

    public function create(string $sessionId, string $type, string $objectType): ?int
    {
        $this->connection->query(
            'INSERT INTO `tmp_playlist` (`session`, `type`, `object_type`) VALUES (?, ?, ?)',
            [$sessionId, $type, $objectType]
        );

        try {
            return $this->connection->getLastInsertedId();
        } catch (InsertIdInvalidException) {
            return null;
        }
    }

    public function deleteItemByRowId(int $rowId): void
    {
        $this->connection->query('DELETE FROM `tmp_playlist_data` WHERE `id` = ?', [$rowId]);
    }

    public function deleteItems(int $playlistId): void
    {
        $this->connection->query('DELETE FROM `tmp_playlist_data` WHERE `tmp_playlist` = ?', [$playlistId]);
    }

    public function deleteOtherSessionPlaylists(string $sessionId, int $playlistId): void
    {
        $this->connection->query(
            'DELETE FROM `tmp_playlist_data` WHERE `tmp_playlist` IN (SELECT `id` FROM `tmp_playlist` WHERE `session` = ? AND `id` != ?)',
            [$sessionId, $playlistId]
        );
        $this->connection->query(
            'DELETE FROM `tmp_playlist` WHERE `session` = ? AND `id` != ?',
            [$sessionId, $playlistId]
        );
    }

    public function findBySession(string $sessionId): ?int
    {
        $playlistId = $this->connection->fetchOne(
            'SELECT `id` FROM `tmp_playlist` WHERE `session` = ?',
            [$sessionId]
        );

        return ($playlistId === false)
            ? null
            : (int) $playlistId;
    }

    public function findByUsername(string $username): ?int
    {
        $playlistId = $this->connection->fetchOne(
            'SELECT `tmp_playlist`.`id` FROM `tmp_playlist` LEFT JOIN `session` ON `session`.`id`=`tmp_playlist`.`session` WHERE `session`.`username` = ? ORDER BY `session`.`expire` DESC',
            [$username]
        );

        return ($playlistId === false)
            ? null
            : (int) $playlistId;
    }

    /**
     * @return list<array{object_type: string, id: int, object_id: int}>
     */
    public function getItems(int $playlistId, int $limit = 0): array
    {
        // filtering on `tmp_playlist` gets the order from the primary key InnoDB carries in every secondary index
        $sql = 'SELECT `tmp_playlist_data`.`object_type`, `tmp_playlist_data`.`id`, `tmp_playlist_data`.`object_id` FROM `tmp_playlist_data` WHERE `tmp_playlist_data`.`tmp_playlist` = ? ORDER BY `id`';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        $result = $this->connection->query($sql, [$playlistId]);

        $items = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $items[] = [
                'object_type' => (string) $row['object_type'],
                'id' => (int) $row['id'],
                'object_id' => (int) $row['object_id'],
            ];
        }

        return $items;
    }

    public function getNextObjectId(int $playlistId): ?int
    {
        $objectId = $this->connection->fetchOne(
            'SELECT `object_id` FROM `tmp_playlist_data` WHERE `tmp_playlist` = ? ORDER BY `id` LIMIT 1',
            [$playlistId]
        );

        return ($objectId === false)
            ? null
            : (int) $objectId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRow(int $playlistId): array
    {
        $row = $this->connection->fetchRow('SELECT * FROM `tmp_playlist` WHERE `id` = ?;', [$playlistId]);

        return ($row === false)
            ? []
            : $row;
    }

    public function hasItems(int $playlistId): bool
    {
        return $this->connection->fetchOne(
            'SELECT 1 FROM `tmp_playlist_data` WHERE `tmp_playlist_data`.`tmp_playlist` = ? LIMIT 1',
            [$playlistId]
        ) !== false;
    }
}
