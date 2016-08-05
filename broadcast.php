<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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
    exit;
}

UI::show_header();

/* Switch on the action passed in */
switch ($_REQUEST['action']) {
    case 'show_delete':
        $id = $_REQUEST['id'];

        $next_url = AmpConfig::get('web_path') . '/broadcast.php?action=delete&id=' . scrub_out($id);
        show_confirmation(T_('Broadcast Delete'), T_('Confirm Deletion Request'), $next_url, 1, 'delete_broadcast');
        UI::show_footer();
        exit;
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();
            exit;
        }

        $id        = $_REQUEST['id'];
        $broadcast = new Broadcast($id);
        if ($broadcast->delete()) {
            $next_url = AmpConfig::get('web_path') . '/browse.php?action=broadcast';
            show_confirmation(T_('Broadcast Deleted'), T_('The Broadcast has been deleted'), $next_url);
        }
        UI::show_footer();
        exit;
} // switch on the action

UI::show_footer();
