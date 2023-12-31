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

namespace Ampache\Module\System\Update\Migration\V3;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add metadata information to albums / songs / videos
 */
final class Migration370012 extends AbstractMigration
{
    protected array $changelog = ['Add metadata information to albums / songs / videos'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        $this->updateDatabase("ALTER TABLE `album` ADD `release_type` varchar(32) CHARACTER SET $charset NULL");

        $this->updateDatabase("ALTER TABLE `song` ADD `composer` varchar(256) CHARACTER SET $charset NULL, ADD `channels` MEDIUMINT NULL");

        $this->updateDatabase("ALTER TABLE `video` ADD `channels` MEDIUMINT NULL, ADD `bitrate` MEDIUMINT(8) NULL, ADD `video_bitrate` MEDIUMINT(8) NULL, ADD `display_x` MEDIUMINT(8) NULL, ADD `display_y` MEDIUMINT(8) NULL, ADD `frame_rate` FLOAT NULL, ADD `mode` enum('abr','vbr','cbr') NULL DEFAULT 'cbr'");

        $this->updatePreferences('allow_video', 'Allow video features', '1', 75, 'integer', 'options');
    }
}
