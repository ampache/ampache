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

/* Make sure they have access to this */
if (!AmpConfig::get('allow_democratic_playback')) {
    UI::access_denied();

    return false;
}

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'manage':
        $democratic = Democratic::get_current_playlist();
        $democratic->set_parent();
        $democratic->format();
        // Intentional break fall-through
    case 'show_create':
        if (!Access::check('interface', 75)) {
            UI::access_denied();
            break;
        }

        // Show the create page
        require_once AmpConfig::get('prefix') . UI::find_template('show_create_democratic.inc.php');
        break;
    case 'delete':
        if (!Access::check('interface', 75)) {
            UI::access_denied();
            break;
        }

        Democratic::delete($_REQUEST['democratic_id']);

        show_confirmation(T_('No Problem'), T_('The Playlist has been deleted'), AmpConfig::get('web_path') . '/democratic.php?action=manage_playlists');
        break;
    case 'create':
        // Only power users here
        if (!Access::check('interface', 75)) {
            UI::access_denied();
            break;
        }

        if (!Core::form_verify('create_democratic')) {
            UI::access_denied();

            return false;
        }

        $democratic = Democratic::get_current_playlist();

        // If we don't have anything currently create something
        if (!$democratic->id) {
            // Create the playlist
            Democratic::create($_POST);
            $democratic = Democratic::get_current_playlist();
        } else {
            if (!$democratic->update($_POST)) {
                show_confirmation(T_("There Was a Problem"),
                    T_("Cooldown out of range."),
                    AmpConfig::get('web_path') . "/democratic.php?action=manage");
                break;
            }
        }

        // Now check for additional things we might have to do
        if (Core::get_post('force_democratic') !== '') {
            Democratic::set_user_preferences();
        }

        header("Location: " . AmpConfig::get('web_path') . "/democratic.php?action=show");
        break;
    case 'manage_playlists':
        if (!Access::check('interface', 75)) {
            UI::access_denied();
            break;
        }
        // Get all of the non-user playlists
        $playlists = Democratic::get_playlists();

        require_once AmpConfig::get('prefix') . UI::find_template('show_manage_democratic.inc.php');

        break;
    case 'show_playlist':
    default:
        $democratic = Democratic::get_current_playlist();
        if (!$democratic->id) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_democratic.inc.php');
            break;
        }

        $democratic->set_parent();
        $democratic->format();
        require_once AmpConfig::get('prefix') . UI::find_template('show_democratic.inc.php');
        $objects = $democratic->get_items();
        Song::build_cache($democratic->object_ids);
        Democratic::build_vote_cache($democratic->vote_ids);
        $browse = new Browse();
        $browse->set_type('democratic');
        $browse->set_static_content(false);
        $browse->save_objects($objects);
        $browse->show_objects();
        $browse->store();
        break;
} // end switch on action

// Show the Footer
UI::show_query_stats();
UI::show_footer();
