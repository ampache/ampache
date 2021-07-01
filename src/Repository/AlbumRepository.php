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
use Ampache\Repository\Model\Catalog;

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
        $allow_group_disks = (AmpConfig::get('album_group'));
        $sort_disk         = ($allow_group_disks)
            ? "AND `album`.`disk` = 1 "
            : "";

        $sql = (AmpConfig::get('catalog_disable'))
            ? sprintf("SELECT DISTINCT `album`.`id` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `catalog`.`enabled` = '1' %s", $sort_disk)
            : "SELECT DISTINCT `album`.`id` FROM `album` " . str_replace("AND", "WHERE", $sort_disk);

        if (AmpConfig::get('catalog_filter')) {
            $sql .= " AND" . Catalog::get_user_filter('album', $userId);
        }
        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5) {
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
        $sql = (AmpConfig::get('catalog_disable'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `catalog`.`enabled` = '1' "
            : "SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? ";
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
        $sql = (AmpConfig::get('catalog_disable'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `catalog`.`enabled` = '1' "
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
        $f_name = Dba::escape($album->f_name);
        if ($f_name == '') {
            return array();
        }
        $album_artist   = "is null";
        $release_type   = "is null";
        $release_status = "is null";
        $mbid           = "is null";
        $original_year  = "is null";
        $year           = (string)$album->year;

        if ($album->album_artist) {
            $album_artist = "= '" . ucwords((string) $album->album_artist) . "'";
        }
        if ($album->release_type) {
            $release_type = "= '" . ucwords((string) $album->release_type) . "'";
        }
        if ($album->release_status) {
            $release_status = "= '" . ucwords((string) $album->release_status) . "'";
        }
        if ($album->mbid) {
            $mbid = "= '$album->mbid'";
        }
        if ($album->original_year) {
            $original_year = "= '$album->original_year'";
        }
        $results       = array();
        $where         = "WHERE `album`.`album_artist` $album_artist AND `album`.`mbid` $mbid AND `album`.`release_type` $release_type AND `album`.`release_status` $release_status AND (`album`.`name` = '$f_name' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = '$f_name') AND `album`.`year` = $year AND `album`.`original_year` $original_year ";
        $catalog_where = "";
        $catalog_join  = "";

        if ($catalogId) {
            $catalog_where .= " AND `catalog`.`id` = '$catalogId'";
        }
        if (AmpConfig::get('catalog_disable')) {
            $catalog_where .= "AND `catalog`.`enabled` = '1'";
            $catalog_join  = "LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog`";
        }

        $db_results = Dba::read(
            sprintf(
                'SELECT DISTINCT `album`.`id`, MAX(`album`.`disk`) AS `disk` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` %s %s %s GROUP BY `album`.`id` ORDER BY `disk` ASC',
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
     * Get time for an album disk by album.
     */
    public function getAlbumDuration(int $albumId): int
    {
        $db_results = Dba::read(
            'SELECT `time` from `album` WHERE `album`.`id` = ?',
            [$albumId]
        );

        $results = Dba::fetch_assoc($db_results);

        return (int) $results['time'];
    }

    /**
     * Get play count for an album disk by album id.
     */
    public function getAlbumPlayCount(int $albumId): int
    {
        $db_results = Dba::read(
            'SELECT `total_count` from `album` WHERE `album`.`id` = ?',
            [$albumId]
        );

        $results = Dba::fetch_assoc($db_results);

        return (int) $results['total_count'];
    }

    /**
     * Get song count for an album disk by album id.
     */
    public function getSongCount(int $albumId): int
    {
        $db_results = Dba::read(
            'SELECT `song_count` from `album` WHERE `album`.`id` = ?',
            [$albumId]
        );

        $results = Dba::fetch_assoc($db_results);

        return (int) $results['song_count'];
    }

    /**
     * Get distinct artist count for an album disk by album id.
     */
    public function getArtistCount(int $albumId): int
    {
        $db_results = Dba::read(
            'SELECT `artist_count` from `album` WHERE `album`.`id` = ?',
            [$albumId]
        );

        $results = Dba::fetch_assoc($db_results);

        return (int) $results['artist_count'];
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
        $catalog_join  = "LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog`";
        if ($catalog !== null) {
            $catalog_where .= " AND `catalog`.`id` = '" . Dba::escape($catalog) . "'";
        }
        if (AmpConfig::get('catalog_disable')) {
            $catalog_where .= "AND `catalog`.`enabled` = '1'";
        }
        $display_year      = AmpConfig::get('use_original_year') ? "IFNULL(`album`.`original_year`, `album`.`year`)" : "`album`.`year`";
        $allow_group_disks = AmpConfig::get('album_group');
        $sort_type         = AmpConfig::get('album_sort');
        $sort_disk         = ($allow_group_disks) ? "" : ", `album`.`disk`";
        //$sql_sort          = (AmpConfig::get('album_release_type')) ? "IFNULL(`album`.`release_type`, 'album'), " : "";
        switch ($sort_type) {
            case 'year_asc':
                $sql_sort = "$display_year ASC" . $sort_disk;
                break;
            case 'year_desc':
                $sql_sort = "$display_year DESC" . $sort_disk;
                break;
            case 'name_asc':
                $sql_sort = "`album`.`name` ASC" . $sort_disk;
                break;
            case 'name_desc':
                $sql_sort = "`album`.`name` DESC" . $sort_disk;
                break;
            default:
                $sql_sort = "`album`.`name`" . $sort_disk . ", $display_year";
        }

        $sql = "SELECT `album`.`id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` " . $catalog_join . " WHERE (`song`.`artist`='$artistId' OR `album`.`album_artist`='$artistId') $catalog_where GROUP BY `album`.`id`, `album`.`release_type`, `album`.`mbid` ORDER BY $sql_sort";

        if ($allow_group_disks) {
            $sql = "SELECT MIN(`album`.`id`) AS `id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` $catalog_join WHERE (`song`.`artist`='$artistId' OR `album`.`album_artist`='$artistId') $catalog_where GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year` ORDER BY $sql_sort";
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
