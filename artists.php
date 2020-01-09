<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once 'lib/init.php';

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $artist_id = (string) scrub_in($_REQUEST['artist_id']);
        show_confirmation(T_('Are You Sure?'), T_("The Artist and all files will be deleted"),
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

        if ($artist->remove_from_disk()) {
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
    case 'match':
    case 'Match':
        $match = scrub_in($_REQUEST['match']);
        if ($match == "Browse" || $match == "Show_all") {
            $chr = "";
        } else {
            $chr = $match;
        }
        /* Enclose this in the purty box! */
        require AmpConfig::get('prefix') . UI::find_template('show_box_top.inc.php');
        show_alphabet_list('artists', 'artists.php', $match);
        show_alphabet_form($chr, T_('Show Artists starting with'), "artists.php?action=match");
        require AmpConfig::get('prefix') . UI::find_template('show_box_bottom.inc.php');

        if ($match === "Browse") {
            show_artists();
        } elseif ($match === "Show_all") {
            $offset_limit = 999999;
            show_artists();
        } else {
            if ($chr == '') {
                show_artists('A');
            } else {
                show_artists($chr);
            }
        }
    break;
    case 'show_missing':
        set_time_limit(600);
        $mbid    = $_REQUEST['mbid'];
        $wartist = Wanted::get_missing_artist($mbid);

        require AmpConfig::get('prefix') . UI::find_template('show_missing_artist.inc.php');
    break;
} // end switch

/* Show the Footer */
UI::show_query_stats();
UI::show_footer();
