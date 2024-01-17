<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 * Add user_playlist table
 */
final class Migration500006 extends AbstractMigration
{
    protected array $changelog = ['Add user_playlist and user_data table'];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `user_playlist` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) DEFAULT NULL, `object_type` enum('song','live_stream','video','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL DEFAULT '0', `track` smallint(6) DEFAULT NULL, `current_track` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `current_time` smallint(5) UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY (`id`),KEY `user` (`user`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `user_data` (`user` int(11) DEFAULT NULL, `key` varchar(128) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `value` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, KEY `user` (`user`), KEY `key` (`key`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Generator {
        yield 'user_data' => "CREATE TABLE IF NOT EXISTS `user_data` (`user` int(11) DEFAULT NULL, `key` varchar(128) COLLATE $collation DEFAULT NULL, `value` varchar(255) COLLATE $collation DEFAULT NULL, UNIQUE KEY `unique_data` (`user`, `key`), KEY `user` (`user`), KEY `key` (`key`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
