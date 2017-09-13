<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    exit;
}

switch ($_REQUEST['action']) {
    case 'search':
        $search = $_REQUEST['search'];
        $target = $_REQUEST['target'];
        $limit  = $_REQUEST['limit'] ?: 5;

        $results = array();

        if ($target == 'anywhere' || $target == 'artist') {
            $searchreq = array(
                'limit' => $limit,
                'type' => 'artist',
                'rule_1_input' => $search,
                'rule_1_operator' => '2',   // Starts with...
                'rule_1' => 'name',
            );
            $sres = Search::run($searchreq);
            // Litmit not reach, new search with another operator
            if (count($sres) < $limit) {
                $searchreq['limit']           = $limit - count($sres);
                $searchreq['rule_1_operator'] = '0';
                $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
            }
            foreach ($sres as $id) {
                $artist = new Artist($id);
                $artist->format(false);
                $results[] = array(
                    'type' => T_('Artists'),
                    'link' => $artist->link,
                    'label' => $artist->name,
                    'value' => $artist->name,
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
                'rule_1_operator' => '2',   // Starts with...
                'rule_1' => 'title',
            );
            $sres = Search::run($searchreq);
            // Litmit not reach, new search with another operator
            if (count($sres) < $limit) {
                $searchreq['limit']           = $limit - count($sres);
                $searchreq['rule_1_operator'] = '0';
                $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
            }
            foreach ($sres as $id) {
                $album = new Album($id);
                $album->format(true);
                $a_title = $album->f_title;
                if ($album->disk) {
                    $a_title .= " [" . T_('Disk') . " " . $album->disk . "]";
                }
                $results[] = array(
                    'type' => T_('Albums'),
                    'link' => $album->link,
                    'label' => $a_title,
                    'value' => $album->f_title,
                    'rels' => $album->f_artist,
                    'image' => Art::url($album->id, 'album', null, 10),
                );
            }
        }

        if ($target == 'anywhere' || $target == 'title') {
            $searchreq = array(
                'limit' => $limit,
                'type' => 'song',
                'rule_1_input' => $search,
                'rule_1_operator' => '2',   // Starts with...
                'rule_1' => 'title',
            );
            $sres = Search::run($searchreq);
            // Litmit not reach, new search with another operator
            if (count($sres) < $limit) {
                $searchreq['limit']           = $limit - count($sres);
                $searchreq['rule_1_operator'] = '0';
                $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
            }
            foreach ($sres as $id) {
                $song = new Song($id);
                $song->format(false);
                $results[] = array(
                    'type' => T_('Songs'),
                    'link' => $song->link,
                    'label' => $song->f_title_full,
                    'value' => $song->f_title_full,
                    'rels' => $song->f_artist_full,
                    'image' => Art::url($song->album, 'album', null, 10),
                );
            }
        }

        if ($target == 'anywhere' || $target == 'playlist_name') {
            $searchreq = array(
                'limit' => $limit,
                'type' => 'playlist',
                'rule_1_input' => $search,
                'rule_1_operator' => '2',   // Starts with...
                'rule_1' => 'name',
            );
            $sres = Search::run($searchreq);
            // Litmit not reach, new search with another operator
            if (count($sres) < $limit) {
                $searchreq['limit']           = $limit - count($sres);
                $searchreq['rule_1_operator'] = '0';
                $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
            }
            foreach ($sres as $id) {
                $playlist = new Playlist($id);
                $playlist->format(false);
                $results[] = array(
                    'type' => T_('Playlists'),
                    'link' => $playlist->link,
                    'label' => $playlist->name,
                    'value' => $playlist->name,
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
                'rule_1_operator' => '2',   // Starts with...
                'rule_1' => 'name',
            );
            $sres = Search::run($searchreq);

            // Litmit not reach, new search with another operator
            if (count($sres) < $limit) {
                $searchreq['limit']           = $limit - count($sres);
                $searchreq['rule_1_operator'] = '0';
                $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
            }
            foreach ($sres as $id) {
                $label = new Label($id);
                $label->format(false);
                $results[] = array(
                    'type' => T_('Labels'),
                    'link' => $label->link,
                    'label' => $label->name,
                    'value' => $label->name,
                    'rels' => '',
                    'image' => Art::url($label->id, 'label', null, 10),
                );
            }
        }

        if ($target == 'missing_artist' && AmpConfig::get('wanted')) {
            $sres = Wanted::search_missing_artists($search);
            $i    = 0;
            foreach ($sres as $r) {
                $results[] = array(
                    'type' => T_('Missing Artists'),
                    'link' => AmpConfig::get('web_path') . '/artists.php?action=show_missing&mbid=' . $r['mbid'],
                    'label' => $r['name'],
                    'value' => $r['name'],
                    'rels' => '',
                    'image' => '',
                );
                $i++;

                if ($i >= $limit) {
                    break;
                }
            }
        }

        if ($target == 'user' && AmpConfig::get('sociable')) {
            $searchreq = array(
                'limit' => $limit,
                'type' => 'user',
                'rule_1_input' => $search,
                'rule_1_operator' => '2',   // Starts with...
                'rule_1' => 'username',
            );
            $sres = Search::run($searchreq);

            // Litmit not reach, new search with another operator
            if (count($sres) < $limit) {
                $searchreq['limit']           = $limit - count($sres);
                $searchreq['rule_1_operator'] = '0';
                $sres                         = array_unique(array_merge($sres, Search::run($searchreq)));
            }
            foreach ($sres as $id) {
                $user = new User($id);
                $user->format();
                $avatar    = $user->get_avatar();
                $results[] = array(
                    'type' => T_('Users'),
                    'link' => '',
                    'label' => $user->username,
                    'value' => $user->username,
                    'rels' => '',
                    'image' => $avatar['url'] ?: '',
                );
            }
        }

    break;
    default:
        $results['rfc3514'] = '0x1';
    break;
} // switch on action;

// We always do this
echo xoutput_from_array($results);
