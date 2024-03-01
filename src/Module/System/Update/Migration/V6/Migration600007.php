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

namespace Ampache\Module\System\Update\Migration\V6;

use Ampache\Module\System\Update\Migration\AbstractMigration;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;

/**
 * Fill album_disk table update count tables
 */
final class Migration600007 extends AbstractMigration
{
    protected array $changelog = ['Fill album_disk table update count tables'];

    public function migrate(): void
    {
        $this->updateDatabase("UPDATE `album`, (SELECT COUNT(DISTINCT `album_disk`.`disk`) AS `disk_count`, `album_id` FROM `album_disk` GROUP BY `album_disk`.`album_id`) AS `album_disk` SET `album`.`disk_count` = `album_disk`.`disk_count` WHERE `album`.`disk_count` != `album_disk`.`disk_count` AND `album`.`id` = `album_disk`.`album_id`");
        $this->updateDatabase("UPDATE `album_disk`, (SELECT COUNT(DISTINCT `album_disk`.`disk`) AS `disk_count`, `album_id` FROM `album_disk` GROUP BY `album_disk`.`album_id`) AS `disk_count` SET `album_disk`.`disk_count` = `disk_count`.`disk_count` WHERE `album_disk`.`disk_count` != `disk_count`.`disk_count` AND `album_disk`.`album_id` = `disk_count`.`album_id`");
        $this->updateDatabase("UPDATE `album_disk`, (SELECT SUM(`time`) AS `time`, `album`, `disk` FROM `song` GROUP BY `album`, `disk`) AS `song` SET `album_disk`.`time` = `song`.`time` WHERE (`album_disk`.`time` != `song`.`time` OR `album_disk`.`time` IS NULL) AND `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk`");
        $this->updateDatabase("UPDATE `album_disk`, (SELECT COUNT(DISTINCT `id`) AS `song_count`, `album`, `disk` FROM `song` GROUP BY `album`, `disk`) AS `song` SET `album_disk`.`song_count` = `song`.`song_count` WHERE `album_disk`.`song_count` != `song`.`song_count` AND `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk`");
        $this->updateDatabase("UPDATE `album_disk`, (SELECT SUM(`song`.`total_count`) AS `total_count`, `album_disk`.`id` AS `object_id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` GROUP BY `album_disk`.`id`) AS `object_count` SET `album_disk`.`total_count` = `object_count`.`total_count` WHERE `album_disk`.`total_count` != `object_count`.`total_count` AND `album_disk`.`id` = `object_count`.`object_id`");

        // now that the data is in it can update counts
        Album::update_table_counts();
        Artist::update_table_counts();
    }
}
