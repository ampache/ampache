<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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
    $accept_agreement = scrub_in($_REQUEST['accept_agreement']);
	$fullname = scrub_in($_REQUEST['fullname']);
	$username = scrub_in($_REQUEST['username']);
	$email = scrub_in($_REQUEST['email']);
	$pass1 = scrub_in($_REQUEST['password_1']);
	$pass2 = scrub_in($_REQUEST['password_2']);
	if(conf('user_agreement')==true){
		if(!$accept_agreement){
			echo("<center><b>You <u>must</u> accept the user agreement</b><br>");
			echo("Click <b><a href=\"javascript:history.back(1)\">here</a></b> to go back");
			break;
		}
	}

	if(!$username){
		echo("<center><b>You did not enter a username</b><br>");
		echo("Click <b><a href=\"javascript:history.back(1)\">here</a></b> to go back");
		break;
	}

	if(!$fullname){
		echo("<center><b>Please enter your full name</b><br>");
		echo("Click <b><a href=\"javascript:history.back(1)\">here</a></b> to go back");
		break;
	}

	if(!good_email($email)){
		echo("<center><b>You must enter a valid email address</b><br>");
		echo("Click <b><a href=\"javascript:history.back(1)\">here</a></b> to go back");
		break;
	}

	if(!$pass1){
		echo("<center><b>You must enter a password</b><br>");
		echo("Click <b><a href=\"javascript:history.back(1)\">here</a></b> to go back");
		break;
	}

	if ( $pass1 != $pass2 ) {
		echo("<center><b>Your passwords do not match</b><br>");
		echo("Click <b><a href=\"javascript:history.back(1)\">here</a></b> to go back");
		break;
	}
	$new_user = new_user("$username", "$fullname", "$email", "$pass1");
	if(!$new_user){
		echo("<center><b>That username already exists</b><br>");
		echo("Click <b><a href=\"javascript:history.back(1)\">here</a></b> to go back");
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
