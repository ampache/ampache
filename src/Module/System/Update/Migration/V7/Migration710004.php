<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add `total_skip` to podcast table
 */
final class Migration710004 extends AbstractMigration
{
    protected array $changelog = [
        'Add `total_skip` to album table',
        'Add `total_skip` to album_disk table',
        'Add `total_skip` to artist table',
    ];

    public function migrate(): void
    {
        // create total_skip column in album table
        if (!Dba::read('SELECT `total_skip` FROM `album` LIMIT 1;', [], true)) {
            $this->updateDatabase("ALTER TABLE `album` ADD COLUMN `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `total_count`;");
        }

        $this->updateDatabase("UPDATE `album`, (SELECT SUM(`song`.`total_skip`) AS `total_skip`, `album` FROM `song` GROUP BY `song`.`album`, `song`.`disk`) AS `object_count` SET `album`.`total_skip` = `object_count`.`total_skip` WHERE `album`.`total_skip` != `object_count`.`total_skip` AND `album`.`id` = `object_count`.`album`;");

        // create total_skip column in album_disk table
        if (!Dba::read('SELECT `total_skip` FROM `album_disk` LIMIT 1;', [], true)) {
            $this->updateDatabase("ALTER TABLE `album_disk` ADD COLUMN `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `total_count`;");
        }

        $this->updateDatabase("UPDATE `album_disk`, (SELECT SUM(`song`.`total_skip`) AS `total_skip`, `album`, `disk` FROM `song` GROUP BY `song`.`album`, `song`.`disk`) AS `object_count` SET `album_disk`.`total_skip` = `object_count`.`total_skip` WHERE `album_disk`.`total_skip` != `object_count`.`total_skip` AND `album_disk`.`album_id` = `object_count`.`album` AND `album_disk`.`disk` = `object_count`.`disk`;");

        // create total_skip column in artist table
        if (!Dba::read('SELECT `total_skip` FROM `artist` LIMIT 1;', [], true)) {
            $this->updateDatabase("ALTER TABLE `artist` ADD COLUMN `total_skip` int(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `total_count`;");
        }

        $this->updateDatabase("UPDATE `artist`, (SELECT COUNT(`object_count`.`object_id`) AS `total_skip`, `artist_map`.`artist_id` FROM `object_count` LEFT JOIN `artist_map` ON `artist_map`.`object_type` = 'song' AND `artist_map`.`object_id` = `object_count`.`object_id` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' GROUP BY `artist_map`.`artist_id`) AS `object_count` SET `artist`.`total_skip` = `object_count`.`total_skip` WHERE `artist`.`total_skip` != `object_count`.`total_skip` AND `artist`.`id` = `object_count`.`artist_id`;");
    }
}
