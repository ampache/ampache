<?php

/*
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

declare(strict_types=0);

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Config\AmpConfig;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\playable_item;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Rating;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Tmp_Playlist;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

final class DefaultAjaxHandler implements AjaxHandlerInterface
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
        $results      = array();
        $request_id   = (int)$this->requestParser->getFromRequest('id');
        $request_type = $this->requestParser->getFromRequest('type');
        $action       = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'refresh_rightbar':
                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            case 'current_playlist':
                if ($request_type == 'delete') {
                    Core::get_global('user')->playlist->delete_track($request_id);
                } // end switch

                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            case 'basket_refresh':
                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                $results['rfc3514']  = '0x0';
                break;
            case 'basket':
                // Handle the users basketcases...
                $object_type = $request_type ?? $this->requestParser->getFromRequest('object_type');
                if (InterfaceImplementationChecker::is_playable_item($object_type)) {
                    $object_id = $request_id ?? $this->requestParser->getFromRequest('object_id');
                    if (!is_array($object_id)) {
                        $object_id = array($object_id);
                    }
                    foreach ($object_id as $item) {
                        $className = ObjectTypeToClassNameMapper::map($object_type);
                        /** @var playable_item $object */
                        $object = new $className($item);
                        $medias = $object->get_medias();
                        /** @var User|string $user */
                        $user = Core::get_global('user');
                        if ($user instanceof User) {
                            if (!$user->playlist instanceof Tmp_Playlist) {
                                $user->load_playlist();
                            }
                            $user->playlist->add_medias($medias);
                        }
                    }
                } else {
                    switch ($request_type) {
                        case 'browse_set':
                        case 'browse_set_random':
                            $songs   = array();
                            $browse  = new Browse($this->requestParser->getFromRequest('browse_id'));
                            $objects = $browse->get_saved();
                            switch ($browse->get_type()) {
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
                            if ($request_type == 'browse_set_random') {
                                shuffle($songs);
                            }
                            foreach ($songs as $object_id) {
                                Core::get_global('user')->playlist->add_object($object_id, 'song');
                            }
                            break;
                        case 'album_random':
                            $songs = $this->albumRepository->getRandomSongs($request_id);
                            foreach ($songs as $song_id) {
                                Core::get_global('user')->playlist->add_object($song_id, 'song');
                            }
                            break;
                        case 'album_disk_random':
                            $songs = $this->albumRepository->getRandomSongsByAlbumDisk($request_id);
                            foreach ($songs as $song_id) {
                                Core::get_global('user')->playlist->add_object($song_id, 'song');
                            }
                            break;
                        case 'artist_random':
                        case 'tag_random':
                            $data      = explode('_', $request_type);
                            $type      = $data['0'];
                            $className = ObjectTypeToClassNameMapper::map($type);
                            $object    = new $className($request_id);
                            $songs     = $this->songRepository->getRandomByArtist($object);
                            foreach ($songs as $song_id) {
                                Core::get_global('user')->playlist->add_object($song_id, 'song');
                            }
                            break;
                        case 'playlist_random':
                            $playlist = new Playlist($request_id);
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
            case 'set_rating':
                /* Setting ratings */
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
            case 'set_userflag':
                /* Setting userflags */
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
                $rating_id   = filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
                $rating_type = filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_SPECIAL_CHARS);
                ob_start();
                if (AmpConfig::get('ratings') && Rating::is_valid($rating_type)) {
                    echo " <span id='rating_" . $rating_id . "_" . $rating_type . "'>";
                    echo Rating::show($rating_id, $rating_type);
                    echo "</span>";
                    echo " <span id='userflag_" . $rating_id . "_" . $rating_type . "'>";
                    echo Userflag::show($rating_id, $rating_type);
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
