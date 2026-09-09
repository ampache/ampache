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
 *
 */

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Recompute the stored playlist totals
 *
 * Migration700005 filled `last_count` with its two parameters swapped, and `last_duration` never had an
 * initial fill at all: only the playlists touched since their column appeared carry a correct value. The
 * web browse sorts on both columns, so the damage is user-visible.
 */
final class Migration810007 extends AbstractMigration
{
    protected array $changelog = ['Recompute the stored playlist song counts and durations, wrong since their columns were added'];

    public function migrate(): void
    {
        // what Playlist::_update_last() writes: every entry counts, orphaned ones included
        $this->updateDatabase(
            "UPDATE `playlist` AS `p` "
            . "LEFT JOIN (SELECT `playlist`, COUNT(`id`) AS `total` FROM `playlist_data` "
            . "WHERE `object_type` IS NOT NULL GROUP BY `playlist`) AS `pd` ON `pd`.`playlist` = `p`.`id` "
            . "SET `p`.`last_count` = COALESCE(`pd`.`total`, 0);"
        );

        // and the duration side only sums the timed types still present in their table, so an orphaned or
        // untimed entry adds nothing, exactly like the per-type walk behind get_total_duration()
        $this->updateDatabase(
            "UPDATE `playlist` AS `p` "
            . "LEFT JOIN (SELECT `pd`.`playlist`, "
            . "SUM(COALESCE(`song`.`time`, 0) + COALESCE(`video`.`time`, 0) + COALESCE(`podcast_episode`.`time`, 0)) AS `total` "
            . "FROM `playlist_data` AS `pd` "
            . "LEFT JOIN `song` ON `pd`.`object_type` = 'song' AND `pd`.`object_id` = `song`.`id` "
            . "LEFT JOIN `video` ON `pd`.`object_type` = 'video' AND `pd`.`object_id` = `video`.`id` "
            . "LEFT JOIN `podcast_episode` ON `pd`.`object_type` = 'podcast_episode' AND `pd`.`object_id` = `podcast_episode`.`id` "
            . "GROUP BY `pd`.`playlist`) AS `pd` ON `pd`.`playlist` = `p`.`id` "
            . "SET `p`.`last_duration` = COALESCE(`pd`.`total`, 0);"
        );
    }
}
