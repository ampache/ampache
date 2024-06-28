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
 * Add `total_count` to podcast table and fill counts into the column
 */
final class Migration500011 extends AbstractMigration
{
    protected array $changelog = ['Add `total_count` to podcast table and fill counts into the column'];

    public function migrate(): void
    {
        Dba::write("ALTER TABLE `podcast` DROP COLUMN `total_count`;");
        $this->updateDatabase("ALTER TABLE `podcast` ADD COLUMN `total_count` int(11) UNSIGNED NOT NULL DEFAULT '0';");
        $this->updateDatabase("UPDATE `podcast`, (SELECT SUM(`podcast_episode`.`total_count`) AS `total_count`, `podcast` FROM `podcast_episode` GROUP BY `podcast_episode`.`podcast`) AS `object_count` SET `podcast`.`total_count` = `object_count`.`total_count` WHERE `podcast`.`total_count` != `object_count`.`total_count` AND `podcast`.`id` = `object_count`.`podcast`;");
    }
}
