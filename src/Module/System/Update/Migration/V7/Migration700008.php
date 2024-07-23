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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

final class Migration700008 extends AbstractMigration
{
    protected array $changelog = ['Create `user_playlist_map` table to allow browse access to playlists with collaborators'];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        if (!Dba::read('SELECT SUM(`user_id`) FROM `user_playlist_map`;')) {
            $this->updateDatabase('DROP TABLE IF EXISTS `user_playlist_map`;');
            $this->updateDatabase(
                sprintf(
                    "CREATE TABLE IF NOT EXISTS `user_playlist_map` (`playlist_id` int(11) UNSIGNED NOT NULL, `user_id` int(11) UNSIGNED NOT NULL, UNIQUE KEY `playlist_user` (`playlist_id`,`user_id`)) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s ;",
                    $engine,
                    $charset,
                    $collation
                )
            );
        }
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Generator {
        yield 'user_playlist_map' => "CREATE TABLE IF NOT EXISTS `user_playlist_map` (`playlist_id` int(11) UNSIGNED NOT NULL, `user_id` int(11) UNSIGNED NOT NULL, UNIQUE KEY `playlist_user` (`playlist_id`,`user_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
