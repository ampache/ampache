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

namespace Ampache\Module\System\Update\Migration\V5;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Create album_map table and fill it with data
 */
final class Migration530001 extends AbstractMigration
{
    protected array $changelog = ['Create album_map table and fill it with data'];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = 'MyISAM';

        // create the table
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `album_map` (`album_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_album_map` (`object_id`, `object_type`, `album_id`), INDEX `object_id_index` (`object_id`), INDEX `album_id_type_index` (`album_id`, `object_type`), INDEX `object_id_type_index` (`object_id`, `object_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");

        // fill the data
        $this->updateDatabase("INSERT IGNORE INTO `album_map` (`album_id`, `object_type`, `object_id`) SELECT DISTINCT `artist_map`.`object_id` AS `album_id`, 'album' AS `object_type`, `artist_map`.`artist_id` AS `object_id` FROM `artist_map` WHERE `artist_map`.`object_type` = 'album' AND `artist_map`.`object_id` > 0 UNION SELECT DISTINCT `song`.`album` AS `album_id`, 'song' AS `object_type`, `song`.`artist` AS `object_id` FROM `song` WHERE `song`.`album` > 0 UNION SELECT DISTINCT `song`.`album` AS `album_id`, 'song' AS `object_type`, `artist_map`.`artist_id` AS `object_id` FROM `artist_map` LEFT JOIN `song` ON `artist_map`.`object_type` = 'song' AND `artist_map`.`object_id` = `song`.`id` WHERE `song`.`album` IS NOT NULL AND `artist_map`.`object_type` = 'song';");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 530001) {
            yield 'album_map' => "CREATE TABLE IF NOT EXISTS `album_map` (`album_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_album_map` (`object_id`, `object_type`, `album_id`), KEY `object_id_index` (`object_id`), KEY `album_id_type_index` (`album_id`, `object_type`), KEY `object_id_type_index` (`object_id`, `object_type`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        }
    }
}
