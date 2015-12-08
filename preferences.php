<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

$title             = "";
$text              = "";
$next_url          = "";
$notification_text = "";

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
            $user_id            = '-1';
            $system             = true;
            $fullname           = T_('Server');
            $_REQUEST['action'] = 'admin';
        } else {
            $user_id  = $GLOBALS['user']->id;
            $fullname = $GLOBALS['user']->fullname;
        }

        /* Update and reset preferences */
        update_preferences($user_id);
        Preference::init();

        // Reset gettext so that it's clear whether the preference took
        // FIXME: do we need to do any header fiddling?
        load_gettext();

        $preferences = $GLOBALS['user']->get_preferences($_REQUEST['tab'], $system);

        if ($_POST['method'] == 'admin') {
            $notification_text = T_('Server preferences updated successfully');
        } else {
            $notification_text = T_('User preferences updated successfully');
        }
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
        $fullname    = T_('Server');
        $preferences = $GLOBALS['user']->get_preferences($_REQUEST['tab'], true);
    break;
    case 'user':
        if (!Access::check('interface','100')) {
            UI::access_denied();
            exit;
        }
        $client      = new User($_REQUEST['user_id']);
        $fullname    = $client->fullname;
        $preferences = $client->get_preferences($_REQUEST['tab']);
    break;
    case 'update_user':
        // Make sure we're a user and they came from the form
        if (!Access::check('interface','25') && $GLOBALS['user']->id > 0) {
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

        $mandatory_fields = (array) AmpConfig::get('registration_mandatory_fields');
        if (in_array('fullname', $mandatory_fields) && !$_POST['fullname']) {
            AmpError::add('fullname', T_("Please fill in your full name (Firstname Lastname)"));
        }
        if (in_array('website', $mandatory_fields) && !$_POST['website']) {
            AmpError::add('website', T_("Please fill in your website"));
        }
        if (in_array('state', $mandatory_fields) && !$_POST['state']) {
            AmpError::add('state', T_("Please fill in your state"));
        }
        if (in_array('city', $mandatory_fields) && !$_POST['city']) {
            AmpError::add('city', T_("Please fill in your city"));
        }

        if (!$GLOBALS['user']->update($_POST)) {
            AmpError::add('general', T_('Error Update Failed'));
        } else {
            $GLOBALS['user']->upload_avatar();

            //$_REQUEST['action'] = 'confirm';
            $title    = T_('Updated');
            $text     = T_('Your Account has been updated');
            $next_url = AmpConfig::get('web_path') . '/preferences.php?tab=account';
        }

        $notification_text = T_('User updated successfully');
    break;
    case 'grant':
        // Make sure we're a user and they came from the form
        if (!Access::check('interface','25') && $GLOBALS['user']->id > 0) {
            UI::access_denied();
            exit;
        }
        if ($_REQUEST['token'] && in_array($_REQUEST['plugin'], Plugin::get_plugins('save_mediaplay'))) {
            // we receive a token for a valid plugin, have to call getSession and obtain a session key
            if ($plugin = new Plugin($_REQUEST['plugin'])) {
                $plugin->load($GLOBALS['user']);
                if ($plugin->_plugin->get_session($GLOBALS['user']->id, $_REQUEST['token'])) {
                    $title    = T_('Updated');
                    $text     = T_('Your Account has been updated') . ' : ' . $_REQUEST['plugin'];
                    $next_url = AmpConfig::get('web_path') . '/preferences.php?tab=plugins';
                } else {
                    $title    = T_('Error');
                    $text     = T_('Your Account has not been updated') . ' : ' . $_REQUEST['plugin'];
                    $next_url = AmpConfig::get('web_path') . '/preferences.php?tab=plugins';
                }
            }
        }
        $fullname    = $GLOBALS['user']->fullname;
        $preferences = $GLOBALS['user']->get_preferences($_REQUEST['tab']);
    break;
    default:
        $fullname    = $GLOBALS['user']->fullname;
        $preferences = $GLOBALS['user']->get_preferences($_REQUEST['tab']);
    break;
} // End Switch Action

UI::show_header();

/**
 * switch on the view
 */
switch ($_REQUEST['action']) {
    case 'confirm':
    case 'grant':
        show_confirmation($title,$text,$next_url,$cancel);
    break;
    default:
        if (!empty($notification_text)) {
            display_notification($notification_text);
        }

        // Show the default preferences page
        require AmpConfig::get('prefix') . UI::find_template('show_preferences.inc.php');
    break;
} // end switch on action

UI::show_footer();
