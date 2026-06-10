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
use Generator;

/**
 * Add `folder` table for mapping folder's to library items
 */
final class Migration800002 extends AbstractMigration
{
    protected array $changelog = ['Add `folder` table for mapping folder\'s to library items'];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        Dba::write("DROP TABLE IF EXISTS `folder`;");

        // create the table
        $this->updateDatabase("CREATE TABLE `folder` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(255) DEFAULT NULL, `catalog` int(11) NOT NULL DEFAULT 0, `parent` int(11) NOT NULL DEFAULT 0, `user` int(11) DEFAULT NULL, `update_time` int(11) UNSIGNED DEFAULT 0, `addition_time` int(11) UNSIGNED DEFAULT 0, `object_count` int(11) UNSIGNED DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, `path` varchar(255) DEFAULT NULL, `path_name` varchar(4096) DEFAULT NULL, PRIMARY KEY (`id`), KEY `name` (`name`), KEY `catalog` (`catalog`), KEY `user` (`user`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build,
    ): Generator {
        if ($build > 800002) {
            yield 'folder' => "CREATE TABLE IF NOT EXISTS `folder` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(255) DEFAULT NULL, `catalog` int(11) NOT NULL DEFAULT 0, `parent` int(11) NOT NULL DEFAULT 0, `user` int(11) DEFAULT NULL, `update_time` int(11) UNSIGNED DEFAULT 0, `addition_time` int(11) UNSIGNED DEFAULT 0, `object_count` int(11) UNSIGNED DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, `path` varchar(255) DEFAULT NULL, `path_name` varchar(4096) DEFAULT NULL, PRIMARY KEY (`id`), KEY `name` (`name`), KEY `catalog` (`catalog`), KEY `user` (`user`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }
}
