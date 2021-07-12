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

declare(strict_types=0);

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Browse;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Rating;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

final class DefaultAjaxHandler implements AjaxHandlerInterface
{
    private AlbumRepositoryInterface $albumRepository;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        SongRepositoryInterface $songRepository
    ) {
        $this->albumRepository = $albumRepository;
        $this->songRepository  = $songRepository;
    }

    public function handle(): void
    {
        $action = Core::get_request('action');

        // Switch on the actions
        switch ($action) {
            case 'refresh_rightbar':
                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            case 'current_playlist':
                switch ($_REQUEST['type']) {
                    case 'delete':
                        Core::get_global('user')->playlist->delete_track($_REQUEST['id']);
                        break;
                } // end switch

                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            // Handle the users basketcases...
            case 'basket':
                $object_type = $_REQUEST['type'] ?: $_REQUEST['object_type'];
                $object_id   = $_REQUEST['id'] ?: $_REQUEST['object_id'];

                if (InterfaceImplementationChecker::is_playable_item($object_type)) {
                    if (!is_array($object_id)) {
                        $object_id = array($object_id);
                    }
                    foreach ($object_id as $item) {
                        $class_name = ObjectTypeToClassNameMapper::map($object_type);
                        $object     = new $class_name($item);
                        $medias     = $object->get_medias();
                        Core::get_global('user')->playlist->add_medias($medias, (bool) AmpConfig::get('unique_playlist'));
                    }
                } else {
                    switch ($_REQUEST['type']) {
                        case 'browse_set':
                        case 'browse_set_random':
                            $songs   = array();
                            $browse  = new Browse($_REQUEST['browse_id']);
                            $objects = $browse->get_saved();
                            switch ($_REQUEST['object_type']) {
                                case 'album':
                                    foreach ($objects as $object_id) {
                                        $songs = array_merge($songs, static::getSongRepository()->getByAlbum($object_id));
                                    }
                                    break;
                                case 'artist':
                                    foreach ($objects as $object_id) {
                                        $songs = array_merge($songs, static::getSongRepository()->getAllByArtist($object_id));
                                    }
                                    break;
                                case 'song':
                                    $songs = $objects;
                                    break;
                            } // end switch type
                            if ($_REQUEST['type'] == 'browse_set_random') {
                                shuffle($songs);
                            }
                            foreach ($songs as $object_id) {
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
                            $class_name = ObjectTypeToClassNameMapper::map($type);
                            $object     = new $class_name($_REQUEST['id']);
                            $songs      = $this->songRepository->getRandomByArtist($object);
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

                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            /* Setting ratings */
            case 'set_rating':
                if (User::is_registered()) {
                    ob_start();
                    $rating = new Rating(filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), Core::get_get('rating_type'));
                    $rating->set_rating(Core::get_get('rating'));
                    echo Rating::show(filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), Core::get_get('rating_type'));
                    $key           = "rating_" . filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT) . "_" . Core::get_get('rating_type');
                    $results[$key] = ob_get_contents();
                    ob_end_clean();
                } else {
                    $results['rfc3514'] = '0x1';
                }
                break;
            /* Setting userflags */
            case 'set_userflag':
                if (User::is_registered()) {
                    ob_start();
                    $flagtype = Core::get_get('userflag_type');
                    $flag_id  = filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
                    $userflag = new Userflag($flag_id, $flagtype);
                    $userflag->set_flag($_GET['userflag']);
                    echo Userflag::show($flag_id, $flagtype);
                    $key           = "userflag_" . $flag_id . "_" . $flagtype;
                    $results[$key] = ob_get_contents();
                    ob_end_clean();
                } else {
                    $results['rfc3514'] = '0x1';
                }
                break;
            case 'action_buttons':
                ob_start();
                if (AmpConfig::get('ratings')) {
                    echo " <span id='rating_" . filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT) . "_" . filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) . "'>";
                    echo Rating::show(filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
                    echo "</span>";
                }
                if (AmpConfig::get('userflags')) {
                    echo " <span id='userflag_" . filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT) . "_" . filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) . "'>";
                    echo Userflag::show(filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
                    echo "</span>";
                }
                $results['action_buttons'] = ob_get_contents();
                ob_end_clean();
                break;
            default:
                $results['rfc3514'] = '0x1';
                break;
        } // end switch action

        // Go ahead and do the echo
        echo (string) xoutput_from_array($results);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
