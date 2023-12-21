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
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Catalog;

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
        ?int $count = 1
    ): array {
        $results = array();
        $sql     = "SELECT DISTINCT `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `song` ON `song`.`artist` = `artist_map`.`artist_id` WHERE `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") ";

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $userId > 0) {
            $sql .= "AND `artist_map`.`artist_id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = 'artist' AND `rating`.`rating` <= $rating_filter AND `rating`.`user` = " . $userId . ") ";
        }

        $sql .= "ORDER BY RAND() LIMIT " . (string)$count;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['artist_id'];
        }

        return $results;
    }
}
