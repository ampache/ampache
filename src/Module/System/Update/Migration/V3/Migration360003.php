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

namespace Ampache\Module\System\Update\Migration\V3;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * This update moves the image data to its own table.
 */
final class Migration360003 extends AbstractMigration
{
    protected array $changelog = [
        'Add image table to store images',
        'Drop album_data and artist_data',
    ];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `image` (`id` int(11) unsigned NOT NULL auto_increment, `image` mediumblob NOT NULL, `mime` varchar(64) NOT NULL, `size` varchar(64) NOT NULL, `object_type` varchar(64) NOT NULL, `object_id` int(11) unsigned NOT NULL, PRIMARY KEY (`id`), KEY `object_type` (`object_type`), KEY `object_id` (`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation");

        foreach (['album', 'artist'] as $type) {
            $sql        = "SELECT `" . $type . "_id` AS `object_id`, `art`, `art_mime` FROM `" . $type . "_data` WHERE `art` IS NOT NULL";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                $sql = "INSERT INTO `image` (`image`, `mime`, `size`, `object_type`, `object_id`) VALUES('" . Dba::escape($row['art']) . "', '" . $row['art_mime'] . "', 'original', '" . $type . "', '" . $row['object_id'] . "')";
                Dba::write($sql);
            }

            $this->updateDatabase("DROP TABLE IF EXISTS `" . $type . "_data`");
        }
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 360003){
            yield 'image' => "CREATE TABLE IF NOT EXISTS `image` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `image` mediumblob DEFAULT NULL, `width` int(4) UNSIGNED DEFAULT 0, `height` int(4) UNSIGNED DEFAULT 0, `mime` varchar(64) COLLATE $collation DEFAULT NULL, `size` varchar(64) COLLATE $collation DEFAULT NULL, `object_type` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `kind` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`), KEY `object_type` (`object_type`), KEY `object_id` (`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }
}
