<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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

// Switch on Action
switch ($_REQUEST['action']) {
    case 'show_lyrics':
        $song = new Song($_REQUEST['song_id']);
        $song->format();
        $song->fill_ext_info();
        $lyrics = $song->get_lyrics();
        require_once AmpConfig::get('prefix') . '/templates/show_lyrics.inc.php';
    break;
    case 'show_song':
    default:
        $song = new Song($_REQUEST['song_id']);
        $song->format();
        $song->fill_ext_info();
        require_once AmpConfig::get('prefix') . '/templates/show_song.inc.php';
    break;
} // end data collection

UI::show_footer();
