<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

/**
 * Convert `object_type` to an enum on `image` table
 */
final class Migration600054 extends AbstractMigration
{
    protected array $changelog = ['Convert `object_type` to an enum on `image` table'];

    public function migrate(): void
    {
        $this->updateDatabase('DELETE FROM `image` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\');');
        $this->updateDatabase('ALTER TABLE `image` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;');
    }
}
