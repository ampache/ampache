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

    /**
     * Update the catalog mapping for various types
     */
    public function collectMappingGarbage(): void
    {
        // delete non-existent maps
        $tables = ['album', 'song', 'video', 'podcast_episode'];
        foreach ($tables as $type) {
            $sql = "DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN `$type` ON `$type`.`id`=`catalog_map`.`object_id` WHERE `catalog_map`.`object_type`='$type' AND `$type`.`id` IS NULL;";
            Dba::write($sql);
        }
        $sql = "DELETE FROM `catalog_map` WHERE `catalog_id` = 0";
        Dba::write($sql);
    }

    /**
     * Update the catalog map for a single item
     */
    public function updateMapping(int $catalogId, string $objectType, int $objectId): void
    {
        debug_event(self::class, "Updating catalog mapping for $objectType ($objectId)", 5);
        if ($objectType == 'artist') {
            $sql = "REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `song`.`catalog`, 'artist', `artist`.`id` FROM `artist` LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` WHERE `artist`.`id` = ? AND `song`.`catalog` > 0;";
            Dba::write($sql, array($objectId));
            $sql = "REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `album`.`catalog`, 'artist', `artist`.`id` FROM `artist` LEFT JOIN `album` ON `album`.`album_artist` = `artist`.`id` WHERE `artist`.`id` = ? AND `album`.`catalog` > 0;";
            Dba::write($sql, array($objectId));
        } elseif ($catalogId > 0) {
            $sql = "REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) VALUES (?, ?, ?);";
            Dba::write($sql, array($catalogId, $objectType, $objectId));
        }
    }
}
