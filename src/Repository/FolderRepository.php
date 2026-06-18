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
use Ampache\Repository\Model\Folder;
use PDO;

final readonly class FolderRepository implements FolderRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection)
    {
    }

    public function findById(?int $folderId = null): ?Folder
    {
        if (!$folderId) {
            return null;
        }

        $folder = new Folder($folderId);
        if ($folder->isNew()) {
            return null;
        }

        return $folder;
    }

    public function getByName(string $folderName, int $catalogId = 0, ?int $parent = null): ?Folder
    {
        $sql = 'SELECT `folder`.`id` FROM `folder` WHERE `folder`.`name` = ? AND `folder`.`catalog` = ? AND `folder`.`parent` = ? LIMIT 1;';
        //debug_event(self::class, 'getByName ' . sprintf('SQL %s', $sql) . print_r([$folderName, $catalogId, $parent], true), 5);

        $rowId = $this->connection->fetchOne($sql, [$folderName, $catalogId, $parent]);

        if ($rowId === false) {
            return null;
        }

        return new Folder((int)$rowId);
    }

    public function getByPathName(string $folderPath, int $catalogId = 0, ?string $parentPath = null): ?Folder
    {
        $sql = ($parentPath)
            ? 'SELECT `folder`.`id` FROM `folder` WHERE `folder`.`path_name` = ? AND `folder`.`catalog` = ? AND `folder`.`parent` = (SELECT `id` FROM `folder` WHERE `path_name` = ?);'
            : 'SELECT `folder`.`id` FROM `folder` WHERE `folder`.`path_name` = ? AND `folder`.`catalog` = ? AND `folder`.`parent` IS NULL;';
        $params = ($parentPath)
            ? [$folderPath, $catalogId, $parentPath]
            : [$folderPath, $catalogId];
        //debug_event(self::class, 'getByPathName ' . sprintf('SQL %s', $sql) . print_r($params, true), 5);

        $rowId = $this->connection->fetchOne($sql, $params);
        if ($rowId === false) {
            return null;
        }

        return new Folder((int)$rowId);
    }

    /**
     * Return the list of all available folders
     *
     * @return string[]
     */
    public function getAll(): array
    {
        $result = $this->connection->query('SELECT `id`, `name` FROM `folder`');

        $folders = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $folders[(int) $row['id']] = $row['name'];
        }

        return $folders;
    }

    public function lookup(string $folderName = '', int $catalogId = 0, ?int $parent = null): int
    {
        $ret  = -1;
        $name = trim($folderName);

        if ($name !== '') {
            $ret    = 0;
            $sql    = 'SELECT `id` FROM `folder` WHERE `name` = ? AND `catalog` = ?';
            $params = [$name, $catalogId];
            if ($parent) {
                $sql .= ' AND `parent` = ?';
                $params[] = $parent;
            }
            //debug_event(self::class, 'lookup' . sprintf('SQL %s', $sql) . print_r($params, true), 5);

            $result = $this->connection->fetchOne($sql, $params);

            if ($result !== false) {
                $ret = (int) $result;
            }
        }

        return $ret;
    }

    public function lookupByPathName(string $folderPath = '', int $catalogId = 0, ?int $parent = null): int
    {
        $ret  = -1;
        $name = trim($folderPath);

        if ($name !== '') {
            $ret    = 0;
            $sql    = 'SELECT `id` FROM `folder` WHERE `path_name` = ? AND `catalog` = ?';
            $params = [$name, $catalogId];
            if ($parent) {
                $sql .= ' AND `parent` = ?';
                $params[] = $parent;
            }
            //debug_event(self::class, 'lookupByPathName ' . sprintf('SQL %s', $sql) . print_r($params, true), 5);

            $result = $this->connection->fetchOne($sql, $params);

            if ($result !== false) {
                $ret = (int) $result;
            }
        }

        return $ret;
    }

    public function create(string $folderName, int $catalogId, string $folderPath = '', ?int $parent = null): ?Folder
    {
        // don't allow duplicate podcasts
        $folderId = $this->lookup($folderPath, $catalogId);
        if (!$folderId) {
            $folderId = Folder::create([
                'name' => $folderName,
                'catalog' => $catalogId,
                'path_name' => $folderPath,
                'parent' => $parent,
            ]);
        }

        return ($folderId)
            ? new Folder($folderId)
            : null;
    }

    public function delete(int $folderId): void
    {
        $this->connection->query(
            'DELETE FROM `folder` WHERE `id` = ?;',
            [$folderId]
        );
        $this->connection->query(
            'DELETE FROM `folder_map` WHERE `folder_id` = ? OR (`object_id` = ? AND `object_type` = \'folder\');',
            [$folderId, $folderId]
        );
        $this->connection->query('UPDATE `folder` SET `object_count` = (SELECT COUNT(*) FROM `folder_map` AS `map_count` WHERE `map_count`.`folder_id` = `folder`.`id`);');
    }

    /**
     * Update mapping table after large actions
     */
    public function update_folder_map(): void
    {
        // folder
        $this->connection->query('INSERT INTO `folder_map` (`object_id`, `folder_id`, `object_type`, `name`, `catalog`, `path_name`) SELECT `id` AS `object_id`, `parent` AS `folder_id`, \'folder\' AS `object_type`, `name`, `catalog`, `path_name` FROM `folder` WHERE `id` NOT IN (SELECT `object_id` FROM `folder_map` WHERE `object_type` = \'folder\');');
        // song
        $this->connection->query('INSERT INTO `folder_map` (`folder_id`, `object_id`, `object_type`, `name`, `catalog`, `path_name`) SELECT `folder`.`id` AS `folder_id`, `source`.`object_id`, \'song\' AS `object_type`, `source`.`filename` AS `name`, `source`.`catalog`, `source`.`dir` AS `path_name` FROM (SELECT `song`.`id` AS `object_id`, `song`.`title` AS `name`, `song`.`catalog`, `song`.`file`, SUBSTRING_INDEX(`song`.`file`, \'/\', -1) AS `filename`, SUBSTRING(`song`.`file`, 1, LENGTH(`song`.`file`) - LENGTH(SUBSTRING_INDEX(`song`.`file`, \'/\', -1)) - 1) AS `dir` FROM `song`) `source` JOIN `folder` ON `folder`.`path_name` = `source`.`dir` AND `folder`.`catalog` = `source`.`catalog` LEFT JOIN `folder_map` ON `folder_map`.`object_id` = `source`.`object_id` AND `folder_map`.`object_type` = \'song\' WHERE `folder_map`.`object_id` IS NULL;');
        // video
        $this->connection->query('INSERT INTO `folder_map` (`folder_id`, `object_id`, `object_type`, `name`, `catalog`, `path_name`) SELECT `folder`.`id` AS `folder_id`, `source`.`object_id`, \'video\' AS `object_type`, `source`.`filename` AS `name`, `source`.`catalog`, `source`.`dir` AS `path_name` FROM (SELECT `video`.`id` AS `object_id`, `video`.`title` AS `name`, `video`.`catalog`, `video`.`file`, SUBSTRING_INDEX(`video`.`file`, \'/\', -1) AS `filename`, SUBSTRING(`video`.`file`, 1, LENGTH(`video`.`file`) - LENGTH(SUBSTRING_INDEX(`video`.`file`, \'/\', -1)) - 1) AS `dir` FROM `video`) `source` JOIN `folder` ON `folder`.`path_name` = `source`.`dir` AND `folder`.`catalog` = `source`.`catalog` LEFT JOIN `folder_map` ON `folder_map`.`object_id` = `source`.`object_id` AND `folder_map`.`object_type` = \'video\' WHERE `folder_map`.`object_id` IS NULL;');
        // podcast_episode
        $this->connection->query('INSERT INTO `folder_map` (`folder_id`, `object_id`, `object_type`, `name`, `catalog`, `path_name`) SELECT `folder`.`id` AS `folder_id`, `source`.`object_id`, \'podcast_episode\' AS `object_type`, `source`.`filename` AS `name`, `source`.`catalog`, `source`.`dir` AS `path_name` FROM (SELECT `podcast_episode`.`id` AS `object_id`, `podcast_episode`.`title` AS `name`, `podcast_episode`.`catalog`, `podcast_episode`.`file`, SUBSTRING_INDEX(`podcast_episode`.`file`, \'/\', -1) AS `filename`, SUBSTRING(`podcast_episode`.`file`, 1, LENGTH(`podcast_episode`.`file`) - LENGTH(SUBSTRING_INDEX(`podcast_episode`.`file`, \'/\', -1)) - 1) AS `dir` FROM `podcast_episode`) `source` JOIN `folder` ON `folder`.`path_name` = `source`.`dir` AND `folder`.`catalog` = `source`.`catalog` LEFT JOIN `folder_map` ON `folder_map`.`object_id` = `source`.`object_id` AND `folder_map`.`object_type` = \'podcast_episode\' WHERE `folder_map`.`object_id` IS NULL;');
    }

    /**
     * Update folder counts columns after large actions
     */
    public function update_folder_counts(): void
    {
        $this->connection->query('UPDATE `folder` SET `object_count` = (SELECT COUNT(*) FROM `folder_map` AS `map_count` WHERE `map_count`.`folder_id` = `folder`.`id`);');
        $this->connection->query('UPDATE `folder` SET `total_count` = (SELECT SUM(`song`.`total_count`) FROM `folder_map` AS `map_count` LEFT JOIN `song` ON `object_type` = \'song\' AND `object_id` = `song`.`id` WHERE `map_count`.`folder_id` = `folder`.`id`);');
        $this->connection->query('UPDATE `folder` SET `total_skip` = (SELECT SUM(`song`.`total_skip`) FROM `folder_map` AS `map_count` LEFT JOIN `song` ON `object_type` = \'song\' AND `object_id` = `song`.`id` WHERE `map_count`.`folder_id` = `folder`.`id`);');
        $this->connection->query('UPDATE `folder` SET `total_count` = (SELECT SUM(`video`.`total_count`) FROM `folder_map` AS `map_count` LEFT JOIN `video` ON `object_type` = \'video\' AND `object_id` = `video`.`id` WHERE `map_count`.`folder_id` = `folder`.`id`);');
        $this->connection->query('UPDATE `folder` SET `total_skip` = (SELECT SUM(`video`.`total_skip`) FROM `folder_map` AS `map_count` LEFT JOIN `video` ON `object_type` = \'video\' AND `object_id` = `video`.`id` WHERE `map_count`.`folder_id` = `folder`.`id`);');
        $this->connection->query('UPDATE `folder` SET `total_count` = (SELECT SUM(`podcast_episode`.`total_count`) FROM `folder_map` AS `map_count` LEFT JOIN `podcast_episode` ON `object_type` = \'podcast_episode\' AND `object_id` = `podcast_episode`.`id` WHERE `map_count`.`folder_id` = `folder`.`id`);');
        $this->connection->query('UPDATE `folder` SET `total_skip` = (SELECT SUM(`podcast_episode`.`total_skip`) FROM `folder_map` AS `map_count` LEFT JOIN `podcast_episode` ON `object_type` = \'podcast_episode\' AND `object_id` = `podcast_episode`.`id` WHERE `map_count`.`folder_id` = `folder`.`id`);');
    }

    /**
     * This cleans out unused folders
     */
    public function collectGarbage(): void
    {
        try {
            $this->connection->query('DELETE FROM `folder_map` WHERE `folder_map`.`folder_id` NOT IN (SELECT `folder`.`id` FROM `folder`);');
            $this->connection->query('DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = \'podcast_episode\' AND `folder_map`.`object_id` NOT IN (SELECT `podcast_episode`.`id` FROM `podcast_episode`);');
            $this->connection->query('DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = \'song\' AND `folder_map`.`object_id` NOT IN (SELECT `song`.`id` FROM `song`);');
            $this->connection->query('DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = \'video\' AND `folder_map`.`object_id` NOT IN (SELECT `video`.`id` FROM `video`);');
            $this->connection->query('DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = \'folder\' AND `folder_map`.`object_id` NOT IN (SELECT `folder`.`id` FROM `folder`);');
            $this->connection->query('DELETE FROM `folder` WHERE `folder`.`catalog` NOT IN (SELECT `catalog`.`id` FROM `catalog`);');
            $this->connection->query('DELETE FROM `folder` WHERE `id` NOT IN (SELECT `folder_id` FROM `folder_map`) AND `parent` IS NOT NULL AND `user` IS NULL;');
            $this->update_folder_counts();
        } catch (DatabaseException) {
            debug_event(self::class, 'collectGarbage error', 5);
        }
    }
}
