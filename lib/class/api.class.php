<?php
/*

 Copyright (c) Ampache.org
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

	public static $version = '350001'; 
	
	/**
 	 * constructor
	 * This really isn't anything to do here, so it's private
	 */
	private function __construct() { 

		// Rien a faire

	} // constructor

	/**
	 * set_filter
	 * This is a play on the browse function, it's different as we expose the filters in a slightly different
	 * and vastly simplier way to the end users so we have to do a little handy work to make them
	 * work internally
	 */
	public static function set_filter($filter,$value) { 

		if (!strlen($value)) { return false; } 

		switch ($filter) { 
			case 'add': 
	                        // Check for a range, if no range default to gt
	                        if (strpos('/',$value)) {
	                                $elements = explode('/',$value);
	                                Browse::set_filter('add_lt',strtotime($elements['1']));
	                                Browse::set_filter('add_gt',strtotime($elements['0']));
	                        }
	                        else {
	                                Browse::set_filter('add_gt',strtotime($value));
	                        }
			break; 
			case 'update': 
				// Check for a range, if no range default to gt
				if (strpos('/',$value)) { 
					$elements = explode('/',$value); 
					Browse::set_filter('update_lt',strtotime($elements['1'])); 
					Browse::set_filter('update_gt',strtotime($elements['0'])); 		
				}
				else { 
					Browse::set_filter('update_gt',strtotime($value)); 
				}
			break; 
			case 'alpha_match':
				Browse::set_filter('alpha_match',$value); 
			break; 
			case 'exact_match': 
				Browse::set_filter('exact_match',$value);
			break; 
			default: 
				// Rien a faire
			break; 
		} // end filter

		return true; 

	} // set_filter

	/**
	 * handshake
	 * This is the function that handles the verifying a new handshake
	 * this takes a timestamp, auth key, and client IP. Optionally it
	 * can take a username, if non is passed the ACL must be non-use 
	 * specific
	 */
	public static function handshake($timestamp,$passphrase,$ip,$username='',$version) { 

		// Let them know we're attempting
		debug_event('API',"Attempting Handshake IP:$ip User:$username Version:$version",'5'); 

		if (intval($version) < self::$version) { 
			debug_event('API','Login Failed version too old','1'); 
			Error::add('api','Login Failed version too old'); 
			return false; 
		} 			

		// If the timestamp is over 2hr old sucks to be them
		if ($timestamp < (time() - 14400)) { 
			debug_event('API','Login Failed, timestamp too old','1'); 
			Error::add('api','Login Failed, timestamp too old'); 
			return false; 
		} 

		// First we'll filter by username and IP 
		if (!trim($username)) { 
			$user_id = '-1'; 
		} 
		else { 
			$client = User::get_from_username($username); 
			$user_id =$client->id; 
		} 

		// Clean incomming variables
		$user_id 	= Dba::escape($user_id); 
		$timestamp 	= intval($timestamp); 
		$ip 		= inet_pton($ip);

		// Log this attempt
		debug_event('API','Login Attempt, IP:' . inet_ntop($ip) . ' Time:' . $timestamp . ' User:' . $username . '(' . $user_id . ') Auth:' . $passphrase,'1'); 

		$ip = Dba::escape($ip); 
		
		// Run the query and return the passphrases as we'll have to mangle them
		// to figure out if they match what we've got
		$sql = "SELECT * FROM `access_list` " . 
			"WHERE `type`='rpc' AND (`user`='$user_id' OR `access_list`.`user`='-1') " . 
			"AND `start` <= '$ip' AND `end` >= '$ip'"; 
		$db_results = Dba::read($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 

			// Now we're sure that there is an ACL line that matches this user or ALL USERS, 
			// pull the users password and then see what we come out with 
			$sql = "SELECT * FROM `user` WHERE `id`='$user_id'"; 
			$user_results = Dba::read($sql); 

			$row = Dba::fetch_assoc($user_results); 

			if (!$row['password']) { 
				debug_event('API','Unable to find user with username of ' . $user_id,'1'); 
				Error::add('api','Invalid Username/Password'); 
				return false; 
			} 

			$sha1pass = hash('sha256',$timestamp . $row['password']); 

			if ($sha1pass === $passphrase) { 
				// Create the Session, in this class for now needs to be moved
				$data['username']	= $client->username; 
				$data['type']		= 'api'; 
				$data['value']		= $timestamp; 
				$token = vauth::session_create($data); 

				// Insert the token into the streamer
				Stream::insert_session($token,$client->id); 
				debug_event('API','Login Success, passphrase matched','1'); 

				// We need to also get the 'last update' of the catalog information in an RFC 2822 Format
				$sql = "SELECT MAX(`last_update`) AS `update`,MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`"; 
				$db_results = Dba::read($sql); 
				$row = Dba::fetch_assoc($db_results); 	 

				// Now we need to quickly get the totals of songs
				$sql = "SELECT COUNT(`id`) AS `song`,COUNT(DISTINCT(`album`)) AS `album`," . 
					"COUNT(DISTINCT(`artist`)) AS `artist` FROM `song`";
				$db_results = Dba::read($sql); 
				$counts = Dba::fetch_assoc($db_results); 

				// Next the video counts
				$sql = "SELECT COUNT(`id`) AS `video` FROM `video`"; 
				$db_results = Dba::read($sql); 
				$vcounts = Dba::fetch_assoc($db_results); 

				$sql = "SELECT COUNT(`id`) AS `playlist` FROM `playlist`"; 
				$db_results = Dba::read($sql);
				$playlist = Dba::fetch_assoc($db_results); 

				return array('auth'=>$token,
					'api'=>self::$version,
					'update'=>date("c",$row['update']),
					'add'=>date("c",$row['add']),
					'clean'=>date("c",$row['clean']),
					'songs'=>$counts['song'],
					'albums'=>$counts['album'],
					'artists'=>$counts['artist'],
					'playlists'=>$playlist['playlist'],
					'videos'=>$vcounts['video']); 
			} // match 

		} // end while

		debug_event('API','Login Failed, unable to match passphrase','1'); 
		Error::add('api','Invalid Username/Password'); 
		return false; 

	} // handhsake

} // API class
?>
