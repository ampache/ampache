<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Catalog;

final readonly class AlbumRepository implements AlbumRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection)
    {
    }

    /**
     * This returns a number of random albums
     *
     * @return int[] Album ids
     */
    public function getRandom(
        int $userId,
        ?int $count = 1
    ): array {
        $results = [];
        $sql     = "SELECT DISTINCT `album`.`id` FROM `album` WHERE `album`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") ";

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $userId > 0) {
            $sql .= "AND" . sprintf(
                " `album`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = 'album' AND `rating`.`rating` <=%d AND `rating`.`user` = %d) ",
                $rating_filter,
                $userId
            );
        }

        $sql .= sprintf(
            'ORDER BY RAND() LIMIT %d',
            $count
        );
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * This returns a number of random album_disks
     *
     * @return int[] AlbumDisk ids
     */
    public function getRandomAlbumDisk(
        int $userId,
        ?int $count = 1
    ): array {
        $results = [];

        if (!$count) {
            $count = 1;
        }

        $sql = "SELECT DISTINCT `album_disk`.`id` FROM `album_disk` LEFT JOIN `album` ON `album`.`id` = `album_disk`.`album_id` WHERE `album_disk`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") ";

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $userId > 0) {
            $sql .= sprintf(
                "AND `album`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = 'album' AND `rating`.`rating` <=%d AND `rating`.`user` = %d) ",
                $rating_filter,
                $userId
            );
        }

        $sql .= sprintf(
            'ORDER BY RAND() LIMIT %d',
            $count
        );
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * gets songs from this album
     *
     * @return int[] Album ids
     */
    public function getSongs(
        int $albumId
    ): array {
        $userId     = Core::get_global('user')?->getId();
        $sql        = "SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") ORDER BY `song`.`disk`, `song`.`track`, `song`.`title`";
        $db_results = Dba::read($sql, [$albumId]);

        $results = [];
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row['0'];
        }

        return $results;
    }

    /**
     * gets songs from this album_disk id
     *
     * @return int[] Song ids
     */
    public function getSongsByAlbumDisk(
        int $albumDiskId
    ): array {
        $user   = Core::get_global('user');
        $userId = $user?->getId() ?? -1;
        $sql    = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ? AND `album_disk`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") "
            : "SELECT `song`.`id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ? ";

        $sql .= "ORDER BY `song`.`disk`, `song`.`track`, `song`.`title`";
        $db_results = Dba::read($sql, [$albumDiskId]);

        $results = [];
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row['0'];
        }

        return $results;
    }

    /**
     * gets a random order of songs from this album
     *
     * @return int[] Album ids
     */
    public function getRandomSongs(
        int $albumId
    ): array {
        $userId = Core::get_global('user')?->id ?? -1;
        $sql    = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") "
            : "SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? ";

        $sql .= 'ORDER BY RAND()';
        $db_results = Dba::read($sql, [$albumId]);

        $results = [];
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row['0'];
        }

        return $results;
    }

    /**
     * gets a random order of songs from this album group
     *
     * @return int[] Album ids
     */
    public function getRandomSongsByAlbumDisk(
        int $albumDiskId
    ): array {
        $userId = Core::get_global('user')?->id ?? -1;
        $sql    = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") "
            : "SELECT `song`.`id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ? ";

        $sql .= 'ORDER BY RAND()';
        $db_results = Dba::read($sql, [$albumDiskId]);

        $results = [];
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row['0'];
        }

        return $results;
    }

    /**
     * Deletes the album entry
     */
    public function delete(
        Album $album
    ): void {
        $this->connection->query(
            'DELETE FROM `album` WHERE `id` = ?',
            [$album->getId()]
        );
    }

    /**
     * Cleans out unused albums
     */
    public function collectGarbage(): void
    {
        try {
            debug_event(self::class, 'collectGarbage', 5);
            // delete old mappings or bad ones
            $this->connection->query('DELETE FROM `album_map` WHERE `object_type` = \'album\' AND `album_id` IN (SELECT `id` FROM `album` WHERE `album_artist` IS NULL)');
            $this->connection->query('DELETE FROM `album_map` WHERE `object_id` NOT IN (SELECT `id` FROM `artist`)');
            $this->connection->query('DELETE FROM `album_map` WHERE `album_map`.`album_id` NOT IN (SELECT DISTINCT `song`.`album` FROM `song`)');
            $this->connection->query('DELETE FROM `album_map` WHERE `album_map`.`album_id` IN (SELECT `album_id` FROM (SELECT DISTINCT `album_map`.`album_id` FROM `album_map` LEFT JOIN `artist_map` ON `artist_map`.`object_type` = `album_map`.`object_type` AND `artist_map`.`artist_id` = `album_map`.`object_id` AND `artist_map`.`object_id` = `album_map`.`album_id` WHERE `artist_map`.`artist_id` IS NULL AND `album_map`.`object_type` = \'album\') AS `null_album`)');

            // delete the albums that don't have any songs left
            $this->connection->query('DELETE FROM `album` WHERE `album`.`id` NOT IN (SELECT DISTINCT `song`.`album` FROM `song`) AND `album`.`id` NOT IN (SELECT DISTINCT `album_id` FROM `album_map`)');

            // delete old album_disks that shouldn't exist
            $this->connection->query('DELETE FROM `album_disk` WHERE `album_id` NOT IN (SELECT `id` FROM `album`)');

            $result = $this->connection->query('SELECT `id` FROM `album_disk` WHERE CONCAT(`album_id`, \'_\', `disk`) NOT IN (SELECT CONCAT(`album`, \'_\', `disk`) AS `id` FROM `song`);');
            // left over garbage
            while ($albumDiskId = $result->fetchColumn()) {
                $this->connection->query('DELETE FROM `album_disk` WHERE `id` = ?;', [$albumDiskId]);
            }
        } catch (DatabaseException) {
            debug_event(self::class, 'collectGarbage error', 5);
        };
    }

    /**
     * gets the album ids that the artist is a part of
     * Return Album or AlbumDisk based on album_group preference
     *
     * @return list<int>|array<string, list<int>>
     */
    public function getByArtist(
        int $artistId,
        ?int $catalogId = null,
        bool $group_release_type = false
    ): array {
        $userId        = Core::get_global('user')?->getId();
        $catalog_where = "AND `album`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ")";
        if ($catalogId !== null) {
            $catalog_where = "AND `album`.`catalog` = '" . Dba::escape($catalogId) . "'";
        }

        $original_year = (AmpConfig::get('use_original_year'))
            ? "IFNULL(`album`.`original_year`, `album`.`year`)"
            : "`album`.`year`";
        $sort_type = AmpConfig::get('album_sort');
        $showAlbum = AmpConfig::get('album_group');
        $sql_sort  = match ($sort_type) {
            'name_asc' => "`album`.`name` ASC",
            'name_desc' => "`album`.`name` DESC",
            'year_asc' => $original_year . ' ASC',
            'year_desc' => $original_year . ' DESC',
            default => '`album`.`name`, ' . $original_year,
        };

        $sql = ($showAlbum)
            ? sprintf('SELECT DISTINCT `album`.`id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `album_map` ON `album_map`.`album_id` = `album`.`id` WHERE `album_map`.`object_id` = ? %s GROUP BY `album`.`id`, `album`.`release_type`, `album`.`mbid` ORDER BY %s', $catalog_where, $sql_sort)
            : sprintf('SELECT DISTINCT `album_disk`.`id`, `album_disk`.`disk`, `album`.`name`, `album`.`release_type`, `album`.`mbid`, %s FROM `album_disk` LEFT JOIN `album` ON `album`.`id` = `album_disk`.`album_id` LEFT JOIN `album_map` ON `album_map`.`album_id` = `album`.`id` WHERE `album_map`.`object_id` = ? %s GROUP BY `album_disk`.`id`, `album_disk`.`disk`, `album`.`name`, `album`.`release_type`, `album`.`mbid`, %s ORDER BY %s, `album_disk`.`disk`', $original_year, $catalog_where, $original_year, $sql_sort);
        $db_results = Dba::read($sql, [$artistId]);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($group_release_type) {
                // We assume undefined release type is album
                $rtype = $row['release_type'] ?? 'album';
                if (!isset($results[$rtype])) {
                    $results[$rtype] = [];
                }

                $results[$rtype][] = (int)$row['id'];

                $sort = (string)AmpConfig::get('album_release_type_sort');
                if ($sort !== '' && $sort !== '0') {
                    $results_sort = [];
                    $asort        = explode(',', $sort);

                    foreach ($asort as $rtype) {
                        if (array_key_exists($rtype, $results)) {
                            $results_sort[$rtype] = $results[$rtype];
                            unset($results[$rtype]);
                        }
                    }

                    $results = array_merge($results_sort, $results);
                }
            } else {
                $results[] = (int)$row['id'];
            }
        }

        return $results;
    }

    /**
     * gets the album ids that the artist is a part of
     * Return Album only
     *
     * @return list<int>
     */
    public function getAlbumByArtist(
        int $artistId,
    ): array {
        $userId        = Core::get_global('user')?->getId();
        $catalog_where = "AND `album`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ")";

        $original_year = (AmpConfig::get('use_original_year'))
            ? "IFNULL(`album`.`original_year`, `album`.`year`)"
            : "`album`.`year`";
        $sort_type = AmpConfig::get('album_sort');
        $sql_sort  = match ($sort_type) {
            'name_asc' => "`album`.`name` ASC",
            'name_desc' => "`album`.`name` DESC",
            'year_asc' => $original_year . ' ASC',
            'year_desc' => $original_year . ' DESC',
            default => '`album`.`name`, ' . $original_year,
        };

        $sql        = sprintf('SELECT DISTINCT `album`.`id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `album_map` ON `album_map`.`album_id` = `album`.`id` WHERE `album_map`.`object_id` = ? %s GROUP BY `album`.`id`, `album`.`release_type`, `album`.`mbid` ORDER BY %s', $catalog_where, $sql_sort);
        $db_results = Dba::read($sql, [$artistId]);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * gets the album id has the same artist and title
     *
     * @return list<int>
     */
    public function getByName(
        string $name,
        int $artistId
    ): array {
        $result = $this->connection->query(
            'SELECT `album`.`id` FROM `album` WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, \'\'), \' \', `album`.`name`)) = ?) AND `album`.`album_artist` = ?',
            [$name, $name, $artistId]
        );

        $albumIds = [];
        while ($albumId = $result->fetchColumn()) {
            $albumIds[] = (int) $albumId;
        }

        return $albumIds;
    }

    /**
     * gets the album id that is part of this mbid_group
     *
     * @return list<int>
     */
    public function getByMbidGroup(
        string $musicBrainzId
    ): array {
        $result = $this->connection->query(
            'SELECT `album`.`id` FROM `album` WHERE `album`.`mbid_group` = ?',
            [$musicBrainzId]
        );

        $albumIds = [];
        while ($albumId = $result->fetchColumn()) {
            $albumIds[] = (int) $albumId;
        }

        return $albumIds;
    }

    /**
     * This returns the ids of artists that have songs/albums mapped
     *
     * @return list<int>
     */
    public function getArtistMap(Album $album, string $objectType): array
    {
        $result = $this->connection->query(
            'SELECT `object_id` FROM `album_map` WHERE `object_type` = ? AND `album_id` = ?',
            [$objectType, $album->getId()]
        );

        $artistIds = [];
        while ($artistId = $result->fetchColumn()) {
            $artistIds[] = (int) $artistId;
        }

        return $artistIds;
    }

    /**
     * Get the primary album_artist
     */
    public function getAlbumArtistId(int $albumId): ?int
    {
        $albumArtistId = $this->connection->fetchOne(
            'SELECT DISTINCT `album_artist` FROM `album` WHERE `id` = ?;',
            [$albumId]
        );

        if ($albumArtistId !== false) {
            return (int) $albumArtistId;
        }

        return null;
    }

    /**
     * Get item prefix, basename and name by the album id
     *
     * @return array{prefix: string, basename: string, name: string}
     */
    public function getNames(int $albumId): array
    {
        /** @var false|array{prefix: string, basename: string, name: string} $result */
        $result = $this->connection->fetchRow(
            'SELECT `album`.`prefix`, `album`.`name` AS `basename`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, \'\'), \' \', `album`.`name`)) AS `name` FROM `album` WHERE `id` = ?',
            [$albumId]
        );

        if ($result !== false) {
            return $result;
        }

        return [
            'prefix' => '',
            'basename' => '',
            'name' => '',
        ];
    }
}
