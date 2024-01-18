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

namespace Ampache\Module\System\Update\Migration\V3;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add Label tables.
 */
final class Migration370033 extends AbstractMigration
{
    protected array $changelog = ['Add Label tables'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `label` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(80) NOT NULL, `category` varchar(40) NULL, `summary` TEXT CHARACTER SET $charset NULL, `address` varchar(256) NULL, `email` varchar(128) NULL, `website` varchar(256) NULL, `user` int(11) unsigned NULL, `creation_date` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine");
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `label_asso` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `label` int(11) unsigned NOT NULL, `artist` int(11) unsigned NOT NULL, `creation_date` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Generator {
        yield 'label' => "CREATE TABLE IF NOT EXISTS `label` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(80) COLLATE $collation DEFAULT NULL, `category` varchar(40) COLLATE $collation DEFAULT NULL, `summary` text COLLATE $collation DEFAULT NULL, `address` varchar(256) COLLATE $collation DEFAULT NULL, `email` varchar(128) COLLATE $collation DEFAULT NULL, `website` varchar(256) COLLATE $collation DEFAULT NULL, `user` int(11) UNSIGNED DEFAULT NULL, `creation_date` int(11) UNSIGNED DEFAULT NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `country` varchar(64) COLLATE $collation DEFAULT NULL, `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        yield 'label_asso' => "CREATE TABLE IF NOT EXISTS `label_asso` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `label` int(11) UNSIGNED NOT NULL, `artist` int(11) UNSIGNED NOT NULL, `creation_date` int(11) UNSIGNED DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
