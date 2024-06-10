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
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Ampache\Repository\Model\Catalog;
use Generator;

/**
 * Create catalog_map table and fill it with data
 */
final class Migration500004 extends AbstractMigration
{
    protected array $changelog = ['Create catalog_map table and fill it with data'];

    protected bool $warning = true;

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        $tables    = [
            'song',
            'album',
            'video',
            'podcast_episode'
        ];
        $catalogs  = Catalog::get_catalogs();

        // Make sure your files have a catalog
        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog !== null) {
                $rootdir = realpath($catalog->get_path());
                foreach ($tables as $type) {
                    $sql = ($type === 'album')
                        ? "UPDATE `album` LEFT JOIN `song` ON `song`.`album` = `album`.`id` SET `album`.`catalog` = ? WHERE `song`.`file` LIKE '$rootdir%' AND (`$type`.`catalog` IS NULL OR `$type`.`catalog` != ?);"
                        : "UPDATE `$type` SET `catalog` = ? WHERE `$type`.`file` LIKE '$rootdir%' AND (`$type`.`catalog` IS NULL OR `$type`.`catalog` != ?);";
                    Dba::write($sql, array($catalog->id, $catalog->id));
                }
            }
        }
        // create the table
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `catalog_map` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `catalog_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `unique_catalog_map` (`object_id`, `object_type`, `catalog_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");

        // fill the data
        foreach ($tables as $type) {
            $this->updateDatabase("REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `$type`.`catalog`, '$type', `$type`.`id` FROM `$type` WHERE `$type`.`catalog` > 0;");
        }

        // artist is a special one as it can be across multiple tables
        $this->updateDatabase("REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `song`.`catalog`, 'artist', `artist`.`id` FROM `artist` LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` WHERE `song`.`catalog` > 0;");

        $this->updateDatabase("REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `album`.`catalog`, 'artist', `artist`.`id` FROM `artist` LEFT JOIN `album` ON `album`.`album_artist` = `artist`.`id` WHERE `album`.`catalog` > 0;");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Generator {
        yield 'catalog_map' => "CREATE TABLE IF NOT EXISTS `catalog_map` (`catalog_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_catalog_map` (`object_id`, `object_type`, `catalog_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
