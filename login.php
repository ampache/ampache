<?php
/*

 Copyright (c) Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

define('NO_SESSION','1');
require_once 'lib/init.php';

/* We have to create a cookie here because IIS
 * can't handle Cookie + Redirect 
 */
vauth::create_cookie(); 
Preference::init();

/**
 * If Access Control is turned on then we don't
 * even want them to be able to get to the login 
 * page if they aren't in the ACL
 */
if (Config::get('access_control')) { 
        if (!Access::check_network('interface','','5')) {
                debug_event('access_denied','Access Denied:' . $_SERVER['REMOTE_ADDR'] . ' is not in the Interface Access list','3');
                access_denied();
		exit(); 
        }
} // access_control is enabled

/* Clean Auth values */
unset($auth);

/* Check for posted username and password */
if ($_POST['username'] && $_POST['password']) {

        if ($_POST['rememberme']) {
		vauth::create_remember_cookie(); 
        } 

	/* If we are in demo mode let's force auth success */
	if (Config::get('demo_mode')) {
		$auth['success'] = 1;
		$auth['info']['username'] = "Admin - DEMO";
		$auth['info']['fullname'] = "Administrative User";
		$auth['info']['offset_limit']	= 25;
	}
	else {
		$username = scrub_in($_POST['username']);
		$password = scrub_in($_POST['password']);
		$auth = vauth::authenticate($username, $password);
                $user = User::get_from_username($username);
		
		if (!$auth['success']) { 
			debug_event('Login',scrub_out($username) . ' attempted to login and failed','1'); 
		} 
	
		if ($user->disabled == '1') { 	
                	$auth['success'] = false;
			Error::add('general',_('User Disabled please contact Admin')); 
			debug_event('Login',scrub_out($username) . ' is disabled and attempted to login','1'); 
                } // if user disabled
			
                
		elseif (!$user->username AND $auth['success']) { 
			/* This is run if we want to auto_create users who don't exist (usefull for non mysql auth) */                
			if (Config::get('auto_create')) {

				$access = Config::get('auto_user') ? User::access_name_to_level(Config::get('auto_user')) : '5'; 
                        	$name = $auth['name'];
                        	$email = $auth['email'];
                        
				/* Attempt to create the user */	
				if (!$user->create($username, $name, $email,hash('sha256',mt_rand()), $access)) {
                                	$auth['success'] = false;
					Error::add('general',_('Unable to create new account')); 
                            	}
				else { 
                        		$user = new User($username);
				}
                        } // End if auto_create

                        else {
                            $auth['success'] = false;
			    Error::add('general',_('No local account found')); 
                        }
                } // else user isn't disabled

	} // if we aren't in demo mode

} // if they passed a username/password

/* If the authentication was a success */
if ($auth['success']) {

	// Generate the user we need for a few things
	$user = User::get_from_username($username);

	if (Config::get('prevent_multiple_logins')) {
		$current_ip = $user->is_logged_in();
		if ($current_ip AND $current_ip != inet_pton($_SERVER['REMOTE_ADDR'])) {
			Error::add('general',_('User Already Logged in'));
			require Config::get('prefix') . '/templates/show_login_form.inc.php';
			exit; 
        	}
	} // if prevent_multiple_logins

	// $auth->info are the fields specified in the config file
	//   to retrieve for each user
	vauth::session_create($auth);
	
	//
	// Not sure if it was me or php tripping out,
	//   but naming this 'user' didn't work at all
	//
	$_SESSION['userdata'] = $auth;

	// 
	// Record the IP of this person!
	// 
	if (Config::get('track_user_ip')) { 
		$user->insert_ip_history();	
	}

	/* Make sure they are actually trying to get to this site and don't try to redirect them back into 
	 * an admin section
	**/
	if (substr($_POST['referrer'],0,strlen(Config::get('web_path'))) == Config::get('web_path') AND 
		!strstr($_POST['referrer'],"install.php") AND 
		!strstr($_POST['referrer'],"login.php") AND 
		!strstr($_POST['referrer'],'logout.php') AND
		!strstr($_POST['referrer'],"update.php") AND
		!strstr($_POST['referrer'],"activate.php") AND
		!strstr($_POST['referrer'],"admin")) { 
		
			header("Location: " . $_POST['referrer']);
			exit();
	} // if we've got a referrer
	header("Location: " . Config::get('web_path') . "/index.php");
	exit();
} // auth success

require Config::get('prefix') . '/templates/show_login_form.inc.php';

?>
