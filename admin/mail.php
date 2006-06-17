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

require('../lib/init.php');

if (!$GLOBALS['user']->has_access(100)) {
	access_denied();
	exit();
}


$action = scrub_in($_POST['action']);
$to = scrub_in($_REQUEST['to']);
$subject = stripslashes(scrub_in($_POST['subject']));
$message = stripslashes(scrub_in($_POST['message']));

/* Always show the header */
show_template('header');

switch ($action) { 
	case 'send_mail':
		if (conf('demo_mode')) { break; } 

		// do the mail mojo here
		if ( $to == 'all' ) {
			$sql = "SELECT * FROM user WHERE email IS NOT NULL";
		}
		elseif ( $to == 'users' ) {
			$sql = "SELECT * FROM user WHERE access='users' OR access='25' AND email IS NOT NULL";
		}
		elseif ( $to == 'admins' ) {
			$sql = "SELECT * FROM user WHERE access='admin' OR access='100' AND email IS NOT NULL";
		}
  
		$db_result = mysql_query($sql, dbh());
  
		$recipient = '';

		while ( $u = mysql_fetch_object($db_result) ) {
			$recipient .= "$u->fullname <$u->email>, ";
		}

		// Remove the last , from the recipient
		$recipient = rtrim($recipient,",");

		$from    = $user->fullname."<".$user->email.">";

		// woohoo!!
		mail ($from, $subject, $message,
			"From: $from\r\n".
			"Bcc: $recipient\r\n");

		/* Confirmation Send */
		$url 	= conf('web_path') . '/admin/mail.php';
		$title 	= _('E-mail Sent'); 
		$body 	= _('Your E-mail was successfully sent.');
		show_confirmation($title,$body,$url);
	break;
	default: 
		if ( empty($to) ) {
			$to = 'all';
		}

		if ( empty($subject) ) {
			$subject = "[" . conf('site_title') . "] ";
		}
		require (conf('prefix') . '/templates/show_mail_users.inc.php');
	break;
} // end switch

show_footer(); 


?>
