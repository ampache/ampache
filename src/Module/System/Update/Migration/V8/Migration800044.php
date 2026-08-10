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
 *
 */

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Index the columns the play history and flag lists are read by.
 */
final class Migration800044 extends AbstractMigration
{
    protected array $changelog = [
        'Add an index on `object_count`.`geo_latitude`, `geo_longitude` so a cached place name is a lookup',
        'Add an index on `user_flag`.`object_type`, `date` so the newest flagged lists stop at the rows they show',
    ];

    public function migrate(): void
    {
        $indexes = [
            ['object_count', 'object_count_geo_IDX', '`geo_latitude`, `geo_longitude`'],
            ['user_flag', 'object_type_date_IDX', '`object_type`, `date`'],
        ];

        foreach ($indexes as [$table, $index, $columns]) {
            if (!Dba::has_index($table, $index)) {
                $this->updateDatabase(sprintf('ALTER TABLE `%s` ADD KEY `%s` (%s);', $table, $index, $columns));
            }
        }
    }
}
