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
 */

namespace Ampache\Module\System\Update\Migration\V6;

use Ampache\Module\System\Update\Migration\AbstractMigration;
use Ampache\Repository\Model\Catalog;

/**
 * Add `song_artist` and `album_artist` maps to catalog_map
 * This is a duplicate of `update_550004` But may have been skipped depending on your site's version history
 */
final class Migration600012 extends AbstractMigration
{
    protected array $changelog = [
        'Add `song_artist` and `album_artist` maps to catalog_map',
        'This is a duplicate of `update_550004` But may have been skipped depending on your site\'s version history'
    ];

    public function migrate(): void
    {
        // delete bad maps if they exist
        $tables = ['song', 'album', 'video', 'podcast', 'podcast_episode', 'live_stream'];
        foreach ($tables as $type) {
            $this->updateDatabase("DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `$type`.`catalog` AS `catalog_id`, '$type' AS `map_type`, `$type`.`id` AS `object_id` FROM `$type` GROUP BY `$type`.`catalog`, `map_type`, `$type`.`id`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` AND `valid_maps`.`map_type` = `catalog_map`.`object_type` WHERE `catalog_map`.`object_type` = '$type' AND `valid_maps`.`object_id` IS NULL;");
        }

        // delete catalog_map artists
        $this->updateDatabase("DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` INNER JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` INNER JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` INNER JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` INNER JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL GROUP BY `album`.`catalog`, `artist_map`.`object_type`, `artist_map`.`artist_id`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` AND `valid_maps`.`map_type` = `catalog_map`.`object_type` WHERE `catalog_map`.`object_type` IN ('artist', 'song_artist', 'album_artist') AND `valid_maps`.`object_id` IS NULL;");

        // insert catalog_map artists
        $this->updateDatabase("INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL GROUP BY `catalog`, `artist_map`.`object_type`, `artist_map`.`artist_id`");

        Catalog::update_mapping('artist');
        Catalog::update_mapping('album');
        Catalog::update_mapping('album_disk');
    }
}
