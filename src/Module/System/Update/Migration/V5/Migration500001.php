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

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add `release_status`, `addition_time`, `catalog` to album table
 * Add `mbid`, `country`, `active` to label table
 * Fill the album `catalog` and `time` values using the song table
 * Fill the artist `album_count`, `album_group_count` and `song_count` values
 */
final class Migration500001 extends AbstractMigration
{
    protected array $changelog = [
        'Add `release_status`, `addition_time`, `catalog` to album table',
        'Add `mbid`, `country` and `active` to label table',
        'Fill the album `catalog` value using the song table',
        'Fill the artist `album_count`, `album_group_count` and `song_count` values'
    ];

    public function migrate(): void
    {
        $this->updateDatabase("ALTER TABLE `album` ADD `release_status` varchar(32) DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `album` ADD `addition_time` int(11) UNSIGNED DEFAULT 0 NULL;");
        $this->updateDatabase("ALTER TABLE `album` ADD `catalog` int(11) UNSIGNED NOT NULL DEFAULT '0';");
        $this->updateDatabase("ALTER TABLE `label` ADD `mbid` varchar(36) DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `label` ADD `country` varchar(64) DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `label` ADD `active` tinyint(1) UNSIGNED NOT NULL DEFAULT '1';");
        $this->updateDatabase("UPDATE `album`, (SELECT min(`song`.`catalog`) AS `catalog`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`catalog` = `song`.`catalog` WHERE `album`.`catalog` != `song`.`catalog` AND `album`.`id` = `song`.`album`;");
        $this->updateDatabase("UPDATE `album`, (SELECT SUM(`song`.`time`) AS `time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`time` = `song`.`time` WHERE `album`.`time` != `song`.`time` AND `album`.`id` = `song`.`album`;");
        $this->updateDatabase("UPDATE `album`, (SELECT MIN(`song`.`addition_time`) AS `addition_time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`addition_time` = `song`.`addition_time` WHERE `album`.`addition_time` != `song`.`addition_time` AND `song`.`album` = `album`.`id`;");
    }
}