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

define('AJAX_INCLUDE','1');

require_once '../lib/init.php';

debug_event('refresh_reordered.server.php', 'Called for action: {'.$_REQUEST['action'].'}', '5');

/* Switch on the action passed in */
switch ($_REQUEST['action']) {
    case 'refresh_playlist_songs':
        $playlist = new Playlist($_REQUEST['id']);
        $playlist->format();
        $object_ids = $playlist->get_items();
        $browse = new Browse();
        $browse->set_type('playlist_song');
        $browse->add_supplemental_object('playlist', $playlist->id);
        $browse->set_static_content(true);
        $browse->show_objects($object_ids);
        $browse->store();
    break;
    case 'refresh_album_songs':
        $browse = new Browse();
        $browse->set_show_header(true);
        $browse->set_type('song');
        $browse->set_simple_browse(true);
        $browse->set_filter('album', $_REQUEST['id']);
        $browse->set_sort('track', 'ASC');
        $browse->get_objects();
        echo "<div id='browse_content_song' class='browse_content'>";
        $browse->show_objects(null, true); // true argument is set to show the reorder column
        $browse->store();
        echo "</div>";
    break;
} // switch on the action
