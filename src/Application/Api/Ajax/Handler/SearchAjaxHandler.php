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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Wanted\MissingArtistFinderInterface;

final readonly class SearchAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private ConfigContainerInterface $configContainer,
        private MissingArtistFinderInterface $missingArtistFinder,
        private LabelRepositoryInterface $labelRepository
    ) {
    }

    public function handle(User $user): void
    {
        $results = [];
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'search':
                $web_path    = AmpConfig::get_web_path();
                $album_group = ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_GROUP));
                $search      = htmlspecialchars_decode(($_REQUEST['search'] ?? ''));
                $target      = $_REQUEST['target'] ?? '';
                $limit       = $_REQUEST['limit'] ?? 5;

                if ($target == 'anywhere' || $target == 'artist') {
                    $searchreq = [
                        'limit' => $limit,
                        'type' => 'artist',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'title',
                    ];
                    $sres = Search::run($searchreq);
                    // Limit not reached, new search with another operator
                    if (count($sres) < $limit) {
                        $searchreq['limit']           = $limit - count($sres);
                        $searchreq['rule_1_operator'] = '0';
                        $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
                    }

                    foreach ($sres as $artistid) {
                        $artist    = new Artist($artistid);
                        $results[] = [
                            'type' => T_('Artists'),
                            'link' => $web_path . '/artists.php?action=show&artist=' . $artistid,
                            'label' => scrub_out($artist->get_fullname()),
                            'value' => scrub_out($artist->get_fullname()),
                            'rels' => '',
                            'image' => (string)Art::url($artist->id, 'artist', null, 10),
                        ];
                    }
                }

                if (($target == 'anywhere' && $album_group) || $target == 'album') {
                    $searchreq = [
                        'limit' => $limit,
                        'type' => 'album',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'title',
                    ];
                    $sres = Search::run($searchreq);
                    // Limit not reached, new search with another operator
                    if (count($sres) < $limit) {
                        $searchreq['limit']           = $limit - count($sres);
                        $searchreq['rule_1_operator'] = '0';
                        $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
                    }

                    foreach ($sres as $albumid) {
                        $album     = new Album($albumid);
                        $results[] = [
                            'type' => T_('Albums'),
                            'link' => $web_path . '/albums.php?action=show&album=' . $albumid,
                            'label' => scrub_out($album->get_fullname()),
                            'value' => scrub_out($album->get_fullname()),
                            'rels' => scrub_out($album->get_artist_fullname()),
                            'image' => (string)Art::url($album->id, 'album', null, 10),
                        ];
                    }
                }

                if (($target == 'anywhere' && !$album_group) || $target == 'album_disk') {
                    $searchreq = [
                        'limit' => $limit,
                        'type' => 'album_disk',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'title',
                    ];
                    $sres = Search::run($searchreq);
                    // Limit not reached, new search with another operator
                    if (count($sres) < $limit) {
                        $searchreq['limit']           = $limit - count($sres);
                        $searchreq['rule_1_operator'] = '0';
                        $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
                    }

                    foreach ($sres as $albumdiskid) {
                        $albumdisk = new AlbumDisk($albumdiskid);
                        $results[] = [
                            'type' => T_('Albums'),
                            'link' => $web_path . '/albums.php?action=show_disk&album_disk=' . $albumdiskid,
                            'label' => scrub_out($albumdisk->get_fullname()),
                            'value' => scrub_out($albumdisk->get_fullname()),
                            'rels' => scrub_out($albumdisk->get_artist_fullname()),
                            'image' => (string)Art::url($albumdisk->album_id, 'album', null, 10),
                        ];
                    }
                }

                if ($target == 'anywhere' || $target == 'title') {
                    $searchreq = [
                        'limit' => $limit,
                        'type' => 'song',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'title',
                    ];
                    $sres = Search::run($searchreq);
                    // Limit not reached, new search with another operator
                    if (count($sres) < $limit) {
                        $searchreq['limit']           = $limit - count($sres);
                        $searchreq['rule_1_operator'] = '0';
                        $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
                    }

                    $show_song_art = AmpConfig::get('show_song_art', false);
                    foreach ($sres as $songid) {
                        $song       = new Song($songid);
                        $has_art    = Art::has_db($song->id, 'song');
                        $art_object = ($show_song_art && $has_art) ? $song->id : $song->album;
                        $art_type   = ($show_song_art && $has_art) ? 'song' : 'album';
                        $results[]  = [
                            'type' => T_('Songs'),
                            'link' => $web_path . "/song.php?action=show_song&song_id=" . $songid,
                            'label' => scrub_out($song->title),
                            'value' => scrub_out($song->title),
                            'rels' => scrub_out($song->get_artist_fullname()),
                            'image' => (string)Art::url($art_object, $art_type, null, 10),
                        ];
                    }
                }

                if ($target == 'anywhere' || $target == 'playlist') {
                    $searchreq = [
                        'limit' => $limit,
                        'type' => 'playlist',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'title',
                    ];
                    $sres = Search::run($searchreq);
                    // Limit not reached, new search with another operator
                    if (count($sres) < $limit) {
                        $searchreq['limit']           = $limit - count($sres);
                        $searchreq['rule_1_operator'] = '0';
                        $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
                    }

                    foreach ($sres as $playlistid) {
                        $playlist  = new Playlist($playlistid);
                        $results[] = [
                            'type' => T_('Playlists'),
                            'link' => $web_path . '/playlist.php?action=show&playlist_id=' . $playlistid,
                            'label' => $playlist->name,
                            'value' => $playlist->get_fullname(),
                            'rels' => '',
                            'image' => '',
                        ];
                    }
                }

                if (($target == 'anywhere' || $target == 'label') && AmpConfig::get('label')) {
                    $searchreq = [
                        'limit' => $limit,
                        'type' => 'label',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'title',
                    ];
                    $sres = Search::run($searchreq);

                    // Limit not reached, new search with another operator
                    if (count($sres) < $limit) {
                        $searchreq['limit']           = $limit - count($sres);
                        $searchreq['rule_1_operator'] = '0';
                        $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
                    }

                    foreach ($sres as $labelid) {
                        $label = $this->labelRepository->findById($labelid);

                        if ($label !== null) {
                            $results[] = [
                                'type' => T_('Labels'),
                                'link' => $web_path . '/labels.php?action=show&label=' . $labelid,
                                'label' => $label->name,
                                'value' => $label->name,
                                'rels' => '',
                                'image' => (string)Art::url($label->getId(), 'label', null, 10),
                            ];
                        }
                    }
                }

                if ($target == 'missing_artist' && AmpConfig::get('wanted')) {
                    $sres  = $this->missingArtistFinder->find($search);
                    $count = 0;
                    foreach ($sres as $artist) {
                        $results[] = [
                            'type' => T_('Missing Artists'),
                            'link' => $web_path . '/artists.php?action=show_missing&mbid=' . $artist['mbid'],
                            'label' => scrub_out($artist['name']),
                            'value' => scrub_out($artist['name']),
                            'rels' => '',
                            'image' => '',
                        ];
                        $count++;

                        if ($count >= $limit) {
                            break;
                        }
                    }
                }

                if ($target == 'user' && AmpConfig::get('sociable')) {
                    $searchreq = [
                        'limit' => $limit,
                        'type' => 'user',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'username',
                    ];
                    $sres = Search::run($searchreq);

                    // Limit not reached, new search with another operator
                    if (count($sres) < $limit) {
                        $searchreq['limit']           = $limit - count($sres);
                        $searchreq['rule_1_operator'] = '0';
                        $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
                    }

                    foreach ($sres as $user_id) {
                        $user      = new User($user_id);
                        $avatar    = $user->get_avatar();
                        $results[] = [
                            'type' => T_('Users'),
                            'link' => '',
                            'label' => $user->username,
                            'value' => $user->username,
                            'rels' => '',
                            'image' => $avatar['url'] ?? '',
                        ];
                    }
                }

                break;
            case 'search_random':
                if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
                    return;
                }

                $_SESSION['iframe']['target'] = AmpConfig::get_web_path() . '/stream.php?action=search_random&search_id=' . scrub_out($_REQUEST['playlist_id']);
                $results['reloader']          = '<script>' . Core::get_reloadutil() . '("' . $_SESSION['iframe']['target'] . '")</script>';
        } // switch on action;

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
