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

namespace Ampache\Module\System\Update\Migration\V4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add system option for cron based cache and create related tables
 */
final class Migration400008 extends AbstractMigration
{
    protected array $changelog = ['Add system option for cron based cache and create related tables'];

    public function migrate(): void
    {
        $this->updatePreferences('cron_cache', 'Cache computed SQL data (eg. media hits stats) using a cron', '0', AccessLevelEnum::ADMIN->value, 'boolean', 'system', 'catalog');

        $tables    = [
            'cache_object_count',
            'cache_object_count_run'
        ];
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        foreach ($tables as $table) {
            $this->updateDatabase("CREATE TABLE IF NOT EXISTS `" . $table . "` (`object_id` int(11) unsigned NOT NULL, `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast_episode') CHARACTER SET utf8 NOT NULL, `count` int(11) unsigned NOT NULL DEFAULT '0', `threshold` int(11) unsigned NOT NULL DEFAULT '0', `count_type` varchar(16) NOT NULL, PRIMARY KEY (`object_id`, `object_type`, `threshold`, `count_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");
        }

        $this->updateDatabase("UPDATE `preference` SET `level`=75 WHERE `preference`.`name`='stats_threshold'");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 400008){
            yield 'cache_object_count' => "CREATE TABLE IF NOT EXISTS `cache_object_count` (`object_id` int(11) UNSIGNED NOT NULL, `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `count` int(11) UNSIGNED NOT NULL DEFAULT 0, `threshold` int(11) UNSIGNED NOT NULL DEFAULT 0, `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, PRIMARY KEY (`object_id`, `object_type`, `threshold`, `count_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
            yield 'cache_object_count_run' => "CREATE TABLE IF NOT EXISTS `cache_object_count_run` (`object_id` int(11) UNSIGNED NOT NULL, `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `count` int(11) UNSIGNED NOT NULL DEFAULT 0, `threshold` int(11) UNSIGNED NOT NULL DEFAULT 0, `count_type` enum('download','stream','skip') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, PRIMARY KEY (`object_id`, `object_type`, `threshold`, `count_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }
}
