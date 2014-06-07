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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    exit;
}

$results = array();
switch ($_REQUEST['action']) {
    case 'song':
    case 'album':
    case 'artist':
    case 'tag':
    case 'playlist':
    case 'smartplaylist':
    case 'channel':
    case 'broadcast':
    case 'live_stream':
    case 'video':
        ob_start();
        debug_event('content.ajax.php', 'I GET HERE', '5');
        require_once AmpConfig::get('prefix') . '/browse.php';
        $results[$_REQUEST['page']] = ob_get_clean();
        ob_end_clean();
        break;
    default:
        // Ne rien faire
        break;

} // switch on action

// We always do this
echo xoutput_from_array($results);
