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
    public function updateCountByTableName(string $tableName): void
    {
        $db_results = Dba::read(
            sprintf('SELECT COUNT(`id`) FROM `%s`', Dba::escape($tableName))
        );

        $data = Dba::fetch_row($db_results);

        Dba::write(
            'REPLACE INTO `update_info` SET `key`= ?, `value`= ?',
            [
                $tableName,
                (int) $data[0]
            ]
        );
    }
}
