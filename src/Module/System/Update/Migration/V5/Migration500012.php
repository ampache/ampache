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

namespace Ampache\Module\System\Update\Migration\V5;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Move user bandwidth calculations out of the user format function into the user_data table
 */
final class Migration500012 extends AbstractMigration
{
    protected array $changelog = ['Move user bandwidth calculations out of the user format function into the user_data table'];

    public function migrate(): void
    {
        $sql       = "SELECT `id` FROM `user`";
        $db_users  = Dba::read($sql);
        $user_list = array();
        while ($results = Dba::fetch_assoc($db_users)) {
            $user_list[] = (int)$results['id'];
        }
        // Calculate their total Bandwidth Usage
        foreach ($user_list as $user_id) {
            $params = array($user_id);
            $total  = 0;
            $sql_s  = "SELECT IFNULL(SUM(`size`), 0) AS `size` FROM `object_count` LEFT JOIN `song` ON `song`.`id`=`object_count`.`object_id` AND `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = ?;";
            $db_s   = Dba::read($sql_s, $params);
            while ($results = Dba::fetch_assoc($db_s)) {
                $total = $total + $results['size'];
            }
            $sql_v = "SELECT IFNULL(SUM(`size`), 0) AS `size` FROM `object_count` LEFT JOIN `video` ON `video`.`id`=`object_count`.`object_id` AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'video' AND `object_count`.`user` = ?;";
            $db_v  = Dba::read($sql_v, $params);
            while ($results = Dba::fetch_assoc($db_v)) {
                $total = $total + $results['size'];
            }
            $sql_p = "SELECT IFNULL(SUM(`size`), 0) AS `size` FROM `object_count`LEFT JOIN `podcast_episode` ON `podcast_episode`.`id`=`object_count`.`object_id` AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`user` = ?;";
            $db_p  = Dba::read($sql_p, $params);
            while ($results = Dba::fetch_assoc($db_p)) {
                $total = $total + $results['size'];
            }

            $this->updateDatabase("REPLACE INTO `user_data` SET `user` = ?, `key` = ?, `value` = ?;");
        }
    }
}
