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
 * Index the play history the way a smart playlist reads it
 */
final class Migration810009 extends AbstractMigration
{
    protected array $changelog = ['Add an index on `object_count` for the play history rules a smart playlist searches on'];

    public function migrate(): void
    {
        // Rules like `last_play`, `myplayed` or `myskipped_times` group one listener's history, and the
        // artist ones reach it without an object type: no key started with the columns they all filter on.
        if (!Dba::has_index('object_count', 'object_count_history_IDX')) {
            $this->updateDatabase(
                'ALTER TABLE `object_count` ADD KEY `object_count_history_IDX` '
                . '(`count_type`, `user`, `object_type`, `object_id`, `date`);'
            );
        }
    }
}
