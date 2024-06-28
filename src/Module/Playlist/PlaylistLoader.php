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
 *
 */

namespace Ampache\Module\Playlist;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;

final class PlaylistLoader implements PlaylistLoaderInterface
{
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    public function loadByUserId(int $userId): array
    {
        $playlists = Playlist::get_playlists($userId, '', true, false);
        Playlist::build_cache($playlists);

        $result = [];

        foreach ($playlists as $playlist_id) {
            $result[] = $this->modelFactory->createPlaylist((int) $playlist_id);
        }

        return $result;
    }
}
