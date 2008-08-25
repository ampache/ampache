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

class AmpacheMail {

	// The message, recipient and from
	public static $message; 
	public static $recipient; 
	public static $from; 
	public static $subject; 
	public static $to;
	public static $additional_header;
	public static $sender;

	/**
	 * Constructor
	 * This isn't used
	 */
	private function __construct($name) { 

		// Rien a faire

	} // Constructor

	/**
 	 * get_users
	 * This returns an array of userid's for people who have e-mail addresses
	 * based on the passed filter
	 */
	public static function get_users($filter) { 

		switch ($filter) { 
			default: 
			case 'all': 
				$sql = "SELECT * FROM `user` WHERE `email` IS NOT NULL"; 
			break;
			case 'users': 
				$sql = "SELECT * FROM `user` WHERE `access`='25' AND `email` IS NOT NULL"; 
			break;
			case 'admins': 
				$sql = "SELECT * FROM `user` WHERE `access`='100' AND `email` IS NOT NULL"; 
			break ;
			case 'inactive': 
				$inactive = time() - (30*86400);
				$sql = "SELECT * FROM `user` WHERE `last_seen` <= '$inactive' AND `email` IS NOT NULL"; 
			break; 
		} // end filter switch
			
		$db_results = Dba::query($sql); 
		
		$results = array(); 
		
		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = array('id'=>$row['id'],'fullname'=>$row['fullname'],'email'=>$row['email']); 
		} 

		return $results;  

	} // get_users

	/**
	 * add_statistics
	 * This should be run if we want to add some statistics to this e-mail, appends to self::$message
	 */
	public static function add_statistics($methods) { 

		

	} // add_statistics

	/**
	 * send
	 * This actually sends the mail, how amazing
	 */
	public static function send() { 

		// Multi-byte Character Mail
		if(function_exists('mb_send_mail')) {
			mb_send_mail(self::$to,
				     self::$subject,
				     self::$message,
				     implode("\n", self::$additional_header),
				     '-f'.self::$sender);
		} else {
			mail(self::$to,
			     self::$subject,
			     self::$message,
			     implode("\r\n", $additional_header),
			     '-f'.self::$sender);
		}

		return true; 

	} // send

} // AmpacheMail class
?>
