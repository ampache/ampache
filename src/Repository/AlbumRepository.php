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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Module\System\Dba;

final class AlbumRepository implements AlbumRepositoryInterface
{
    /**
     * This returns a number of random album
     *
     * @return int[] Album ids
     */
    public function getRandom(
        int $userId,
        ?int $count = 1
    ): array {
        $results = [];

        if (!$count) {
            $count = 1;
        }

        $sort_disk = (AmpConfig::get('album_group')) ? 'AND `album`.`disk` = 1 ' : '';

        $sql = 'SELECT DISTINCT `album`.`id` FROM `album` LEFT JOIN `song` ON `song`.`album` = `album`.`id` ';
        if (AmpConfig::get('catalog_disable')) {
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ';
            $where = sprintf(
                'WHERE `catalog`.`enabled` = \'1\' %s',
                $sort_disk
            );
        } else {
            $where = sprintf(
                'WHERE 1=1 %s',
                $sort_disk
            );
        }
        $sql .= $where;

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5) {
            $sql .= sprintf(
                'AND `album`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = \'album\' AND `rating`.`rating` <=%d AND `rating`.`user` = %d) ',
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
            $results[] = $row['id'];
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
        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ';
        }
        $sql .= 'WHERE `song`.`album` = ? ';
        if (AmpConfig::get('catalog_disable')) {
            $sql .= 'AND `catalog`.`enabled` = \'1\' ';
        }
        $sql .= "ORDER BY `song`.`track`, `song`.`title`";
        $db_results = Dba::read($sql, [$albumId]);

        $results = [];
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row['0'];
        }

        return $results;
    }

    /**
     * gets songs from this album group
     *
     * @return int[] Song ids
     */
    public function getSongsGrouped(
        array $albumIdList
    ): array {
        $results = array();
        foreach ($albumIdList as $album_id) {
            $results = array_merge($results, self::getSongs((int)$album_id));
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
        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ';
        }
        $sql .= 'WHERE `song`.`album` = ? ';
        if (AmpConfig::get('catalog_disable')) {
            $sql .= 'AND `catalog`.`enabled` = \'1\' ';
        }
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
    public function getRandomSongsGrouped(
        array $albumIdList
    ): array {
        $results = array();
        foreach ($albumIdList as $album_id) {
            $results = array_merge($results, self::getRandomSongs((int)$album_id));
        }
        shuffle($results);

        return $results;
    }

    /**
     * Deletes the album entry
     */
    public function delete(
        int $albumId
    ): bool {
        $result = Dba::write(
            'DELETE FROM `album` WHERE `id` = ?',
            [$albumId]
        );

        return $result !== false;
    }

    /**
     * gets the album ids with the same musicbrainz identifier
     *
     * @return int[]
     */
    public function getAlbumSuite(
        Album $album,
        int $catalogId = 0
    ): array {
        $full_name = Dba::escape($album->full_name);
        if ($full_name == '') {
            return array();
        }
        $album_artist = "is null";
        $release_type = "is null";
        $mbid         = "is null";
        $year         = (string)$album->year;

        if ($album->album_artist) {
            $album_artist = "= '" . ucwords((string) $album->album_artist) . "'";
        }
        if ($album->release_type) {
            $release_type = "= '" . ucwords((string) $album->release_type) . "'";
        }
        if ($album->mbid) {
            $mbid = "= '$album->mbid'";
        }
        $results       = array();
        $where         = "WHERE `album`.`album_artist` $album_artist AND `album`.`mbid` $mbid AND `album`.`release_type` $release_type AND " .
            "(`album`.`name` = '$full_name' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = '$full_name') " .
            "AND `album`.`year` = $year ";
        $catalog_where = "";
        $catalog_join  = "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`";

        if ($catalogId) {
            $catalog_where .= " AND `catalog`.`id` = '$catalogId'";
        }
        if (AmpConfig::get('catalog_disable')) {
            $catalog_where .= "AND `catalog`.`enabled` = '1'";
        }

        $db_results = Dba::read(
            sprintf(
                'SELECT DISTINCT `album`.`id`, MAX(`album`.`disk`) AS `disk` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` %s %s %s GROUP BY `album`.`id` ORDER BY `album`.`disk` ASC',
                $catalog_join,
                $where,
                $catalog_where
            )
        );

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['disk']] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Cleans out unused albums
     */
    public function collectGarbage(): void
    {
        Dba::write('DELETE FROM `album` WHERE `album`.`id` NOT IN (SELECT `song`.`album` FROM `song`);');
    }

    /**
     * Get time for an album disk.
     */
    public function getDuration(int $albumId): int
    {
        $db_results = Dba::read(
            'SELECT SUM(`song`.`time`) AS `time` from `song` WHERE `song`.`album` = ?',
            [$albumId]
        );

        $results = Dba::fetch_assoc($db_results);

        return (int) $results['time'];
    }

    /**
     * Get time for an album disk and set it.
     */
    public function updateTime(
        Album $album
    ): int {
        $albumId = $album->getId();

        $time = $this->getDuration($albumId);
        if ($time !== $album->time && $albumId) {
            Dba::write(
                sprintf(
                    "UPDATE `album` SET `time`=$time WHERE `id`=%d",
                    $albumId
                )
            );
        }

        return $time;
    }

    /**
     * gets the album ids that the artist is a part of
     *
     * @return int[]
     */
    public function getByArtist(
        int $artistId,
        ?int $catalog = null,
        bool $group_release_type = false
    ): array {
        $catalog_where = "";
        $catalog_join  = "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`";
        if ($catalog !== null) {
            $catalog_where .= " AND `catalog`.`id` = '" . Dba::escape($catalog) . "'";
        }
        if (AmpConfig::get('catalog_disable')) {
            $catalog_where .= "AND `catalog`.`enabled` = '1'";
        }

        $sort_type = AmpConfig::get('album_sort');
        $sort_disk = (AmpConfig::get('album_group')) ? "" : ", `album`.`disk`";
        switch ($sort_type) {
            case 'year_asc':
                $sql_sort = '`album`.`year` ASC' . $sort_disk;
                break;
            case 'year_desc':
                $sql_sort = '`album`.`year` DESC' . $sort_disk;
                break;
            case 'name_asc':
                $sql_sort = '`album`.`name` ASC' . $sort_disk;
                break;
            case 'name_desc':
                $sql_sort = '`album`.`name` DESC' . $sort_disk;
                break;
            default:
                $sql_sort = '`album`.`name`' . $sort_disk . ', `album`.`year`';
        }

        $sql = "SELECT `album`.`id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` " . $catalog_join . " " . "WHERE (`song`.`artist`='$artistId' OR `album`.`album_artist`='$artistId') $catalog_where GROUP BY `album`.`id`, `album`.`release_type`, `album`.`mbid` ORDER BY $sql_sort";

        if (AmpConfig::get('album_group')) {
            $sql = "SELECT MAX(`album`.`id`) AS `id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` $catalog_join " . "WHERE (`song`.`artist`='$artistId' OR `album`.`album_artist`='$artistId') $catalog_where GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year` ORDER BY $sql_sort";
        }

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($group_release_type) {
                // We assume undefined release type is album
                $rtype = $row['release_type'] ?: 'album';
                if (!isset($results[$rtype])) {
                    $results[$rtype] = array();
                }
                $results[$rtype][] = $row['id'];

                $sort = (string)AmpConfig::get('album_release_type_sort');
                if ($sort) {
                    $results_sort = array();
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
                $results[] = (int) $row['id'];
            }
        }

        return $results;
    }
}
