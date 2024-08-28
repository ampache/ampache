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
use Generator;

final class Migration550001 extends AbstractMigration
{
    protected array $changelog = [
        'Add tables `catalog_filter_group` and `catalog_filter_group_map` for catalog filtering by groups',
        'Add column `catalog_filter_group` to `user` table to assign a filter group',
    ];

    /**
     * Add tables `catalog_filter_group` and `catalog_filter_group_map` for catalog filtering by groups
     * Add column `catalog_filter_group` to `user` table to assign a filter group
     * Create a DEFAULT group
     */
    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        // Add the new catalog_filter_group table
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `catalog_filter_group` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");

        // Add the default group (autoincrement starts at 1 so force it to be 0)
        $this->updateDatabase("INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('DEFAULT');");
        $this->updateDatabase("UPDATE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT';");
        $this->updateDatabase("ALTER TABLE `catalog_filter_group` AUTO_INCREMENT = 1;");

        // Add the new catalog_filter_group_map table
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `catalog_filter_group_map` (`group_id` int(11) UNSIGNED NOT NULL, `catalog_id` int(11) UNSIGNED NOT NULL, `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, UNIQUE KEY (group_id,catalog_id)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");

        // Add the default access group to the user table
        Dba::write("ALTER TABLE `user` DROP COLUMN `catalog_filter_group`;");
        $this->updateDatabase("ALTER TABLE `user` ADD COLUMN `catalog_filter_group` INT(11) UNSIGNED NOT NULL DEFAULT 0;");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 550001) {
            yield 'catalog_filter_group' => "CREATE TABLE IF NOT EXISTS `catalog_filter_group` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('DEFAULT'); UPDATE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT'; ALTER TABLE `catalog_filter_group` AUTO_INCREMENT = 1;";
            yield 'catalog_filter_group_map' => "CREATE TABLE IF NOT EXISTS `catalog_filter_group_map` (`group_id` int(11) UNSIGNED NOT NULL, `catalog_id` int(11) UNSIGNED NOT NULL, `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, UNIQUE KEY (group_id,catalog_id)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }
}
