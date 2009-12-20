<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

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

/* Check Perms */
if (!Config::get('allow_public_registration') || Config::get('demo_mode')) {
	debug_event('DENIED','Error Attempted registration','1');
	access_denied();
	exit(); 
}

/**
 * These are only needed for this page so they aren't included in init.php 
 * this is for email validation and the cool little graphic
*/
require_once Config::get('prefix') . '/modules/validatemail/validateEmailFormat.php';
require_once Config::get('prefix') . '/modules/validatemail/validateEmail.php';

/* Don't even include it if we aren't going to use it */
if (Config::get('captcha_public_reg')) { 
	define ("CAPTCHA_INVERSE", 1);
	include Config::get('prefix') . '/modules/captcha/captcha.php';
}


/* Start switch based on action passed */
switch ($_REQUEST['action']) {
	case 'validate': 
		$username 	= scrub_in($_GET['username']); 
		$validation	= scrub_in($_GET['auth']); 
		require_once Config::get('prefix') . '/templates/show_user_activate.inc.php'; 
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
		$fullname 		= scrub_in($_POST['fullname']);
		$username		= scrub_in($_POST['username']);
		$email 			= scrub_in($_POST['email']);
		$pass1 			= scrub_in($_POST['password_1']);
		$pass2 			= scrub_in($_POST['password_2']);

		/* If we're using the captcha stuff */
		if (Config::get('captcha_public_reg')) { 
		    	$captcha 		= captcha::solved(); 
			if(!isset ($captcha)) {
				Error::add('captcha',_('Error Captcha Required'));
			}	
			if (isset ($captcha)) {
				if ($captcha) {
					$msg="SUCCESS";
				}
		    		else {
			    		Error::add('captcha',_('Error Captcha Failed'));
		    		}
			} // end if we've got captcha
		} // end if it's enabled

		if (Config::get('user_agreement')) {
			if (!$_POST['accept_agreement']) {
				Error::add('user_agreement',_("You <U>must</U> accept the user agreement"));
			} 
		} // if they have to agree to something

		if (!$_POST['username']) {
			Error::add('username',_("You did not enter a username"));
		}

		if(!$fullname) {
			Error::add('fullname',_("Please fill in your full name (Firstname Lastname)"));
		}

		/* Check the mail for correct address formation. */
		$attempt = 0;
		$max_attempts = 3;
		$response_code = "";

		while ( $response_code == "" || strstr( $response_code, "fsockopen error" )) {
			$validate_results = validateEmail( $email );
			$response_code = $validate_results[1];
			if($attempt == $max_attempts) {
				break;
			}
			$attempt++;
		}

		if ($validate_results[0] OR strstr($validate_results[1],"greylist")) {
			$mmsg = "MAILOK";
		}
	        else {
	                Error::add('email',_("Error Email address not confirmed<br />$validate_results[1]"));
	        }
		/* End of mailcheck */
	
		if (!$pass1) {
			Error::add('password',_("You must enter a password"));
		}

		if ( $pass1 != $pass2 ) {
			Error::add('password',_("Your passwords do not match"));
		}

		if (!User::check_username($username)) { 
			Error::add('duplicate_user',_("Error Username already exists"));
		}

		// If we've hit an error anywhere up there break!
		if (Error::occurred()) {
			require_once Config::get('prefix') . '/templates/show_user_registration.inc.php';
			break;
		}

		/* Attempt to create the new user */
		$access = '5';
		switch (Config::get('auto_user')) { 
			case 'admin': 
				$access = '100'; 
			break;
			case 'user': 
				$access = '25'; 
			break;
			default: 
			case 'guest': 
				$access = '5'; 
			break;
		} // auto-user level

			
		$new_user = User::create($username,$fullname,$email,$pass1,$access);

		if (!$new_user) {
			Error::add('duplicate_user',_("Error: Insert Failed"));
			require_once Config::get('prefix') . '/templates/show_user_registration.inc.php';
			break;
		}

		$client = new User($new_user);
		$validation = md5(uniqid(rand(), true));
		$client->update_validation($validation);

		Registration::send_confirmation($username, $fullname, $email, $pass1, $validation);
		require_once Config::get('prefix') . '/templates/show_registration_confirmation.inc.php'; 
	break;
	case 'show_add_user':
	default:
		require_once Config::get('prefix') . '/templates/show_user_registration.inc.php'; 
	break;
} // end switch on action
?>
