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
 * Migrate catalog `filter_user` settings to catalog_filter groups
 * Assign all public catalogs to the DEFAULT group
 * Drop table `user_catalog`
 * Remove `filter_user` from the `catalog` table
 */
final class Migration550002 extends AbstractMigration
{
    protected array $changelog = [
        '**IMPORTANT UPDATE NOTES** Any user that has a private catalog will have their own filter group created which includes all public catalogs',
        'Migrate catalog `filter_user` settings to the `catalog_filter_group` table',
        'Assign all public catalogs to the DEFAULT group',
        'Drop table `user_catalog`',
        'Remove `filter_user` from the `catalog` table',
    ];

    public function migrate(): void
    {
        // Copy existing filters into individual groups for each user. (if a user only has access to public catalogs they are given the default list)
        $sql        = "SELECT `id`, `username` FROM `user`;";
        $db_results = Dba::read($sql);
        $user_list  = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $user_list[$row['id']] = $row['username'];
        }
        // If the user had a private catalog, create an individual group for them using the current filter and public catalogs.
        foreach ($user_list as $key => $value) {
            $group_id   = 0;
            $sql        = 'SELECT `filter_user` FROM `catalog` WHERE `filter_user` = ?;';
            $db_results = Dba::read($sql, array($key));
            if (Dba::num_rows($db_results)) {
                $sql = "INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('" . Dba::escape($value) . "');";
                Dba::write($sql);
                $group_id = (int)Dba::insert_id();
            }
            if ($group_id > 0) {
                $sql        = "SELECT `id`, `filter_user` FROM `catalog`;";
                $db_results = Dba::read($sql);
                while ($row = Dba::fetch_assoc($db_results)) {
                    $catalog = $row['id'];
                    $enabled = ($row['filter_user'] == 0 || $row['filter_user'] == $key)
                        ? 1
                        : 0;
                    $this->updateDatabase("INSERT IGNORE INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) VALUES ($group_id, $catalog, $enabled);");
                }
                $sql = "UPDATE `user` SET `catalog_filter_group` = ? WHERE `id` = ?";
                Dba::write($sql, array($group_id, $key));
            }
        }

        // Add all public catalogs in the DEFAULT profile.
        $sql        = "SELECT `id` FROM `catalog` WHERE `filter_user` = 0;";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog = (int)$row['id'];
            $this->updateDatabase("INSERT IGNORE INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) VALUES (0, $catalog, 1);");
        }

        $this->updateDatabase("DROP TABLE IF EXISTS `user_catalog`;");

        // Drop filter_user but only if the migration has worked
        Dba::write("ALTER TABLE `catalog` DROP COLUMN `filter_user`;");
    }
}
