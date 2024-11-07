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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration710001 extends AbstractMigration
{
    protected array $changelog = ['Add addition_time to artist table.'];

    public function migrate(): void
    {
        if (!Dba::read('SELECT `addition_time` FROM `artist` LIMIT 1;', [], true)) {
            $this->updateDatabase("ALTER TABLE `artist` ADD COLUMN `addition_time` int(11) UNSIGNED DEFAULT 0 NULL;");
        }

        $this->updateDatabase("UPDATE `artist`, (SELECT MIN(`song`.`addition_time`) AS `addition_time`, `artist_map`.`artist_id` FROM `song` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' AND `artist_map`.`object_type` IS NOT NULL GROUP BY `artist_map`.`artist_id` UNION SELECT MIN(`album`.`addition_time`) AS `addition_time`, `artist_map`.`artist_id` FROM `album` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `album`.`id` AND `artist_map`.`object_type` = 'album' AND `artist_map`.`object_type` IS NOT NULL GROUP BY `artist_map`.`artist_id`) AS `addition` SET `artist`.`addition_time` = `addition`.`addition_time` WHERE (`artist`.`addition_time` > `addition`.`addition_time` OR `artist`.`addition_time` IS NULL) AND `addition`.`artist_id` = `artist`.`id`;");
    }
}
