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

define('NO_SESSION', '1');
$_SESSION['login'] = true;

$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

/* Check Perms */
if (!AmpConfig::get('allow_public_registration')) {
    debug_event('register', 'Error Attempted registration', 2);
    UI::access_denied();

    return false;
}

/* Don't even include it if we aren't going to use it */
if (AmpConfig::get('captcha_public_reg')) {
    define("CAPTCHA_INVERSE", 1);
    define("CAPTCHA_BASE_URL", AmpConfig::get('web_path') . '/modules/captcha/captcha.php');
    require_once AmpConfig::get('prefix') . '/modules/captcha/captcha.php';
}

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'validate':
        $username      = scrub_in(Core::get_get('username'));
        $validation    = scrub_in(Core::get_get('auth'));
        require_once AmpConfig::get('prefix') . UI::find_template('show_user_activate.inc.php');
        break;
    case 'add_user':
        /**
         * User information has been entered
         * we need to check the database for possible existing username first
         * if username exists, error and say "Please choose a different name."
         * if username does not exist, insert user information into database
         * then allow the user to 'click here to login'
         * possibly by logging them in right then and there with their current info
         * and 'click here to login' would just be a link back to index.php
         */
        $fullname       = (string) scrub_in(Core::get_post('fullname'));
        $username       = (string) scrub_in(Core::get_post('username'));
        $email          = (string) scrub_in(Core::get_post('email'));
        $pass1          = Core::get_post('password_1');
        $pass2          = Core::get_post('password_2');
        $website        = (string) scrub_in(Core::get_post('website'));
        $state          = (string) scrub_in(Core::get_post('state'));
        $city           = (string) scrub_in(Core::get_post('city'));

        if ($website === null) {
            $website = '';
        }
        if ($state === null) {
            $state = '';
        }
        if ($city === null) {
            $city = '';
        }

        /* If we're using the captcha stuff */
        if (AmpConfig::get('captcha_public_reg')) {
            $captcha         = captcha::solved();
            if (!isset($captcha)) {
                AmpError::add('captcha', T_('Captcha is required'));
            }
            if (isset($captcha)) {
                if ($captcha) {
                    $msg="SUCCESS";
                } else {
                    AmpError::add('captcha', T_('Captcha failed'));
                }
            } // end if we've got captcha
        } // end if it's enabled

        if (AmpConfig::get('user_agreement')) {
            if (!$_POST['accept_agreement']) {
                AmpError::add('user_agreement', T_("You must accept the user agreement"));
            }
        } // if they have to agree to something

        if (!filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) {
            AmpError::add('username', T_("You must enter a Username"));
        }

        // Check the mail for correct address formation.
        if (!Mailer::validate_address($email)) {
            AmpError::add('email', T_('Invalid e-mail address'));
        }

        $mandatory_fields = (array) AmpConfig::get('registration_mandatory_fields');
        if (in_array('fullname', $mandatory_fields) && !$fullname) {
            AmpError::add('fullname', T_("Please fill in your full name (first name, last name)"));
        }
        if (in_array('website', $mandatory_fields) && !$website) {
            AmpError::add('website', T_("Please fill in your website"));
        }
        if (in_array('state', $mandatory_fields) && !$state) {
            AmpError::add('state', T_("Please fill in your state"));
        }
        if (in_array('city', $mandatory_fields) && !$city) {
            AmpError::add('city', T_("Please fill in your city"));
        }

        if (!$pass1) {
            AmpError::add('password', T_("You must enter a password"));
        }

        if ($pass1 != $pass2) {
            AmpError::add('password', T_("Passwords do not match"));
        }

        if (!User::check_username((string) $username)) {
            AmpError::add('duplicate_user', T_("That Username already exists"));
        }

        // If we've hit an error anywhere up there break!
        if (AmpError::occurred()) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_user_registration.inc.php');
            break;
        }

        /* Attempt to create the new user */
        $access = 5;
        switch (AmpConfig::get('auto_user')) {
            case 'admin':
                $access = 100;
                break;
            case 'user':
                $access = 25;
                break;
            case 'guest':
            default:
                $access = 5;
                break;
        } // auto-user level

        $user_id = User::create($username, $fullname, $email, (string) $website, $pass1, $access, (string) $state, (string) $city, AmpConfig::get('admin_enable_required'));

        if ($user_id < 1) {
            AmpError::add('duplicate_user', T_("Failed to create user"));
            require_once AmpConfig::get('prefix') . UI::find_template('show_user_registration.inc.php');
            break;
        }

        if (!AmpConfig::get('user_no_email_confirm')) {
            $client     = new User($user_id);
            $validation = md5(uniqid((string) rand(), true));
            $client->update_validation($validation);

            // Notify user and/or admins
            Registration::send_confirmation($username, $fullname, $email, $website, $validation);
        }

        require_once AmpConfig::get('prefix') . UI::find_template('show_registration_confirmation.inc.php');
        break;
    case 'show_add_user':
    default:
        require_once AmpConfig::get('prefix') . UI::find_template('show_user_registration.inc.php');
        break;
} // end switch on action
