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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add podcasts
 */
final class Migration380001 extends AbstractMigration
{
    protected array $changelog = ['Add podcasts'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `podcast` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `feed` varchar(4096) NOT NULL, `catalog` int(11) NOT NULL, `title` varchar(255) CHARACTER SET $charset NOT NULL, `website` varchar(255) NULL, `description` varchar(4096) CHARACTER SET $charset NULL, `language` varchar(5) NULL, `copyright` varchar(64) NULL, `generator` varchar(64) NULL, `lastbuilddate` int(11) UNSIGNED DEFAULT '0' NOT NULL, `lastsync` int(11) UNSIGNED DEFAULT '0' NOT NULL) ENGINE=$engine");
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `podcast_episode` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` varchar(255) CHARACTER SET $charset NOT NULL, `guid` varchar(255) NOT NULL, `podcast` int(11) NOT NULL, `state` varchar(32) NOT NULL, `file` varchar(4096) CHARACTER SET $charset NULL, `source` varchar(4096) NULL, `size` bigint(20) UNSIGNED DEFAULT '0' NOT NULL, `time` smallint(5) UNSIGNED DEFAULT '0' NOT NULL, `website` varchar(255) NULL, `description` varchar(4096) CHARACTER SET $charset NULL, `author` varchar(64) NULL, `category` varchar(64) NULL, `played` tinyint(1) UNSIGNED DEFAULT '0' NOT NULL, `pubdate` int(11) UNSIGNED NOT NULL, `addition_time` int(11) UNSIGNED NOT NULL) ENGINE=$engine");

        $this->updatePreferences('podcast_keep', 'Podcast: # latest episodes to keep', '10', AccessLevelEnum::ADMIN->value, 'integer', 'system');
        $this->updatePreferences('podcast_new_download', 'Podcast: # episodes to download when new episodes are available', '1', AccessLevelEnum::ADMIN->value, 'integer', 'system');

        $this->updateDatabase("ALTER TABLE `rating` CHANGE COLUMN `object_type` `object_type` enum('artist','album','song','stream','video','playlist','tvshow','tvshow_season','podcast','podcast_episode') NULL");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Generator {
        yield 'podcast' => "CREATE TABLE IF NOT EXISTS `podcast` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `feed` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) NOT NULL, `title` varchar(255) COLLATE $collation DEFAULT NULL, `website` varchar(255) COLLATE $collation DEFAULT NULL, `description` varchar(4096) COLLATE $collation DEFAULT NULL, `language` varchar(5) COLLATE $collation DEFAULT NULL, `copyright` varchar(255) COLLATE $collation DEFAULT NULL, `generator` varchar(64) COLLATE $collation DEFAULT NULL, `lastbuilddate` int(11) UNSIGNED NOT NULL DEFAULT 0, `lastsync` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `episodes` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        yield 'podcast_episode' => "CREATE TABLE IF NOT EXISTS `podcast_episode` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `title` varchar(255) COLLATE $collation DEFAULT NULL, `guid` varchar(255) COLLATE $collation DEFAULT NULL, `podcast` int(11) NOT NULL, `state` varchar(32) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `source` varchar(4096) COLLATE $collation DEFAULT NULL, `size` bigint(20) UNSIGNED NOT NULL DEFAULT 0, `time` int(11) UNSIGNED NOT NULL DEFAULT 0, `website` varchar(255) COLLATE $collation DEFAULT NULL, `description` varchar(4096) COLLATE $collation DEFAULT NULL, `author` varchar(64) COLLATE $collation DEFAULT NULL, `category` varchar(64) COLLATE $collation DEFAULT NULL, `played` tinyint(1) UNSIGNED NOT NULL DEFAULT 0, `pubdate` int(11) UNSIGNED NOT NULL, `addition_time` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0, `waveform` mediumblob DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
