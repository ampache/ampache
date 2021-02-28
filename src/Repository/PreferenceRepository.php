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

use Ampache\Module\Cache\DatabaseObjectCacheInterface;
use Ampache\Module\System\Dba;

final class PreferenceRepository implements PreferenceRepositoryInterface
{
    private DatabaseObjectCacheInterface $databaseObjectCache;

    public function __construct(
        DatabaseObjectCacheInterface $databaseObjectCache
    ) {
        $this->databaseObjectCache = $databaseObjectCache;
    }

    /**
     * This takes a name and returns the id
     */
    public function getIdByName(string $name): int
    {
        $name = Dba::escape($name);

        $cacheItem = $this->databaseObjectCache->retrieve('id_from_name', $name);

        if ($cacheItem !== []) {
            return (int) $cacheItem['id'];
        }

        $db_results = Dba::read(
            'SELECT `id` FROM `preference` WHERE `name` = ?',
            [$name]
        );
        $row = Dba::fetch_assoc($db_results);

        $this->databaseObjectCache->add('id_from_name', $name, $row);

        return (int) $row['id'];
    }

    /**
     * This returns a nice flat array of all of the possible preferences for the specified user
     *
     * @return array<array<string, mixed>>
     */
    public function getAll(int $userId): array
    {
        $userId     = Dba::escape($userId);
        $user_limit = ($userId != -1) ? "AND `preference`.`catagory` != 'system'" : "";

        $sql = "SELECT `preference`.`id`, `preference`.`name`, `preference`.`description`, `preference`.`level`," .
            " `preference`.`type`, `preference`.`catagory`, `preference`.`subcatagory`, `user_preference`.`value`" .
            " FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` " .
            " WHERE `user_preference`.`user` = ? AND `preference`.`catagory` != 'internal' $user_limit " .
            " ORDER BY `preference`.`subcatagory`, `preference`.`description`";

        $db_results = Dba::read($sql, [$userId]);
        $results    = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'level' => $row['level'],
                'description' => $row['description'],
                'value' => $row['value'],
                'type' => $row['type'],
                'category' => $row['catagory'],
                'subcategory' => $row['subcatagory']
            ];
        }

        return $results;
    }

    /**
     * This returns a nice flat array of all of the possible preferences for the specified user
     *
     * @return array<array<string, mixed>>
     */
    public function get(string $preferenceName, int $userId): array
    {
        $userId    = Dba::escape($userId);
        $userLimit = ($userId != -1) ? 'AND `preference`.`catagory` != \'system\'' : "";

        $sql = 'SELECT `preference`.`id`, `preference`.`name`, `preference`.`description`, `preference`.`level`,' .
            ' `preference`.`type`, `preference`.`catagory`, `preference`.`subcatagory`, `user_preference`.`value`' .
            ' FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference`=`preference`.`id` ' .
            ' WHERE `preference`.`name` = ? AND `user_preference`.`user`= ? AND `preference`.`catagory` != \'internal\' ' . $userLimit .
            ' ORDER BY `preference`.`subcatagory`, `preference`.`description`';

        $db_results = Dba::read($sql, [$preferenceName, $userId]);
        $results    = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'level' => $row['level'],
                'description' => $row['description'],
                'value' => $row['value'],
                'type' => $row['type'],
                'category' => $row['catagory'],
                'subcategory' => $row['subcatagory']
            ];
        }

        return $results;
    }

    /**
     * This deletes the specified preference by id
     */
    public function deleteById(int $preferenceId): void
    {
        Dba::write(
            'DELETE FROM `preference` WHERE `id` = ?',
            [$preferenceId]
        );

        $this->cleanPreferences();
    }

    /**
     * This deletes the specified preference by name
     */
    public function deleteByName(string $preferenceName): void
    {
        Dba::write(
            'DELETE FROM `preference` WHERE `name` = ?',
            [$preferenceName]
        );

        $this->cleanPreferences();
    }

    /**
     * This removes any garbage
     */
    public function cleanPreferences(): void
    {
        // First remove garbage
        Dba::write('DELETE FROM `user_preference` USING `user_preference` LEFT JOIN `preference` ON `preference`.`id`=`user_preference`.`preference` WHERE `preference`.`id` IS NULL');
    }

    /**
     * This inserts a new preference into the preference table
     * it does NOT sync up the users, that should be done independently
     *
     * @param string $name
     * @param string $description
     * @param int|string $default
     * @param int $level
     * @param string $type
     * @param string $category
     * @param null|string $subcategory
     */
    public function add(
        string $name,
        string $description,
        $default,
        int $level,
        string $type,
        string $category,
        ?string $subcategory = null
    ): bool {
        if ($subcategory !== null) {
            $subcategory = strtolower((string)$subcategory);
        }
        $db_results = Dba::write(
            'INSERT INTO `preference` (`name`, `description`, `value`, `level`, `type`, `catagory`, `subcatagory`) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$name, $description, $default, $level, $type, $category, $subcategory]
        );

        if (!$db_results) {
            return false;
        }
        $pref_id    = Dba::insert_id();
        $params     = [$pref_id, $default];
        $db_results = Dba::write(
            'INSERT INTO `user_preference` VALUES (-1,?,?)',
            $params
        );
        if (!$db_results) {
            return false;
        }
        if ($category !== "system") {
            $db_results = Dba::write(
                'INSERT INTO `user_preference` SELECT `user`.`id`, ?, ? FROM `user`',
                $params
            );
            if (!$db_results) {
                return false;
            }
        }

        return true;
    }
}
