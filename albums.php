<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

require_once AmpConfig::get('prefix') . UI::find_template('header.inc.php');

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $album_id = (string) scrub_in($_REQUEST['album_id']);
        show_confirmation(T_('Are You Sure?'),
            T_("The Album and all files will be deleted"),
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
            debug_event('albums', 'Unauthorized to remove the album `.' . $album->id . '`.', 2);
            UI::access_denied();

            return false;
        }

        if ($album->remove()) {
            show_confirmation(T_('No Problem'), T_('The Album has been deleted'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_("There Was a Problem"), T_("Couldn't delete this Album."), AmpConfig::get('web_path'));
        }
        break;
    case 'update_from_tags':
        // Make sure they are a 'power' user at least
        if (!Access::check('interface', 75)) {
            UI::access_denied();

            return false;
        }
        $album = new Album($_REQUEST['album_id']);
        $album->format();
        $catalog_id = $album->get_catalogs();
        $type       = 'album';
        $object_id  = (int) filter_input(INPUT_GET, 'album_id', FILTER_SANITIZE_NUMBER_INT);
        $target_url = AmpConfig::get('web_path') . '/albums.php?action=show&amp;album=' . $object_id;
        require_once AmpConfig::get('prefix') . UI::find_template('show_update_items.inc.php');
        break;
    case 'update_group_from_tags':
        // Make sure they are a 'power' user at least
        if (!Access::check('interface', 75)) {
            UI::access_denied();

            return false;
        }
        $album = new Album($_REQUEST['album_id']);
        $album->format();
        $catalog_id = $album->get_catalogs();
        $type       = 'album';
        $object_id  = (int) filter_input(INPUT_GET, 'album_id', FILTER_SANITIZE_NUMBER_INT);
        $objects    = $album->get_album_suite();
        $target_url = AmpConfig::get('web_path') . '/albums.php?action=show&amp;album=' . $object_id;
        require_once AmpConfig::get('prefix') . UI::find_template('show_update_item_group.inc.php');
        break;
    case 'set_track_numbers':
        debug_event('albums', 'Set track numbers called.', 5);

        if (!Access::check('interface', 75)) {
            UI::access_denied();

            return false;
        }

        // Retrieving final song order from url
        foreach ($_GET as $key => $data) {
            $_GET[$key] = unhtmlentities((string) scrub_in($data));
            debug_event('albums', $key . '=' . Core::get_get($key), 5);
        }

        if (filter_has_var(INPUT_GET, 'order')) {
            $songs = explode(";", Core::get_get('order'));
            $track = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT) ? ((filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT)) + 1) : 1;
            foreach ($songs as $song_id) {
                if ($song_id != '') {
                    Song::update_track($track, (int) $song_id);
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
        if (!$album->id) {
            debug_event('albums', 'Requested an album that does not exist', 2);
            echo T_("You have requested an Album that does not exist.");
        // allow single disks to not be shown as multi's
        } elseif (count($album->album_suite) <= 1) {
            require AmpConfig::get('prefix') . UI::find_template('show_album.inc.php');
        } else {
            require AmpConfig::get('prefix') . UI::find_template('show_album_group_disks.inc.php');
        }
        break;
} // switch on view

// Show the Footer
UI::show_query_stats();
UI::show_footer();
