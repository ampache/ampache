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
 */

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration800004 extends AbstractMigration
{
    protected array $changelog = [
        'Add \'folder\' to `object_type` enum for `cache_object_count` table` table',
        'Add \'folder\' to `object_type` enum for `cache_object_count_run` table',
        'Add \'folder\' to `object_type` enum for `image` table',
        'Add \'folder\' to `object_type` enum for `object_count` table',
        'Add \'folder\' to `object_type` enum for `rating` table',
        'Add \'folder\' to `object_type` enum for `tag_map` table',
        'Add \'folder\' to `object_type` enum for `user_activity` table',
        'Add \'folder\' to `object_type` enum for `user_flag` table',
    ];

    public function migrate(): void
    {
        //cache_object_count
        $this->updateDatabase('DELETE FROM `cache_object_count` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');');
        $this->updateDatabase('ALTER TABLE `cache_object_count` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;');

        //cache_object_count_run
        $this->updateDatabase('DELETE FROM `cache_object_count_run` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');');
        $this->updateDatabase('ALTER TABLE `cache_object_count_run` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;');

        //image
        $this->updateDatabase('DELETE FROM `image` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');');
        $this->updateDatabase('ALTER TABLE `image` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;');

        //object_count
        $this->updateDatabase('DELETE FROM `object_count` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');');
        $this->updateDatabase('ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;');

        //rating
        $this->updateDatabase('DELETE FROM `rating` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');');
        $this->updateDatabase('ALTER TABLE `rating` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;');

        //tag_map
        $this->updateDatabase('DELETE FROM `tag_map` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');');
        $this->updateDatabase('ALTER TABLE `tag_map` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;');

        //user_activity
        $this->updateDatabase('DELETE FROM `user_activity` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');');
        $this->updateDatabase('ALTER TABLE `user_activity` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;');

        //user_flag
        $this->updateDatabase('DELETE FROM `user_flag` WHERE `object_type` IS NULL OR `object_type` NOT IN (\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'user\', \'video\');');
        $this->updateDatabase('ALTER TABLE `user_flag` MODIFY COLUMN `object_type` enum(\'album\', \'album_disk\', \'artist\', \'catalog\', \'folder\', \'tag\', \'label\', \'live_stream\', \'playlist\', \'podcast\', \'podcast_episode\', \'search\', \'song\', \'tvshow\', \'tvshow_season\', \'user\', \'video\') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;');
    }
}
