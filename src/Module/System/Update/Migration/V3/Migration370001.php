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
 * Drop unused dynamic_playlist tables and add session id to votes
 */
final class Migration370001 extends AbstractMigration
{
    protected array $changelog = ['Drop unused dynamic_playlist tables and add session id to votes'];

    public function migrate(): void
    {
        $charset = (AmpConfig::get('database_charset', 'utf8mb4'));

        $this->updateDatabase("DROP TABLE IF EXISTS `dynamic_playlist`");
        $this->updateDatabase("DROP TABLE IF EXISTS `dynamic_playlist_data`");
        $this->updateDatabase("ALTER TABLE `user_vote` ADD `sid` varchar(256) CHARACTER SET $charset NULL AFTER `date`");

        $this->updatePreferences('demo_clear_sessions', 'Clear democratic votes of expired user sessions', '0', 25, 'boolean', 'playlist');
    }
}
