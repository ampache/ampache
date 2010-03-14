<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
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

$action = (isset($_POST['action'])) ? $_POST['action'] : "";

switch ($action) {
	case 'send':
		/* Check for posted email */
		$result = false;
		if (isset($_POST['email']) && $_POST['email']) {
			/* Get the email address and the current ip*/
			$email = scrub_in($_POST['email']);
			$current_ip =(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] :$_SERVER['REMOTE_ADDR'];
			$result = send_newpassword($email, $current_ip);
		}
		if ($result) {
			Error::add('general',_('Password has been send'));
		} else {
			Error::add('general',_('Password has not been send'));
		}

		require Config::get('prefix') . '/templates/show_login_form.inc.php';
		break;
	default:
		require Config::get('prefix') . '/templates/show_lostpassword_form.inc.php';
}

function send_newpassword($email,$current_ip){
	/* get the Client and set the new password */
	$client = User::get_from_email($email);
	if ($client->email == $email) {
		$newpassword = generate_password(6);
		$client->update_password($newpassword);

		AmpacheMail::$subject = _("Lost Password");
		AmpacheMail::$fullname = $client->fullname;
		AmpacheMail::$to = $client->email;
		AmpacheMail::$fromname = "Ampache";
		AmpacheMail::$sender = $GLOBALS['user']->email;

		$message  = sprintf(_("An user from %s has requested an new password."), $current_ip);
		$message .= "\n";
		$message .= sprintf(_("The password has been set to: %s"), $newpassword);
		AmpacheMail::$message = $message;

		return AmpacheMail::send();
	}
	return false;
}
?>
