<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

/* Because this is accessed via Ajax we are going to allow the session_id
 * as part of the get request
 */

define('AJAX_INCLUDE','1');

require_once '../lib/init.php';

$results = '';

debug_event('show_edit_playlist.server.php', 'Called.', '5');

switch ($_REQUEST['action']) {
    case 'show_edit_object':
        ob_start();
        require AmpConfig::get('prefix') . '/templates/show_playlists_dialog.inc.php';
        $results = ob_get_contents();
        ob_end_clean();
    break;
    default:
        exit();
}

echo $results;
