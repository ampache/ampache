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
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

final readonly class FolderRepository implements FolderRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    /**
     * This cleans out unused folders
     */
    public function collectGarbage(): void
    {
        try {
            $this->connection->query('DELETE FROM `folder_map` WHERE `folder_map`.`folder_id` NOT IN (SELECT `folder`.`id` FROM `folder`);');
            $this->connection->query("DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = 'podcast_episode' AND `folder_map`.`object_id` NOT IN (SELECT `podcast_episode`.`id` FROM `podcast_episode`);");
            $this->connection->query("DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = 'song' AND `folder_map`.`object_id` NOT IN (SELECT `song`.`id` FROM `song`);");
            $this->connection->query("DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = 'video' AND `folder_map`.`object_id` NOT IN (SELECT `video`.`id` FROM `video`);");
            $this->connection->query("DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = 'folder' AND `folder_map`.`object_id` NOT IN (SELECT `folder`.`id` FROM `folder`);");
            $this->connection->query('DELETE FROM `folder` WHERE `folder`.`catalog` NOT IN (SELECT `catalog`.`id` FROM `catalog`);');
            $this->connection->query('DELETE FROM `folder` WHERE `id` NOT IN (SELECT `folder_id` FROM `folder_map`) AND `parent` IS NOT NULL AND `user` IS NULL;');
            $this->update_folder_counts();
        } catch (DatabaseException) {
            $this->logger->debug(
                'collectGarbage error',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }
    }

    public function create(string $folderName, int $catalogId, string $folderPath = '', ?int $parent_id = null): ?Folder
    {
        //$this->logger->debug('CREATE ' . $folderName . ' ' . $folderPath . ' ' . $parent_id, [LegacyLogger::CONTEXT_TYPE => self::class]);
        $folderId = Folder::create([
            'name' => $folderName,
            'catalog' => $catalogId,
            'path_name' => $folderPath,
            'parent' => $parent_id,
        ]);

        return ($folderId)
            ? new Folder($folderId)
            : null;
    }

    public function delete(int $folderId): void
    {
        $folder = new Folder($folderId);
        if ($folder->isNew() || $folder->object_count > 0) {
            return;
        }

        if (!$folder->path_name || !file_exists($folder->path_name) || rmdir($folder->path_name)) {
            $this->connection->query(
                'DELETE FROM `folder` WHERE `id` = ?;',
                [$folderId]
            );
            $this->connection->query(
                "DELETE FROM `folder_map` WHERE `folder_id` = ? OR (`object_id` = ? AND `object_type` = 'folder');",
                [$folderId, $folderId]
            );
            $this->connection->query('UPDATE `folder` SET `object_count` = (SELECT COUNT(*) FROM `folder_map` AS `map_count` WHERE `map_count`.`folder_id` = `folder`.`id`);');
        }
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

    /**
     * Reads the folders of one catalog keyed by lowercased path, for the scanner's in-process cache
     *
     * @return array<string, int>
     */
    public function getByCatalogKeyedByPathName(int $catalogId): array
    {
        $result = $this->connection->query(
            'SELECT `id`, `path_name` FROM `folder` WHERE `catalog` = ? AND `path_name` IS NOT NULL;',
            [$catalogId]
        );

        $folders = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $folders[strtolower((string) $row['path_name'])] = (int) $row['id'];
        }

        return $folders;
    }

    public function getByName(string $folderName, ?int $catalogId = null, ?int $parent = null): Folder|Podcast_Episode|Song|Video|null
    {
        $sql    = 'SELECT `folder_map`.`object_id`, `folder_map`.`object_type` FROM `folder_map` WHERE `folder_map`.`name` = ?';
        $params = [$folderName];
        if (($catalogId)) {
            $sql .= 'AND `folder_map`.`catalog` = ? ';
            $params[] = $catalogId;
        }

        if ($parent) {
            $sql .= 'AND `folder_map`.`folder_id` = ? ';
            $params[] = $parent;
        }

        if ($parent === null) {
            $sql .= 'AND `folder_map`.`folder_id` IS NULL ';
        }

        //$this->logger->debug('getByName ' . sprintf('SQL %s', $sql) . print_r([$folderName, $catalogId, $parent], true), [LegacyLogger::CONTEXT_TYPE => self::class]);

        $result = $this->connection->query($sql . 'LIMIT 1;', $params);

        if ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            switch ($row['object_type']) {
                case 'folder':
                    return new Folder($row['object_id']);
                case 'song':
                    return new Song($row['object_id']);
                case 'video':
                    return new Video($row['object_id']);
                case 'podcast_episode':
                    return new Podcast_Episode($row['object_id']);
            }
        }

        return null;
    }

    public function getByPathName(string $folderPath, int $catalogId = 0, ?string $parentPath = null): ?Folder
    {
        $sql    = 'SELECT `folder`.`id` FROM `folder` WHERE `folder`.`path_name` = ? ';
        $params = [$folderPath];
        if (($catalogId !== 0)) {
            $sql .= 'AND `folder`.`catalog` = ? ';
            $params[] = $catalogId;
        }

        if ($parentPath) {
            $sql .= 'AND `folder`.`parent` = (SELECT `id` FROM `folder` WHERE `path_name` = ?);';
            $params[] = $parentPath;
        }

        //$this->logger->debug('getByPathName ' . sprintf('SQL %s', $sql) . print_r($params, true), [LegacyLogger::CONTEXT_TYPE => self::class]);

        $rowId = $this->connection->fetchOne($sql, $params);
        if ($rowId === false) {
            return null;
        }

        return new Folder((int) $rowId);
    }

    /**
     * Returns the direct children of a folder. Pass null for the virtual root, whose children are
     * the unparented folder_map rows.
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function getChildren(?int $folderId): array
    {
        $result = ($folderId === null)
            ? $this->connection->query('SELECT `object_id`, `object_type` FROM `folder_map` WHERE `folder_id` IS NULL ORDER BY `name`;')
            : $this->connection->query('SELECT `object_id`, `object_type` FROM `folder_map` WHERE `folder_id` = ? ORDER BY `name`;', [$folderId]);

        return $this->mapObjectRows($result);
    }

    /**
     * Return the number of entries in the database...
     */
    public function getItemCount(): int
    {
        $db_results = $this->connection->query('SELECT COUNT(*) AS `count` FROM `folder`;');
        if (($results = $db_results->fetch(PDO::FETCH_ASSOC)) && array_key_exists('count', $results)) {
            return (int) $results['count'];
        }

        return 0;
    }

    /**
     * Counts everything playable below a folder, without hydrating the rows
     */
    public function getMediaCount(Folder $folder): int
    {
        $count = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM `folder_map` WHERE `folder_map`.`object_type` != 'folder' AND (`folder_map`.`folder_id` = ? OR `folder_map`.`path_name` LIKE ?);",
            [$folder->getId(), $folder->path_name . '/%']
        );

        return ($count === false)
            ? 0
            : (int) $count;
    }

    /**
     * Returns everything below a folder, optionally narrowed to a single type
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function getMedias(Folder $folder, ?string $filterType = null): array
    {
        $result = ($filterType === null)
            ? $this->connection->query(
                "SELECT `folder_map`.`object_id`, `folder_map`.`object_type` FROM `folder_map` WHERE `folder_map`.`object_type` != 'folder' AND (`folder_map`.`folder_id` = ? OR `folder_map`.`path_name` LIKE ?) ORDER BY `folder_map`.`name`;",
                [$folder->getId(), $folder->path_name . '/%']
            )
            : $this->connection->query(
                'SELECT `folder_map`.`object_id`, `folder_map`.`object_type` FROM `folder_map` WHERE `folder_map`.`object_type` = ? AND (`folder_map`.`folder_id` = ? OR `folder_map`.`path_name` LIKE ?) ORDER BY `folder_map`.`name`;',
                [$filterType, $folder->getId(), $folder->path_name . '/%']
            );

        return $this->mapObjectRows($result);
    }

    /**
     * Returns a folder's own name, null when there is no such folder
     */
    public function getNameById(int $folderId): ?string
    {
        $name = $this->connection->fetchOne(
            'SELECT `folder`.`name` AS `f_name` FROM `folder` WHERE `id` = ?;',
            [$folderId]
        );

        return ($name === false)
            ? null
            : (string) $name;
    }

    /**
     * Returns the contents of a folder. Pass null for the virtual root, which lists the top-level
     * folders themselves rather than folder_map rows.
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function getObjects(?int $folderId): array
    {
        $result = ($folderId === null)
            ? $this->connection->query("SELECT `id` AS `object_id`, 'folder' AS `object_type` FROM `folder` WHERE `parent` IS NULL;")
            : $this->connection->query('SELECT `object_id`, `object_type` FROM `folder_map` WHERE `folder_id` = ?;', [$folderId]);

        return $this->mapObjectRows($result);
    }

    /**
     * Whether the folder has any mapped children
     */
    public function hasChildren(int $folderId): bool
    {
        $result = $this->connection->query(
            'SELECT `object_id`, `object_type` FROM `folder_map` WHERE `folder_id` = ?;',
            [$folderId]
        );

        return $result->rowCount() > 0;
    }

    public function lookup(string $folderName = '', int $catalogId = 0, ?int $parent_id = null): int
    {
        $ret  = -1;
        $name = trim($folderName);

        if ($name !== '') {
            $ret    = 0;
            $sql    = 'SELECT `id` FROM `folder` WHERE `name` = ? AND `catalog` = ?';
            $params = [$name, $catalogId];
            if ($parent_id) {
                $sql .= ' AND `parent` = ?';
                $params[] = $parent_id;
            }

            //$this->logger->debug('lookup' . sprintf('SQL %s', $sql) . print_r($params, true), [LegacyLogger::CONTEXT_TYPE => self::class]);

            $result = $this->connection->fetchOne($sql, $params);

            if ($result !== false) {
                $ret = (int) $result;
            }
        }

        return $ret;
    }

    public function lookupByPathName(string $folderPath = '', int $catalogId = 0): int
    {
        $ret  = -1;
        $name = trim($folderPath);

        if ($name !== '') {
            $ret    = 0;
            $sql    = 'SELECT `id` FROM `folder` WHERE `path_name` = ? AND `catalog` = ?';
            $params = [$name, $catalogId];
            //$this->logger->debug('lookupByPathName ' . sprintf('SQL %s', $sql) . print_r($params, true), [LegacyLogger::CONTEXT_TYPE => self::class]);

            $result = $this->connection->fetchOne($sql, $params);

            if ($result !== false) {
                $ret = (int) $result;
            }
        }

        return $ret;
    }

    /**
     * Moves every folder_map row of the given type from one object onto another
     */
    public function migrateObject(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->query(
            'UPDATE `folder_map` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = ?;',
            [$newObjectId, $oldObjectId, $objectType]
        );
    }

    /**
     * Saves the folder, inserting it when it is new
     *
     * An insert writes the whole row; an update touches only the fields the edit form owns, so the
     * recorded path and creation time survive a rename.
     *
     * Returns the id of a newly created folder, null when an existing one was updated
     */
    public function persist(Folder $folder): ?int
    {
        if (!$folder->isNew()) {
            $this->connection->query(
                'UPDATE `folder` SET `name` = ?, `catalog` = ?, `parent` = ?, `update_time` = ? WHERE `id` = ?',
                [
                    $folder->name,
                    $folder->catalog,
                    $folder->parent,
                    $folder->update_time,
                    $folder->getId(),
                ]
            );

            return null;
        }

        $this->connection->query(
            'INSERT INTO `folder` (`name`, `catalog`, `parent`, `user`, `addition_time`, `update_time`, `path`, `path_name`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $folder->name,
                $folder->catalog,
                $folder->parent,
                $folder->user,
                $folder->addition_time,
                $folder->update_time,
                $folder->path,
                $folder->path_name,
            ]
        );

        return $this->connection->getLastInsertedId() ?: null;
    }

    /**
     * Update folder counts columns after large actions
     */
    public function update_folder_counts(): void
    {
        $this->connection->query('UPDATE `folder` SET `object_count` = (SELECT COUNT(*) FROM `folder_map` AS `map_count` WHERE `map_count`.`folder_id` = `folder`.`id`);');

        $this->rollUpPlayCounts();

        $this->connection->query("UPDATE `folder` SET `playable` = 1 WHERE `playable` = 0 AND `id` IN (SELECT `folder_id` FROM `folder_map` WHERE `object_type` != 'folder');");
        $this->connection->query("UPDATE `folder` SET `playable` = 0 WHERE `playable` = 1 AND `id` NOT IN (SELECT `folder_id` FROM `folder_map` WHERE `object_type` != 'folder');");
    }

    /**
     * Update mapping table after large actions
     */
    public function update_folder_map(): void
    {
        // folder
        $this->connection->query("INSERT INTO `folder_map` (`object_id`, `folder_id`, `object_type`, `name`, `catalog`, `path_name`) SELECT `id`, `parent`, 'folder', `name`, `catalog`, `path_name` FROM `folder` WHERE `id` NOT IN (SELECT `object_id` FROM `folder_map` WHERE `object_type` = 'folder');");
        // song, podcast_episode, video
        $this->connection->query("INSERT INTO folder_map (folder_id, object_id, object_type, name, catalog, path_name) SELECT f.id, s.id, 'song', SUBSTRING_INDEX(s.file, '/', -1), s.catalog, REGEXP_REPLACE(s.file, '/[^/]+$', '') FROM song s INNER JOIN folder f ON f.catalog = s.catalog AND f.path_name = REGEXP_REPLACE(s.file, '/[^/]+$', '') LEFT JOIN folder_map fm ON fm.object_id = s.id AND fm.object_type = 'song' WHERE fm.object_id IS NULL;");
    }

    /**
     * update_utime
     * sets a new update time
     */
    public function update_utime(int $folder_id, int $time = 0): void
    {
        if ($time === 0) {
            $time = time();
        }

        $sql = "UPDATE `folder` SET `update_time` = ? WHERE `id` = ?;";
        $this->connection->query($sql, [$time, $folder_id]);
    }

    /**
     * Rows whose object_type is not a known library item are dropped rather than surfaced as null
     *
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    private function mapObjectRows(PDOStatement $result): array
    {
        $results = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $objectType = LibraryItemEnum::tryFrom((string) $row['object_type']);
            if ($objectType !== null) {
                $results[] = [
                    'object_type' => $objectType,
                    'object_id' => (int) $row['object_id'],
                ];
            }
        }

        return $results;
    }

    /**
     * Sets each folder's play totals to the sum of every media item in its whole subtree
     *
     * `Stats::count()` walks the ancestry and increments every parent as a track plays, so a folder's
     * total covers what is under it, not only what is mapped directly to it. The accumulation is done in
     * PHP because the ancestry lives in a comma-separated `path`, and matching it in SQL means
     * `FIND_IN_SET` over a folder-by-folder join that no index can help.
     */
    private function rollUpPlayCounts(): void
    {
        $result = $this->connection->query(
            "SELECT `counting`.`folder_id`, SUM(`counting`.`total_count`) AS `total_count`, SUM(`counting`.`total_skip`) AS `total_skip` FROM (SELECT `smap`.`folder_id`, COALESCE(`song`.`total_count`, 0) AS `total_count`, COALESCE(`song`.`total_skip`, 0) AS `total_skip` FROM `folder_map` AS `smap` JOIN `song` ON `smap`.`object_type` = 'song' AND `smap`.`object_id` = `song`.`id` UNION ALL SELECT `vmap`.`folder_id`, COALESCE(`video`.`total_count`, 0), COALESCE(`video`.`total_skip`, 0) FROM `folder_map` AS `vmap` JOIN `video` ON `vmap`.`object_type` = 'video' AND `vmap`.`object_id` = `video`.`id` UNION ALL SELECT `pmap`.`folder_id`, COALESCE(`podcast_episode`.`total_count`, 0), COALESCE(`podcast_episode`.`total_skip`, 0) FROM `folder_map` AS `pmap` JOIN `podcast_episode` ON `pmap`.`object_type` = 'podcast_episode' AND `pmap`.`object_id` = `podcast_episode`.`id`) AS `counting` GROUP BY `counting`.`folder_id`;"
        );

        $direct = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $direct[(int) $row['folder_id']] = [(int) $row['total_count'], (int) $row['total_skip']];
        }

        $result = $this->connection->query('SELECT `id`, `path` FROM `folder`;');

        $totals = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $folderId       = (int) $row['id'];
            [$count, $skip] = $direct[$folderId] ?? [0, 0];
            if ($count === 0 && $skip === 0) {
                continue;
            }

            // the folder itself, then every ancestor its path names
            foreach ([$folderId, ...array_map(intval(...), array_filter(explode(',', (string) $row['path'])))] as $id) {
                $totals[$id] ??= [0, 0];
                $totals[$id][0] += $count;
                $totals[$id][1] += $skip;
            }
        }

        $this->connection->query('UPDATE `folder` SET `total_count` = 0, `total_skip` = 0 WHERE `total_count` > 0 OR `total_skip` > 0;');

        foreach ($totals as $folderId => [$count, $skip]) {
            $this->connection->query(
                'UPDATE `folder` SET `total_count` = ?, `total_skip` = ? WHERE `id` = ?;',
                [$count, $skip, $folderId]
            );
        }
    }
}
