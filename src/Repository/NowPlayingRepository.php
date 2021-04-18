<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Module\System\Dba;

final class NowPlayingRepository implements NowPlayingRepositoryInterface
{

    /**
     * This will garbage collect the Now Playing data,
     * this is done on every play start.
     */
    public function collectGarbage(): void
    {
        // Remove any Now Playing entries for sessions that have been GC'd
        $sql = "DELETE FROM `now_playing` USING `now_playing` " .
            "LEFT JOIN `session` ON `session`.`id` = `now_playing`.`id` " .
            "WHERE (`session`.`id` IS NULL AND `now_playing`.`id` NOT IN (SELECT `username` FROM `user`)) OR `now_playing`.`expire` < '" . time() . "'";
        Dba::write($sql);
    }

    /**
     * There really isn't anywhere else for this function, shouldn't have
     * deleted it in the first place.
     */
    public function truncate(): void
    {
        Dba::write('TRUNCATE `now_playing`');
    }
}
