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

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Create `total_count` and `total_skip` to album, artist, song, video and podcast_episode tables
 * Fill counts into the columns
 */
final class Migration500002 extends AbstractMigration
{
    protected array $changelog = [
        'Create `total_count` and `total_skip` to album, artist, song, video and podcast_episode tables',
        'Fill counts into the columns'
    ];

    public function migrate(): void
    {
        // tables which usually calculate a count
        $tables = ['album', 'artist', 'song', 'video', 'podcast_episode'];
        foreach ($tables as $type) {
            Dba::write("ALTER TABLE `$type` DROP COLUMN `total_count`;");
            $this->updateDatabase("ALTER TABLE `$type` ADD COLUMN `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0';");

            $this->updateDatabase("UPDATE `$type`, (SELECT COUNT(`object_count`.`object_id`) AS `total_count`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = '$type' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` SET `$type`.`total_count` = `object_count`.`total_count` WHERE `$type`.`total_count` != `object_count`.`total_count` AND `$type`.`id` = `object_count`.`object_id`;");
        }

        // tables that also have a skip count
        $tables = ['song', 'video', 'podcast_episode'];
        foreach ($tables as $type) {
            Dba::write("ALTER TABLE `$type` DROP COLUMN `total_skip`;");
            $this->updateDatabase("ALTER TABLE `$type` ADD COLUMN `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0';");

            $this->updateDatabase("UPDATE `$type`, (SELECT COUNT(`object_count`.`object_id`) AS `total_skip`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = '$type' AND `object_count`.`count_type` = 'skip' GROUP BY `object_count`.`object_id`) AS `object_count` SET `$type`.`total_skip` = `object_count`.`total_skip` WHERE `$type`.`total_skip` != `object_count`.`total_skip` AND `$type`.`id` = `object_count`.`object_id`;");
        }
    }
}
