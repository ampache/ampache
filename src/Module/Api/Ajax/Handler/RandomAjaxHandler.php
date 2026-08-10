<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Ajax\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\Query\Random;
use Ampache\Module\System\Core;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;

final readonly class RandomAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private AlbumRepositoryInterface $albumRepository,
        private SongRepositoryInterface $songRepository,
        private UiInterface $ui,
    ) {}

    public function handle(User $user): void
    {
        $results = [];
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'song':
                $songs = Random::get_default(AmpConfig::get_int('offset_limit', 50), $user);
                if ($songs === []) {
                    break;
                }

                $user->load_playlist();
                foreach ($songs as $song_id) {
                    $user->playlist?->add_object($song_id, LibraryItemEnum::SONG);
                }

                $results['rightbar'] = $this->ui->showRightbar();
                break;
            case 'album':
                $album_id = $this->albumRepository->getRandom(
                    $user->getId()
                );

                if ($album_id === []) {
                    break;
                }

                $album = new Album($album_id[0]);
                $songs = $this->songRepository->getByAlbum($album->id);

                $user->load_playlist();
                foreach ($songs as $song_id) {
                    $user->playlist?->add_object($song_id, LibraryItemEnum::SONG);
                }

                $results['rightbar'] = $this->ui->showRightbar();
                break;
            case 'artist':
                $artist_id = Random::artist();
                if ($artist_id === 0) {
                    break;
                }

                $songs = $this->songRepository->getByArtist($artist_id);

                $user->load_playlist();
                foreach ($songs as $song_id) {
                    $user->playlist?->add_object($song_id, LibraryItemEnum::SONG);
                }

                $results['rightbar'] = $this->ui->showRightbar();
                break;
            case 'playlist':
                $playlist_id = Random::playlist();

                if ($playlist_id === 0) {
                    break;
                }

                $playlist = new Playlist($playlist_id);
                $items    = $playlist->get_items();

                $user->load_playlist();
                foreach ($items as $item) {
                    $user->playlist?->add_object((int) $item['object_id'], $item['object_type']);
                }

                $results['rightbar'] = $this->ui->showRightbar();
                break;
            case 'send_playlist':
                $_SESSION['iframe']['target'] = AmpConfig::get_web_path() . '/stream.php?action=random' . '&random_type=' . scrub_out((string) ($_REQUEST['random_type'] ?? '')) . '&random_id=' . scrub_out((string) ($_REQUEST['random_id'] ?? ''));
                $results['reloader']          = '<script>' . Core::get_reloadutil() . '("' . $_SESSION['iframe']['target'] . '")</script>';
        } // switch on action;

        // We always do this
        echo xoutput_from_array($results);
    }
}
