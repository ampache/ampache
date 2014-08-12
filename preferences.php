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

$title = "";
$text = "";
$next_url = "";

// Switch on the action
switch ($_REQUEST['action']) {
    case 'update_preferences':
        if ($_POST['method'] == 'admin' && !Access::check('interface','100')) {
            UI::access_denied();
            exit;
        }

        if (!Core::form_verify('update_preference','post')) {
            UI::access_denied();
            exit;
        }

        $system = false;
        /* Reset the Theme */
        if ($_POST['method'] == 'admin') {
            $user_id = '-1';
            $system = true;
            $fullname = T_('Server');
            $_REQUEST['action'] = 'admin';
        } else {
            $user_id = $GLOBALS['user']->id;
            $fullname = $GLOBALS['user']->fullname;
        }

        /* Update and reset preferences */
        update_preferences($user_id);
        Preference::init();

        // Reset gettext so that it's clear whether the preference took
        // FIXME: do we need to do any header fiddling?
        load_gettext();

        $preferences = $GLOBALS['user']->get_preferences($_REQUEST['tab'], $system);
    break;
    case 'admin_update_preferences':
        // Make sure only admins here
        if (!Access::check('interface','100')) {
            UI::access_denied();
            exit;
        }

        if (!Core::form_verify('update_preference','post')) {
            UI::access_denied();
            exit;
        }

        update_preferences($_POST['user_id']);
        header("Location: " . AmpConfig::get('web_path') . "/admin/users.php?action=show_preferences&user_id=" . scrub_out($_POST['user_id']));
    break;
    case 'admin':
        // Make sure only admins here
        if (!Access::check('interface','100')) {
            UI::access_denied();
            exit;
        }
        $fullname= T_('Server');
        $preferences = $GLOBALS['user']->get_preferences($_REQUEST['tab'], true);
    break;
    case 'user':
        if (!Access::check('interface','100')) {
            UI::access_denied();
            exit;
        }
        $client = new User($_REQUEST['user_id']);
        $fullname = $client->fullname;
        $preferences = $client->get_preferences($_REQUEST['tab']);
    break;
    case 'update_user':
        // Make sure we're a user and they came from the form
        if (!Access::check('interface','25') OR !AmpConfig::get('use_auth')) {
            UI::access_denied();
            exit;
        }

        if (!Core::form_verify('update_user','post')) {
            UI::access_denied();
            exit;
        }

        // Remove the value
        unset($_SESSION['forms']['account']);

        // Don't let them change access, or username here
        unset($_POST['access']);
        $_POST['username'] = $GLOBALS['user']->username;

        if (!$GLOBALS['user']->update($_POST)) {
            $GLOBALS['user']->upload_avatar();
            Error::add('general', T_('Error Update Failed'));
        } else {
            $_REQUEST['action'] = 'confirm';
            $title = T_('Updated');
            $text = T_('Your Account has been updated');
            $next_url = AmpConfig::get('web_path') . '/preferences.php?tab=account';
        }
    break;
    default:
        $fullname = $GLOBALS['user']->fullname;
        $preferences = $GLOBALS['user']->get_preferences($_REQUEST['tab']);
    break;
} // End Switch Action

UI::show_header();

/**
 * switch on the view
 */
switch ($_REQUEST['action']) {
    case 'confirm':
        show_confirmation($title,$text,$next_url,$cancel);
    break;
    default:
        // Show the default preferences page
        require AmpConfig::get('prefix') . '/templates/show_preferences.inc.php';
    break;
} // end switch on action

UI::show_footer();
