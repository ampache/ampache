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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;

/**
 * Provides database access to album-disks
 */
final readonly class AlbumDiskRepository implements AlbumDiskRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private ConfigContainerInterface $configContainer,
    ) {}

    /**
     * Returns the id of the matching disk, creating or moving it when needed
     *
     * Pass $currentId to move an existing row rather than create a second; 0 means nothing was written.
     */
    public function check(
        int $albumId,
        int $disk,
        int $catalogId,
        ?string $disksubtitle = null,
        ?int $currentId = null,
    ): int {
        $existingId = $this->findId($albumId, $disk, $catalogId, $disksubtitle);
        if ($existingId !== null) {
            return $existingId;
        }

        if ($currentId !== null && $currentId > 0) {
            $movedId = $this->move($albumId, $disk, $catalogId, $disksubtitle, $currentId);
            if ($movedId !== null) {
                return $movedId;
            }
        }

        return $this->create($albumId, $disk, $catalogId, $disksubtitle);
    }

    /**
     * Returns the number of distinct artists mapped to the disk's album
     */
    public function getArtistCount(AlbumDisk $albumDisk): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT(`object_id`)) AS `artist_count` FROM `album_map` WHERE `album_id` = ?;',
            [$albumDisk->getId()]
        );
    }

    /**
     * Returns the disks for an album
     * @return list<AlbumDisk>
     */
    public function getByAlbum(Album $album): array
    {
        $result = $this->connection->query(
            'SELECT DISTINCT `id`, `disk` FROM `album_disk` WHERE `album_id` = ? ORDER BY `disk`',
            [$album->getId()]
        );

        $results = [];
        while ($rowId = $result->fetchColumn()) {
            $results[] = new AlbumDisk((int) $rowId);
        }

        return $results;
    }

    /**
     * Returns the ids of every song on the disk
     *
     * @return int[]
     */
    public function getSongs(AlbumDisk $albumDisk): array
    {
        $sql = ($this->configContainer->get(ConfigurationKeyEnum::CATALOG_DISABLE))
            ? "SELECT DISTINCT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `song`.`disk` = ? AND `catalog`.`enabled` = '1'"
            : 'SELECT DISTINCT `song`.`id` FROM `song` WHERE `song`.`album` = ? AND `song`.`disk` = ?';

        $result = $this->connection->query($sql, [$albumDisk->album_id, $albumDisk->disk]);

        $results = [];
        while ($rowId = $result->fetchColumn()) {
            $results[] = (int) $rowId;
        }

        return $results;
    }

    /**
     * Inserts the missing disk and seeds it with the song that triggered the create
     */
    private function create(int $albumId, int $disk, int $catalogId, ?string $disksubtitle): int
    {
        try {
            $this->connection->query(
                'REPLACE INTO `album_disk` (`album_id`, `disk`, `catalog`, `disksubtitle`) SELECT `album`.`id`, ?, CASE WHEN `album`.`catalog` = 0 THEN 0 ELSE ? END, ? FROM `album` WHERE `album`.`id` = ?;',
                [$disk, $catalogId, $disksubtitle ?: null, $albumId]
            );
        } catch (DatabaseException) {
            return 0;
        }

        $albumDiskId = $this->connection->getLastInsertedId();

        // count a new song on the new disk right away
        $this->connection->query(
            'UPDATE `album_disk` SET `song_count` = `song_count` + 1 WHERE `id` = ?;',
            [$albumDiskId]
        );

        if (!empty($disksubtitle)) {
            // set the subtitle on insert too
            $this->connection->query(
                'UPDATE `album_disk` SET `disksubtitle` = ? WHERE `id` = ?;',
                [$disksubtitle, $albumDiskId]
            );
        }

        return $albumDiskId;
    }

    /**
     * Looks up the disk by its natural key. A catalog=0 album keeps album_disk.catalog at 0, so the
     * comparison has to be derived from the album row rather than taken from the argument.
     */
    private function findId(int $albumId, int $disk, int $catalogId, ?string $disksubtitle): ?int
    {
        $objectId = (!empty($disksubtitle))
            ? $this->connection->fetchOne(
                'SELECT `album_disk`.`id` FROM `album_disk` INNER JOIN `album` ON `album`.`id` = `album_disk`.`album_id` WHERE `album_disk`.`album_id` = ? AND `album_disk`.`disk` = ? AND `album_disk`.`catalog` = CASE WHEN `album`.`catalog` = 0 THEN 0 ELSE ? END AND album_disk.`disksubtitle` = ?;',
                [$albumId, $disk, $catalogId, $disksubtitle]
            )
            : $this->connection->fetchOne(
                "SELECT `album_disk`.`id` FROM `album_disk` INNER JOIN `album` ON `album`.`id` = `album_disk`.`album_id` WHERE `album_disk`.`album_id` = ? AND `album_disk`.`disk` = ? AND `album_disk`.`catalog` = CASE WHEN `album`.`catalog` = 0 THEN 0 ELSE ? END AND (`album_disk`.`disksubtitle` = '' OR `album_disk`.`disksubtitle` IS NULL);",
                [$albumId, $disk, $catalogId]
            );

        return ($objectId === false)
            ? null
            : (int) $objectId;
    }

    /**
     * Moves an existing disk onto the new values, returning null when there was no such row
     */
    private function move(int $albumId, int $disk, int $catalogId, ?string $disksubtitle, int $currentId): ?int
    {
        $row = $this->connection->fetchRow('SELECT * FROM `album_disk` WHERE `id` = ?;', [$currentId]);
        if (!isset($row['id'])) {
            return null;
        }

        // remember the current disk before a collision re-fetch can clobber it
        $oldDisk = (int) $row['disk'];

        try {
            $this->connection->query(
                'UPDATE `album_disk` SET `album_id` = ?, `disk` = ?, `catalog` = CASE WHEN (SELECT `catalog` FROM `album` WHERE `id` = ?) = 0 THEN 0 ELSE ? END, `disksubtitle` = ? WHERE `id` = ?;',
                [$albumId, $disk, $albumId, $catalogId, $disksubtitle, $currentId]
            );
        } catch (DatabaseException) {
            // Duplicates might collide here. Match the unique key (album_id, disk, catalog) alone
            $collidedId = $this->connection->fetchOne(
                'SELECT `album_disk`.`id` FROM `album_disk` INNER JOIN `album` ON `album`.`id` = `album_disk`.`album_id` WHERE `album_disk`.`album_id` = ? AND `album_disk`.`disk` = ? AND `album_disk`.`catalog` = CASE WHEN `album`.`catalog` = 0 THEN 0 ELSE ? END;',
                [$albumId, $disk, $catalogId]
            );

            if ($collidedId !== false) {
                $currentId = (int) $collidedId;
            }
        }

        // Update songs when you edit an album_disk object
        if ($oldDisk !== $disk) {
            $this->connection->query(
                'UPDATE `song` SET `disk` = ? WHERE `album` = ? AND `disk` = ?;',
                [$disk, $albumId, $oldDisk]
            );
        }

        return $currentId;
    }
}
