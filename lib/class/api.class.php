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
 * API Class
 * This handles functions relating to the API written for ampache, initially this is very focused
 * on providing functionality for Amarok so it can integrate with Ampache
 */
class Api { 

	public static $version = '340001'; 
	
	/**
 	 * constructor
	 * This really isn't anything to do here, so it's private
	 */
	private function __construct() { 

		// Rien a faire

	} // constructor

	/**
	 * handshake
	 * This is the function that handles the verifying a new handshake
	 * this takes a timestamp, auth key, and client IP. Optionally it
	 * can take a username, if non is passed the ACL must be non-use 
	 * specific
	 */
	public static function handshake($timestamp,$passphrase,$ip,$username='') { 

		// If the timestamp is over 2hr old sucks to be them
//		if ($timestamp < (time() - 7200)) { 
//			return 'Timestamp too old, try again'; 
//		} 

		// First we'll filter by username and IP 
		if (!$username) { 
			$user_id = '-1'; 
		} 
		else { 
			$client = User::get_from_username($username); 
			$user_id =$client->id; 
		} 

		// Clean incomming variables
		$user_id 	= Dba::escape($user_id); 
		$timestamp 	= intval($timestamp); 
		$ip 		= ip2int($ip); 

		// Log this attempt
		debug_event('API','Login Attempt, IP:' . int2ip($ip) . ' Time:' . $timestamp . ' User:' . $user_id . ' Auth:' . $passphrase,'1'); 
		
		// Run the query and return the passphrases as we'll have to mangle them
		// to figure out if they match what we've got
		$sql = "SELECT * FROM `access_list` WHERE `user`='$user_id' AND `start` <= '$ip' AND `end` >= '$ip'"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 

			// Combine and MD5 this mofo
			$md5pass = md5($timestamp . $row['key']); 

			if ($md5pass === $passphrase) { 
				// Create the Session, in this class for now needs to be moved
				$token = self::create_session($row['level'],$ip,$user_id); 
				debug_event('API','Login Success, passphrase matched','1'); 

				return array('auth'=>$token,'api'=>self::$version); 
			} // match 

		} // end while

		debug_event('API','Login Failed, unable to match passphrase','1'); 

	} // handhsake

	/**
	 * create_session
	 * This actually creates the new session it takes the level, ip and user
	 * and figures out the agent and expire then returns the token
	 */
	public static function create_session($level,$ip,$user_id) { 

		// Generate the token 
		$token = md5(uniqid(rand(), true));
		$level = Dba::escape($level); 
		$agent = Dba::escape($_SERVER['HTTP_USER_AGENT']); 
		$expire = time() + 3600; 

		$sql = "REPLACE INTO `session_api` (`id`,`user`,`agent`,`level`,`expire`,`ip`) " . 
			"VALUES ('$token','$user_id','$agent','$level','$expire','$ip')"; 
		$db_results = Dba::query($sql); 

		if ($db_results) { 
			return $token; 
		} 

		return false; 

	} // create_session

} // API class
?>
