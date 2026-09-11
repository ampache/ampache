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
 * Let a per-user upload list find its rows instead of walking the whole title index.
 */
final class Migration810003 extends AbstractMigration
{
    protected array $changelog = [
        'Add an index on `song`.`user_upload` for the upload browses',
    ];

    public function migrate(): void
    {
        // `Catalog::get_uploads_sql('song', $user_id)` filters on `user_upload`, which carried no key. With a
        // sort on the title the optimiser walked `title_enabled_IDX` end to end and checked the column per row:
        // on one instance, 61744 rows read to return the 2 a user had uploaded. The key makes it 2
        if (!Dba::has_index('song', 'user_upload')) {
            $this->updateDatabase('ALTER TABLE `song` ADD KEY `user_upload` (`user_upload`);');
        }
    }
}
