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

$title             = "";
$text              = "";
$next_url          = "";
$notification_text = "";
$action            = Core::get_request('action');

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'update_preferences':
        if (Core::get_post('method') == 'admin' && !Access::check('interface', 100)) {
            UI::access_denied();

            return false;
        }

        if (!Core::form_verify('update_preference', 'post')) {
            UI::access_denied();

            return false;
        }

        $system = false;
        /* Reset the Theme */
        if (Core::get_post('method') == 'admin') {
            $user_id            = '-1';
            $system             = true;
            $fullname           = T_('Server');
            $_REQUEST['action'] = 'admin';
        } else {
            $user_id  = Core::get_global('user')->id;
            $fullname = Core::get_global('user')->fullname;
        }

        /* Update and reset preferences */
        update_preferences($user_id);
        Preference::init();

        // Reset gettext so that it's clear whether the preference took
        // FIXME: do we need to do any header fiddling?
        load_gettext();

        $preferences = Core::get_global('user')->get_preferences($_REQUEST['tab'], $system);

        if (Core::get_post('method') == 'admin') {
            $notification_text = T_('Server preferences updated successfully');
        } else {
            $notification_text = T_('User preferences updated successfully');
        }
        break;
    case 'admin_update_preferences':
        // Make sure only admins here
        if (!Access::check('interface', 100)) {
            UI::access_denied();

            return false;
        }

        if (!Core::form_verify('update_preference', 'post')) {
            UI::access_denied();

            return false;
        }

        update_preferences((int) Core::get_post('user_id'));

        header("Location: " . AmpConfig::get('web_path') . "/admin/users.php?action=show_preferences&user_id=" . scrub_out(Core::get_post('user_id')));
        break;
    case 'admin':
        // Make sure only admins here
        if (!Access::check('interface', 100)) {
            UI::access_denied();

            return false;
        }
        $fullname    = T_('Server');
        $preferences = Core::get_global('user')->get_preferences($_REQUEST['tab'], true);
        break;
    case 'user':
        if (!Access::check('interface', 100)) {
            UI::access_denied();

            return false;
        }
        $client      = new User((int) Core::get_request('user_id'));
        $fullname    = $client->fullname;
        $preferences = $client->get_preferences($_REQUEST['tab']);
        break;
    case 'update_user':
        // Make sure we're a user and they came from the form
        if (!Access::check('interface', 25) && Core::get_global('user')->id > 0) {
            UI::access_denied();

            return false;
        }

        if (!Core::form_verify('update_user', 'post')) {
            UI::access_denied();

            return false;
        }

        // block updates from simple users
        if (AmpConfig::get('simple_user_mode')) {
            UI::access_denied();

            return false;
        }

        // Remove the value
        unset($_SESSION['forms']['account']);

        // Don't let them change access, or username here
        unset($_POST['access']);
        $_POST['username'] = Core::get_global('user')->username;

        $mandatory_fields = (array) AmpConfig::get('registration_mandatory_fields');
        if (in_array('fullname', $mandatory_fields) && !$_POST['fullname']) {
            AmpError::add('fullname', T_("Please fill in your full name (first name, last name)"));
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

        if (!Core::get_global('user')->update($_POST)) {
            AmpError::add('general', T_('Update failed'));
        } else {
            Core::get_global('user')->upload_avatar();

            //$_REQUEST['action'] = 'confirm';
            $title    = T_('No Problem');
            $text     = T_('Your account has been updated');
            $next_url = AmpConfig::get('web_path') . '/preferences.php?tab=account';
        }

        $notification_text = T_('User updated successfully');
        break;
    case 'grant':
        // Make sure we're a user and they came from the form
        if (!Access::check('interface', 25) && Core::get_global('user')->id > 0) {
            UI::access_denied();

            return false;
        }
        if (Core::get_request('token') && in_array(Core::get_request('plugin'), Plugin::get_plugins('save_mediaplay'))) {
            // we receive a token for a valid plugin, have to call getSession and obtain a session key
            if ($plugin = new Plugin(Core::get_request('plugin'))) {
                $plugin->load(Core::get_global('user'));
                if ($plugin->_plugin->get_session(Core::get_global('user')->id, Core::get_request('token'))) {
                    $title    = T_('No Problem');
                    $text     = T_('Your account has been updated') . ' : ' . Core::get_request('plugin');
                    $next_url = AmpConfig::get('web_path') . '/preferences.php?tab=plugins';
                } else {
                    $title    = T_("There Was a Problem");
                    $text     = T_('Your account has not been updated') . ' : ' . Core::get_request('plugin');
                    $next_url = AmpConfig::get('web_path') . '/preferences.php?tab=plugins';
                }
            }
        }
        $fullname    = Core::get_global('user')->fullname;
        $preferences = Core::get_global('user')->get_preferences($_REQUEST['tab']);
        break;
    default:
        $fullname    = Core::get_global('user')->fullname;
        $preferences = Core::get_global('user')->get_preferences($_REQUEST['tab']);
        break;
} // End Switch Action

UI::show_header();

/**
 * switch on the view
 */
switch ($_REQUEST['action']) {
    case 'confirm':
    case 'grant':
        show_confirmation($title, $text, $next_url, $cancel);
        break;
    default:
        if (!empty($notification_text)) {
            display_notification($notification_text);
        }
        // Show the default preferences page
        require AmpConfig::get('prefix') . UI::find_template('show_preferences.inc.php');
        break;
} // end switch on action

// Show the Footer
UI::show_query_stats();
UI::show_footer();
