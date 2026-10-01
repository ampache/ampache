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

final class Migration810021 extends AbstractMigration
{
    protected array $changelog = [
        'Remove the dead `tvshow`/`tvshow_season` values from the `object_type` enum on `cache_object_count`, `cache_object_count_run`, `image`, `object_count`, `rating`, `tag_map`, `user_activity` and `user_flag`',
    ];

    protected bool $warning = true;

    public function migrate(): void
    {
        $tables = [
            'cache_object_count',
            'cache_object_count_run',
            'image',
            'object_count',
            'rating',
            'tag_map',
            'user_activity',
            'user_flag',
        ];

        foreach ($tables as $table) {
            $this->updateDatabase("DELETE FROM `$table` WHERE `object_type` IN ('tvshow', 'tvshow_season');");
        }

        $this->updateDatabase("ALTER TABLE `cache_object_count` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
        $this->updateDatabase("ALTER TABLE `cache_object_count_run` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
        $this->updateDatabase("ALTER TABLE `image` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video','wanted') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
        $this->updateDatabase("ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
        $this->updateDatabase("ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
        $this->updateDatabase("ALTER TABLE `tag_map` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video','broadcast') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
        $this->updateDatabase("ALTER TABLE `user_activity` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
        $this->updateDatabase("ALTER TABLE `user_flag` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','user','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
    }
}
