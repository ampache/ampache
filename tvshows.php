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

UI::show_header();

/**
 * Display Switch
 */
switch ($_REQUEST['action']) {
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $tvshow_id = scrub_in($_REQUEST['tvshow_id']);
        show_confirmation(
            T_('TVShow Deletion'),
            T_('Are you sure you want to permanently delete this tvshow?'),
            AmpConfig::get('web_path') . "/tvshows.php?action=confirm_delete&tvshow_id=" . $tvshow_id,
            1,
            'delete_tvshow'
        );
    break;
    case 'confirm_delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $tvshow = new TVShow($_REQUEST['tvshow_id']);
        if (!Catalog::can_remove($tvshow)) {
            debug_event('tvshow', 'Unauthorized to remove the tvshow `.' . $tvshow->id . '`.', 1);
            UI::access_denied();
            exit;
        }

        if ($tvshow->remove_from_disk()) {
            show_confirmation(T_('TVShow Deletion'), T_('TVShow has been deleted.'), AmpConfig::get('web_path'));
        } else {
            show_confirmation(T_('TVShow Deletion'), T_('Cannot delete this tvshow.'), AmpConfig::get('web_path'));
        }
    break;
    case 'show':
        $tvshow = new TVShow($_REQUEST['tvshow']);
        $tvshow->format();
        $object_ids  = $tvshow->get_seasons();
        $object_type = 'tvshow_season';
        require_once AmpConfig::get('prefix') . UI::find_template('show_tvshow.inc.php');
        break;
    case 'match':
    case 'Match':
        $match = scrub_in($_REQUEST['match']);
        if ($match == "Browse") {
            $chr = "";
        } else {
            $chr = $match;
        }
        /* Enclose this in the purty box! */
        require AmpConfig::get('prefix') . UI::find_template('show_box_top.inc.php');
        show_alphabet_list('tvshows', 'tvshows.php', $match);
        show_alphabet_form($chr, T_('Show TV Shows starting with'), "tvshows.php?action=match");
        require AmpConfig::get('prefix') . UI::find_template('show_box_bottom.inc.php');

        if ($match === "Browse") {
            show_tvshows();
        } elseif ($match === "Show_all") {
            $offset_limit = 999999;
            show_tvshows();
        } else {
            if ($chr == '') {
                show_tvshows('A');
            } else {
                show_tvshows($chr);
            }
        }
    break;
} // end switch

UI::show_footer();
