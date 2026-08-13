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
 * Drop indexes whose columns are already the leftmost prefix of a wider key on the same table.
 */
final class Migration800051 extends AbstractMigration
{
    protected array $changelog = [
        'Drop redundant indexes that repeat the leading columns of a wider key on the same table',
    ];

    public function migrate(): void
    {
        // each row is table, the redundant key, and the wider key that covers it; every covering key outlives this migration
        $indexes = [
            ['album_disk', 'id_index', 'id_disk_index'],
            ['album_disk', 'album_id_type_index', 'unique_album_disk'],
            ['album_map', 'object_id_index', 'unique_album_map'],
            ['album_map', 'object_id_type_index', 'unique_album_map'],
            ['album_map', 'object_type_IDX', 'object_type_id_IDX'],
            ['artist_map', 'object_id_index', 'unique_artist_map'],
            ['artist_map', 'object_id_type_index', 'unique_artist_map'],
            ['artist_map', 'artist_id_index', 'artist_id_object_type_id_IDX'],
            ['artist_map', 'artist_id_type_index', 'artist_id_object_type_id_IDX'],
            ['catalog_map', 'catalog_id_object_type_IDX', 'catalog_id_object_type_id_IDX'],
            ['folder', 'catalog', 'folder_catalog_IDX'],
            ['folder_map', 'object_id_index', 'unique_folder_map'],
            ['folder_map', 'object_id_type_index', 'unique_folder_map'],
            ['folder_map', 'object_type_IDX', 'object_type_id_IDX'],
            ['image', 'object_type', 'object_type_size_kind_IDX'],
            ['metadata', 'object_id', 'objecttype'],
            ['playlist_data', 'playlist', 'playlist_object_type_IDX'],
            ['preference', 'name', 'preference_UN'],
            ['rating', 'user_object_type_IDX', 'unique_rating'],
            ['recommendation', 'object_type_IDX', 'object_type_object_id_IDX'],
            ['song_map', 'object_id_index', 'unique_song_map'],
            ['song_map', 'object_id_type_index', 'unique_song_map'],
            ['user_data', 'user', 'unique_data'],
            ['user_flag', 'user_object_type_IDX', 'unique_userflag'],
        ];

        foreach ($indexes as [$table, $index, $covering]) {
            if (Dba::has_index($table, $index) && Dba::has_index($table, $covering)) {
                $this->updateDatabase(sprintf('ALTER TABLE `%s` DROP KEY `%s`;', $table, $index));
            }
        }
    }
}
