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
 * Add song_count, artist_count to album
 */
final class Migration500005 extends AbstractMigration
{
    protected array $changelog = ['Add song_count, artist_count to album'];

    public function migrate(): void
    {
        Dba::write("ALTER TABLE `album` DROP COLUMN `song_count`;");
        $this->updateDatabase("ALTER TABLE `album` ADD COLUMN `song_count` smallint(5) unsigned DEFAULT 0 NULL;");
        Dba::write("ALTER TABLE `album` DROP COLUMN `artist_count`;");
        $this->updateDatabase("ALTER TABLE `album` ADD COLUMN `artist_count` smallint(5) unsigned DEFAULT 0 NULL;");
        $this->updateDatabase("REPLACE INTO `update_info` SET `key`= 'album_group', `value`= (SELECT COUNT(DISTINCT(`album`.`id`)) AS `count` FROM `album` WHERE `id` in (SELECT MIN(`id`) FROM `album` GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`, `album`.`mbid_group`));");
        $this->updateDatabase("UPDATE `album`, (SELECT COUNT(`song`.`id`) AS `song_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`song_count` = `song`.`song_count` WHERE `album`.`song_count` != `song`.`song_count` AND `album`.`id` = `song`.`album`;");
        $this->updateDatabase("UPDATE `album`, (SELECT COUNT(DISTINCT(`song`.`artist`)) AS `artist_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`artist_count` = `song`.`artist_count` WHERE `album`.`artist_count` != `song`.`artist_count` AND `album`.`id` = `song`.`album`;");
    }
}
