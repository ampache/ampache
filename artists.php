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

UI::show_header();

/**
 * Display Switch
 */
switch ($_REQUEST['action']) {
    case 'show':
        $artist = new Artist($_REQUEST['artist']);
        $artist->format();
        if (AmpConfig::get('album_release_type')) {
            $multi_object_ids = $artist->get_albums($_REQUEST['catalog'], false, true);
        } else {
            $object_ids = $artist->get_albums($_REQUEST['catalog']);
        }
        $object_type = 'album';
        require_once AmpConfig::get('prefix') . '/templates/show_artist.inc.php';
        break;
    case 'show_all_songs':
        $artist = new Artist($_REQUEST['artist']);
        $artist->format();
        $object_type = 'song';
        $object_ids = $artist->get_songs();
        require_once AmpConfig::get('prefix') . '/templates/show_artist.inc.php';
        break;
    case 'update_from_tags':
        $type  = 'artist';
        $object_id = intval($_REQUEST['artist']);
        $target_url = AmpConfig::get('web_path') . "/artists.php?action=show&amp;artist=" . $object_id;
        require_once AmpConfig::get('prefix') . '/templates/show_update_items.inc.php';
    break;
    case 'match':
    case 'Match':
        $match = scrub_in($_REQUEST['match']);
        if ($match == "Browse" || $match == "Show_all") { $chr = ""; } else { $chr = $match; }
        /* Enclose this in the purty box! */
        require AmpConfig::get('prefix') . '/templates/show_box_top.inc.php';
        show_alphabet_list('artists','artists.php',$match);
        show_alphabet_form($chr, T_('Show Artists starting with'),"artists.php?action=match");
        require AmpConfig::get('prefix') . '/templates/show_box_bottom.inc.php';

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
        $mbid = $_REQUEST['mbid'];
        $wartist = Wanted::get_missing_artist($mbid);

        require AmpConfig::get('prefix') . '/templates/show_missing_artist.inc.php';
    break;
} // end switch

UI::show_footer();
