<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;

final readonly class ArtistRepository implements ArtistRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection)
    {
    }

    /**
     * Deletes the artist entry
     */
    public function delete(
        Artist $artist
    ): void {
        $this->connection->query(
            'DELETE FROM `artist` WHERE `id` = ?',
            [$artist->getId()]
        );
    }

    /**
     * This returns a number of random artists.
     *
     * @return list<int>
     */
    public function getRandom(
        int $userId,
        ?int $count = 1
    ): array {
        $results = [];
        $sql     = "SELECT DISTINCT `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `song` ON `song`.`artist` = `artist_map`.`artist_id` WHERE `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") ";

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $userId > 0) {
            $sql .= sprintf('AND `artist_map`.`artist_id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = \'artist\' AND `rating`.`rating` <= %d AND `rating`.`user` = ', $rating_filter) . $userId . ") ";
        }

        $sql .= "ORDER BY RAND() LIMIT " . $count;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['artist_id'];
        }

        return $results;
    }

    /**
     * This cleans out unused artists
     */
    public function collectGarbage(): void
    {
        $this->connection->query('DELETE FROM `artist_map` WHERE `object_type` = \'album\' AND `object_id` IN (SELECT `id` FROM `album` WHERE `album_artist` IS NULL);');
        $this->connection->query('DELETE FROM `artist_map` WHERE `object_type` = \'album\' AND `object_id` NOT IN (SELECT `id` FROM `album`);');
        $this->connection->query('DELETE FROM `artist_map` WHERE `object_type` = \'song\' AND `object_id` NOT IN (SELECT `id` FROM `song`);');
        $this->connection->query('DELETE FROM `artist_map` WHERE `artist_id` NOT IN (SELECT `id` FROM `artist`);');

        // delete the artists
        $this->connection->query('DELETE FROM `artist` WHERE `id` IN (SELECT `id` FROM (SELECT `id` FROM `artist` LEFT JOIN (SELECT DISTINCT `song`.`artist` AS `artist_id` FROM `song` UNION SELECT DISTINCT `album`.`album_artist` AS `artist_id` FROM `album` UNION SELECT DISTINCT `wanted`.`artist` AS `artist_id` FROM `wanted` UNION SELECT DISTINCT `clip`.`artist` AS `artist_id` FROM `clip` UNION SELECT DISTINCT `artist_id` FROM `artist_map`) AS `artist_map` ON `artist_map`.`artist_id` = `artist`.`id` WHERE `artist_map`.`artist_id` IS NULL) AS `null_artist`);');
    }

    /**
     * This finds an artist based on its name
     */
    public function findByName(string $name): ?Artist
    {
        $rowId = $this->connection->fetchOne(
            'SELECT `id` FROM `artist` WHERE `name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, \'\'), \' \', `artist`.`name`)) = ? ',
            [$name, $name]
        );

        if ($rowId === false) {
            return null;
        }

        return new Artist((int) $rowId);
    }
}
