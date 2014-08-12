<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2010 - 2013 Ampache.org
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

// We special-case this so we can send a 302 if the delete succeeded
if ($_REQUEST['action'] == 'delete_playlist') {
    // Check rights
    $playlist = new Search($_REQUEST['playlist_id'], 'song');
    if ($playlist->has_access()) {
        $playlist->delete();
        // Go elsewhere
        header('Location: ' . AmpConfig::get('web_path') . '/browse.php?action=smartplaylist');
        exit;
    }
}

UI::show_header();

/* Switch on the action passed in */
switch ($_REQUEST['action']) {
    case 'create_playlist':
        /* Check rights */
        if (!Access::check('interface','25')) {
            UI::access_denied();
            break;
        }

        foreach ($_REQUEST as $key => $value) {
            $prefix = substr($key, 0, 4);
            $value = trim($value);

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

        $playlist_name    = scrub_in($_REQUEST['playlist_name']);

        $playlist = new Search(null, 'song');
        $playlist->parse_rules($data);
        $playlist->logic_operator = $operator;
        $playlist->name = $playlist_name;
        $playlist->save();

    break;
    case 'delete_playlist':
        // If we made it here, we didn't have sufficient rights.
        UI::access_denied();
    break;
    case 'show_playlist':
        $playlist = new Search($_REQUEST['playlist_id'], 'song');
        $playlist->format();
        $object_ids = $playlist->get_items();
        require_once AmpConfig::get('prefix') . '/templates/show_search.inc.php';
    break;
    case 'update_playlist':
        $playlist = new Search($_REQUEST['playlist_id'], 'song');
        if ($playlist->has_access()) {
            $playlist->parse_rules(Search::clean_request($_REQUEST));
            $playlist->update();
            $playlist->format();
        } else {
            UI::access_denied();
            break;
        }
        $object_ids = $playlist->get_items();
        require_once AmpConfig::get('prefix') . '/templates/show_search.inc.php';
    break;
    default:
        $object_ids = $playlist->get_items();
        require_once AmpConfig::get('prefix') . '/templates/show_search.inc.php';
    break;
} // switch on the action

UI::show_footer();
