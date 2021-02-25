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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

if (!Core::is_session_started()) {
    session_start();
}

if (!defined('AJAX_INCLUDE')) {
    return false;
}

if (isset($_REQUEST['browse_id'])) {
    $browse_id = $_REQUEST['browse_id'];
} else {
    $browse_id = null;
}

debug_event('browse.ajax', 'Called for action: {' . Core::get_request('action') . '}', 5);

$browse = new Browse($browse_id);

if (isset($_REQUEST['show_header']) && $_REQUEST['show_header']) {
    $browse->set_show_header($_REQUEST['show_header'] == 'true');
}

$argument = false;
if ($_REQUEST['argument']) {
    $argument = scrub_in($_REQUEST['argument']);
}

$results = array();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'browse':
        $object_ids = array();

        // Check 'value' with isset because it can null
        //(user type a "start with" word and deletes it)
        if ($_REQUEST['key'] && (isset($_REQUEST['multi_alpha_filter']) || isset($_REQUEST['value']))) {
            // Set any new filters we've just added
            $browse->set_filter($_REQUEST['key'], $_REQUEST['multi_alpha_filter']);
            $browse->set_catalog($_SESSION['catalog']);
        }

        if ($_REQUEST['sort']) {
            // Set the new sort value
            $browse->set_sort($_REQUEST['sort']);
        }

        if ($_REQUEST['catalog_key'] || $_SESSION['catalog'] != 0) {
            $browse->set_filter('catalog', $_REQUEST['catalog_key']);
            $_SESSION['catalog'] = $_REQUEST['catalog_key'];
        } elseif ((int) Core::get_request('catalog_key') == 0) {
            $browse->set_filter('catalog', null);
            unset($_SESSION['catalog']);
        }

        ob_start();
        $browse->show_objects(null, $argument);
        $results[$browse->get_content_div()] = ob_get_clean();
        break;
    case 'set_sort':
        if ($_REQUEST['sort']) {
            $browse->set_sort($_REQUEST['sort']);
        }

        if (!$browse->is_use_pages()) {
            $browse->set_start(0);
        }

        ob_start();
        $browse->show_objects(null, $argument);
        $results[$browse->get_content_div()] = ob_get_clean();
        break;
    case 'toggle_tag':
        $type = $_SESSION['tagcloud_type'] ? $_SESSION['tagcloud_type'] : 'song';
        $browse->set_type($type);
        break;
    case 'delete_object':
        switch ($_REQUEST['type']) {
            case 'playlist':
                // Check the perms we need to on this
                $playlist = new Playlist((int) Core::get_request('id'));
                if (!$playlist->has_access()) {
                    return false;
                }

                // Delete it!
                $playlist->delete();
                $key = 'playlist_row_' . $playlist->id;
                break;
            case 'smartplaylist':
                $playlist = new Search((int) Core::get_request('id'));
                if (!$playlist->has_access()) {
                    return false;
                }
                $playlist->delete();
                $key = 'smartplaylist_row_' . $playlist->id;
                break;
            case 'live_stream':
                if (!Core::get_global('user')->has_access('75')) {
                    return false;
                }
                $radio = new Live_Stream((int) Core::get_request('id'));
                $radio->delete();
                $key = 'live_stream_' . $radio->id;
                break;
            default:
                return false;
        } // end switch on type

        $results[$key] = '';

        break;
    case 'page':
        $browse->set_start($_REQUEST['start']);
        ob_start();
        $browse->show_objects(null, $argument);
        $results[$browse->get_content_div()] = ob_get_clean();
        break;
    case 'show_art':
        Art::set_enabled();

        ob_start();
        $browse->show_objects(null, $argument);
        $results[$browse->get_content_div()] = ob_get_clean();
        break;
    case 'get_filters':
        ob_start();
        require_once AmpConfig::get('prefix') . UI::find_template('browse_filters.inc.php');
        $results['browse_filters'] = ob_get_clean();
        break;
    case 'options':
        $option = $_REQUEST['option'];
        $value  = $_REQUEST['value'];

        switch ($option) {
            case 'use_pages':
                $value = ($value == 'true');
                $browse->set_use_pages($value);
                if ($value) {
                    $browse->set_start(0);
                }
                break;
            case 'use_alpha':
                $value = ($value == 'true');
                $browse->set_use_alpha($value);
                $browse->set_start(0);
                if ($value) {
                    $browse->set_filter('regex_match', '^A');
                } else {
                    $browse->set_filter('regex_not_match', '');
                }
                break;
            case 'grid_view':
                /**
                 * The `grid view` is implemented inverted, so apply an inverted logic.
                 * This ensures the `grid view` checkbox behaves as expected
                 */
                $value = ($value == 'false');
                $browse->set_grid_view($value);
                break;
            case 'limit':
                $value = (int) ($value);
                if ($value > 0) {
                    $browse->set_offset($value);
                }
                break;
            case 'custom':
                $value = (int) ($value);
                $limit = $browse->get_offset();
                if ($limit > 0 && $value > 0) {
                    $total = $browse->get_total();
                    $pages = ceil($total / $limit);

                    if ($value <= $pages) {
                        $offset = ($value - 1) * $limit;
                        $browse->set_start($offset);
                    }
                }
                break;
        }

        ob_start();
        $browse->show_objects(null, $argument);
        $results[$browse->get_content_div()] = ob_get_clean();
        break;
    case 'get_share_links':
        $object_type = Core::get_request('object_type');
        $object_id   = (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);

        if (Core::is_library_item($object_type) && $object_id > 0) {
            Share::display_ui_links($object_type, $object_id);

            return false;
        }
        break;
    default:
        $results['rfc3514'] = '0x1';
        break;
} // switch on action;

$browse->store();

// We always do this
echo (string) xoutput_from_array($results);
