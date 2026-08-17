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
 * Index `folder_map`.`path_name` so a folder page's subtree media count stops needing a full table scan.
 */
final class Migration802001 extends AbstractMigration
{
    protected array $changelog = [
        'Add an index on `folder_map`.`path_name` so counting the media below a folder can use an index instead of scanning the whole table',
    ];

    public function migrate(): void
    {
        // `getMediaCount()` matches `folder_id = ? OR path_name LIKE '<path>/%'`; without a key starting with
        // `path_name`, that OR can't use an index at all and every folder page pays for a full table scan.
        if (!Dba::has_index('folder_map', 'path_name_index')) {
            $this->updateDatabase('ALTER TABLE `folder_map` ADD KEY `path_name_index` (`path_name`);');
        }
    }
}
