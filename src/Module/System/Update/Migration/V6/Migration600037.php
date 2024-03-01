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

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;

/**
 * Update user server and user counts now that the scaling has changed
 */
final class Migration600037 extends AbstractMigration
{
    protected array $changelog = ['Update user server and user counts now that the scaling has changed'];

    public function migrate(): void
    {
        // update server total counts
        $catalog_disable = AmpConfig::get('catalog_disable');
        // tables with media items to count, song-related tables and the rest
        $media_tables = array('song', 'video', 'podcast_episode');
        $items        = 0;
        $time         = 0;
        $size         = 0;
        foreach ($media_tables as $table) {
            $enabled_sql = ($catalog_disable) ? " WHERE `$table`.`enabled` = '1'" : '';
            $sql         = "SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`)/1024/1024, 0) FROM `$table`" . $enabled_sql;
            $db_results  = Dba::read($sql);
            $row         = Dba::fetch_row($db_results);
            // save the object and add to the current size
            $items += (int)($row[0] ?? 0);
            $time += (int)($row[1] ?? 0);
            $size += $row[2] ?? 0;
            Catalog::set_update_info($table, (int)($row[0] ?? 0));
        }
        Catalog::set_update_info('items', $items);
        Catalog::set_update_info('time', $time);
        Catalog::set_update_info('size', $size);
        User::update_counts();
    }
}
