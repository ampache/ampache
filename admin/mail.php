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

require_once '../lib/init.php';

if (!Access::check('interface','75')) { 
	access_denied();
	exit();
}

show_header(); 

// Action switch
switch ($_REQUEST['action']) { 
	case 'send_mail':
		if (Config::get('demo_mode')) { 
			access_denied(); 
			exit;
		} 

		// Multi-byte Character Mail
		if(function_exists('mb_language')) {
			ini_set("mbstring.internal_encoding","UTF-8");
			mb_language("uni");
		}

		$clients = AmpacheMail::get_users($_REQUEST['to']); 

		foreach ($clients as $client) { 
			if(function_exists('mb_encode_mimeheader')) {
				$recipient .= mb_encode_mimeheader($client['fullname']) ." <" . $client['email'] . ">, ";
			} else {
				$recipient .= $client['fullname'] ." <" . $client['email'] . ">, ";
			}
		}
		
		// Remove the last , from the recipient
		$recipient = rtrim($recipient,", ");
		
		// Set the vars on the object
		AmpacheMail::$recipient = $recipient; 
		if(function_exists('mb_encode_mimeheader')) {
			$fullname = mb_encode_mimeheader($GLOBALS['user']->fullname);
		} else {
			$fullname = $GLOBALS['user']->fullname;
		}
		AmpacheMail::$to = $fullname . "<" . $GLOBALS['user']->email . ">";
		AmpacheMail::$from = $fullname . "<" . $GLOBALS['user']->email . ">";
		AmpacheMail::$subject = scrub_in($_REQUEST['subject']);
		if(function_exists('mb_eregi_replace')) {
			AmpacheMail::$message = mb_eregi_replace("\r\n", "\n", scrub_in($_REQUEST['message']));
		} else {
			AmpacheMail::$message = scrub_in($_REQUEST['message']);
		}
		AmpacheMail::$additional_header = array();
		AmpacheMail::$additional_header[] = 'X-Ampache-Mailer: 0.0.1';
		AmpacheMail::$additional_header[] = "From: " . AmpacheMail::$from;
		AmpacheMail::$additional_header[] = "Bcc: $recipient";
		if(function_exists('mb_send_mail')) {
			AmpacheMail::$additional_header[] = 'Content-Type: text/plain; charset=UTF-8';
			AmpacheMail::$additional_header[] = 'Content-Transfer-Encoding: 8bit';
		} else {
			AmpacheMail::$additional_header[] = 'Content-Type: text/plain; charset=us-ascii';
			AmpacheMail::$additional_header[] = 'Content-Transfer-Encoding: 7bit';
		}
		AmpacheMail::$sender = $GLOBALS['user']->email;

		AmpacheMail::send(); 	
        
		/* Confirmation Send */
		$url 	= Config::get('web_path') . '/admin/mail.php';
		$title 	= _('E-mail Sent'); 
		$body 	= _('Your E-mail was successfully sent.');
		show_confirmation($title,$body,$url);
	break;
	default: 
		require_once Config::get('prefix') . '/templates/show_mail_users.inc.php';
	break;
} // end switch

show_footer(); 


?>
