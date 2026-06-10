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
 * Add `folder_map` table for mapping folder's to library items
 */
final class Migration800003 extends AbstractMigration
{
    protected array $changelog = ['Add `folder_map` table for mapping folder\'s to library items'];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $this->updateDatabase("DROP TABLE IF EXISTS `folder_map`;");

        // create the table
        $this->updateDatabase("CREATE TABLE `folder_map` (`folder_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) DEFAULT NULL, UNIQUE KEY `unique_folder_map` (`object_id`,`object_type`,`folder_id`), KEY `object_id_index` (`object_id`), KEY `folder_id_type_index` (`folder_id`,`object_type`), KEY `object_id_type_index` (`object_id`,`object_type`), KEY `object_type_IDX` (`object_type`) USING BTREE, KEY `object_type_id_IDX` (`object_type`,`object_id`) USING BTREE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build,
    ): Generator {
        if ($build > 800003) {
            yield 'folder_map' => "CREATE TABLE IF NOT EXISTS `folder_map` (`folder_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) DEFAULT NULL, UNIQUE KEY `unique_folder_map` (`object_id`,`object_type`,`folder_id`), KEY `object_id_index` (`object_id`), KEY `folder_id_type_index` (`folder_id`,`object_type`), KEY `object_id_type_index` (`object_id`,`object_type`), KEY `object_type_IDX` (`object_type`) USING BTREE, KEY `object_type_id_IDX` (`object_type`,`object_id`) USING BTREE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }
}
