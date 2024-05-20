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

use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration600070 extends AbstractMigration
{
    protected array $changelog = ['Add Indexes to object_count: object_count_idx_count_object_date_object and object_count_idx_count_type_object_typ_object_id'];

    public function migrate(): void
    {
        $this->updateDatabase('ALTER TABLE `object_count` ADD INDEX IF NOT EXISTS `object_count_idx_count_object_date_object` (`count_type`,`object_type`,`date`,`object_id`);');
        $this->updateDatabase('ALTER TABLE `object_count` ADD INDEX IF NOT EXISTS `object_count_idx_count_type_object_typ_object_id` (`count_type`,`object_type`,`object_id`);');
    }
}
