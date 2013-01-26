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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
require_once '../lib/init.php';
session_start();

if (!defined('AJAX_INCLUDE')) { exit; }

if (isset($_REQUEST['browse_id'])) {
    $browse_id = $_REQUEST['browse_id'];
}
else {
    $browse_id = null;
}

$browse = new Browse($browse_id);

switch ($_REQUEST['action']) {
    case 'browse':
        $object_ids = array();

        // Check 'value' with isset because it can null
        //(user type a "start with" word and deletes it)
        if ($_REQUEST['key'] && (isset($_REQUEST['multi_alpha_filter']) OR isset($_REQUEST['value']))) {
            // Set any new filters we've just added
            $browse->set_filter($_REQUEST['key'],$_REQUEST['multi_alpha_filter']);
            $browse->set_catalog($_SESSION['catalog']);
        }

        if ($_REQUEST['sort']) {
            // Set the new sort value
            $browse->set_sort($_REQUEST['sort']);
        }
        
        
        if ($_REQUEST['catalog_key'] || $SESSION['catalog'] != 0) {
            $browse->set_filter('catalog',$_REQUEST['catalog_key']);
            $_SESSION['catalog'] = $_REQUEST['catalog_key'];
        } elseif ($_REQUEST['catalog_key'] == 0) {
            $browse->set_filter('catalog', null);
            unset($_SESSION['catalog']);
        }

        ob_start();
                $browse->show_objects();
                $results['browse_content'] = ob_get_clean();
    break;
    
    case 'set_sort':
    
        if ($_REQUEST['sort']) {
            $browse->set_sort($_REQUEST['sort']);
        }

        ob_start();
        $browse->show_objects();
        $results['browse_content'] = ob_get_clean();
    break;
    case 'toggle_tag':
        $type = $_SESSION['tagcloud_type'] ? $_SESSION['tagcloud_type'] : 'song';
        $browse->set_type($type);
    break;
    case 'delete_object':
        switch ($_REQUEST['type']) {
            case 'playlist':
                // Check the perms we need to on this
                $playlist = new Playlist($_REQUEST['id']);
                if (!$playlist->has_access()) { exit; }

                // Delete it!
                $playlist->delete();
                $key = 'playlist_row_' . $playlist->id;
            break;
            case 'smartplaylist':
                $playlist = new Search('song', $_REQUEST['id']);
                if (!$playlist->has_access()) { exit; }
                $playlist->delete();
                $key = 'playlist_row_' . $playlist->id;
            break;
            case 'live_stream':
                if (!$GLOBALS['user']->has_access('75')) { exit; }
                $radio = new Radio($_REQUEST['id']);
                $radio->delete();
                $key = 'live_stream_' . $radio->id;
            break;
            default:

            break;
        } // end switch on type

        $results[$key] = '';

    break;
    case 'page':
        $browse->set_start($_REQUEST['start']);

        ob_start();
        $browse->show_objects();
        $results['browse_content'] = ob_get_clean();
    break;
    case 'show_art':
        Art::set_enabled();

        ob_start();
        $browse->show_objects();
        $results['browse_content'] = ob_get_clean();
    break;
    case 'get_filters':
        ob_start();
        require_once Config::get('prefix') . '/templates/browse_filters.inc.php';
        $results['browse_filters'] = ob_get_clean();
    default:
        $results['rfc3514'] = '0x1';
    break;
} // switch on action;

$browse->store();

// We always do this
echo xml_from_array($results);
?>
