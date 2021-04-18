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

namespace Ampache\Repository;

use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Query;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\Tmp_Playlist;

final class SessionRepository implements SessionRepositoryInterface
{
    /**
     * This function is randomly called and it cleans up the expired sessions
     */
    public function collectGarbage(): void
    {
        $sql = 'DELETE FROM `session` WHERE `expire` < ?';
        Dba::write($sql, [time()]);

        $sql = 'DELETE FROM `session_remember` WHERE `expire` < ?';
        Dba::write($sql, [time()]);

        // Also clean up things that use sessions as keys
        Query::garbage_collection();
        Tmp_Playlist::garbage_collection();
        Stream_Playlist::garbage_collection();
        Song_Preview::garbage_collection();
    }
}
