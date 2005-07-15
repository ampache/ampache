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
$no_session = 1;
require_once ("modules/init.php");

/* Check Perms */
if (!conf('allow_public_registration')) { 
	access_denied();
}


$action = scrub_in($_REQUEST['action']);


show_template('header');

/* Start switch based on action passed */ 
switch ($action) {
    case 'add_user':
        if (conf('demo_mode')) { break; }
	$username = scrub_in($_REQUEST['username']);
	$fullname = scrub_in($_REQUEST['fullname']);
	$email = scrub_in($_REQUEST['email']);
	$pass1 = scrub_in($_REQUEST['password_1']);
	$pass2 = scrub_in($_REQUEST['password_2']);
	if ( $pass1 != $pass2 ) {
		echo "<CENTER><B>Your passwords do not match</b><br />";
		echo "Click <B><a href=\"javascript:history.back(1)\">here</a></B> to go back";
		break;
	}
// INSERTED BY TERRY FOR MAIL ADDRESS CHECK
    // require("../templates/validateEmailFormat.php");
    // require("../templates/validateEmail.php");
    // get the address from wherever you get it ... form input, etc.
    // $email = "info@xs4all.nl";
    // $email = $_GET['email'];
    // try a few extra times if we're concerned about fsockopen problems
    $attempt = 0;
    $max_attempts = 3;
    $response_code = "";

    while ( $response_code == "" || strstr( $response_code, "fsockopen error" )) {
        $validate_results = validateEmail( $email );

        $response_code = $validate_results[1];
        if($attempt == $max_attempts) break;
        $attempt++;
    }

    // display results
    //echo "successful check during attempt #$attempt<br />";
    if ( $validate_results[0] ) {
    $validation = str_rand(20);
    $regdate = "2004-01-01";
	if (!$user->create($username, $fullname, $email, $pass1, $access, $validation)) {
		echo "<CENTER>Registratie van gebruiker gefaald!<br />";
		echo "User ID of Email adres reeds in gebruik<br />";
		echo "Click <B><a href=\"javascript:history.back(1)\">here</a></B> to go back";
	break;
	}
	echo "<CENTER>Successvol gechecked na #$attempt poging<br />";
        echo "Email verificatie van het email adres <B>$email</B> is gelukt<br />";
	echo "<B>Your User ID is Created<br />";
	echo "<P>You will receive an email when your account is approved<B><br />";   
	echo "<A HREF=\"http://www.pb1unx.com\">Ga naar homepagina</A><br />";
        } else {
        echo "<CENTER>Geen successvolle check. Er is/zijn #$attempt pogingen gedaan!<br />";
	echo "D'oh! <B>$email</B> is niet in orde!<br />";
        echo "$validate_results[1]<br />";
	echo "Click <B><a href=\"javascript:history.back(1)\">here</a></B> to go back";
	}
	break;


    case 'show_add_user':
    default:
        if (conf('demo_mode')) { break; }
	$values = array('type'=>"new_user");
	show_user_registration($values);
	break;

}

echo "<br /><br />";

?>

</body>
</html>
