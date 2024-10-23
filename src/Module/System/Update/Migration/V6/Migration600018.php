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

namespace Ampache\Module\System\Update\Migration\V6;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Drop `user_playlist` table and recreate it
 */
final class Migration600018 extends AbstractMigration
{
    protected array $changelog = ['Drop `user_playlist` table and recreate it'];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase('DROP TABLE IF EXISTS `user_playlist`;');

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `user_playlist` (`playqueue_time` int(11) UNSIGNED NOT NULL, `playqueue_client` varchar(255) CHARACTER SET $charset COLLATE $collation, user int(11) DEFAULT 0, `object_type` enum('song','live_stream','video','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci, `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0, `track` smallint(6) UNSIGNED NOT NULL DEFAULT 0, `current_track` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `current_time` smallint(5) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`playqueue_time`, `playqueue_client`, `user`, `track`), KEY `user` (`user`), KEY `object_type` (`object_type`), KEY `object_id` (`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 600018) {
            yield 'user_playlist' => "CREATE TABLE IF NOT EXISTS `user_playlist` (`playqueue_time` int(11) UNSIGNED NOT NULL, `playqueue_client` varchar(255) CHARACTER SET $charset COLLATE $collation, user int(11) DEFAULT 0, `object_type` enum('song','live_stream','video','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci, `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0, `track` smallint(6) UNSIGNED NOT NULL DEFAULT 0, `current_track` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `current_time` smallint(5) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`playqueue_time`, `playqueue_client`, `user`, `track`), KEY `user` (`user`), KEY `object_type` (`object_type`), KEY `object_id` (`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }
}
