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
 * Add channels
 */
final class Migration360041 extends AbstractMigration
{
    protected array $changelog = ['Add channels'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine  = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `channel` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(64) CHARACTER SET $charset NULL, `description` varchar(256) CHARACTER SET $charset NULL, `url` varchar(256) CHARACTER SET $charset NULL, `interface` varchar(64) CHARACTER SET $charset NULL, `port` int(11) unsigned NOT NULL DEFAULT '0', `fixed_endpoint` tinyint(1) unsigned NOT NULL DEFAULT '0', `object_type` varchar(32) NOT NULL, `object_id` int(11) unsigned NOT NULL, `is_private` tinyint(1) unsigned NOT NULL DEFAULT '0', `random` tinyint(1) unsigned NOT NULL DEFAULT '0', `loop` tinyint(1) unsigned NOT NULL DEFAULT '0', `admin_password` varchar(20) CHARACTER SET $charset NULL, `start_date` int(11) unsigned NOT NULL DEFAULT '0', `max_listeners` int(11) unsigned NOT NULL DEFAULT '0', `peak_listeners` int(11) unsigned NOT NULL DEFAULT '0', `listeners` int(11) unsigned NOT NULL DEFAULT '0', `connections` int(11) unsigned NOT NULL DEFAULT '0', `stream_type` varchar(8) CHARACTER SET $charset NOT NULL DEFAULT 'mp3', `bitrate` int(11) unsigned NOT NULL DEFAULT '128', `pid` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=$engine;");
    }
}
