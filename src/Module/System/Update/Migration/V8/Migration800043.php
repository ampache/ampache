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
 * Let `image` hold the art of a wanted album.
 *
 * A wanted album is not in the library, so its cover had to be gathered from the art providers on every page load.
 * Storing it needs `wanted` in the object_type enum. Nothing for Ampache7 to roll back: the extra enum value holds
 * rows Ampache7 never reads, and leaving it in place costs an unused option.
 */
final class Migration800043 extends AbstractMigration
{
    protected array $changelog = [
        'Add `wanted` to the `image`.`object_type` enum so a wanted album keeps the art it gathered',
    ];

    public function migrate(): void
    {
        $this->updateDatabase("ALTER TABLE `image` MODIFY COLUMN `object_type` enum('album','album_disk','artist','catalog','collection','folder','tag','label','live_stream','playlist','podcast','podcast_episode','search','song','tvshow','tvshow_season','user','video','wanted') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
    }
}
