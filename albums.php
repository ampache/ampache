<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

require_once 'lib/init.php';

require_once AmpConfig::get('prefix') . '/templates/header.inc.php';

/* Switch on Action */
switch ($_REQUEST['action']) {
    case 'update_from_tags':
        // Make sure they are a 'power' user at least
        if (!Access::check('interface','75')) {
            UI::access_denied();
            exit;
        }

        $type         = 'album';
        $object_id     = intval($_REQUEST['album_id']);
        $target_url    = AmpConfig::get('web_path') . '/albums.php?action=show&amp;album=' . $object_id;
        require_once AmpConfig::get('prefix') . '/templates/show_update_items.inc.php';
    break;
    case 'set_track_numbers':
        debug_event('albums', 'Set track numbers called.', '5');

        if (!Access::check('interface','75')) {
            UI::access_denied();
            exit;
        }

        // Retrieving final song order from url
        foreach ($_GET as $key => $data) {
            $_GET[$key] = unhtmlentities(scrub_in($data));
            debug_event('albums', $key.'='.$_GET[$key], '5');
        }

        if (isset($_GET['order'])) {
            $songs = explode(";", $_GET['order']);
            $track = 1;
            foreach ($songs as $song_id) {
                if ($song_id != '') {
                    Song::update_track($track, $song_id);
                    ++$track;
                }
            }
        }
    break;
    case 'show_missing':
        set_time_limit(600);
        $mbid = $_REQUEST['mbid'];
        $walbum = new Wanted(Wanted::get_wanted($mbid));

        if (!$walbum->id) {
            $walbum->mbid = $mbid;
            if (isset($_REQUEST['artist'])) {
                $artist = new Artist($_REQUEST['artist']);
                $walbum->artist = $artist->id;
                $walbum->artist_mbid = $artist->mbid;
            } elseif (isset($_REQUEST['artist_mbid'])) {
                $walbum->artist_mbid = $_REQUEST['artist_mbid'];
            }
        }
        $walbum->load_all();
        $walbum->format();
        require AmpConfig::get('prefix') . '/templates/show_missing_album.inc.php';
    break;
    // Browse by Album
    case 'show':
    default:
        $album = new Album($_REQUEST['album']);
        $album->format();

        if (!count($album->album_suite)) {
            require AmpConfig::get('prefix') . '/templates/show_album.inc.php';
        } else {
            require AmpConfig::get('prefix') . '/templates/show_album_group_disks.inc.php';
        }

    break;
} // switch on view

UI::show_footer();
