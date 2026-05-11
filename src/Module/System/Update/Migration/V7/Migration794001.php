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
 * Add weight columns to song, album, artist, podcast, podcast_episode and video tables
 */
final class Migration794001 extends AbstractMigration
{
    protected array $changelog = ['Add weight columns to song, album, artist, podcast, podcast_episode and video tables'];

    protected bool $warning = true;

    public function migrate(): void
    {
        // tables which can be weighted based on activity
        $tables = [
            'album',
            'artist',
            'podcast',
            'podcast_episode',
            'song',
            'video',
        ];
        foreach ($tables as $type) {
            Dba::write("ALTER TABLE `$type` DROP COLUMN `weight`;", [], true);
            $this->updateDatabase("ALTER TABLE `$type` ADD COLUMN `weight` int(11) SIGNED NOT NULL DEFAULT '0';");

            $this->updateDatabase("UPDATE `$type` LEFT JOIN (SELECT `object_id`, COUNT(*) AS `rating_count` FROM `rating`  WHERE `object_type` = '$type' GROUP BY `object_id`) `rating` ON `$type`.`id` = `rating`.`object_id` LEFT JOIN (SELECT `object_id`, COUNT(*) AS `flag_count` FROM `user_flag` WHERE `object_type` = '$type' GROUP BY `object_id`) `flag` ON `$type`.`id` = `flag`.`object_id` SET `$type`.`weight` = COALESCE(`rating`.`rating_count`, 0) + COALESCE(`flag`.`flag_count`, 0) + (COALESCE(`$type`.`total_count`, 0) - COALESCE(`$type`.`total_skip`, 0));");
        }
    }
}
