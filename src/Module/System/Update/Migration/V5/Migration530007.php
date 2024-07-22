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

namespace Ampache\Module\System\Update\Migration\V5;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Convert basic text columns into utf8/utf8_unicode_ci
 */
final class Migration530007 extends AbstractMigration
{
    protected array $changelog = ['Convert basic text columns into utf8 to reduce index sizes'];

    public function migrate(): void
    {
        $this->updateDatabase("UPDATE `album` SET `mbid` = NULL WHERE CHAR_LENGTH(`mbid`) > 36;");
        $this->updateDatabase("UPDATE `album` SET `mbid_group` = NULL WHERE CHAR_LENGTH(`mbid`) > 36;");

        $this->updateDatabase("ALTER TABLE `album` MODIFY COLUMN `mbid` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `album` MODIFY COLUMN `mbid_group` varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `object_count` MODIFY COLUMN `object_type` enum('album','artist','song','playlist','genre','catalog','live_stream','video','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `rating` MODIFY COLUMN `object_type` enum('album','artist','song','stream','video','playlist','tvshow','tvshow_season','podcast','podcast_episode') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `user_flag` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `user_shout` MODIFY COLUMN `object_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
        $this->updateDatabase("ALTER TABLE `video` MODIFY COLUMN `mode` enum('abr','vbr','cbr') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
    }
}
