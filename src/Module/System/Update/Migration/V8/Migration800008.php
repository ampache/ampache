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
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Create `folder_map` table for browsing Folder\'s and items together
 */
final class Migration800008 extends AbstractMigration
{
    protected array $changelog = ['Create `folder_map` table for browsing Folder\'s and items together'];

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build,
    ): Generator {
        if ($build > 800008) {
            yield 'folder_map' => "CREATE TABLE `folder_map` (`folder_id` int(11) UNSIGNED NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) DEFAULT NULL, `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL NULL, `catalog` int(11) DEFAULT 0 NOT NULL, `path_name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL NULL, UNIQUE KEY `unique_folder_map` (`object_id`,`object_type`,`folder_id`), KEY `folder_catalog_IDX` (`catalog`,`path_name`), KEY `object_id_index` (`object_id`), KEY `folder_id_type_index` (`folder_id`,`object_type`), KEY `object_id_type_index` (`object_id`,`object_type`), KEY `object_type_IDX` (`object_type`) USING BTREE, KEY `object_type_id_IDX` (`object_type`,`object_id`) USING BTREE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $this->updateDatabase("DROP TABLE IF EXISTS `folder_map`;");

        // create the table
        $this->updateDatabase("CREATE TABLE `folder_map` (`folder_id` int(11) UNSIGNED NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) DEFAULT NULL, `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL NULL, `catalog` int(11) DEFAULT 0 NOT NULL, `path_name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL NULL, UNIQUE KEY `unique_folder_map` (`object_id`,`object_type`,`folder_id`), KEY `folder_catalog_IDX` (`catalog`,`path_name`), KEY `object_id_index` (`object_id`), KEY `folder_id_type_index` (`folder_id`,`object_type`), KEY `object_id_type_index` (`object_id`,`object_type`), KEY `object_type_IDX` (`object_type`) USING BTREE, KEY `object_type_id_IDX` (`object_type`,`object_id`) USING BTREE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");
    }
}
