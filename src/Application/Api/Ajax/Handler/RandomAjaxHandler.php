<?php

declare(strict_types=0);

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
 *
 */

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Album;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Browse;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Random;
use Ampache\Module\Util\Ui;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;

final class RandomAjaxHandler implements AjaxHandlerInterface
{
    private RequestParserInterface $requestParser;

    private AlbumRepositoryInterface $albumRepository;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        RequestParserInterface $requestParser,
        AlbumRepositoryInterface $albumRepository,
        SongRepositoryInterface $songRepository
    ) {
        $this->requestParser   = $requestParser;
        $this->albumRepository = $albumRepository;
        $this->songRepository  = $songRepository;
    }

    public function handle(): void
    {
        $results = array();
        $songs   = array();
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'song':
                $songs = Random::get_default((int)AmpConfig::get('offset_limit', 50), Core::get_global('user'));
                $user  = Core::get_global('user');
                if (!count($songs) || $user === null) {
                    $results['rfc3514'] = '0x1';
                    break;
                }

                $user->load_playlist();
                foreach ($songs as $song_id) {
                    $user->playlist?->add_object($song_id, LibraryItemEnum::SONG);
                }
                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            case 'album':
                $album_id = $this->albumRepository->getRandom(
                    Core::get_global('user')->id ?? -1
                );
                $user = Core::get_global('user');

                if (empty($album_id) || $user === null) {
                    $results['rfc3514'] = '0x1';
                    break;
                }

                $album = new Album($album_id[0]);
                $songs = $this->songRepository->getByAlbum($album->id);

                $user->load_playlist();
                foreach ($songs as $song_id) {
                    $user->playlist?->add_object($song_id, LibraryItemEnum::SONG);
                }
                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            case 'album_disk':
                $albumDisk_id = $this->albumRepository->getRandomAlbumDisk(
                    Core::get_global('user')->id ?? -1,
                    null
                );
                $user = Core::get_global('user');

                if (empty($albumDisk_id) || $user === null) {
                    $results['rfc3514'] = '0x1';
                    break;
                }

                $albumDisk = new AlbumDisk($albumDisk_id[0]);
                $songs     = $this->songRepository->getByAlbumDisk($albumDisk->id);

                $user->load_playlist();
                foreach ($songs as $song_id) {
                    $user->playlist?->add_object($song_id, LibraryItemEnum::SONG);
                }
                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            case 'artist':
                $artist_id = Random::artist();
                $user      = Core::get_global('user');

                if (!$artist_id || $user === null) {
                    $results['rfc3514'] = '0x1';
                    break;
                }

                $songs = $this->songRepository->getByArtist($artist_id);

                $user->load_playlist();
                foreach ($songs as $song_id) {
                    $user->playlist?->add_object($song_id, LibraryItemEnum::SONG);
                }
                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            case 'playlist':
                $playlist_id = Random::playlist();
                $user        = Core::get_global('user');

                if (!$playlist_id || $user === null) {
                    $results['rfc3514'] = '0x1';
                    break;
                }

                $playlist = new Playlist($playlist_id);
                $items    = $playlist->get_items();

                $user->load_playlist();
                foreach ($items as $item) {
                    $user->playlist?->add_object((int)$item['object_id'], $item['object_type']);
                }
                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            case 'send_playlist':
                $_SESSION['iframe']['target'] = AmpConfig::get('web_path') . '/stream.php?action=random' . '&random_type=' . scrub_out($_REQUEST['random_type']) . '&random_id=' . scrub_out($_REQUEST['random_id']);
                $results['rfc3514']           = '<script>' . Core::get_reloadutil() . '("' . $_SESSION['iframe']['target'] . '")</script>';
                break;
            case 'advanced_random':
                $object_ids = Random::advanced('song', $_POST);
                $user       = Core::get_global('user');

                // First add them to the active playlist
                if (!empty($object_ids) && $user instanceof User) {
                    $user->load_playlist();
                    foreach ($object_ids as $object_id) {
                        $user->playlist?->add_object($object_id, LibraryItemEnum::SONG);
                    }
                }
                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');

                // Now setup the browse and show them below!
                $browse = new Browse();
                $browse->set_type('song');
                $browse->save_objects($object_ids);
                ob_start();
                $browse->show_objects();
                $results['browse'] = ob_get_contents();
                ob_end_clean();
                break;
            default:
                $results['rfc3514'] = '0x1';
                break;
        } // switch on action;

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
