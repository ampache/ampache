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

require_once '../lib/init.php';

if (!Access::check('interface', '100')) {
    UI::access_denied();

    return false;
}

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'update_user':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        if (!Core::form_verify('edit_user', 'post')) {
            UI::access_denied();

            return false;
        }

        /* Clean up the variables */
        $user_id         = (int) filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
        $username        = scrub_in(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $fullname        = scrub_in(filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $email           = scrub_in(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $website         = scrub_in(filter_input(INPUT_POST, 'website', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $access          = scrub_in(filter_input(INPUT_POST, 'access', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $pass1           = filter_input(INPUT_POST, 'password_1', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $pass2           = filter_input(INPUT_POST, 'password_2', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $state           = scrub_in(filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $city            = scrub_in(filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $fullname_public = filter_has_var(INPUT_POST, 'fullname_public');

        /* Setup the temp user */
        $client = new User($user_id);

        /* Verify Input */
        if (empty($username)) {
            AmpError::add('username', T_("Error Username Required"));
        } else {
            if ($username != $client->username) {
                if (!User::check_username($username)) {
                    AmpError::add('username', T_("Error Username already exists"));
                }
            }
        }
        if ($pass1 !== $pass2 && !empty($pass1)) {
            AmpError::add('password', T_("Error Passwords don't match"));
        }

        // Check the mail for correct address formation.
        if (!Mailer::validate_address($email)) {
            AmpError::add('email', T_('Invalid email address'));
        }

        /* If we've got an error then show edit form! */
        if (AmpError::occurred()) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_edit_user.inc.php');
            break;
        }

        if ($access != $client->access) {
            $client->update_access($access);
        }
        if ($email != $client->email) {
            $client->update_email($email);
        }
        if ($website != $client->website) {
            $client->update_website($website);
        }
        if ($username != $client->username) {
            $client->update_username($username);
        }
        if ($fullname != $client->fullname) {
            $client->update_fullname($fullname);
        }
        if ($fullname_public != $client->fullname_public) {
            $client->update_fullname_public($fullname_public);
        }
        if ($pass1 == $pass2 && strlen($pass1)) {
            $client->update_password($pass1);
        }
        if ($state != $client->state) {
            $client->update_state($state);
        }
        if ($city != $client->city) {
            $client->update_city($city);
        }
        $client->upload_avatar();

        show_confirmation(T_('User Updated'), $client->fullname . "(" . $client->username . ")" . T_('updated'), AmpConfig::get('web_path') . '/admin/users.php');
    break;
    case 'add_user':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        if (!Core::form_verify('add_user', 'post')) {
            UI::access_denied();

            return false;
        }

        $username       = scrub_in(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $fullname       = scrub_in(filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $email          = scrub_in(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $website        = scrub_in(filter_input(INPUT_POST, 'website', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $access         = scrub_in(filter_input(INPUT_POST, 'access', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $pass1          = filter_input(INPUT_POST, 'password_1', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $pass2          = filter_input(INPUT_POST, 'password_2', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $state          = (string) scrub_in(filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $city           = (string) scrub_in(Core::get_get('city'));

        if ($pass1 !== $pass2 || !strlen($pass1)) {
            AmpError::add('password', T_("Error Passwords don't match"));
        }

        if (empty($username)) {
            AmpError::add('username', T_('Error Username Required'));
        }

        /* make sure the username doesn't already exist */
        if (!User::check_username($username)) {
            AmpError::add('username', T_('Error Username already exists'));
        }

        // Check the mail for correct address formation.
        if (!Mailer::validate_address($email)) {
            AmpError::add('email', T_('Invalid email address'));
        }

        /* If we've got an error then show add form! */
        if (AmpError::occurred()) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_add_user.inc.php');
            break;
        }

        /* Attempt to create the user */
        $user_id = User::create($username, $fullname, $email, $website, $pass1, $access, $state, $city);
        if (!$user_id) {
            AmpError::add('general', T_("Error: Insert Failed"));
        }
        $user = new User($user_id);
        $user->upload_avatar();

        switch ($access) {
            case 5:
                $access = T_('Guest');
                break;
            case 25:
                $access = T_('User');
                break;
            case 100:
                $access = T_('Admin');
        }

        /* HINT: %1 Username, %2 Access num */
        show_confirmation(T_('New User Added'), sprintf(T_('%1$s has been created with an access level of %2$s'), $username, $access), AmpConfig::get('web_path') . '/admin/users.php');
    break;
    case 'enable':
        $client = new User(Core::get_request('user_id'));
        $client->enable();
        if (!AmpConfig::get('user_no_email_confirm')) {
            Registration::send_account_enabled($client->username, $client->fullname, $client->email);
        }
        show_confirmation(T_('User Enabled'), $client->fullname . ' (' . $client->username . ')', AmpConfig::get('web_path') . '/admin/users.php');
    break;
    case 'disable':
        $client = new User(Core::get_request('user_id'));
        if ($client->disable()) {
            show_confirmation(T_('User Disabled'), $client->fullname . ' (' . $client->username . ')', AmpConfig::get('web_path') . '/admin/users.php');
        } else {
            show_confirmation(T_('Error'), T_('Unable to disable last Administrator'), AmpConfig::get('web_path') . '/admin/users.php');
        }
    break;
    case 'show_edit':
        if (AmpConfig::get('demo_mode')) {
            break;
        }
        $client    = new User(Core::get_request('user_id'));
        require_once AmpConfig::get('prefix') . UI::find_template('show_edit_user.inc.php');
    break;
    case 'confirm_delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }
        if (!Core::form_verify('delete_user')) {
            UI::access_denied();

            return false;
        }
        $client = new User(Core::get_request('user_id'));
        if ($client->delete()) {
            show_confirmation(T_('User Deleted'), sprintf(T_('%s has been Deleted'), $client->username), AmpConfig::get('web_path') . "/admin/users.php");
        } else {
            show_confirmation(T_('Delete Error'), T_("Unable to delete last Administrator"), AmpConfig::get('web_path') . "/admin/users.php");
        }
    break;
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }
        $client = new User(Core::get_request('user_id'));
        show_confirmation(T_('Confirm Action'),
            sprintf(T_('Are you sure you want to permanently delete %s?'), $client->fullname),
            AmpConfig::get('web_path') . "/admin/users.php?action=confirm_delete&amp;user_id=" . Core::get_request('user_id'), 1, 'delete_user');
    break;
    case 'show_delete_avatar':
        $user_id = Core::get_request('user_id');

        $next_url = AmpConfig::get('web_path') . '/admin/users.php?action=delete_avatar&user_id=' . scrub_out($user_id);
        show_confirmation(T_('Confirm Action'), T_('Delete User Avatar'), $next_url, 1, 'delete_avatar');
    break;
    case 'delete_avatar':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        if (!Core::form_verify('delete_avatar', 'post')) {
            UI::access_denied();

            return false;
        }

        $client = new User(Core::get_request('user_id'));
        $client->delete_avatar();

        $next_url = AmpConfig::get('web_path') . '/admin/users.php';
        show_confirmation(T_('Deleted'), T_('User Avatar has been deleted'), $next_url);
    break;
    case 'show_generate_apikey':
        $user_id = Core::get_request('user_id');

        $next_url = AmpConfig::get('web_path') . '/admin/users.php?action=generate_apikey&user_id=' . scrub_out($user_id);
        show_confirmation(T_('Generate new API Key'), T_('Confirm API Key Generation'), $next_url, 1, 'generate_apikey');
    break;
    case 'generate_apikey':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        if (!Core::form_verify('generate_apikey', 'post')) {
            UI::access_denied();

            return false;
        }

        $client = new User(Core::get_request('user_id'));
        $client->generate_apikey();

        $next_url = AmpConfig::get('web_path') . '/admin/users.php';
        show_confirmation(T_('API Key Generated'), T_('New user API Key has been generated.'), $next_url);
    break;
    /* Show IP History for the Specified User */
    case 'show_ip_history':
        /* get the user and their history */
        $working_user    = new User(Core::get_request('user_id'));

        if (!isset($_REQUEST['all'])) {
            $history    = $working_user->get_ip_history(0, 1);
        } else {
            $history    = $working_user->get_ip_history();
        }
        require AmpConfig::get('prefix') . UI::find_template('show_ip_history.inc.php');
    break;
    case 'show_add_user':
            if (AmpConfig::get('demo_mode')) {
                break;
            }
        require_once AmpConfig::get('prefix') . UI::find_template('show_add_user.inc.php');
    break;
    case 'show_preferences':
        $client      = new User(Core::get_request('user_id'));
        $preferences = Preference::get_all($client->id);
        require_once AmpConfig::get('prefix') . UI::find_template('show_user_preferences.inc.php');
    break;
    default:
        $browse = new Browse();
        $browse->reset_filters();
        $browse->set_type('user');
        $browse->set_simple_browse(true);
        $browse->set_sort('name', 'ASC');
        $user_ids = $browse->get_objects();
        $browse->show_objects($user_ids);
        $browse->store();
    break;
} // end switch on action

/* Show the footer */
UI::show_footer();
