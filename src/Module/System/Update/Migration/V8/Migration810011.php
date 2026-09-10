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
 * Add `album`.`hidden` and `artist`.`hidden` so a release can be taken off the shelves without deleting it
 *
 * Deleting the rows would take the playlist entries, ratings and play history with them, which is why an
 * artist asking for a takedown had no answer before this. The flag is read like `tag`.`is_hidden`: the
 * browse filter and its `album_hidden`/`artist_hidden` type, and one exclusion for everyone below manager.
 */
final class Migration810011 extends AbstractMigration
{
    protected array $changelog = [
        'Add `album`.`hidden` and `artist`.`hidden`, so an album or artist can be withdrawn without deleting it',
    ];

    public function migrate(): void
    {
        // A partly-applied migration re-runs from the top, so each column is only added when it is absent.
        foreach (['album', 'artist'] as $table) {
            if (!Dba::has_column($table, 'hidden')) {
                $this->updateDatabase(
                    sprintf('ALTER TABLE `%s` ADD COLUMN `hidden` tinyint(1) unsigned NOT NULL DEFAULT 0;', $table)
                );
            }

            // every browse of these tables now carries `hidden` = 0, so the column is read on all of them
            if (!Dba::has_index($table, 'hidden')) {
                $this->updateDatabase(sprintf('ALTER TABLE `%s` ADD KEY `hidden` (`hidden`);', $table));
            }
        }
    }
}
