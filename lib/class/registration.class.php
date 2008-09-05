<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

/**
 * Registration
 * This class handles all the doodlys for the registration
 * stuff in ampache
 */
class Registration {

	/**
	 * constructor
	 * This is what is called when the class is loaded
	 */
	public function __construct() { 

		// Rien a faire

	} // constructor

	/**
 	 * send_confirmation
	 * This sends the confirmation e-mail for the specified user
	 */
	public static function send_confirmation($username,$fullname,$email,$password,$validation) { 

		// Multi-byte Character Mail
		if(function_exists('mb_language')) {
			ini_set("mbstring.internal_encoding","UTF-8");
			mb_language("uni");
		}

		if(function_exists('mb_encode_mimeheader')) {
			$from = mb_encode_mimeheader(_("From: Ampache "));
		} else {
			$from = _("From: Ampache ");
		}
		$subject = sprintf(_("New User Registration at %s"), Config::get('site_title'));

		$additional_header = array();
		$additional_header[] = 'X-Ampache-Mailer: 0.0.1';
		$additional_header[] = $from . "<" .Config::get('mail_from') . ">";
		if(function_exists('mb_send_mail')) {
			$additional_header[] = 'Content-Type: text/plain; charset=UTF-8';
			$additional_header[] = 'Content-Transfer-Encoding: 8bit';
		} else {
			$additional_header[] = 'Content-Type: text/plain; charset=us-ascii';
			$additional_header[] = 'Content-Transfer-Encoding: 7bit';
		}

		$body = sprintf(_("Thank you for registering\n\n
Please keep this e-mail for your records. Your account information is as follows:
----------------------
Username: %s
Password: %s
----------------------

Your account is currently inactive. You cannot use it until you've visited the following link:

%s 

Thank you for registering
"), $username, $password, Config::get('web_path') . "/register.php?action=validate&username=$username&auth=$validation");

		if(function_exists('mb_eregi_replace')) {
			$body = mb_eregi_replace("\r\n", "\n", $body);
		}
		// Send the mail!
		if(function_exists('mb_send_mail')) {
			mb_send_mail ($email,
					$subject,
					$body,
					implode("\n", $additional_header),
					'-f'.Config::get('mail_from'));
		} else {
			mail($email,$subject,$body,implode("\r\n", $additional_header),'-f'.Config::get('mail_from'));
		}

		// Check to see if the admin should be notified
		if (Config::get('admin_notify_reg')) { 
			$body = sprintf(_("A new user has registered
The following values were entered.

Username: %s
Fullname: %s
E-mail: %s

"), $username, $fullname, $email);

			if(function_exists('mb_send_mail')) {
				mb_send_mail (Config::get('mail_from'),
						$subject,
						$body,
						implode("\n", $additional_header),
						'-f'.Config::get('mail_from'));
			} else {
				mail(Config::get('mail_from'),$subject,$body,implode("\r\n", $additional_header),'-f'.Config::get('mail_from')); 
			}
		} 
				
		return true; 

	} // send_confirmation

	/**
 	 * show_agreement
	 * This shows the registration agreement, /config/registration_agreement.php
	 */
	public static function show_agreement() { 

	        $filename = Config::get('prefix') . '/config/registration_agreement.php';

	        if (!file_exists($filename)) { return false; } 

	        /* Check for existance */
	        $fp = fopen($filename,'r');

	        if (!$fp) { return false; }

	        $data = fread($fp,filesize($filename));

	        /* Scrub and show */
	        echo $data;

	} // show_agreement

} // end registration class
?>
