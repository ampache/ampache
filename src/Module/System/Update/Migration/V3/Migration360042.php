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
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add broadcasts and player control
 */
final class Migration360042 extends AbstractMigration
{
    protected array $changelog = ['Add broadcasts and player control'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `broadcast` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `name` varchar(64) CHARACTER SET $charset NULL, `description` varchar(256) CHARACTER SET $charset NULL, `is_private` tinyint(1) unsigned NOT NULL DEFAULT '0', `song` int(11) unsigned NOT NULL DEFAULT '0', `started` tinyint(1) unsigned NOT NULL DEFAULT '0', `listeners` int(11) unsigned NOT NULL DEFAULT '0', `key` varchar(32) CHARACTER SET $charset NULL, PRIMARY KEY (`id`)) ENGINE=$engine");

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `player_control` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user` int(11) unsigned NOT NULL, `cmd` varchar(32) CHARACTER SET $charset NOT NULL, `value` varchar(256) CHARACTER SET $charset NULL, `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `send_date` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Generator {
        yield 'broadcast' => "CREATE TABLE IF NOT EXISTS `broadcast` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `name` varchar(64) COLLATE $collation DEFAULT NULL, `description` varchar(256) COLLATE $collation DEFAULT NULL, `is_private` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `song` int(11) UNSIGNED NOT NULL DEFAULT 0, `started` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `listeners` int(11) UNSIGNED NOT NULL DEFAULT 0, `key` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";

        yield 'player_control' => "CREATE TABLE IF NOT EXISTS `player_control` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `cmd` varchar(32) COLLATE $collation DEFAULT NULL, `value` varchar(256) COLLATE $collation DEFAULT NULL, `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `send_date` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
