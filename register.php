<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

/*!
	@header User Registration page
	@discussion this page handles new user
		registration, this is by default disabled
		(it allows public reg)

*/

define('NO_SESSION','1');
require_once ('lib/init.php');

/* Load the preferences */
init_preferences();

$web_path = conf('web_path');

/* Check Perms */
if (!conf('allow_public_registration') || conf('demo_mode')) {
	access_denied();
}

/**
 * These are only needed for this page so they aren't included in init.php 
 * this is for email validation and the cool little graphic
*/
require_once (conf('prefix') . '/modules/validatemail/validateEmailFormat.php');
require_once (conf('prefix') . '/modules/validatemail/validateEmail.php');

/* Don't even include it if we aren't going to use it */
if (conf('captcha_public_reg')) { 
	define ("CAPTCHA_INVERSE, 1");
	require_once (conf('prefix') . '/modules/captcha/captcha.php');
}


$action = scrub_in($_REQUEST['action']);

/* Start switch based on action passed */
switch ($action) {
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
		$accept_agreement 	= scrub_in($_REQUEST['accept_agreement']);
		$fullname 		= scrub_in($_REQUEST['fullname']);
		$username		= scrub_in($_REQUEST['username']);
		$email 			= scrub_in($_REQUEST['email']);
		$pass1 			= scrub_in($_REQUEST['password_1']);
		$pass2 			= scrub_in($_REQUEST['password_2']);

		/* If we're using the captcha stuff */
		if (conf('captcha_public_reg')) { 
		    	$captcha 		= captcha::check(); 
			if(!isset ($captcha)) {
				$GLOBALS['error']->add_error('captcha',_('Error Captcha Required'));
			}	
			if (isset ($captcha)) {
				if ($captcha) {
					$msg="SUCCESS";
				}
		    		else {
			    		$GLOBALS['error']->add_error('captcha',_('Error Captcha Failed'));
		    		}
			} // end if we've got captcha
		} // end if it's enabled

		if(conf('user_agreement')) {
			if(!$accept_agreement) {
				$GLOBALS['error']->add_error('user_agreement',_("You <U>must</U> accept the user agreement"));
			} 
		} // if they have to agree to something

		if(!$username) {
			$GLOBALS['error']->add_error('username',_("You did not enter a username"));
		}

		if(!$fullname) {
			$GLOBALS['error']->add_error('fullname',_("Please fill in your full name (Firstname Lastname)"));
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

		if ($validate_results[0]) {
			$mmsg = "MAILOK";
		}
	        else {
	                $GLOBALS['error']->add_error('email',_("Error Email address not confirmed<br>$validate_results[1]"));
	        }
		/* End of mailcheck */
	
		if(!$pass1){
			$GLOBALS['error']->add_error('password',_("You must enter a password"));
		}

		if ( $pass1 != $pass2 ) {
			$GLOBALS['error']->add_error('password',_("Your passwords do not match"));
		}

		if (!check_username($username)) { 
			$GLOBALS['error']->add_error('duplicate_user',_("Error Username already exists"));
		}

		if($GLOBALS['error']->error_state){
			show_user_registration($values);
			break;
		}

		/* Attempt to create the new user */
		$access = '5';
		if (conf('auto_user')) { 
		    if (conf('auto_user') == "guest"){$access = "5";}
		    elseif (conf('auto_user') == "user"){$access = "25";}
		    elseif (conf('auto_user') == "admin"){$access = "100";}
		}	
		$new_user = $GLOBALS['user']->create($username,$fullname,$email,$pass1,$access);

		if (!$new_user) {
			$GLOBALS['error']->add_error('duplicate_user',_("Error: Insert Failed"));
			show_user_registration($values);
			break;
		}

		$user_object = new User($new_user);
		$validation = str_rand(20);
		$user_object->update_validation($validation);

		$message = 'Your account has been created. However, this application requires account activation.' .
				' An activation key has been sent to the e-mail address you provided. ' .
				'Please check your e-mail for further information';

		send_confirmation($username, $fullname, $email, $pass1, $validation);
		?>
		<link rel="stylesheet" href="<?php echo $web_path; ?><?php echo conf('theme_path'); ?>/templates/default.css" type="text/css" />
		<?php
		show_confirmation(_('Registration Complete'),$message,'/login.php');	
	break;
	case 'show_add_user':
	default:
		$values = array('type'=>"new_user");
		show_user_registration($values);
	break;
} // end switch on action
?>
