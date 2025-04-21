<?php

declare(strict_types=0);

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
 *
 */

namespace Ampache\Module\Playback\Scrobble;

use Ampache\Repository\Model\Song;
use WpOrg\Requests;
use Thread;
use Ampache\Repository\Model\User;

abstract class ScrobblerAsync extends Thread
{
    public User $user;
    public Song $song;

    /**
     * scrobbler_async constructor.
     */
    public function __construct(
        User $user,
        Song $song
    ) {
        $this->user = $user;
        $this->song = $song;
    }

    public function run(): void
    {
        Requests\Autoload::register();
        if ($this->song->isNew() === false) {
            User::save_mediaplay($this->user, $this->song);
        }
    }
}
