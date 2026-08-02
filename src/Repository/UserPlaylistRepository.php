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
use PDO;

final readonly class UserPlaylistRepository implements UserPlaylistRepositoryInterface
{
    /** @var list<string> the media types a play queue may hold */
    private const array VALID_TYPES = [
        'song',
        'live_stream',
        'video',
        'podcast_episode',
    ];

    public function __construct(private DatabaseConnectionInterface $connection) {}

    /**
     * @param list<array{object_type: string, object_id: int|string, track: int|string}> $items
     */
    public function addItems(int $userId, string $client, int $time, array $items): void
    {
        $placeholders = [];
        $values       = [];
        foreach ($items as $item) {
            if (!in_array($item['object_type'], self::VALID_TYPES, true)) {
                continue;
            }

            $placeholders[] = '(?, ?, ?, ?, ?, ?)';
            $values[]       = $time;
            $values[]       = $client;
            $values[]       = $userId;
            $values[]       = $item['object_type'];
            $values[]       = $item['object_id'];
            $values[]       = $item['track'];
        }

        if ($placeholders === []) {
            return;
        }

        $this->connection->query(
            'INSERT INTO `user_playlist` (`playqueue_time`, `playqueue_client`, `user`, `object_type`, `object_id`, `track`) VALUES ' . implode(',', $placeholders) . ';',
            $values
        );
    }

    public function clear(int $userId, string $client): void
    {
        $this->connection->query(
            'DELETE FROM `user_playlist` WHERE `user` = ? AND `playqueue_client` = ?',
            [$userId, $client]
        );
    }

    public function clearCurrent(int $userId): void
    {
        $this->connection->query(
            'UPDATE `user_playlist` SET `current_track` = 0, `current_time` = 0 WHERE `user` = ?',
            [$userId]
        );
    }

    public function getCount(int $userId, string $client): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT MAX(`track`) AS `count` FROM `user_playlist` WHERE `user` = ? AND `playqueue_client` = ?',
            [$userId, $client]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getCurrentRow(int $userId): array
    {
        $row = $this->connection->fetchRow(
            'SELECT `object_type`, `object_id`, `track`, `current_track`, `current_time` FROM `user_playlist` WHERE `user` = ? AND `current_track` = 1 LIMIT 1',
            [$userId]
        );

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getItems(int $userId, string $client): array
    {
        $result = $this->connection->query(
            'SELECT `object_type`, `object_id`, `track`, `current_track`, `current_time` FROM `user_playlist` WHERE `user` = ? AND `playqueue_client` = ? ORDER BY `track`',
            [$userId, $client]
        );

        $items = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $items[] = $row;
        }

        return $items;
    }

    public function getLatestClient(int $userId): string
    {
        $row = $this->connection->fetchRow(
            'SELECT MAX(`playqueue_time`) AS `time`, `playqueue_client`, `user` FROM `user_playlist` WHERE `user` = ? GROUP BY `playqueue_client`, `user`',
            [$userId]
        );

        return ($row === false)
            ? ''
            : (string) ($row['playqueue_client'] ?? '');
    }

    public function getTime(int $userId, string $client): ?int
    {
        $time = $this->connection->fetchOne(
            'SELECT DISTINCT(`playqueue_time`) AS `time` FROM `user_playlist` WHERE `user` = ? AND `playqueue_client` = ?',
            [$userId, $client]
        );

        return ($time === false)
            ? null
            : (int) $time;
    }

    public function setCurrentByObject(int $userId, string $objectType, int $objectId, int $position): void
    {
        $this->clearCurrent($userId);

        $this->connection->query(
            'UPDATE `user_playlist` SET `current_track` = 1, `current_time` = ? WHERE `object_type` = ? AND `object_id` = ? AND `user` = ? LIMIT 1',
            [$position, $objectType, $objectId, $userId]
        );
    }

    public function setCurrentByTrack(int $userId, string $objectType, int $track, int $position): void
    {
        $this->clearCurrent($userId);

        $this->connection->query(
            'UPDATE `user_playlist` SET `current_track` = 1, `current_time` = ? WHERE `object_type` = ? AND `track` = ? AND `user` = ? LIMIT 1',
            [$position, $objectType, $track, $userId]
        );
    }
}
