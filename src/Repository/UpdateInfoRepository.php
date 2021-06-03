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
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Module\System\Dba;

final class UpdateInfoRepository implements UpdateInfoRepositoryInterface
{
    /**
     * Updates the count of item by table name
     */
    public function updateCountByTableName(string $tableName): int
    {
        $db_results = Dba::read(
            sprintf('SELECT COUNT(`id`) FROM `%s`', Dba::escape($tableName))
        );

        $data = Dba::fetch_row($db_results);

        $value = (int) $data[0];

        $this->setCount($tableName, $value);

        return $value;
    }

    /**
     * Record when the cron has finished.
     */
    public function setLastCronDate(): void
    {
        Dba::write(
            'REPLACE INTO `update_info` SET `key`= \'cron_date\', `value`=UNIX_TIMESTAMP()'
        );
    }

    /**
     * This returns the date cron has finished.
     */
    public function getLastCronDate(): int
    {
        $db_results = Dba::read(
            'SELECT * FROM `update_info` WHERE `key` = \'cron_date\''
        );

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int) $results['value'];
        }

        return 0;
    }

    /**
     * This returns the current number of songs, videos, albums, artists, items, etc across all catalogs on the server
     *
     * @return array<string, int>
     */
    public function getServerCounts(): array
    {
        $sql = "SELECT `key`, `value` FROM `update_info`;";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[(string) $row['key']] = (int) $row['value'];
        }

        return $results;
    }

    /**
     * write the total_counts to update_info
     */
    private function setCount(string $tableName, int $value): void
    {
        Dba::write(
            'REPLACE INTO `update_info` SET `key`= ?, `value`= ?',
            [
                $tableName,
                $value
            ]
        );
    }
}
