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

require_once '../lib/init.php';

if (!Access::check('interface','100')) {
    UI::access_denied();
    exit;
}

UI::show_header();

// Switch on the incomming action
switch ($_REQUEST['action']) {
    case 'edit_shout':
        $shout = new Shoutbox($_REQUEST['shout_id']);
        if ($shout->id) {
            $shout->update($_POST);
        }
        show_confirmation(T_('Shoutbox Post Updated'),'',AmpConfig::get('web_path').'/admin/shout.php');
    break;
    case 'show_edit':
        $shout = new Shoutbox($_REQUEST['shout_id']);
        $object = Shoutbox::get_object($shout->object_type,$shout->object_id);
        $object->format();
        $client = new User($shout->user);
        $client->format();
        require_once AmpConfig::get('prefix') . '/templates/show_edit_shout.inc.php';
        break;
    case 'delete':
        Shoutbox::delete($_REQUEST['shout_id']);
        show_confirmation(T_('Shoutbox Post Deleted'),'',AmpConfig::get('web_path').'/admin/shout.php');
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

UI::show_footer();
