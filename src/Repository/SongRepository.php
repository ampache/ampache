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
        $results = [];

        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song`.`album` = ? ";
        $params = array($albumId);

        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `song`.`track`, `song`.`title`";
        if ($limit) {
            $sql .= " LIMIT " . (string)$limit;
        }
        $db_results = Dba::read($sql, $params);

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
        $sql = "SELECT `song`.`id` FROM `song` " . "LEFT JOIN `song_data` ON `song_data`.`song_id` = `song`.`id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song_data`.`label` = ? ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `song`.`album`, `song`.`track`";
        $db_results = Dba::read($sql, [$labelName]);

        $results = [];
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
        $results = [];

        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song`.`artist` = ? ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY RAND()";
        $db_results = Dba::read($sql, array($artist->getId()));

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
        $sql = "SELECT `song`.`id`, COUNT(`object_count`.`object_id`) AS counting FROM `song` ";
        $sql .= "LEFT JOIN `object_count` ON `object_count`.`object_id` = `song`.`id` AND `object_type` = 'song' ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song`.`artist` = " . $artist->getId() . " ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "GROUP BY `song`.`id` ORDER BY count(`object_count`.`object_id`) DESC LIMIT " . (string)$count;
        $db_results = Dba::read($sql);

        $results = array();
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
        Artist $artist
    ): array {
        $sql = "SELECT `song`.`id` FROM `song` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song`.`artist` = ? ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `song`.`album`, `song`.`track`";
        $db_results = Dba::read($sql, array($artist->getId()));

        $results = array();
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
        $deleted = Dba::write(
            'DELETE FROM `song` WHERE `id` = ?',
            [$songId]
        );

        return $deleted !== false;
    }
}
