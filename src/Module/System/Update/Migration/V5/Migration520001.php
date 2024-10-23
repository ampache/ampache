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
 * Make sure preference names are always unique
 */
final class Migration520001 extends AbstractMigration
{
    protected array $changelog = ['Make sure preference names are always unique'];

    public function migrate(): void
    {
        $sql        = "SELECT `id` FROM `preference` WHERE `name` IN (SELECT `name` FROM `preference` GROUP BY `name` HAVING count(`name`) >1) AND `id` NOT IN (SELECT MIN(`id`) FROM `preference` GROUP by `name`);";
        $dupe_prefs = Dba::read($sql);
        $pref_list  = [];
        while ($results = Dba::fetch_assoc($dupe_prefs)) {
            $pref_list[] = (int)$results['id'];
        }

        // delete duplicates (if they exist)
        foreach ($pref_list as $pref_id) {
            $sql = "DELETE FROM `preference` WHERE `id` = ?;";
            $this->updateDatabase($sql, [$pref_id]);
        }

        $this->updateDatabase("DELETE FROM `user_preference` WHERE `preference` NOT IN (SELECT `id` FROM `preference`);");
        Dba::write("ALTER TABLE `preference` DROP KEY `preference_UN`;", [], true);
        $this->updateDatabase("ALTER TABLE `preference` ADD CONSTRAINT preference_UN UNIQUE KEY (`name`);");
    }
}
