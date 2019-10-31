<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once 'lib/init.php';

if (!AmpConfig::get('broadcast')) {
    UI::access_denied();

    return false;
}

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'show_delete':
        $object_id = Core::get_request('id');

        $next_url = AmpConfig::get('web_path') . '/broadcast.php?action=delete&id=' . scrub_out($object_id);
        show_confirmation(T_('Are You Sure?'), T_('This Broadcast will be deleted'), $next_url, 1, 'delete_broadcast');
        UI::show_footer();

        return false;
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();

            return false;
        }

        $object_id = Core::get_request('id');
        $broadcast = new Broadcast((int) $object_id);
        if ($broadcast->delete()) {
            $next_url = AmpConfig::get('web_path') . '/browse.php?action=broadcast';
            show_confirmation(T_('No Problem'), T_('Broadcast has been deleted'), $next_url);
        }
        UI::show_footer();

        return false;
} // switch on the action

/* Show the Footer */
UI::show_query_stats();
UI::show_footer();
