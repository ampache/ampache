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

    public function findById(int $folderId): ?Folder
    {
        $folder = new Folder($folderId);
        if ($folder->isNew()) {
            return null;
        }

        return $folder;
    }

    /**
     * @return string[]
     */
    public function getByArtist(int $artistId): array
    {
        $folders = [];

        $result = $this->connection->query(
            'SELECT `folder`.`id`, `folder`.`name` FROM `folder` LEFT JOIN `folder_map` ON `folder_map`.`folder_id` = `folder`.`id` WHERE `folder_map`.`object_id` = ?',
            [$artistId]
        );

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $folders[(int) $row['id']] = $row['name'];
        }

        return $folders;
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
     * Return the list of all available folders
     */
    public function getPathName(int $folderId): string
    {
        $result = $this->connection->query('SELECT`name` FROM `folder` WHERE `id` = ?', [$folderId]);

        $folders = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $folders[(int) $row['id']] = $row['name'];
        }

        $result = $this->connection->fetchOne('SELECT`name` FROM `folder` WHERE `id` = ?', [$folderId]);

        return $result ?: '';
    }

    public function lookup(string $folderName, int $folderId = 0): int
    {
        $ret  = -1;
        $name = trim($folderName);

        if ($name !== '') {
            $ret    = 0;
            $sql    = 'SELECT `id` FROM `folder` WHERE `name` = ?';
            $params = [$name];
            if ($folderId > 0) {
                $sql .= ' AND `id` != ?';
                $params[] = $folderId;
            }

            $result = $this->connection->fetchOne($sql, $params);

            if ($result !== false) {
                $ret = (int) $result;
            }
        }

        return $ret;
    }

    public function delete(int $folderId): void
    {
        $this->connection->query(
            'DELETE FROM `folder` WHERE `id` = ?',
            [$folderId]
        );
    }

    /**
     * This cleans out unused folders
     */
    public function collectGarbage(): void
    {
        try {
            $this->connection->query('DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = \'album\' AND `folder_map`.`object_id` NOT IN (SELECT `album`.`id` FROM `album`)');
            $this->connection->query('DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = \'artist\' AND `folder_map`.`object_id` NOT IN (SELECT `artist`.`id` FROM `artist`)');
            $this->connection->query('DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = \'podcast\' AND `folder_map`.`object_id` NOT IN (SELECT `podcast`.`id` FROM `podcast`)');
            $this->connection->query('DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = \'podcast_episode\' AND `folder_map`.`object_id` NOT IN (SELECT `podcast_episode`.`id` FROM `podcast_episode`)');
            $this->connection->query('DELETE FROM `folder_map` WHERE `folder_map`.`object_type` = \'song\' AND `folder_map`.`object_id` NOT IN (SELECT `song`.`id` FROM `song`)');
            $this->connection->query('DELETE FROM `folder` WHERE `id` NOT IN (SELECT `folder_id` FROM `folder_map`) AND `user` IS NULL');
        } catch (DatabaseException) {
            debug_event(self::class, 'collectGarbage error', 5);
        }
    }
}
