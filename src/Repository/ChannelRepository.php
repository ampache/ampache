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

use Ampache\Repository\Model\ChannelInterface;
use Doctrine\DBAL\Connection;

final class ChannelRepository implements ChannelRepositoryInterface
{
    private Connection $database;

    public function __construct(
        Connection $database
    ) {
        $this->database = $database;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDataById(int $channelId): array
    {
        $dbResults = $this->database->fetchAssociative(
            'SELECT * FROM `channel` WHERE `id` = ?',
            [$channelId]
        );

        if ($dbResults === false) {
            return [];
        }

        return $dbResults;
    }

    public function getNextPort(int $defaultPort): int
    {
        $maxPort = $this->database->fetchOne(
            'SELECT MAX(`port`) AS `max_port` FROM `channel`',
        );

        if ($maxPort !== null) {
            return ((int) $maxPort + 1);
        }

        return $defaultPort;
    }

    public function delete(ChannelInterface $channel): void
    {
        $this->database->executeQuery(
            'DELETE FROM `channel` WHERE `id` = ?',
            [$channel->getId()]
        );
    }

    public function updateListeners(
        int $channelId,
        int $listeners,
        int $peakListeners,
        int $connections
    ): void {
        $this->database->executeQuery(
            'UPDATE `channel` SET `listeners` = ?, `peak_listeners` = ?, `connections` = ? WHERE `id` = ?',
            [$listeners, $peakListeners, $connections, $channelId]
        );
    }

    public function updateStart(
        int $channelId,
        int $startDate,
        string $address,
        int $port,
        int $pid
    ): void {
        $this->database->executeQuery(
            'UPDATE `channel` SET `start_date` = ?, `interface` = ?, `port` = ?, `pid` = ?, `listeners` = \'0\' WHERE `id` = ?',
            [$startDate, $address, $port, $pid, $channelId]
        );
    }

    public function stop(
        int $channelId
    ): void {
        $this->database->executeQuery(
            'UPDATE `channel` SET `start_date` = \'0\', `listeners` = \'0\', `pid` = \'0\' WHERE `id` = ?',
            [$channelId]
        );
    }
}
