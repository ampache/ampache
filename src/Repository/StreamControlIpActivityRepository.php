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
use Ampache\Module\System\LegacyLogger;
use Psr\Log\LoggerInterface;

/**
 * Manages stream-control-ip related database access
 *
 * Table: `tmp_stream_control`
 */
final readonly class StreamControlIpActivityRepository implements StreamControlIpActivityRepositoryInterface
{
    /** A safety-net ceiling, independent of any plugin's configured `_days` window */
    private const int MAX_RETENTION_DAYS = 90;

    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    public function collectGarbage(): void
    {
        try {
            $this->connection->query(
                'DELETE FROM `tmp_stream_control` WHERE `date` < ?',
                [time() - (86400 * self::MAX_RETENTION_DAYS)]
            );
        } catch (DatabaseException) {
            $this->logger->debug(
                'collectGarbage error',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }
    }

    public function record(string $ip, int $date, int $hits, int $size, int $time): void
    {
        $this->connection->query(
            'INSERT INTO `tmp_stream_control` (`ip`, `date`, `hits`, `size`, `time`) VALUES (?, ?, ?, ?, ?)',
            [$ip, $date, $hits, $size, $time]
        );
    }

    public function sumBandwidth(string $ip, int $startDate, int $endDate): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT IFNULL(SUM(`size`), 0) FROM `tmp_stream_control` WHERE `ip` = ? AND `date` >= ? AND `date` <= ?',
            [$ip, $startDate, $endDate]
        );
    }

    public function sumHits(string $ip, int $startDate, int $endDate): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT IFNULL(SUM(`hits`), 0) FROM `tmp_stream_control` WHERE `ip` = ? AND `date` >= ? AND `date` <= ?',
            [$ip, $startDate, $endDate]
        );
    }

    public function sumTime(string $ip, int $startDate, int $endDate): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT IFNULL(SUM(`time`), 0) FROM `tmp_stream_control` WHERE `ip` = ? AND `date` >= ? AND `date` <= ?',
            [$ip, $startDate, $endDate]
        );
    }
}
