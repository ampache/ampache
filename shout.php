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

require_once 'lib/init.php';

UI::show_header();

// Switch on the incomming action
switch ($_REQUEST['action']) {
    case 'add_shout':
        // Must be at least a user to do this
        if (!Access::check('interface','25')) {
            UI::access_denied();
            exit;
        }

        if (!Core::form_verify('add_shout','post')) {
            UI::access_denied();
            exit;
        }

        $shout_id = Shoutbox::create($_POST);
        header("Location:" . AmpConfig::get('web_path'));
    break;
    case 'show_add_shout':
        // Get our object first
        $object = Shoutbox::get_object($_REQUEST['type'],$_REQUEST['id']);

        if (!$object || !$object->id) {
            Error::add('general', T_('Invalid Object Selected'));
            Error::display('general');
            break;
        }

        $object->format();
        if (strtolower(get_class($object)) == 'song') {
            $data = $_REQUEST['offset'];
        }

        // Now go ahead and display the page where we let them add a comment etc
        require_once AmpConfig::get('prefix') . '/templates/show_add_shout.inc.php';
    break;
    default:
        header("Location:" . AmpConfig::get('web_path'));
    break;
} // end switch on action

UI::show_footer();
