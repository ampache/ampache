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
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Song;
use Generator;

/**
 * Add `disk` to song table
 * Create album_disk table and migrate user ratings & flags
 */
final class Migration600004 extends AbstractMigration
{
    protected array $changelog = [
        'Add `disk` to song table',
        'Create album_disk table and migrate user ratings & flags'
    ];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = 'MyISAM';
        // add disk to song table
        Dba::write("ALTER TABLE `song` DROP COLUMN `disk`;");
        $this->updateDatabase("ALTER TABLE `song` ADD COLUMN `disk` smallint(5) UNSIGNED DEFAULT NULL AFTER `album`;");

        // fill the data
        $this->updateDatabase("UPDATE `song`, (SELECT DISTINCT `id`, `disk` FROM `album`) AS `album` SET `song`.`disk` = `album`.`disk` WHERE (`song`.`disk` != `album`.`disk` OR `song`.`disk` IS NULL) AND `song`.`album` = `album`.`id`;");

        // create the table
        $this->updateDatabase("DROP TABLE IF EXISTS `album_disk`;");
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `album_disk` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `album_id` int(11) UNSIGNED NOT NULL, `disk` int(11) UNSIGNED NOT NULL, `disk_count` int(11) unsigned DEFAULT 0 NOT NULL, `time` bigint(20) UNSIGNED DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0, `song_count` smallint(5) UNSIGNED DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, UNIQUE KEY `unique_album_disk` (`album_id`, `disk`, `catalog`), INDEX `id_index` (`id`), INDEX `album_id_type_index` (`album_id`, `disk`), INDEX `id_disk_index` (`id`, `disk`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");

        // make sure ratings and counts will be entered
        $this->updateDatabase("ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','genre','live_stream','playlist','podcast','podcast_episode','song','stream','tvshow','tvshow_season','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','genre','live_stream','playlist','podcast','podcast_episode','song','stream','tvshow','tvshow_season','video') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");

        // fill the data
        $this->updateDatabase("INSERT IGNORE INTO `album_disk` (`album_id`, `disk`, `catalog`) SELECT DISTINCT `song`.`album` AS `album_id`, `song`.`disk` AS `disk`, `song`.`catalog` AS `catalog` FROM `song`;");

        // rating (id, `user`, object_type, object_id, rating)
        $this->updateDatabase("INSERT IGNORE INTO `rating` (`object_type`, `object_id`, `user`, `rating`) SELECT DISTINCT 'album_disk', `album_disk`.`id`, `rating`.`user`, `rating`.`rating` FROM `rating` LEFT JOIN `album` ON `rating`.`object_type` = 'album' AND `rating`.`object_id` = `album`.`id` LEFT JOIN `album_disk` ON `album`.`id` = `album_disk`.`album_id` LEFT JOIN `rating` AS `album_rating` ON `album_rating`.`object_type` = 'album' AND `rating`.`rating` = `album_rating`.`rating` AND `rating`.`user` = `album_rating`.`user` WHERE `rating`.`object_type` = 'album' AND `album_disk`.`id` IS NOT NULL;");

        // user_flag (id, `user`, object_id, object_type, `date`)
        $this->updateDatabase("INSERT IGNORE INTO `user_flag` (`object_type`, `object_id`, `user`, `date`) SELECT DISTINCT 'album_disk', `album_disk`.`id`, `user_flag`.`user`, `user_flag`.`date` FROM `user_flag` LEFT JOIN `album` ON `user_flag`.`object_type` = 'album' AND `user_flag`.`object_id` = `album`.`id` LEFT JOIN `album_disk` ON `album`.`id` = `album_disk`.`album_id` LEFT JOIN `user_flag` AS `album_flag` ON `album_flag`.`object_type` = 'album' AND `user_flag`.`date` = `album_flag`.`date` AND `user_flag`.`user` = `album_flag`.`user` WHERE `user_flag`.`object_type` = 'album' AND `album_disk`.`id` IS NOT NULL;");

        Song::clear_cache();
        Artist::clear_cache();
        Album::clear_cache();
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Generator {
        yield 'album_disk' => "CREATE TABLE IF NOT EXISTS `album_disk` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `album_id` int(11) UNSIGNED NOT NULL, `disk` int(11) UNSIGNED NOT NULL, `disk_count` int(11) unsigned DEFAULT 0 NOT NULL, `time` bigint(20) UNSIGNED DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0, `song_count` smallint(5) UNSIGNED DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, UNIQUE KEY `unique_album_disk` (`album_id`, `disk`, `catalog`), INDEX `id_index` (`id`), INDEX `album_id_type_index` (`album_id`, `disk`), INDEX `id_disk_index` (`id`, `disk`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
