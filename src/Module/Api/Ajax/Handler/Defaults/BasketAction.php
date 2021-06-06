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
 */

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Defaults;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\Ui;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PlayableMediaInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BasketAction implements ActionInterface
{
    private ModelFactoryInterface $modelFactory;

    private AlbumRepositoryInterface $albumRepository;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        AlbumRepositoryInterface $albumRepository,
        SongRepositoryInterface $songRepository
    ) {
        $this->modelFactory    = $modelFactory;
        $this->albumRepository = $albumRepository;
        $this->songRepository  = $songRepository;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $object_type = $_REQUEST['type'] ?: $_REQUEST['object_type'];
        $object_id   = $_REQUEST['id'] ?: $_REQUEST['object_id'];

        if (InterfaceImplementationChecker::is_playable_item($object_type)) {
            if (!is_array($object_id)) {
                $object_id = array($object_id);
            }
            foreach ($object_id as $item) {
                /** @var PlayableMediaInterface&library_item $object */
                $object = $this->modelFactory->mapObjectType(
                    $object_type,
                    (int) $item
                );
                $medias = $object->get_medias();
                Core::get_global('user')->playlist->add_medias($medias, (bool) AmpConfig::get('unique_playlist'));
            }
        } else {
            switch ($_REQUEST['type']) {
                case 'browse_set':
                    $browse  = new Browse($_REQUEST['browse_id']);
                    $objects = $browse->get_saved();
                    foreach ($objects as $object_id) {
                        Core::get_global('user')->playlist->add_object($object_id, 'song');
                    }
                    break;
                case 'album_full':
                    $songs = $this->albumRepository->getSongsGrouped(explode(',', $_REQUEST['id']));
                    foreach ($songs as $song_id) {
                        Core::get_global('user')->playlist->add_object($song_id, 'song');
                    }
                    break;
                case 'album_random':
                    $songs = $this->albumRepository->getRandomSongsGrouped(explode(',', $_REQUEST['id']));
                    foreach ($songs as $song_id) {
                        Core::get_global('user')->playlist->add_object($song_id, 'song');
                    }
                    break;
                case 'artist_random':
                case 'tag_random':
                    $data       = explode('_', $_REQUEST['type']);
                    $type       = $data['0'];
                    /** @var Artist $object */
                    $object     = $this->modelFactory->mapObjectType(
                        $type,
                        (int) $_REQUEST['id']
                    );
                    $songs  = $this->songRepository->getRandomByArtist($object);
                    foreach ($songs as $song_id) {
                        Core::get_global('user')->playlist->add_object($song_id, 'song');
                    }
                    break;
                case 'playlist_random':
                    $playlist = new Playlist($_REQUEST['id']);
                    $items    = $playlist->get_random_items();
                    foreach ($items as $item) {
                        Core::get_global('user')->playlist->add_object($item['object_id'], $item['object_type']);
                    }
                    break;
                case 'clear_all':
                    Core::get_global('user')->playlist->clear();
                    break;
            }
        }

        return ['rightbar' => Ui::ajax_include('rightbar.inc.php')];
    }
}
