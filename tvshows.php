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
        $tvshow = new TVShow($_REQUEST['tvshow']);
        $tvshow->format();
        $object_ids = $tvshow->get_seasons();
        $object_type = 'tvshow_season';
        require_once AmpConfig::get('prefix') . '/templates/show_tvshow.inc.php';
        break;
    case 'match':
    case 'Match':
        $match = scrub_in($_REQUEST['match']);
        if ($match == "Browse") { $chr = ""; } else { $chr = $match; }
        /* Enclose this in the purty box! */
        require AmpConfig::get('prefix') . '/templates/show_box_top.inc.php';
        show_alphabet_list('tvshows','tvshows.php',$match);
        show_alphabet_form($chr, T_('Show TV Shows starting with'),"tvshows.php?action=match");
        require AmpConfig::get('prefix') . '/templates/show_box_bottom.inc.php';

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
