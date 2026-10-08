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
 * Add `playlist_folder`.`type`, so a folder's owner can share it read-only with other users
 */
final class Migration830001 extends AbstractMigration
{
    protected array $changelog = [
        'Add `playlist_folder`.`type`, so a folder can be shared read-only with other users',
    ];

    public function migrate(): void
    {
        if (!Dba::has_column('playlist_folder', 'type')) {
            $this->updateDatabase(
                "ALTER TABLE `playlist_folder` ADD COLUMN `type` enum('private','public') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'private';"
            );
        }
    }
}
