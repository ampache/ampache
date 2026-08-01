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
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\container_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;

final readonly class DefaultAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private AlbumRepositoryInterface $albumRepository,
        private SongRepositoryInterface $songRepository,
    ) {}

    public function handle(User $user): void
    {
        $results      = [];
        $request_id   = (int) $this->requestParser->getFromRequest('id');
        $request_type = $this->requestParser->getFromRequest('type');
        $action       = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'basket_refresh':
            case 'refresh_rightbar':
                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            case 'current_playlist':
                if ($request_type === 'delete') {
                    $user->load_playlist();
                    $user->playlist?->delete_track($request_id);
                }

                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            case 'basket':
                // Handle the users basketcases...
                $object_type = ($request_type === '' || $request_type === '0')
                    ? $this->requestParser->getFromRequest('object_type')
                    : $request_type;

                if (InterfaceImplementationChecker::is_library_item($object_type)) {
                    // A multi-select bar sends a comma separated list, so gather the medias behind every id and
                    // hand the basket a single batch rather than one request (and one rightbar render) per row.
                    $request_ids = $this->requestParser->getFromRequest('id');
                    if ($request_ids === '' || $request_ids === '0') {
                        $request_ids = $this->requestParser->getFromRequest('object_id');
                    }

                    $object_ids = array_filter(
                        array_map('intval', explode(',', $request_ids)),
                        static fn(int $object_id): bool => $object_id > 0
                    );
                    if ($object_ids !== []) {
                        $className = ObjectTypeToClassNameMapper::map($object_type);
                        $medias    = [];
                        foreach ($object_ids as $object_id) {
                            /** @var container_item $object */
                            $object = new $className($object_id);
                            $medias = array_merge($medias, $object->get_medias());
                        }

                        $user->load_playlist();
                        $user->playlist?->add_medias($medias);
                    }
                } else {
                    switch ($request_type) {
                        case 'browse_set':
                        case 'browse_set_random':
                            $songs   = [];
                            $browse  = new Browse((int) $this->requestParser->getFromRequest('browse_id'));
                            $objects = $browse->get_saved();
                            switch ($browse->get_type()) {
                                case 'album':
                                    foreach ($objects as $object) {
                                        $songs = (is_array($object))
                                            ? array_merge($songs, $this->songRepository->getByAlbum($object['object_id'] ?? 0))
                                            : array_merge($songs, $this->songRepository->getByAlbum((int) $object));
                                    }

                                    break;
                                case 'artist':
                                    foreach ($objects as $object) {
                                        $songs = (is_array($object))
                                            ? array_merge($songs, $this->songRepository->getAllByArtist($object['object_id'] ?? 0))
                                            : array_merge($songs, $this->songRepository->getAllByArtist((int) $object));
                                    }

                                    break;
                                case 'song':
                                    $songs = $objects;
                                    break;
                            }

                            if ($request_type === 'browse_set_random') {
                                shuffle($songs);
                            }

                            foreach ($songs as $object) {
                                $user->playlist?->add_object(
                                    (is_array($object) && isset($object['object_id'])) ? $object['object_id'] : (int) $object,
                                    LibraryItemEnum::SONG
                                );
                            }

                            break;
                        case 'album_random':
                            $songs = $this->albumRepository->getRandomSongs($request_id);
                            foreach ($songs as $song_id) {
                                $user->playlist?->add_object($song_id, LibraryItemEnum::SONG);
                            }

                            break;
                        case 'album_disk_random':
                            $songs = $this->albumRepository->getRandomSongsByAlbumDisk($request_id);
                            foreach ($songs as $song_id) {
                                $user->playlist?->add_object($song_id, LibraryItemEnum::SONG);
                            }

                            break;
                        case 'tag_random':
                            $object = new Tag($request_id);
                            $songs  = $this->songRepository->getRandomByGenre($object);
                            foreach ($songs as $song_id) {
                                $user->playlist?->add_object($song_id, LibraryItemEnum::SONG);
                            }

                            break;
                        case 'artist_random':
                            $object = new Artist($request_id);
                            $songs  = $this->songRepository->getRandomByArtist($object);
                            foreach ($songs as $song_id) {
                                $user->playlist?->add_object($song_id, LibraryItemEnum::SONG);
                            }

                            break;
                        case 'playlist_random':
                            $playlist = new Playlist($request_id);
                            $items    = $playlist->get_random_items();
                            foreach ($items as $item) {
                                $user->playlist?->add_object($item['object_id'], $item['object_type']);
                            }

                            break;
                        case 'clear_all':
                            $user->playlist?->clear();
                            break;
                    }
                }

                $results['rightbar'] = Ui::ajax_include('rightbar.inc.php');
                break;
            case 'set_rating':
                /* Setting ratings */
                if (User::is_registered()) {
                    ob_start();
                    $object_id = (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
                    $rating    = new Rating($object_id, Core::get_get('rating_type'));
                    $rating->set_rating((int) Core::get_get('rating'));
                    echo Rating::show($object_id, Core::get_get('rating_type'));
                    $key           = "rating_" . filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT) . "_" . Core::get_get('rating_type');
                    $results[$key] = ob_get_contents();
                    ob_end_clean();
                }

                break;
            case 'set_userflag':
                /* Setting userflags */
                if (User::is_registered()) {
                    ob_start();
                    $flagtype = Core::get_get('userflag_type');
                    $flag_id  = filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
                    $userflag = new Userflag((int) $flag_id, $flagtype);
                    $userflag->set_flag(make_bool($_GET['userflag'] ?? null));
                    echo Userflag::show((int) $flag_id, $flagtype);
                    $key           = "userflag_" . $flag_id . "_" . $flagtype;
                    $results[$key] = ob_get_contents();
                    ob_end_clean();
                }

                break;
            case 'action_buttons':
                $rating_id   = (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
                $rating_type = (string) filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_SPECIAL_CHARS);
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
        }

        // Go ahead and do the echo
        echo xoutput_from_array($results);
    }
}
