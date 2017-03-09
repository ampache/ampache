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

define('NO_SESSION','1');
$_SESSION['login'] = true;
require_once 'lib/init.php';

/* Check Perms */
if (!AmpConfig::get('allow_public_registration') || AmpConfig::get('demo_mode')) {
    debug_event('DENIED','Error Attempted registration','1');
    UI::access_denied();
    exit();
}

/* Don't even include it if we aren't going to use it */
if (AmpConfig::get('captcha_public_reg')) {
    define ("CAPTCHA_INVERSE", 1);
    define ("CAPTCHA_BASE_URL", AmpConfig::get('web_path') . '/modules/captcha/captcha.php');
    require_once AmpConfig::get('prefix') . '/modules/captcha/captcha.php';
}


/* Start switch based on action passed */
switch ($_REQUEST['action']) {
    case 'validate':
        $username      = scrub_in($_GET['username']);
        $validation    = scrub_in($_GET['auth']);
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
        $fullname       = scrub_in($_POST['fullname']);
        $username       = scrub_in($_POST['username']);
        $email          = scrub_in($_POST['email']);
        $website        = scrub_in($_POST['website']);
        $pass1          = $_POST['password_1'];
        $pass2          = $_POST['password_2'];
        $state          = (string) scrub_in($_POST['state']);
        $city           = (string) scrub_in($_POST['city']);

        /* If we're using the captcha stuff */
        if (AmpConfig::get('captcha_public_reg')) {
            $captcha         = captcha::solved();
            if (!isset ($captcha)) {
                AmpError::add('captcha', T_('Error Captcha Required'));
            }
            if (isset ($captcha)) {
                if ($captcha) {
                    $msg="SUCCESS";
                } else {
                    AmpError::add('captcha', T_('Error Captcha Failed'));
                }
            } // end if we've got captcha
        } // end if it's enabled

        if (AmpConfig::get('user_agreement')) {
            if (!$_POST['accept_agreement']) {
                AmpError::add('user_agreement', T_("You <U>must</U> accept the user agreement"));
            }
        } // if they have to agree to something

        if (!$_POST['username']) {
            AmpError::add('username', T_("You did not enter a username"));
        }

        // Check the mail for correct address formation.
        if (!Mailer::validate_address($email)) {
            AmpError::add('email', T_('Invalid email address'));
        }

        $mandatory_fields = (array) AmpConfig::get('registration_mandatory_fields');
        if (in_array('fullname', $mandatory_fields) && !$fullname) {
            AmpError::add('fullname', T_("Please fill in your full name (Firstname Lastname)"));
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
            AmpError::add('password', T_("Your passwords do not match"));
        }

        if (!User::check_username($username)) {
            AmpError::add('duplicate_user', T_("Error Username already exists"));
        }

        // If we've hit an error anywhere up there break!
        if (AmpError::occurred()) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_user_registration.inc.php');
            break;
        }

        /* Attempt to create the new user */
        $access = '5';
        switch (AmpConfig::get('auto_user')) {
            case 'admin':
                $access = '100';
            break;
            case 'user':
                $access = '25';
            break;
            case 'guest':
            default:
                $access = '5';
            break;
        } // auto-user level


        $new_user = User::create($username, $fullname, $email, $website, $pass1,
            $access, $state, $city, AmpConfig::get('admin_enable_required'));

        if (!$new_user) {
            AmpError::add('duplicate_user', T_("Error: Insert Failed"));
            require_once AmpConfig::get('prefix') . UI::find_template('show_user_registration.inc.php');
            break;
        }

        if (!AmpConfig::get('user_no_email_confirm')) {
            $client     = new User($new_user);
            $validation = md5(uniqid(rand(), true));
            $client->update_validation($validation);

            // Notify user and/or admins
            Registration::send_confirmation($username, $fullname, $email, $website, $pass1, $validation);
        }

        require_once AmpConfig::get('prefix') . UI::find_template('show_registration_confirmation.inc.php');
    break;
    case 'show_add_user':
    default:
        require_once AmpConfig::get('prefix') . UI::find_template('show_user_registration.inc.php');
    break;
} // end switch on action
