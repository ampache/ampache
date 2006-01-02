<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

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
$no_session = true;
require_once ("modules/init.php");

//Captcha

define ("CAPTCHA_INVERSE, 1");
include ("modules/captcha/captcha.php");
require ("modules/validatemail/validateEmailFormat.php");
require ("modules/validatemail/validateEmail.php");


/* Check Perms */
if (!conf('allow_public_registration')) {
	access_denied();
}


$action = scrub_in($_REQUEST['action']);

?>

<?php

/* Start switch based on action passed */
switch ($action) {
    case 'add_user':
    // User information has been entered
    // we need to check the database for possible existing username first
    // if username exists, error and say "Please choose a different name."
    // if username does not exist, insert user information into database
    // then allow the user to 'click here to login'
    // possibly by logging them in right then and there with their current info
    // and 'click here to login' would just be a link back to index.php
    if (conf('demo_mode')) { break; }
    $captcha = captcha::check(); 
    $accept_agreement = scrub_in($_REQUEST['accept_agreement']);
	$fullname = scrub_in($_REQUEST['fullname']);
	$username = scrub_in($_REQUEST['username']);
	$email = scrub_in($_REQUEST['email']);
	$pass1 = scrub_in($_REQUEST['password_1']);
	$pass2 = scrub_in($_REQUEST['password_2']);

	if(!isset ($captcha)){
		$GLOBALS['error']->add_error('captcha',_("Error Captcha Required"));
	}	
	if (isset ($captcha)){
		if ($captcha) {
			$msg="SUCCESS";
		}
    		else {
	    		$GLOBALS['error']->add_error('captcha',_("Error Captcha Failed"));
    		}
	}

	if(conf('user_agreement')==true){
		if(!$accept_agreement){
		$GLOBALS['error']->add_error('user_agreement',_("You <U>must</U> accept the user agreement"));
		}
	}

	if(!$username){
		$GLOBALS['error']->add_error('username',_("You did not enter a username"));
	}

	if(!$fullname){
		$GLOBALS['error']->add_error('fullname',_("Please fill in your full name (Firstname Lastname)"));
	}

//Check the mail for correct address formation.

    $attempt = 0;
    $max_attempts = 3;
    $response_code = "";

    while ( $response_code == "" || strstr( $response_code, "fsockopen error" )) {
        $validate_results = validateEmail( $email );

        $response_code = $validate_results[1];
        if($attempt == $max_attempts) break;
        $attempt++;
    }

    if ( $validate_results[0] ) {
		$mmsg = "MAILOK";
        }
        else {
                $GLOBALS['error']->add_error('email',_("Error Email address not confirmed<br>$validate_results[1]"));
        }
// End of mailcheck
	if(!$pass1){
		$GLOBALS['error']->add_error('password',_("You must enter a password"));
	}

	if ( $pass1 != $pass2 ) {
		$GLOBALS['error']->add_error('password',_("Your passwords do not match"));
	}

	if($GLOBALS['error']->error_state){
		show_user_registration($values);
		break;
	}

	$new_user = new_user("$username", "$fullname", "$email", "$pass1");
	if(!$new_user){
		$GLOBALS['error']->add_error('duplicate_user',_("That username already exists"));
	}
	if($GLOBALS['error']->error_state){
		show_user_registration($values);
		break;
	}

break;
    // This is the default action.
    case 'show_add_user':
    default:
        if (conf('demo_mode')) { break; }
	$values = array('type'=>"new_user");
	show_user_registration($values);
	break;
	case 'new_user':
	include("templates/show_new_user.inc");
	break;

}


?>
