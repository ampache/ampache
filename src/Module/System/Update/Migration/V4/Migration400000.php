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

namespace Ampache\Module\System\Update\Migration\V4;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Increase copyright column size to fix issue #1861
 * Add name_track, name_artist, name_album to user_activity
 * Add mbid_track, mbid_artist, mbid_album to user_activity
 * Insert some decent SmartLists for a better default experience
 * Delete the following plex preferences from the server
 *   plex_backend
 *   myplex_username
 *   myplex_authtoken
 *   myplex_published
 *   plex_uniqid
 *   plex_servername
 *   plex_public_address
 *   plex_public_port
 *   plex_local_auth
 *   plex_match_email
 * Add preference for master/develop branch selection
 */
final class Migration400000 extends AbstractMigration
{
    protected array $changelog = [
        'Enable better podcast defaults',
        'Increase copyright column size to fix issue #1861',
        'Add name_track, name_artist, name_album to user_activity',
        'Add mbid_track, mbid_artist, mbid_album to user_activity',
        'Insert some decent SmartLists for a better default experience',
        'Delete plex preferences from the server'
    ];

    public function migrate(): void
    {
        $sql_array = [
            "ALTER TABLE `podcast` MODIFY `copyright` varchar(255)",
            "ALTER TABLE `user_activity` ADD COLUMN `name_track` varchar(255) NULL DEFAULT NULL, ADD COLUMN `name_artist` varchar(255) NULL DEFAULT NULL, ADD COLUMN `name_album` varchar(255) NULL DEFAULT NULL;",
            "ALTER TABLE `user_activity` ADD COLUMN `mbid_track` varchar(255) NULL DEFAULT NULL, ADD COLUMN `mbid_artist` varchar(255) NULL DEFAULT NULL, ADD COLUMN `mbid_album` varchar(255) NULL DEFAULT NULL;",
            "INSERT IGNORE INTO `search` (`user`, `type`, `rules`, `name`, `logic_operator`, `random`, `limit`) VALUES (-1, 'public', '[[\"artistrating\",\"equal\",\"5\",null]]', 'Artist 5*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"4\",null]]', 'Artist 4*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"3\",null]]', 'Artist 3*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"2\",null]]', 'Artist 2*', 'AND', 0, 0), (-1, 'public', '[[\"artistrating\",\"equal\",\"1\",null]]', 'Artist 1*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"5\",null]]', 'Album 5*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"4\",null]]', 'Album 4*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"3\",null]]', 'Album 3*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"2\",null]]', 'Album 2*', 'AND', 0, 0), (-1, 'public', '[[\"albumrating\",\"equal\",\"1\",null]]', 'Album 1*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"5\",null]]', 'Song 5*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"4\",null]]', 'Song 4*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"3\",null]]', 'Song 3*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"2\",null]]', 'Song 2*', 'AND', 0, 0), (-1, 'public', '[[\"myrating\",\"equal\",\"1\",null]]', 'Song 1*', 'AND', 0, 0);",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_backend');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'myplex_username');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'myplex_authtoken');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'myplex_published');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_uniqid');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_servername');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_public_address');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_public_port');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_local_auth');",
            "DELETE FROM `user_preference` WHERE `user_preference`.`preference` IN (SELECT `preference`.`id` FROM `preference` WHERE `preference`.`name` = 'plex_match_email');",
            "DELETE FROM `preference` WHERE `preference`.`name` IN ('plex_backend', 'myplex_username', 'myplex_authtoken', 'myplex_published', 'plex_uniqid', 'plex_servername', 'plex_public_address', 'plex_public_port ', 'plex_local_auth', 'plex_match_email');"
        ];
        foreach ($sql_array as $sql) {
            $this->updateDatabase($sql);
        }
    }
}
