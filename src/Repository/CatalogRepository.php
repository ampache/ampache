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

use Ampache\Module\System\Dba;

final class CatalogRepository implements CatalogRepositoryInterface
{
    /**
     * Pull all the current catalogs and return a list of ids
     * of what you find
     *
     * @return int[]
     */
    public function getList(?string $filterType = null): array
    {
        $params = array();
        $sql    = "SELECT `id` FROM `catalog` ";
        if ($filterType !== null) {
            $sql .= "WHERE `gather_types` = ? ";
            $params[] = $filterType;
        }
        $sql .= "ORDER BY `name`";
        $db_results = Dba::read($sql, $params);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Returne the date for last updates, additions and cleanup
     *
     * @return array<string, string>
     */
    public function getLastActionDates(): array
    {
        // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
        $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
        $db_results = Dba::read($sql);

        return Dba::fetch_assoc($db_results);
    }

    /**
     * Migrate an object associated catalog to a new object
     */
    public function migrateMap(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $sql    = 'UPDATE IGNORE `catalog_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?';
        $params = [$newObjectId, $objectType, $oldObjectId];

        Dba::write($sql, $params);
    }
}
