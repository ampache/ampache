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
 * Converts `stream_playlist`.`type` to an enum of the `Media` kinds `Stream_Playlist` ever writes there.
 */
final class Migration810029 extends AbstractMigration
{
    protected array $changelog = [
        'Convert `stream_playlist`.`type` from a plain `varchar` to an `enum` of its real, verified values',
    ];
    protected bool $warning = true;

    public function migrate(): void
    {
        // add_urls() leaves this NULL for urls built from a raw string rather than a Media object
        $this->updateDatabase("DELETE FROM `stream_playlist` WHERE `type` IS NOT NULL AND `type` NOT IN ('broadcast', 'live_stream', 'podcast_episode', 'song', 'song_preview', 'video');");
        $this->updateDatabase("ALTER TABLE `stream_playlist` MODIFY COLUMN `type` enum('broadcast','live_stream','podcast_episode','song','song_preview','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;");
    }
}
