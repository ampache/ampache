<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) { exit; }

switch ($_REQUEST['action']) {
    case 'search':
        $search = $_REQUEST['search'];
        $target = $_REQUEST['target'];
        $limit = $_REQUEST['limit'] ?: 5;

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
                $searchreq['limit'] = $limit - count($sres);
                $searchreq['rule_1_operator'] = '0';
                $sres = array_unique(array_merge($sres, Search::run($searchreq)));
            }
            foreach ($sres as $id) {
                $artist = new Artist($id);
                $artist->format();
                $results[] = array(
                    'type' => T_('Artists'),
                    'link' => $artist->f_link,
                    'label' => $artist->f_name,
                    'value' => $artist->f_name,
                    'rels' => '',
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
                $searchreq['limit'] = $limit - count($sres);
                $searchreq['rule_1_operator'] = '0';
                $sres = array_unique(array_merge($sres, Search::run($searchreq)));
            }
            foreach ($sres as $id) {
                $album = new Album($id);
                $album->format();
                $results[] = array(
                    'type' => T_('Albums'),
                    'link' => $album->f_link_src,
                    'label' => $album->f_title,
                    'value' => $album->f_title,
                    'rels' => $album->f_artist,
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
                $searchreq['limit'] = $limit - count($sres);
                $searchreq['rule_1_operator'] = '0';
                $sres = array_unique(array_merge($sres, Search::run($searchreq)));
            }
            foreach ($sres as $id) {
                $song = new Song($id);
                $song->format();
                $results[] = array(
                    'type' => T_('Songs'),
                    'link' => $song->link,
                    'label' => $song->f_title_full,
                    'value' => $song->f_title_full,
                    'rels' => $song->f_artist_full,
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
