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

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $artist_id = (string) scrub_in($_REQUEST['artist_id']);
        show_confirmation(T_('Are You Sure?'),
            T_("The Artist and all files will be deleted"),
            AmpConfig::get('web_path') . "/artists.php?action=confirm_delete&artist_id=" . $artist_id,
            1,
            'delete_artist'
        );
        break;
    case 'confirm_delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $artist = new Artist($_REQUEST['artist_id']);
        if (!Catalog::can_remove($artist)) {
            debug_event('artists', 'Unauthorized to remove the artist `.' . $artist->id . '`.', 2);
            UI::access_denied();

            return false;
        }

        if ($artist->remove()) {
            show_confirmation(T_('No Problem'), T_('The Artist has been deleted'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_("There Was a Problem"), T_("Couldn't delete this Artist."), AmpConfig::get('web_path'));
        }
        break;
    case 'show':
        $artist = new Artist($_REQUEST['artist']);
        $artist->format();
        if (AmpConfig::get('album_release_type')) {
            $multi_object_ids = $artist->get_albums($_REQUEST['catalog'], true);
        } else {
            $object_ids = $artist->get_albums($_REQUEST['catalog']);
        }
        $object_type = 'album';
        if (!$artist->id) {
            debug_event('artists', 'Requested an artist that does not exist', 2);
            echo T_("You have requested an Artist that does not exist.");
        } else {
            require_once AmpConfig::get('prefix') . UI::find_template('show_artist.inc.php');
        }
        break;
    case 'show_all_songs':
        $artist = new Artist($_REQUEST['artist']);
        $artist->format();
        $object_type = 'song';
        $object_ids  = $artist->get_songs();
        require_once AmpConfig::get('prefix') . UI::find_template('show_artist.inc.php');
        break;
    case 'update_from_tags':
        $type       = 'artist';
        $object_id  = (int) filter_input(INPUT_GET, 'artist', FILTER_SANITIZE_NUMBER_INT);
        $target_url = AmpConfig::get('web_path') . "/artists.php?action=show&amp;artist=" . $object_id;
        require_once AmpConfig::get('prefix') . UI::find_template('show_update_items.inc.php');
        break;
    case 'show_missing':
        set_time_limit(600);
        $mbid    = $_REQUEST['mbid'];
        $wartist = Wanted::get_missing_artist($mbid);

        require AmpConfig::get('prefix') . UI::find_template('show_missing_artist.inc.php');
        break;
} // end switch

// Show the Footer
UI::show_query_stats();
UI::show_footer();
