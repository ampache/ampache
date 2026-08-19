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
 * Make `image` lookups by object id index-only, instead of reading the blob-bearing row for every match.
 */
final class Migration810002 extends AbstractMigration
{
    protected array $changelog = [
        'Add a covering index on `image` for the art cache lookup, and drop the `object_id` key it replaces',
    ];

    public function migrate(): void
    {
        // `ImageRepository::getRowsByObjectIds()` matches `object_id IN (...)` (optionally `AND object_type = ?`)
        // and only ever selects `object_type`, `object_id`, `mime`, `size`. The old `object_id` key could locate
        // the matching rows but not answer the query from the index alone, so every match still pulled in its
        // `image` blob just to read two short columns. This key carries every column the query needs
        if (!Dba::has_index('image', 'object_id_type_IDX')) {
            $this->updateDatabase('ALTER TABLE `image` ADD KEY `object_id_type_IDX` (`object_id`, `object_type`, `size`, `mime`);');
        }

        // now a redundant prefix of the key above
        if (Dba::has_index('image', 'object_id') && Dba::has_index('image', 'object_id_type_IDX')) {
            $this->updateDatabase('ALTER TABLE `image` DROP KEY `object_id`;');
        }
    }
}
