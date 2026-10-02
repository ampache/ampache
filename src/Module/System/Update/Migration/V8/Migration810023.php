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

final class Migration810023 extends AbstractMigration
{
    protected array $changelog = [
        'Convert `object_type` from a plain `varchar` to an `enum` on `bookmark`, `tmp_playlist` and `tmp_playlist_data`, using the value list each table actually writes',
    ];
    protected bool $warning = true;

    public function migrate(): void
    {
        $this->updateDatabase("DELETE FROM `bookmark` WHERE `object_type` IS NOT NULL AND `object_type` NOT IN ('song', 'podcast_episode', 'video');");
        $this->updateDatabase("ALTER TABLE `bookmark` MODIFY COLUMN `object_type` enum('song','podcast_episode','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");

        $this->updateDatabase("DELETE FROM `tmp_playlist` WHERE `object_type` IS NOT NULL AND `object_type` NOT IN ('song');");
        $this->updateDatabase("ALTER TABLE `tmp_playlist` MODIFY COLUMN `object_type` enum('song') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");

        $this->updateDatabase("DELETE FROM `tmp_playlist_data` WHERE `object_type` IS NOT NULL AND `object_type` NOT IN ('broadcast', 'democratic', 'live_stream', 'podcast_episode', 'song', 'song_preview', 'video');");
        $this->updateDatabase("ALTER TABLE `tmp_playlist_data` MODIFY COLUMN `object_type` enum('broadcast','democratic','live_stream','podcast_episode','song','song_preview','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");
    }
}
