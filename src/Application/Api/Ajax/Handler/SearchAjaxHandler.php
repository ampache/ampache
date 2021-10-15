<?php

declare(strict_types=0);

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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Repository\Model\Album;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Module\Wanted\MissingArtistFinderInterface;
use Ampache\Repository\AlbumRepositoryInterface;

final class SearchAjaxHandler implements AjaxHandlerInterface
{
    private MissingArtistFinderInterface $missingArtistFinder;

    public function __construct(
        MissingArtistFinderInterface $missingArtistFinder
    ) {
        $this->missingArtistFinder = $missingArtistFinder;
    }

    public function handle(): void
    {
        // Switch on the actions
        switch ($_REQUEST['action']) {
            case 'search':
                $web_path = AmpConfig::get('web_path');
                $search   = $_REQUEST['search'];
                $target   = $_REQUEST['target'];
                $limit    = $_REQUEST['limit'] ?? 5;

                $results = array();

                if ($target == 'anywhere' || $target == 'artist') {
                    $searchreq = array(
                        'limit' => $limit,
                        'type' => 'artist',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'name',
                    );
                    $sres = Search::run($searchreq);
                    // Limit not reached, new search with another operator
                    if (count($sres) < $limit) {
                        $searchreq['limit']           = $limit - count($sres);
                        $searchreq['rule_1_operator'] = '0';
                        $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
                    }
                    foreach ($sres as $artistid) {
                        $artist    = new Artist($artistid);
                        $results[] = array(
                            'type' => T_('Artists'),
                            'link' => $web_path . '/artists.php?action=show&artist=' . $artistid,
                            'label' => scrub_out($artist->get_fullname()),
                            'value' => scrub_out($artist->get_fullname()),
                            'rels' => '',
                            'image' => Art::url($artist->id, 'artist', null, 10),
                        );
                    }
                }

                if ($target == 'anywhere' || $target == 'album') {
                    $searchreq = array(
                        'limit' => $limit,
                        'type' => 'album',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'title',
                    );
                    $sres = Search::run($searchreq);
                    // Limit not reached, new search with another operator
                    if (count($sres) < $limit) {
                        $searchreq['limit']           = $limit - count($sres);
                        $searchreq['rule_1_operator'] = '0';
                        $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
                    }
                    foreach ($sres as $albumid) {
                        $album     = new Album($albumid);
                        $results[] = array(
                            'type' => T_('Albums'),
                            'link' => $web_path . '/albums.php?action=show&album=' . $albumid,
                            'label' => scrub_out($album->get_fullname()),
                            'value' => scrub_out($album->get_fullname()),
                            'rels' => scrub_out($album->get_album_artist_name()),
                            'image' => Art::url($album->id, 'album', null, 10),
                        );
                    }
                }

                if ($target == 'anywhere' || $target == 'title') {
                    $searchreq = array(
                        'limit' => $limit,
                        'type' => 'song',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'title',
                    );
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
                        $results[]  = array(
                            'type' => T_('Songs'),
                            'link' => $web_path . "/song.php?action=show_song&song_id=" . $songid,
                            'label' => scrub_out($song->title),
                            'value' => scrub_out($song->title),
                            'rels' => scrub_out($song->get_artist_name()),
                            'image' => Art::url($art_object, $art_type, null, 10),
                        );
                    }
                }

                if ($target == 'anywhere' || $target == 'playlist_name') {
                    $searchreq = array(
                        'limit' => $limit,
                        'type' => 'playlist',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'name',
                    );
                    $sres = Search::run($searchreq);
                    // Limit not reached, new search with another operator
                    if (count($sres) < $limit) {
                        $searchreq['limit']           = $limit - count($sres);
                        $searchreq['rule_1_operator'] = '0';
                        $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
                    }
                    foreach ($sres as $playlistid) {
                        $playlist  = new Playlist($playlistid);
                        $results[] = array(
                            'type' => T_('Playlists'),
                            'link' => $web_path . '/playlist.php?action=show_playlist&playlist_id=' . $playlistid,
                            'label' => $playlist->name,
                            'value' => $playlist->get_fullname(),
                            'rels' => '',
                            'image' => '',
                        );
                    }
                }

                if (($target == 'anywhere' || $target == 'label') && AmpConfig::get('label')) {
                    $searchreq = array(
                        'limit' => $limit,
                        'type' => 'label',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'name',
                    );
                    $sres = Search::run($searchreq);

                    // Limit not reached, new search with another operator
                    if (count($sres) < $limit) {
                        $searchreq['limit']           = $limit - count($sres);
                        $searchreq['rule_1_operator'] = '0';
                        $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
                    }
                    foreach ($sres as $labelid) {
                        $label     = new Label($labelid);
                        $results[] = array(
                            'type' => T_('Labels'),
                            'link' => $web_path . '/labels.php?action=show&label=' . $labelid,
                            'label' => $label->name,
                            'value' => $label->name,
                            'rels' => '',
                            'image' => Art::url($label->id, 'label', null, 10),
                        );
                    }
                }

                if ($target == 'missing_artist' && AmpConfig::get('wanted')) {
                    $sres     = $this->missingArtistFinder->find($search);
                    $count    = 0;
                    foreach ($sres as $artist) {
                        $results[] = array(
                            'type' => T_('Missing Artists'),
                            'link' => AmpConfig::get('web_path') . '/artists.php?action=show_missing&mbid=' . $artist['mbid'],
                            'label' => scrub_out($artist['name']),
                            'value' => scrub_out($artist['name']),
                            'rels' => '',
                            'image' => '',
                        );
                        $count++;

                        if ($count >= $limit) {
                            break;
                        }
                    }
                }

                if ($target == 'user' && AmpConfig::get('sociable')) {
                    $searchreq = array(
                        'limit' => $limit,
                        'type' => 'user',
                        'rule_1_input' => $search,
                        'rule_1_operator' => '2', // Starts with...
                        'rule_1' => 'username',
                    );
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
                        $results[] = array(
                            'type' => T_('Users'),
                            'link' => '',
                            'label' => $user->username,
                            'value' => $user->username,
                            'rels' => '',
                            'image' => $avatar['url'] ?? '',
                        );
                    }
                }

                break;
            default:
                $results['rfc3514'] = '0x1';
                break;
        } // switch on action;

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
