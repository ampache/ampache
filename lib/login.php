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

/* We have to create a cookie here because IIS
 * can't handle Cookie + Redirect
 */
Session::create_cookie();
Preference::init();

/**
 * If Access Control is turned on then we don't
 * even want them to be able to get to the login
 * page if they aren't in the ACL
 */
if (AmpConfig::get('access_control')) {
    if (!Access::check_network('interface', '', '5')) {
        debug_event('UI::access_denied', 'Access Denied:' . $_SERVER['REMOTE_ADDR'] . ' is not in the Interface Access list', '3');
        UI::access_denied();
        exit();
    }
} // access_control is enabled

/* Clean Auth values */
unset($auth);

if (empty($_REQUEST['step'])) {
    /* Check for posted username and password, or appropriate environment variable if using HTTP auth */
    if (($_POST['username']) ||
        (in_array('http', AmpConfig::get('auth_methods')) &&
        ($_SERVER['REMOTE_USER'] || $_SERVER['HTTP_REMOTE_USER']))) {

        /* If we are in demo mode let's force auth success */
        if (AmpConfig::get('demo_mode')) {
            $auth['success']        = true;
            $auth['info']['username']    = 'Admin - DEMO';
            $auth['info']['fullname']    = 'Administrative User';
            $auth['info']['offset_limit']    = 25;
        } else {
            if ($_POST['username']) {
                $username = scrub_in($_POST['username']);
                $password = $_POST['password'];
            } else {
                if ($_SERVER['REMOTE_USER']) {
                    $username = $_SERVER['REMOTE_USER'];
                } elseif ($_SERVER['HTTP_REMOTE_USER']) {
                    $username = $_SERVER['HTTP_REMOTE_USER'];
                } else {
                    $username = '';
                }
                $password = '';
            }

            $auth = Auth::login($username, $password, true);
            if ($auth['success']) {
                $username = $auth['username'];
            } elseif ($auth['ui_required']) {
                echo $auth['ui_required'];
                exit();
            } else {
                debug_event('Login', scrub_out($username) . ' From ' . $_SERVER['REMOTE_ADDR'] . ' attempted to login and failed', '1');
                Error::add('general', T_('Error Username or Password incorrect, please try again'));
            }
        }
    }
} elseif ($_REQUEST['step'] == '2') {
    $auth_mod = $_REQUEST['auth_mod'];
    $auth = Auth::login_step2($auth_mod);
    if ($auth['success']) {
        $username = $auth['username'];
    } else {
        debug_event('Login', 'Second step authentication failed', '1');
        Error::add('general', $auth['error']);
    }
}

if (!empty($username) && isset($auth)) {
    $user = User::get_from_username($username);

    if ($user->disabled) {
        $auth['success'] = false;
        Error::add('general', T_('User Disabled please contact Admin'));
        debug_event('Login', scrub_out($username) . ' is disabled and attempted to login', '1');
    } // if user disabled
    elseif (AmpConfig::get('prevent_multiple_logins')) {
        $session_ip = $user->is_logged_in();
        $current_ip = inet_pton($_SERVER['REMOTE_ADDR']);
        if ($current_ip && ($current_ip != $session_ip)) {
            $auth['success'] = false;
            Error::add('general', T_('User Already Logged in'));
            debug_event('Login', scrub_out($username) . ' is already logged in from ' . $session_ip . ' and attempted to login from ' . $current_ip, '1');
        } // if logged in multiple times
    } // if prevent multiple logins
    elseif (AmpConfig::get('auto_create') && $auth['success'] &&
        ! $user->username) {
        /* This is run if we want to autocreate users who don't
        exist (useful for non-mysql auth) */
        $access    = AmpConfig::get('auto_user')
            ? User::access_name_to_level(AmpConfig::get('auto_user'))
            : '5';
        $name    = $auth['name'];
        $email    = $auth['email'];
        $website    = $auth['website'];

        /* Attempt to create the user */
        if (User::create($username, $name, $email, $website,
            hash('sha256', mt_rand()), $access)) {
            $user = User::get_from_username($username);
        } else {
            $auth['success'] = false;
            Error::add('general', T_('Unable to create local account'));
        }
    } // End if auto_create

    // This allows stealing passwords validated by external means
    // such as LDAP
    if (AmpConfig::get('auth_password_save') && $auth['success'] && isset($password)) {
        $user->update_password($password);
    }
}

/* If the authentication was a success */
if (isset($auth) && $auth['success'] && isset($user)) {
    // $auth->info are the fields specified in the config file
    //   to retrieve for each user
    Session::create($auth);

    // Not sure if it was me or php tripping out,
    //   but naming this 'user' didn't work at all
    $_SESSION['userdata'] = $auth;

    // Record the IP of this person!
    if (AmpConfig::get('track_user_ip')) {
        $user->insert_ip_history();
    }

    if ($_POST['rememberme'] && isset($username)) {
        Session::create_remember_cookie($username);
    }

    // Update data from this auth if ours are empty
    if (empty($user->fullname) && !empty($auth['name'])) {
        $user->update_fullname($auth['name']);
    }
    if (empty($user->email) && !empty($auth['email'])) {
        $user->update_email($auth['email']);
    }
    if (empty($user->website) && !empty($auth['website'])) {
        $user->update_website($auth['website']);
    }

    $GLOBALS['user'] = $user;
    // If an admin, check for update
    if (AmpConfig::get('autoupdate') && Access::check('interface','100')) {
        AutoUpdate::is_update_available(true);
    }

    /* Make sure they are actually trying to get to this site and don't try
     * to redirect them back into an admin section
     */
    $web_path = AmpConfig::get('web_path');
    if ((substr($_POST['referrer'], 0, strlen($web_path)) == $web_path) &&
        strpos($_POST['referrer'], 'install.php')    === false &&
        strpos($_POST['referrer'], 'login.php')        === false &&
        strpos($_POST['referrer'], 'logout.php')    === false &&
        strpos($_POST['referrer'], 'update.php')    === false &&
        strpos($_POST['referrer'], 'activate.php')    === false &&
        strpos($_POST['referrer'], 'admin')        === false ) {

            header('Location: ' . $_POST['referrer']);
            exit();
    } // if we've got a referrer
    header('Location: ' . AmpConfig::get('web_path') . '/index.php');
    exit();
} // auth success
