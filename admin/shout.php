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

$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

if (!Access::check('interface', 100)) {
    UI::access_denied();

    return false;
}

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'edit_shout':
        $shout = new Shoutbox($_REQUEST['shout_id']);
        if ($shout->id) {
            $shout->update($_POST);
        }
        show_confirmation(T_('No Problem'), T_('Shoutbox post has been updated'), AmpConfig::get('web_path') . '/admin/shout.php');
        break;
    case 'show_edit':
        $shout  = new Shoutbox($_REQUEST['shout_id']);
        $object = Shoutbox::get_object($shout->object_type, $shout->object_id);
        $object->format();
        $client = new User($shout->user);
        $client->format();
        require_once AmpConfig::get('prefix') . UI::find_template('show_edit_shout.inc.php');
        break;
    case 'delete':
        $shout = new Shoutbox($_REQUEST['shout_id']);
        Shoutbox::delete($_REQUEST['shout_id']);
        show_confirmation(T_('No Problem'), T_('Shoutbox post has been deleted'), AmpConfig::get('web_path') . '/admin/shout.php');
        break;
    default:
        $browse = new Browse();
        $browse->set_type('shoutbox');
        $browse->set_simple_browse(true);
        $shoutbox_ids = $browse->get_objects();
        $browse->show_objects($shoutbox_ids);
        $browse->store();
        break;
} // end switch on action

// Show the Footer
UI::show_query_stats();
UI::show_footer();
