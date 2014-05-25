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

define('NO_SESSION','1');
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
    require_once AmpConfig::get('prefix') . '/modules/captcha/captcha.php';
}


/* Start switch based on action passed */
switch ($_REQUEST['action']) {
    case 'validate':
        $username     = scrub_in($_GET['username']);
        $validation    = scrub_in($_GET['auth']);
        require_once AmpConfig::get('prefix') . '/templates/show_user_activate.inc.php';
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
        $fullname         = scrub_in($_POST['fullname']);
        $username        = scrub_in($_POST['username']);
        $email             = scrub_in($_POST['email']);
        $website             = scrub_in($_POST['website']);
        $pass1             = scrub_in($_POST['password_1']);
        $pass2             = scrub_in($_POST['password_2']);

        /* If we're using the captcha stuff */
        if (AmpConfig::get('captcha_public_reg')) {
                $captcha         = captcha::solved();
            if (!isset ($captcha)) {
                Error::add('captcha', T_('Error Captcha Required'));
            }
            if (isset ($captcha)) {
                if ($captcha) {
                    $msg="SUCCESS";
                } else {
                        Error::add('captcha', T_('Error Captcha Failed'));
                    }
            } // end if we've got captcha
        } // end if it's enabled

        if (AmpConfig::get('user_agreement')) {
            if (!$_POST['accept_agreement']) {
                Error::add('user_agreement', T_("You <U>must</U> accept the user agreement"));
            }
        } // if they have to agree to something

        if (!$_POST['username']) {
            Error::add('username', T_("You did not enter a username"));
        }

        if (!$fullname) {
            Error::add('fullname', T_("Please fill in your full name (Firstname Lastname)"));
        }

        // Check the mail for correct address formation.
        if (!Mailer::validate_address($email)) {
            Error::add('email', T_('Invalid email address'));
        }

        if (!$pass1) {
            Error::add('password', T_("You must enter a password"));
        }

        if ($pass1 != $pass2) {
            Error::add('password', T_("Your passwords do not match"));
        }

        if (!User::check_username($username)) {
            Error::add('duplicate_user', T_("Error Username already exists"));
        }

        // If we've hit an error anywhere up there break!
        if (Error::occurred()) {
            require_once AmpConfig::get('prefix') . '/templates/show_user_registration.inc.php';
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
            $access, AmpConfig::get('admin_enable_required'));

        if (!$new_user) {
            Error::add('duplicate_user', T_("Error: Insert Failed"));
            require_once AmpConfig::get('prefix') . '/templates/show_user_registration.inc.php';
            break;
        }

        if (!AmpConfig::get('admin_enable_required') && !AmpConfig::get('user_no_email_confirm')) {
            $client = new User($new_user);
            $validation = md5(uniqid(rand(), true));
            $client->update_validation($validation);

            Registration::send_confirmation($username, $fullname, $email, $website, $pass1, $validation);
        }
        require_once AmpConfig::get('prefix') . '/templates/show_registration_confirmation.inc.php';
    break;
    case 'show_add_user':
    default:
        require_once AmpConfig::get('prefix') . '/templates/show_user_registration.inc.php';
    break;
} // end switch on action
