<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\User;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use PDO;

/**
 * Manages ip-history related database access
 *
 * Table: `ip_history`
 */
final class IpHistoryRepository implements IpHistoryRepositoryInterface
{
    private DatabaseConnectionInterface $connection;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        DatabaseConnectionInterface $connection,
        ConfigContainerInterface $configContainer
    ) {
        $this->connection      = $connection;
        $this->configContainer = $configContainer;
    }

    /**
     * This returns the ip_history for the provided user
     *
     * @return Generator<array{ip: string, date: DateTimeInterface}>
     */
    public function getHistory(
        User $user,
        int $limit = 1,
        bool $distinct = false
    ): Generator {
        $group_sql = '';
        $limit_sql = '';

        if ($limit > 0) {
            $limit_sql = sprintf('LIMIT %d', $limit);
        }

        if ($distinct === true) {
            $group_sql = 'GROUP BY ip, date';
        }


        $result = $this->connection->query(
            sprintf(
                'SELECT ip, date FROM ip_history WHERE user = ? %s ORDER BY date DESC %s',
                $group_sql,
                $limit_sql,
            ),
            [
                $user->getId(),
            ]
        );

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            yield [
                'ip' => (string) inet_ntop($row['ip']),
                'date' => new DateTimeImmutable(sprintf('@%d', $row['date']))
            ];
        }
    }

    /**
     * Returns the most recent ip-address used by the provided user
     */
    public function getRecentIpForUser(User $user): ?string
    {
        $result = $this->connection->fetchOne(
            'SELECT ip FROM ip_history WHERE user = ? ORDER BY date DESC LIMIT 1',
            [
                $user->getId(),
            ]
        );

        if ($result !== false) {
            return (string) inet_ntop($result);
        }

        return null;
    }

    /**
     * Deletes outdated records
     */
    public function collectGarbage(): void
    {
        $this->connection->query(
            'DELETE FROM `ip_history` WHERE `date` < `date` - ?',
            [
                86400 * (int) $this->configContainer->get('user_ip_cardinality')
            ]
        );
    }

    /**
     * Inserts a new row into the database
     */
    public function create(
        User $user,
        string $ipAddress,
        string $userAgent,
        DateTimeInterface $date
    ): void {
        if ($ipAddress !== '') {
            $ipAddress = inet_pton($ipAddress);
        }

        $this->connection->query(
            'INSERT INTO `ip_history` (`ip`, `user`, `date`, `agent`) VALUES (?, ?, ?, ?)',
            [
                $ipAddress,
                $user->getId(),
                $date->getTimestamp(),
                $userAgent
            ]
        );
    }
}
