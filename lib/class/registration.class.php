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

		$headers = "From: Ampache <" . Config::get('mail_from') . ">"; 
		$subject = "New User Registration at " . Config::get('site_title'); 
		$body = "Thank you for registering\n\n" . 
			"Please keep this e-mail for your records. Your account information is as follows:\n\n" . 
			"----------------------\n" . 
			"Username: $username\n" . 
			"Password: $password\n" . 
			"----------------------\n\n" . 
			"Your account is currently inactive. You cannot use it until you've visited the following link:\n\n" . 
			Config::get('web_path') . "/register.php?action=validate&username=$username&auth=$validation\n\n" . 
			"Thank you for registering\n"; 
		
		// Send the mail!	
		mail($email,$subject,$body,$headers); 	

		// Check to see if the admin should be notified
		if (Config::get('admin_notify_reg')) { 
			$body = "A new user has registered\n\n" . 
				"The following values were entered.\n\n" . 
				"Username:$username\nFullname:$fullname\nE-mail:$mail\n\n"; 
			mail(Config::get('mail_from'),$subject,$body,$headers); 
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
