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
use Ampache\Repository\Model\Catalog;

/**
 * Fix up Orphan Album Disk objects to be unique and update from tags
 */
final class Migration794004 extends AbstractMigration
{
    protected array $changelog = ['Fix up Orphan Album Disk objects to be unique and update from tags'];

    protected bool $warning = true;

    public function migrate(): void
    {
        // set the original disk id to be the unique album_disk
        $this->updateDatabase("UPDATE `album_disk` SET `catalog` = 0 WHERE `album_id` IN (SELECT `id` FROM `album` WHERE `name` = 'Unknown (Orphaned)' OR name = ? AND `catalog` = 0) ORDER BY `id` ASC LIMIT 1;", [T_('Unknown (Orphaned)')]);

        // Find duplicate orphans and remove them
        $tables = [
            'object_count',
            'rating',
            'share',
            'tag_map',
            'user_activity',
            'user_flag'
        ];
        $db_results = Dba::read("SELECT `album_disk`.`id`, `album_disk`.`catalog` FROM `album_disk` LEFT JOIN `album` ON `album_id` = `album`.id WHERE `album`.`name` = 'Unknown (Orphaned)' OR `album`.`name` = ?;", [T_('Unknown (Orphaned)')]);
        $orphan_id  = null;
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['catalog'] == 0) {
                $orphan_id = $row['id'];
            } else {
                $results[] = $row['id'];
            }
        }
        foreach ($results as $album_disk_id) {
            foreach ($tables as $table) {
                $this->updateDatabase("UPDATE IGNORE `" . $table . "` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = 'album_disk'", [$orphan_id, $album_disk_id]);
            }
            Dba::write("DELETE FROM `album_disk` WHERE `id` = ?;", [$album_disk_id]);
        }
        if ($results !== []) {
            Catalog::clean_empty_albums();
        }
    }
}
