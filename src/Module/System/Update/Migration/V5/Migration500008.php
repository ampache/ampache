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
 * Add filter_user to catalog table, set unique on user_data
 */
final class Migration500008 extends AbstractMigration
{
    protected array $changelog = [
        'Add filter_user to catalog table',
        'Set a unique key on user_data',
    ];

    public function migrate(): void
    {
        Dba::write("ALTER TABLE `catalog` DROP COLUMN `filter_user`;");
        $this->updateDatabase("ALTER TABLE `catalog` ADD COLUMN `filter_user` int(11) unsigned DEFAULT 0 NOT NULL;");

        $tables = [
            'podcast',
            'live_stream'
        ];
        foreach ($tables as $type) {
            $this->updateDatabase("REPLACE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `$type`.`catalog`, '$type', `$type`.`id` FROM `$type`;");
        }

        Dba::write("ALTER TABLE `user_data` DROP KEY `unique_data`;");
        $this->updateDatabase("ALTER TABLE `user_data` ADD UNIQUE `unique_data` (`user`, `key`);");
    }
}
