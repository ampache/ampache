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

final class Migration810024 extends AbstractMigration
{
    protected array $changelog = [
        'Convert `object_type` from a plain `varchar` to an `enum` on `recommendation`, `user_shout` and `collection`, using the value list each table actually writes',
    ];

    protected bool $warning = true;

    public function migrate(): void
    {
        $this->updateDatabase("DELETE FROM `recommendation` WHERE `object_type` IS NOT NULL AND `object_type` NOT IN ('song', 'artist');");
        $this->updateDatabase("ALTER TABLE `recommendation` MODIFY COLUMN `object_type` enum('song','artist') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");

        $this->updateDatabase("DELETE FROM `user_shout` WHERE `object_type` IS NOT NULL AND `object_type` NOT IN ('album', 'album_disk', 'art', 'artist', 'broadcast', 'collection', 'democratic', 'folder', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'song_preview', 'tag', 'tag_hidden', 'video');");
        $this->updateDatabase("ALTER TABLE `user_shout` MODIFY COLUMN `object_type` enum('album','album_disk','art','artist','broadcast','collection','democratic','folder','label','live_stream','playlist','podcast','podcast_episode','search','song','song_preview','tag','tag_hidden','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");

        $this->updateDatabase("DELETE FROM `collection` WHERE `object_type` IS NOT NULL AND `object_type` NOT IN ('album', 'album_disk', 'artist', 'folder', 'genre', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'song', 'video');");
        $this->updateDatabase("ALTER TABLE `collection` MODIFY COLUMN `object_type` enum('album','album_disk','artist','folder','genre','label','live_stream','playlist','podcast','podcast_episode','song','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");
    }
}
