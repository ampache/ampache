<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

require_once 'lib/init.php';

require_once AmpConfig::get('prefix') . UI::find_template('header.inc.php');

/* Switch on Action */
switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $album_id = scrub_in($_REQUEST['album_id']);
        show_confirmation(
            T_('Album Deletion'),
            T_('Are you sure you want to permanently delete this album?'),
            AmpConfig::get('web_path') . "/albums.php?action=confirm_delete&album_id=" . $album_id,
            1,
            'delete_album'
        );
    break;
    case 'confirm_delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $album = new Album($_REQUEST['album_id']);
        if (!Catalog::can_remove($album)) {
            debug_event('album', 'Unauthorized to remove the album `.' . $album->id . '`.', 1);
            UI::access_denied();
            exit;
        }

        if ($album->remove_from_disk()) {
            show_confirmation(T_('Album Deletion'), T_('Album has been deleted.'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_('Album Deletion'), T_('Cannot delete this album.'), AmpConfig::get('web_path'));
        }
    break;
    case 'update_from_tags':
        // Make sure they are a 'power' user at least
        if (!Access::check('interface', '75')) {
            UI::access_denied();
            exit;
        }

        $type          = 'album';
        $object_id     = intval($_REQUEST['album_id']);
        $target_url    = AmpConfig::get('web_path') . '/albums.php?action=show&amp;album=' . $object_id;
        require_once AmpConfig::get('prefix') . UI::find_template('show_update_items.inc.php');
    break;
    case 'set_track_numbers':
        debug_event('albums', 'Set track numbers called.', '5');

        if (!Access::check('interface', '75')) {
            UI::access_denied();
            exit;
        }

        // Retrieving final song order from url
        foreach ($_GET as $key => $data) {
            $_GET[$key] = unhtmlentities(scrub_in($data));
            debug_event('albums', $key . '=' . $_GET[$key], '5');
        }

        if (isset($_GET['order'])) {
            $songs = explode(";", $_GET['order']);
            $track = $_GET['offset'] ? (intval($_GET['offset']) + 1) : 1;
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
        $mbid   = $_REQUEST['mbid'];
        $walbum = new Wanted(Wanted::get_wanted($mbid));

        if (!$walbum->id) {
            $walbum->mbid = $mbid;
            if (isset($_REQUEST['artist'])) {
                $artist              = new Artist($_REQUEST['artist']);
                $walbum->artist      = $artist->id;
                $walbum->artist_mbid = $artist->mbid;
            } elseif (isset($_REQUEST['artist_mbid'])) {
                $walbum->artist_mbid = $_REQUEST['artist_mbid'];
            }
        }
        $walbum->load_all();
        $walbum->format();
        require AmpConfig::get('prefix') . UI::find_template('show_missing_album.inc.php');
    break;
    // Browse by Album
    case 'show':
    default:
        $album = new Album($_REQUEST['album']);
        $album->format();

        if (!count($album->album_suite)) {
            require AmpConfig::get('prefix') . UI::find_template('show_album.inc.php');
        } else {
            require AmpConfig::get('prefix') . UI::find_template('show_album_group_disks.inc.php');
        }

    break;
} // switch on view

UI::show_footer();
