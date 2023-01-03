<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
use Ampache\Module\System\Core;
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

        $sql  = "SELECT DISTINCT `album`.`id` FROM `album` ";
        $join = 'WHERE';

        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` $join `catalog`.`enabled` = '1' ";
            $join = 'AND';
        }
        if (AmpConfig::get('catalog_filter') && $userId > 0) {
            $sql .= $join . Catalog::get_user_filter('album', $userId);
            $join = 'AND';
        }
        if (AmpConfig::get('album_group')) {
            $sql .= $join . " `album`.`disk` = 1 ";
            $join = 'AND';
        }
        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $userId > 0) {
            $sql .= $join . sprintf(
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
        if (AmpConfig::get('catalog_filter') && !empty(Core::get_global('user')) && Core::get_global('user')->id > 0) {
            $sql .= "AND" . Catalog::get_user_filter('song', Core::get_global('user')->id) . " ";
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
        $sql = (AmpConfig::get('catalog_disable'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `catalog`.`enabled` = '1' "
            : "SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? ";
        if (AmpConfig::get('catalog_filter') && !empty(Core::get_global('user')) && Core::get_global('user')->id > 0) {
            $sql .= "AND" . Catalog::get_user_filter('song', Core::get_global('user')->id) . " ";
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
    public function getAlbumSuite(Album $album): array
    {
        $user    = Core::get_global('user');
        $user_id = $user->id ?? 0;

        $f_name = $album->get_fullname(true);
        if ($f_name == '') {
            return array();
        }
        $results = array();

        $where   = " WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ? ) ";
        $params  = array($f_name, $f_name);
        if ($album->mbid) {
            $where .= 'AND `album`.`mbid` = ? ';
            $params[] = $album->mbid;
        } else {
            $where .= 'AND `album`.`mbid` IS NULL ';
        }
        if ($album->mbid_group) {
            $where .= 'AND `album`.`mbid_group` = ? ';
            $params[] = $album->mbid_group;
        } else {
            $where .= 'AND `album`.`mbid_group` IS NULL ';
        }
        if ($album->prefix) {
            $where .= 'AND `album`.`prefix` = ? ';
            $params[] = $album->prefix;
        }
        if ($album->album_artist) {
            $where .= 'AND `album`.`album_artist` = ? ';
            $params[] = $album->album_artist;
        }
        if ($album->original_year) {
            $where .= 'AND `album`.`original_year` = ? ';
            $params[] = $album->original_year;
        }
        if ($album->release_type) {
            $where .= 'AND `album`.`release_type` = ? ';
            $params[] = $album->release_type;
        }
        if ($album->release_status) {
            $where .= 'AND `album`.`release_status` = ? ';
            $params[] = $album->release_status;
        }

        $catalog_where = "";
        $catalog_join  = "";

        if (AmpConfig::get('catalog_filter') && !empty(Core::get_global('user')) && Core::get_global('user')->id > 0) {
            $catalog_where .= " AND" . Catalog::get_user_filter('album', $user_id);
        }

        if (AmpConfig::get('catalog_disable')) {
            $catalog_where .= "AND `catalog`.`enabled` = '1'";
            $catalog_join  = "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`";
        }
        $sql        = "SELECT DISTINCT `album`.`id`, MAX(`album`.`disk`) AS `disk` FROM `album` LEFT JOIN `song` ON `song`.`album`=`album`.`id` $catalog_join $where $catalog_where GROUP BY `album`.`id` ORDER BY `disk` ASC";
        $db_results = Dba::read($sql, $params);

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
        // delete old mappings or bad ones
        Dba::write("DELETE FROM `album_map` WHERE `object_type` = 'album'  AND `album_id` IN (SELECT `id` FROM `album` WHERE `album_artist` IS NULL);");
        Dba::write("DELETE FROM `album_map` WHERE `object_id` NOT IN (SELECT `id` FROM `artist`);");
        Dba::write("DELETE FROM `album_map` WHERE `album_map`.`album_id` NOT IN (SELECT DISTINCT `song`.`album` FROM `song`);");
        Dba::write("DELETE FROM `album_map` WHERE `album_map`.`album_id` IN (SELECT `album_id` FROM (SELECT DISTINCT `album_map`.`album_id` FROM `album_map` LEFT JOIN `artist_map` ON `artist_map`.`object_type` = `album_map`.`object_type` AND `artist_map`.`artist_id` = `album_map`.`object_id` AND `artist_map`.`object_id` = `album_map`.`album_id` WHERE `artist_map`.`artist_id` IS NULL AND `album_map`.`object_type` = 'album') AS `null_album`);");
        // delete the albums that don't have any songs left
        Dba::write("DELETE FROM `album` WHERE `album`.`id` NOT IN (SELECT DISTINCT `song`.`album` FROM `song`) AND `album`.`id` NOT IN (SELECT DISTINCT `album_id` FROM `album_map`);");
    }

    /**
     * Get time for an album disk by album.
     */
    public function getAlbumDuration(int $albumId): int
    {
        $db_results = Dba::read(
            'SELECT `time` FROM `album` WHERE `album`.`id` = ?',
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
            'SELECT `total_count` FROM `album` WHERE `album`.`id` = ?',
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
            'SELECT `song_count` FROM `album` WHERE `album`.`id` = ?',
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
            'SELECT `artist_count` FROM `album` WHERE `album`.`id` = ? AND `album`.`album_artist` IS NOT NULL',
            [$albumId]
        );

        $results = Dba::fetch_assoc($db_results);

        return (int) $results['artist_count'];
    }

    /**
     * Get distinct artist count for an album array.
     */
    public function getArtistCountGroup(array $albumArray): int
    {
        $idlist     = '(' . implode(',', $albumArray) . ')';
        $sql        = "SELECT COUNT(DISTINCT(`song`.`artist`)) AS `artist_count` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' AND `song`.`album` IN $idlist;";
        $db_results = Dba::read($sql);
        $results    = Dba::fetch_assoc($db_results);

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
            $catalog_where .= " AND `catalog`.`enabled` = '1'";
        }
        if (AmpConfig::get('catalog_filter') && !empty(Core::get_global('user')) && Core::get_global('user')->id > 0) {
            $catalog_where .= " AND" . Catalog::get_user_filter('album', Core::get_global('user')->id);
        }
        $original_year     = AmpConfig::get('use_original_year') ? "IFNULL(`album`.`original_year`, `album`.`year`)" : "`album`.`year`";
        $allow_group_disks = AmpConfig::get('album_group');
        $sort_type         = AmpConfig::get('album_sort');
        $sort_disk         = ($allow_group_disks) ? "" : ", `album`.`disk`";
        switch ($sort_type) {
            case 'year_asc':
                $sql_sort = "$original_year ASC" . $sort_disk;
                break;
            case 'year_desc':
                $sql_sort = "$original_year DESC" . $sort_disk;
                break;
            case 'name_asc':
                $sql_sort = "`album`.`name` ASC" . $sort_disk;
                break;
            case 'name_desc':
                $sql_sort = "`album`.`name` DESC" . $sort_disk;
                break;
            default:
                $sql_sort = "`album`.`name`" . $sort_disk . ", $original_year";
        }

        $sql = "SELECT DISTINCT `album`.`id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `album_map` ON `album_id` = `album`.`id` " . $catalog_join . " WHERE `album_map`.`object_id` = ? $catalog_where GROUP BY `album`.`id`, `album`.`release_type`, `album`.`mbid` ORDER BY $sql_sort";
        if ($allow_group_disks) {
            $sql = "SELECT MIN(`album`.`id`) AS `id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `album_map` ON `album_id` = `album`.`id` " . $catalog_join . " WHERE `album_map`.`object_id` = ? $catalog_where GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`, `album`.`mbid_group` ORDER BY $sql_sort";
        }

        $db_results = Dba::read($sql, array($artistId));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($group_release_type) {
                // We assume undefined release type is album
                $rtype = $row['release_type'] ?? 'album';
                if (!isset($results[$rtype])) {
                    $results[$rtype] = array();
                }
                $results[$rtype][] = (int)$row['id'];

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
                $results[] = (int)$row['id'];
            }
        }

        return $results;
    }

    /**
     * gets the album id has the same artist and title
     *
     * @return int[]
     */
    public function getByName(
        string $name,
        int $artistId
    ): array {
        $sql        = "SELECT `album`.`id` FROM `album` WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ?) AND `album`.`album_artist` = ?";
        $params     = array($name, $name, $artistId);
        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }

    /**
     * gets the album id that is part of this mbid_group
     *
     * @return int[]
     */
    public function getByMbidGroup(
        string $mbid
    ): array {
        $sql        = "SELECT `album`.`id` FROM `album` WHERE `album`.`mbid_group` = ?";
        $db_results = Dba::read($sql, array($mbid));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    }
}
