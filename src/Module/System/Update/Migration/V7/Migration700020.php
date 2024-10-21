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
use Ampache\Repository\Model\User;

/**
 * Update `user_preference`.`name` to match `preference`.`name` column
 * Delete duplicate user_preferences
 * Require unique preference names per-user in `user_preference` table
 */
final class Migration700020 extends AbstractMigration
{
    protected array $changelog = [
        'Update `user_preference`.`name` to match `preference`.`name` column',
        'Delete duplicate user_preferences',
        'Require unique preference names per-user in `user_preference` table'
    ];

    public function migrate(): void
    {
        // update user_preference.name to match preference name
        $this->updateDatabase('ALTER TABLE `user_preference` CHANGE COLUMN `name` `name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;');

        // Ensure valid prefs are set
        User::rebuild_all_preferences();

        // delete duplicates before setting the unique key
        $sql        = "SELECT `user`, `name` FROM `user_preference` GROUP BY `user`, `name` HAVING COUNT(CONCAT(`user`, `name`)) > 1;";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $sql = "DELETE FROM `user_preference` WHERE `user` = ? AND `name` = ? LIMIT 1;";
            Dba::write($sql, [$row['user'], $row['name']]);
        }

        // Require unique preference names per-user in `user_preference` table
        Dba::write("ALTER TABLE `user_preference` DROP KEY `unique_name`;", [], true);
        $this->updateDatabase("ALTER TABLE `user_preference` ADD UNIQUE `unique_name` (`user`, `name`);");
    }
}
