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

namespace Ampache\Module\Application\Playlist;

use Ampache\Module\Playlist\PlaylistSongSorterInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SortTrackAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'sort_tracks';

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    private PlaylistSongSorterInterface $playlistSongSorter;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        UiInterface $ui,
        PlaylistSongSorterInterface $playlistSongSorter
    ) {
        $this->modelFactory       = $modelFactory;
        $this->ui                 = $ui;
        $this->playlistSongSorter = $playlistSongSorter;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $playlist = $this->modelFactory->createPlaylist((int) ($request->getQueryParams()['playlist_id'] ?? 0));
        if (!$playlist->has_access()) {
            throw new AccessDeniedException();
        }

        $this->playlistSongSorter->sort($playlist);

        $this->ui->showHeader();
        $this->ui->show(
            'show_playlist.inc.php',
            [
                'playlist' => $playlist,
                'object_ids' => $playlist->get_items(),
            ]
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
