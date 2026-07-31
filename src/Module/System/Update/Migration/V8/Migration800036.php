<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Restore columns that `resources/sql/ampache.sql` was missing.
 */
final class Migration800036 extends AbstractMigration
{
    protected array $changelog = [
        'Restore `album`.`subtitle`, `folder`.`playable`, `folder`.`weight` and the `folder_map` name/catalog/path columns on databases installed from a stale ampache.sql',
    ];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));

        $columns = [
            ['album', 'subtitle', "varchar(64) CHARACTER SET $charset COLLATE $collation DEFAULT NULL AFTER `catalog_number`"],
            ['folder', 'playable', 'tinyint(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `addition_time`'],
            ['folder', 'weight', "int(11) SIGNED NOT NULL DEFAULT '0'"],
            ['folder_map', 'name', "varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL"],
            ['folder_map', 'catalog', 'int(11) NOT NULL DEFAULT 0'],
            ['folder_map', 'path_name', "varchar(512) CHARACTER SET $charset COLLATE $collation DEFAULT NULL"],
        ];

        foreach ($columns as [$table, $column, $definition]) {
            if (!Dba::has_column($table, $column)) {
                $this->updateDatabase(sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s;', $table, $column, $definition));
            }
        }

        // The stale dump declared `folder`.`path_name` as varchar(4096), which is too wide for `folder_catalog_IDX`
        if (!Dba::has_index('folder', 'folder_catalog_IDX')) {
            $this->updateDatabase("ALTER TABLE `folder` MODIFY COLUMN `path_name` varchar(512) CHARACTER SET $charset COLLATE $collation DEFAULT NULL;");
            $this->updateDatabase('ALTER TABLE `folder` ADD KEY `folder_catalog_IDX` (`catalog`,`path_name`);');
        }

        if (!Dba::has_index('folder_map', 'folder_catalog_IDX')) {
            $this->updateDatabase('ALTER TABLE `folder_map` ADD KEY `folder_catalog_IDX` (`catalog`,`path_name`);');
        }

        // `folder_map`.`folder_id` is nullable for catalog roots, which have no parent folder
        $this->updateDatabase('ALTER TABLE `folder_map` MODIFY COLUMN `folder_id` int(11) UNSIGNED DEFAULT NULL;');
    }
}
