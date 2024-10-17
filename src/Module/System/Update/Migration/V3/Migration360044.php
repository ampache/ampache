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
 * Add artist description/recommendation external service data cache
 */
final class Migration360044 extends AbstractMigration
{
    protected array $changelog = ['Add artist description/recommendation external service data cache'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase("ALTER TABLE `artist` ADD COLUMN `summary` TEXT CHARACTER SET $charset NULL, ADD COLUMN `placeformed` varchar(64) NULL, ADD COLUMN `yearformed` int(4) NULL, ADD COLUMN `last_update` int(11) unsigned NOT NULL DEFAULT '0'");

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `recommendation` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `last_update` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine");

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `recommendation_item` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `recommendation` int(11) unsigned NOT NULL, `recommendation_id` int(11) unsigned NULL, `name` varchar(256) NULL, `rel` varchar(256) NULL, `mbid` varchar(36) NULL, PRIMARY KEY (`id`)) ENGINE=$engine");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 360044) {
            yield 'recommendation' => "CREATE TABLE IF NOT EXISTS `recommendation` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `object_id` int(11) UNSIGNED NOT NULL, `last_update` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
            yield 'recommendation_item' => "CREATE TABLE IF NOT EXISTS `recommendation_item` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `recommendation` int(11) UNSIGNED NOT NULL, `recommendation_id` int(11) UNSIGNED DEFAULT NULL, `name` varchar(256) COLLATE $collation DEFAULT NULL, `rel` varchar(256) COLLATE $collation DEFAULT NULL, `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }
}
