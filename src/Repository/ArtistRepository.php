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
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Artist;

final class ArtistRepository implements ArtistRepositoryInterface
{
    /**
     * Deletes the artist entry
     */
    public function delete(
        int $artistId
    ): bool {
        $result = Dba::write(
            'DELETE FROM `artist` WHERE `id` = ?',
            [$artistId]
        );

        return $result !== false;
    }

    /**
     * This returns a number of random artists.
     *
     * @return int[]
     */
    public function getRandom(
        int $userId,
        int $count = 1
    ): array {
        $results = array();

        if (!$count) {
            $count = 1;
        }

        $sql = "SELECT DISTINCT `artist`.`id` FROM `artist` " . "LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
            $where = "WHERE `catalog`.`enabled` = '1' ";
        } else {
            $where = "WHERE 1=1 ";
        }
        $sql .= $where;

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5) {
            $sql .= " AND `artist`.`id` NOT IN" .
                " (SELECT `object_id` FROM `rating`" .
                " WHERE `rating`.`object_type` = 'artist'" .
                " AND `rating`.`rating` <=" . $rating_filter .
                " AND `rating`.`user` = " . $userId . ") ";
        }

        $sql .= "ORDER BY RAND() LIMIT " . (string)$count;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Get time for an artist's songs.
     */
    public function getDuration(Artist $artist): int
    {
        $params     = array($artist->getId());
        $sql        = "SELECT SUM(`song`.`time`) AS `time` from `song` WHERE `song`.`artist` = ?";
        $db_results = Dba::read($sql, $params);
        $results    = Dba::fetch_assoc($db_results);
        // album artists that don't have any songs
        if ((int) $results['time'] == 0) {
            $sql        = "SELECT SUM(`album`.`time`) AS `time` from `album` WHERE `album`.`album_artist` = ?";
            $db_results = Dba::read($sql, $params);
            $results    = Dba::fetch_assoc($db_results);
        }

        return (int) $results['time'];
    }

    /**
     * This gets an artist object based on the artist name
     */
    public function findByName(string $name): Artist
    {
        $dbResults = Dba::read(
            'SELECT `id` FROM `artist` WHERE `name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, \'\'), \' \', `artist`.`name`)) = ? ',
            [$name, $name]
        );

        $row = Dba::fetch_assoc($dbResults);

        return new Artist($row['id'] ?? 0);
    }

    /**
     * This cleans out unused artists
     */
    public function collectGarbage(): void
    {
        Dba::write('DELETE FROM `artist` USING `artist` LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` LEFT JOIN `album` ON `album`.`album_artist` = `artist`.`id` ' . 'LEFT JOIN `wanted` ON `wanted`.`artist` = `artist`.`id` ' . 'LEFT JOIN `clip` ON `clip`.`artist` = `artist`.`id` ' . 'WHERE `song`.`id` IS NULL AND `album`.`id` IS NULL AND `wanted`.`id` IS NULL AND `clip`.`id` IS NULL');
    }

    /**
     * Update artist associated user.
     */
    public function updateArtistUser(Artist $artist, int $user): void
    {
        Dba::write(
            'UPDATE `artist` SET `user` = ? WHERE `id` = ?',
            [$user, $artist->getId()]
        );
    }
}
