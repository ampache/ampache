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
 * Catalog types are plugins now
 */
final class Migration360020 extends AbstractMigration
{
    protected array $changelog = ['Catalog types are plugins now'];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql = "CREATE TABLE IF NOT EXISTS `catalog_local` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `path` varchar(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        Dba::write($sql);
        $sql = "CREATE TABLE IF NOT EXISTS `catalog_remote` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `uri` varchar(255) COLLATE $collation NOT NULL, `username` varchar(255) COLLATE $collation NOT NULL, `password` varchar(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        Dba::write($sql);

        $sql        = "SELECT `id`, `catalog_type`, `path`, `remote_username`, `remote_password` FROM `catalog`";
        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            if ($results['catalog_type'] == 'local') {
                $this->updateDatabase("INSERT INTO `catalog_local` (`path`, `catalog_id`) VALUES (?, ?)");
            } elseif ($results['catalog_type'] == 'remote') {
                $this->updateDatabase("INSERT INTO `catalog_remote` (`uri`, `username`, `password`, `catalog_id`) VALUES (?, ?, ?, ?)");
            }
        }

        $sql_array = [
            "ALTER TABLE `catalog` DROP COLUMN `path`, DROP COLUMN `remote_username`, DROP COLUMN `remote_password`",
            "ALTER TABLE `catalog` MODIFY COLUMN `catalog_type` varchar(128)",
            "UPDATE `artist` SET `mbid` = NULL WHERE `mbid` = ''",
            "UPDATE `album` SET `mbid` = NULL WHERE `mbid` = ''",
            "UPDATE `song` SET `mbid` = NULL WHERE `mbid` = ''"
        ];
        foreach ($sql_array as $sql) {
            $this->updateDatabase($sql);
        }
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 360020){
            yield 'catalog_local' => "CREATE TABLE IF NOT EXISTS `catalog_local` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `path` varchar(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
            yield 'catalog_remote' => "CREATE TABLE IF NOT EXISTS `catalog_remote` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `uri` varchar(255) COLLATE $collation NOT NULL, `username` varchar(255) COLLATE $collation NOT NULL, `password` varchar(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }
}
