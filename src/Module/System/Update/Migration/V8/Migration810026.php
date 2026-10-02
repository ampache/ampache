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
 * Let the similar artists/songs lookup find its rows instead of scanning the whole table.
 */
final class Migration810026 extends AbstractMigration
{
    protected array $changelog = [
        'Add an index on `recommendation_item`.`recommendation` for the similar artists/songs lookup',
    ];

    public function migrate(): void
    {
        // Recommendation::get_items() filters on `recommendation` with no key, so the optimiser walked the
        // whole table per lookup. Reported on GitHub (#4528) as a full scan of 2.6M rows taking ~1.3s; the
        // key drops that to ~100 rows read and well under 1ms
        if (!Dba::has_index('recommendation_item', 'recommendation_item_recommendation_IDX')) {
            $this->updateDatabase('ALTER TABLE `recommendation_item` ADD KEY `recommendation_item_recommendation_IDX` (`recommendation`);');
        }
    }
}
