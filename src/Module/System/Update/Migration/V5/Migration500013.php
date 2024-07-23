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

namespace Ampache\Module\System\Update\Migration\V5;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add tables for tracking deleted files. (deleted_song, deleted_video, deleted_podcast_episode)
 * Add username to the playlist table to stop pulling user all the time
 */
final class Migration500013 extends AbstractMigration
{
    protected array $changelog = [
        'Add tables for tracking deleted files. (deleted_song, deleted_video, deleted_podcast_episode)',
        'Add username to the playlist table to stop pulling user all the time',
    ];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';
        // deleted_song (id, addition_time, delete_time, title, file, catalog, total_count, total_skip, album, artist)
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `deleted_song` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED DEFAULT '0', `delete_time` int(11) UNSIGNED DEFAULT '0', `title` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `file` varchar(4096) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0', `update_time` int(11) UNSIGNED DEFAULT '0', `album` int(11) UNSIGNED NOT NULL DEFAULT '0', `artist` int(11) UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");

        // deleted_video (id, addition_time, delete_time, title, file, catalog, total_count, total_skip)
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `deleted_video` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `file` varchar(4096) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");

        // deleted_podcast_episode (id, addition_time, delete_time, title, file, catalog, total_count, total_skip, podcast)
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `deleted_podcast_episode` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `file` varchar(4096) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0', `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0', `podcast` int(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");

        // add username to playlist and searches to stop calling the objects all the time
        Dba::write("ALTER TABLE `playlist` DROP COLUMN `username`;");
        $this->updateDatabase("ALTER TABLE `playlist` ADD COLUMN `username` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL;");

        Dba::write("ALTER TABLE `search` DROP COLUMN `username`;");
        $this->updateDatabase("ALTER TABLE `search` ADD COLUMN `username` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL;");

        // fill the data
        $this->updateDatabase("UPDATE `playlist`, (SELECT `id`, `username` FROM `user`) AS `user` SET `playlist`.`username` = `user`.`username` WHERE `playlist`.`user` = `user`.`id`;");
        $this->updateDatabase("UPDATE `search`, (SELECT `id`, `username` FROM `user`) AS `user` SET `search`.`username` = `user`.`username` WHERE `search`.`user` = `user`.`id`;");
        $this->updateDatabase("UPDATE `playlist` SET `playlist`.`username` = ? WHERE `playlist`.`user` = -1;", [T_('System')]);
        $this->updateDatabase("UPDATE `search` SET `search`.`username` = ? WHERE `search`.`user` = -1;", [T_('System')]);
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Generator {
        yield 'deleted_song' => "CREATE TABLE IF NOT EXISTS `deleted_song` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED DEFAULT 0, `delete_time` int(11) UNSIGNED DEFAULT 0, `title` varchar(255) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, `update_time` int(11) UNSIGNED DEFAULT 0, `album` int(11) UNSIGNED NOT NULL DEFAULT 0, `artist` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        yield 'deleted_video' => "CREATE TABLE IF NOT EXISTS `deleted_video` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
        yield 'deleted_podcast_episode' => "CREATE TABLE IF NOT EXISTS `deleted_podcast_episode` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `addition_time` int(11) UNSIGNED NOT NULL, `delete_time` int(11) UNSIGNED NOT NULL, `title` varchar(255) COLLATE $collation DEFAULT NULL, `file` varchar(4096) COLLATE $collation DEFAULT NULL, `catalog` int(11) UNSIGNED NOT NULL, `total_count` int(11) UNSIGNED NOT NULL DEFAULT 0, `total_skip` int(11) UNSIGNED NOT NULL DEFAULT 0, `podcast` int(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
