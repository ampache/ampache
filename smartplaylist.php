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

$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

// We special-case this so we can send a 302 if the delete succeeded
if (Core::get_request('action') == 'delete_playlist') {
    // Check rights
    $playlist = new Search((int) $_REQUEST['playlist_id']);
    if ($playlist->has_access()) {
        $playlist->delete();
        // Go elsewhere
        header('Location: ' . AmpConfig::get('web_path') . '/browse.php?action=smartplaylist');

        return false;
    }
}

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'create_playlist':
        /* Check rights */
        if (!Access::check('interface', 25)) {
            UI::access_denied();
            break;
        }

        foreach ($_REQUEST as $key => $value) {
            $prefix = substr($key, 0, 4);
            $value  = trim($value);

            if ($prefix == 'rule' && strlen($value)) {
                $rules[$key] = Dba::escape($value);
            }
        }

        switch ($_REQUEST['operator']) {
            case 'or':
                $operator = 'OR';
                break;
            default:
                $operator = 'AND';
                break;
        } // end switch on operator

        $playlist_name    = (string) scrub_in($_REQUEST['playlist_name']);

        $playlist = new Search(null);
        $playlist->parse_rules($data);
        $playlist->logic_operator = $operator;
        $playlist->name           = $playlist_name;
        $playlist->save();

        break;
    case 'delete_playlist':
        // If we made it here, we didn't have sufficient rights.
        UI::access_denied();
        break;
    case 'show_playlist':
        $playlist = new Search((int) $_REQUEST['playlist_id']);
        $playlist->format();
        $object_ids = $playlist->get_items();
        require_once AmpConfig::get('prefix') . UI::find_template('show_search.inc.php');
        break;
    case 'update_playlist':
        $playlist = new Search((int) $_REQUEST['playlist_id']);
        if ($playlist->has_access()) {
            $playlist->parse_rules(Search::clean_request($_REQUEST));
            $playlist->update();
            $playlist->format();
        } else {
            UI::access_denied();
            break;
        }
        $object_ids = $playlist->get_items();
        require_once AmpConfig::get('prefix') . UI::find_template('show_search.inc.php');
        break;
    default:
        $playlist   = new Search((int) $_REQUEST['playlist_id']);
        $object_ids = $playlist->get_items();
        require_once AmpConfig::get('prefix') . UI::find_template('show_search.inc.php');
        break;
} // switch on the action

// Show the Footer
UI::show_query_stats();
UI::show_footer();
