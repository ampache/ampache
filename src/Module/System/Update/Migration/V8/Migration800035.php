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
 * Let a collection hold the same object twice, which makes `track` the only unambiguous address for a member
 *
 * The unique key decided the question in the schema, where no preference could reach it. Playlists have always
 * left it to `unique_playlist`; collections now share that preference instead of hard-coding the stricter answer.
 *
 * `Migration800028` still creates the key, so this runs against every database rather than only upgrades.
 */
final class Migration800035 extends AbstractMigration
{
    protected array $changelog = [
        'Drop the unique key on `collection_map` so `unique_playlist` decides whether a collection allows duplicates',
    ];

    public function migrate(): void
    {
        // Both writes are silent because there is no `has_index()` to ask first, so a replay after a partly
        // applied run has to be allowed to fail
        Dba::write('ALTER TABLE `collection_map` DROP KEY `unique_collection_map`;', [], true);

        // Keeps the lookups the dropped key served: removing by object, and the uniqueness check
        Dba::write('ALTER TABLE `collection_map` ADD KEY `collection_object_IDX` (`collection`,`object_type`,`object_id`);', [], true);

        // Description only, by hand: `Preference::insert(replace: true)` deletes the row first, which would
        // reset the value every user has already chosen for their playlists
        $this->updateDatabase(
            "UPDATE `preference` SET `description` = 'Only add unique items to playlists and collections' WHERE `name` = 'unique_playlist';"
        );
    }
}
