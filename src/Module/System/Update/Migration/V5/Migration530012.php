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
 * Index data on object_count
 */
final class Migration530012 extends AbstractMigration
{
    protected array $changelog = ['Index data on object_count'];

    public function migrate(): void
    {
        Dba::write("ALTER TABLE `object_count` DROP KEY `object_count_full_index`;");
        $this->updateDatabase("CREATE INDEX `object_count_full_index` USING BTREE ON `object_count` (`object_type`, `object_id`, `date`, `user`, `agent`, `count_type`);");

        Dba::write("ALTER TABLE `object_count` DROP KEY `object_count_type_IDX`;");
        $this->updateDatabase("CREATE INDEX `object_count_type_IDX` USING BTREE ON `object_count` (`object_type`, `object_id`);");

        Dba::write("ALTER TABLE `object_count` DROP KEY `object_count_date_IDX`;");
        $this->updateDatabase("CREATE INDEX `object_count_date_IDX` USING BTREE ON `object_count` (`date`, `count_type`);");

        Dba::write("ALTER TABLE `object_count` DROP KEY `object_count_user_IDX`;");
        $this->updateDatabase("CREATE INDEX `object_count_user_IDX` USING BTREE ON `object_count` (`object_type`, `object_id`, `user`, `count_type`);");
    }
}
