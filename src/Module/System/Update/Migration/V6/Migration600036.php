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

namespace Ampache\Module\System\Update\Migration\V6;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Ampache\Repository\Model\User;

/**
 * Update user `play_size` and catalog `size` fields to megabytes (Stop large catalogs overflowing 32bit ints)
 */
final class Migration600036 extends AbstractMigration
{
    protected array $changelog = ['Update user `play_size` and catalog `size` fields to megabytes (Stop large catalogs overflowing 32bit ints)'];

    public function migrate(): void
    {
        $sql       = "SELECT `id` FROM `user`";
        $db_users  = Dba::read($sql);
        $user_list = array();
        while ($results = Dba::fetch_assoc($db_users)) {
            $user_list[] = (int)$results['id'];
        }

        // After the change recalculate their total Bandwidth Usage
        foreach ($user_list as $user_id) {
            $total = User::get_play_size($user_id);
            $sql   = "REPLACE INTO `user_data` SET `user` = ?, `key` = ?, `value` = ?;";
            $this->updateDatabase($sql, array($user_id, 'play_size', $total));
        }
    }
}
