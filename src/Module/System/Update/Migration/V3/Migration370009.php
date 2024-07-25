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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Enhance video support with TVShows and Movies
 */
final class Migration370009 extends AbstractMigration
{
    protected array $changelog = ['Enhance video support with TVShows and Movies'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $sql_array = [
            "ALTER TABLE `video` ADD COLUMN `release_date` date NULL AFTER `enabled`, ADD COLUMN `played` tinyint(1) unsigned DEFAULT '0' NOT NULL AFTER `enabled`",
            "CREATE TABLE IF NOT EXISTS `tvshow` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(80) NOT NULL, `summary` varchar(256) NULL, `year` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "CREATE TABLE IF NOT EXISTS `tvshow_season` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `season_number` int(11) unsigned NOT NULL, `tvshow` int(11) unsigned NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "CREATE TABLE IF NOT EXISTS `tvshow_episode` (`id` int(11) unsigned NOT NULL, `original_name` varchar(80) NULL, `season` int(11) unsigned NOT NULL, `episode_number` int(11) unsigned NOT NULL, `summary` varchar(256) NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "CREATE TABLE IF NOT EXISTS `movie` (`id` int(11) unsigned NOT NULL, `original_name` varchar(80) NULL, `summary` varchar(256) NULL, `year` int(11) unsigned NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "CREATE TABLE IF NOT EXISTS `personal_video` (`id` int(11) unsigned NOT NULL, `location` varchar(256) NULL, `summary` varchar(256) NULL, PRIMARY KEY (`id`)) ENGINE=$engine",
            "CREATE TABLE IF NOT EXISTS `clip` (`id` int(11) unsigned NOT NULL, `artist` int(11) NULL, `song` int(11) NULL, PRIMARY KEY (`id`)) ENGINE=$engine"
        ];
        foreach ($sql_array as $sql) {
            $this->updateDatabase($sql);
        }

        $this->updatePreferences('allow_video', 'Allow video features', '1', AccessLevelEnum::MANAGER->value, 'integer', 'options');
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if (
            $build > 370009 &&
            $build < 700011
        ) {
            yield 'tvshow' => "CREATE TABLE IF NOT EXISTS `tvshow` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(80) COLLATE $collation DEFAULT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, `year` int(11) UNSIGNED DEFAULT NULL, `prefix` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
            yield 'tvshow_season' => "CREATE TABLE IF NOT EXISTS `tvshow_season` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `season_number` int(11) UNSIGNED NOT NULL, `tvshow` int(11) UNSIGNED NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
            yield 'tvshow_episode' => "CREATE TABLE IF NOT EXISTS `tvshow_episode` (`id` int(11) UNSIGNED NOT NULL, `original_name` varchar(80) COLLATE $collation DEFAULT NULL, `season` int(11) UNSIGNED NOT NULL, `episode_number` int(11) UNSIGNED NOT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
            yield 'movie' => "CREATE TABLE IF NOT EXISTS `movie` (`id` int(11) UNSIGNED NOT NULL, `original_name` varchar(80) COLLATE $collation DEFAULT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, `year` int(11) UNSIGNED DEFAULT NULL, `prefix` varchar(32) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
            yield 'personal_video' => "CREATE TABLE IF NOT EXISTS `personal_video` (`id` int(11) UNSIGNED NOT NULL, `location` varchar(256) COLLATE $collation DEFAULT NULL, `summary` varchar(256) COLLATE $collation DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
            yield 'clip' => "CREATE TABLE IF NOT EXISTS `clip` (`id` int(11) UNSIGNED NOT NULL, `artist` int(11) DEFAULT NULL, `song` int(11) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        }
    }
}
