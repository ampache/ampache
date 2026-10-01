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

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Every other `object_type` tag column is pinned to `utf8mb3` (it only ever holds short ASCII values
 * like `song`/`album`); these six were added without that pin and silently inherited the table's
 * utf8mb4 default instead.
 */
final class Migration810020 extends AbstractMigration
{
    protected array $changelog = [
        'Pin `object_type` to `utf8mb3` on `folder_map`, `collection`, `collection_map`, `playlist_folder_map`, `object_count_archive` and `object_count_summary`, matching every other object_type column',
    ];
    protected bool $warning = true;

    public function migrate(): void
    {
        $this->updateDatabase("ALTER TABLE `folder_map` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `collection` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `collection_map` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
        $this->updateDatabase("ALTER TABLE `playlist_folder_map` MODIFY COLUMN `object_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
        $this->updateDatabase("ALTER TABLE `object_count_archive` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','collection','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
        $this->updateDatabase("ALTER TABLE `object_count_summary` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','collection','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
    }
}
