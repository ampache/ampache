<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 */

namespace Ampache\Module\System\Update\Migration\V6;

use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration600070 extends AbstractMigration
{
    protected array $changelog = ['Convert `object_type` to an enum on `playlist_data` table'];

    public function migrate(): void
    {
        $this->updateDatabase('UPDATE `playlist_data` SET `object_type` = \'song\' WHERE `object_type` IS NULL OR `object_type` NOT IN (\'broadcast\', \'democratic\', \'live_stream\', \'podcast_episode\', \'song\', \'song_preview\', \'video\');');
        $this->updateDatabase('ALTER TABLE `playlist_data` MODIFY COLUMN `object_type` enum(\'broadcast\', \'democratic\', \'live_stream\', \'podcast_episode\', \'song\', \'song_preview\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;');
    }
}
