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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Artist;
use Ampache\Module\System\Dba;

final class SongRepository implements SongRepositoryInterface
{
    /**
     * gets the songs for an album takes an optional limit
     *
     * @return int[]
     */
    public function getByAlbum(
        int $albumId,
        int $limit = 0
    ): array {
        $sql   = (AmpConfig::get('catalog_disable'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `catalog`.`enabled` = '1' "
            : "SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? ";
        $sql .= "ORDER BY `song`.`track`, `song`.`title`";
        if ($limit) {
            $sql .= " LIMIT " . (string)$limit;
        }

        $db_results = Dba::read($sql, array($albumId));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * gets the songs for a label, based on label name
     *
     * @return int[]
     */
    public function getByLabel(
        string $labelName
    ): array {
        $sql = (AmpConfig::get('catalog_disable'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `song_data` ON `song_data`.`song_id` = `song`.`id` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song_data`.`label` = ? AND `catalog`.`enabled` = '1' "
            : "SELECT `song`.`id` FROM `song` LEFT JOIN `song_data` ON `song_data`.`song_id` = `song`.`id` WHERE `song_data`.`label` = ? ";
        $sql .= "ORDER BY `song`.`album`, `song`.`track`";

        $db_results = Dba::read($sql, [$labelName]);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Gets the songs from the artist in a random order
     *
     * @return int[]
     */
    public function getRandomByArtist(
        Artist $artist
    ): array {
        $sql   = (AmpConfig::get('catalog_disable'))
            ? "SELECT DISTINCT `song`.`id` FROM `song` LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `song`.`artist` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song' AND `catalog`.`enabled` = '1' "
            : "SELECT DISTINCT `song`.`id` FROM `song` LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `song`.`artist` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song' ";
        $sql .= "ORDER BY RAND()";

        $db_results = Dba::read($sql, array($artist->getId()));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * gets the songs for this artist

     * @return int[]
     */
    public function getTopSongsByArtist(
        Artist $artist,
        int $count = 50
    ): array {
        $sql = (AmpConfig::get('catalog_disable'))
            ? "SELECT DISTINCT `song`.`id`, COUNT(`object_count`.`object_id`) AS `counting` FROM `song` LEFT JOIN `object_count` ON `object_count`.`object_id` = `song`.`id` AND `object_type` = 'song' LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `song`.`artist` WHERE `artist_map`.`artist_id` = " . $artist->getId() . " AND `artist_map`.`object_type` = 'song' AND `catalog`.`enabled` = '1' "
            : "SELECT DISTINCT `song`.`id`, COUNT(`object_count`.`object_id`) AS `counting` FROM `song` LEFT JOIN `object_count` ON `object_count`.`object_id` = `song`.`id` AND `object_type` = 'song' LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `song`.`artist` WHERE `artist_map`.`artist_id` = " . $artist->getId() . " AND `artist_map`.`object_type` = 'song' ";
        $sql .= "GROUP BY `song`.`id` ORDER BY count(`object_count`.`object_id`) DESC LIMIT " . (string)$count;

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * gets the songs for this artist
     *
     * @return int[]
     */
    public function getByArtist(
        int $artistId
    ): array {
        $sql   = (AmpConfig::get('catalog_disable'))
            ? "SELECT DISTINCT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `song`.`artist` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song' AND `catalog`.`enabled` = '1' "
            : "SELECT DISTINCT `artist_map`.`object_id` FROM `artist_map` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song' ";
        $sql .= "ORDER BY `song`.`album`, `song`.`track`";

        $db_results = Dba::read($sql, array($artistId));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * gets the songs (including songs where they are the album artist) for this artist
     *
     * @return int[]
     */
    public function getAllByArtist(
        int $artistId
    ): array {
        $sql = (AmpConfig::get('catalog_disable'))
            ? "SELECT DISTINCT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE (`song`.`album` IN (SELECT `song`.`album` FROM `song` LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `song`.`artist` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song') OR `song`.`album` IN (SELECT `album`.`id` FROM `album` LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `album`.`album_artist` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song')) AND `catalog`.`enabled` = '1' "
            : "SELECT DISTINCT `song`.`id` FROM `song` WHERE `song`.`album` IN (SELECT `song`.`album` FROM `song` LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `song`.`artist` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song') OR `song`.`album` IN (SELECT `album`.`id` FROM `album` LEFT JOIN `artist_map` ON `artist_map`.`artist_id` = `album`.`album_artist` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song') ";
        $sql .= "ORDER BY `song`.`album`, `song`.`track`";

        $db_results = Dba::read($sql, array($artistId, $artistId));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Returns a list of song ID's attached to a license ID.
     *
     * @return int[]
     */
    public function getByLicense(int $licenseId): array
    {
        $db_results = Dba::read(
            'SELECT `id` from `song` WHERE `song`.`license` = ?',
            [$licenseId]
        );

        $results = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    public function delete(int $songId): bool
    {
        // keep details about deletions
        Dba::write(
            'REPLACE INTO `deleted_song` (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`, `album`, `artist`) SELECT `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip`, `album`, `artist` FROM `song` WHERE `id` = ?;',
            [$songId]
        );

        $deleted = Dba::write(
            'DELETE FROM `song` WHERE `id` = ?',
            [$songId]
        );

        return $deleted !== false;
    }
}
