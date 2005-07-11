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
	@header User Object
	View object that is thrown into their session

*/


class User {

	//Basic Componets
	var $username;
	var $fullname;
	var $access;
	var $disabled;
	var $offset_limit=25;
	var $email;
	var $last_seen;
	
	function User($username=0) {

		if (!$username) { 
			return true;
		}

		$this->username 	= $username;
		$info 			= $this->get_info();
		$this->username 	= $info->username;
		$this->fullname 	= $info->fullname;
		$this->access 		= $info->access;
		$this->disabled		= $info->disabled;
		$this->offset_limit 	= $info->offset_limit;
		$this->email		= $info->email;
		$this->last_seen	= $info->last_seen;
		$this->set_preferences();

		// Make sure the Full name is always filled
		if (strlen($this->fullname) < 1) { $this->fullname = $this->username; }

	} // User


	/*! 
		@function get_info
		@dicussion gets the info!
	*/
	function get_info() {

		$sql = "SELECT * FROM user WHERE username='$this->username'";
		
		$db_results = mysql_query($sql, dbh());

		return mysql_fetch_object($db_results);

	} // get_info

	/*!
		@function get_preferences
		@discussion gets the prefs for this specific
			user and returns them as an array
	*/
	function get_preferences($user_id=0) { 
		
		if (!$user_id) { 
			$user_id = $this->username;
		}
		
		$sql = "SELECT preferences.name, preferences.description, preferences.type, user_preference.value FROM preferences,user_preference " .
			"WHERE user_preference.user='$user_id' AND user_preference.preference=preferences.id AND preferences.type='user'";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_object($db_results)) { 
			$results[] = $r;
		}

		return $results;
	
	} // get_preferences

	/*!
		@function set_preferences
		@discussion sets the prefs for this specific 
			user
	*/
	function set_preferences() {

		$sql = "SELECT preferences.name,user_preference.value FROM preferences,user_preference WHERE user_preference.user='$this->username' " .
			"AND user_preference.preference=preferences.id AND preferences.type='user'";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_object($db_results)) {
			$this->prefs[$r->name] = $r->value;
		} 
	} // get_preferences

	/*!
		@function get_favorites
		@discussion returns an array of your $type
			favorites
	*/
	function get_favorites($type) { 

	        $sql = "SELECT * FROM object_count" .
	                " WHERE count > 0" .
	                " AND object_type = '$type'" .
	                " AND userid = '" . $this->username . "'" .
	                " ORDER BY count DESC LIMIT " . conf('popular_threshold');
	        $db_result = mysql_query($sql, dbh());

		$items = array();
	        $web_path = conf('web_path');

        	while ($r = @mysql_fetch_object($db_result) ) {
			/* If its a song */
			if ($type == 'song') { 
				$data = new Song($r->object_id);
				$data->count = $r->count;
				$data->format_song();
				$data->f_name = $data->f_link;
				$items[] = $data;
			}
			/* If its an album */
			elseif ($type == 'album') { 
				$data = new Album($r->object_id);
				$data->count = $r->count;
				$data->format_album();
				$items[] = $data;
			} 
			/* If its an artist */
			elseif ($type == 'artist') { 
				$data = new Artist($r->object_id);
				$data->count = $r->count;
				$data->format_artist();
				$data->f_name = $data->link;
				$items[] = $data;
			} 		 
			/* If it's a genre */
			elseif ($type == 'genre') { 
				$data = new Genre($r->object_id);
				$data->count = $r->count;
				$data->format_genre();
				$data->f_name = $data->link;
				$items[] = $data;
			}

		} // end while
		
		return $items;

	} // get_favorites

	/*!
		@function is_xmlrpc
		@discussion checks to see if this is a valid
			xmlrpc user
	*/
	function is_xmlrpc() { 

		/* If we aren't using XML-RPC return true */
		if (!conf('xml_rpc')) { 
			return false;
		}

		//FIXME: Ok really what we will do is check the MD5 of the HTTP_REFERER 
		//FIXME: combined with the song title to make sure that the REFERER
		//FIXME: is in the access list with full rights
		return true;

	} // is_xmlrpc

	/*!
		@function is_logged_in
		@discussion checks to see if $this user is logged in
	*/
	function is_logged_in() { 

		$sql = "SELECT id FROM session WHERE username='$this->username'" .
			" AND expire > ". time();
		$db_results = mysql_query($sql,dbh());

		if (mysql_num_rows($db_results)) { 
			return true;
		}

		return false;

	} // is_logged_in

	/*!
		@function has_access
		@discussion this function checkes to see if this user has access
			to the passed action (pass a level requirement)
	*/
	function has_access($needed_level) { 

		if ($this->access == "admin") { $level = 100; }
		elseif ($this->access == "user") { $level = 25; }
		else { $level = $this->access; }

		if (!conf('use_auth') || conf('demo_mode')) { return true; }

		if ($level >= $needed_level) { return true; }

		return false;

	} // has_access

	/**
	 * update_preference
	 * updates a single preference if the query fails
	 * it attempts to insert the preference instead
	 * @package User
	 * @catagory Class
	 * @todo Do a has_preference_access check
	 */
	function update_preference($preference_id, $value, $username=0) {
		
		if (!$username) { 
			$username = $this->username;
		}

		$value = sql_escape($value);
		$sql = "UPDATE user_preference SET value='$value' WHERE user='$username' AND preference='$preference_id'";

		$db_results = @mysql_query($sql, dbh());

	} // update_preference

	/**
	 * add_preference
	 * adds a new preference
	 * @package User
	 * @catagory Class
	 * @param $key	preference name
	 * @param $value	preference value
	 * @param $id	user is
	 */
	function add_preference($preference_id, $value, $username=0) { 
	
		if (!$username) { 
			$username = $this->username;
		}

		$value = sql_escape($value);

		if (!is_numeric($preference_id)) { 
			$sql = "SELECT id FROM preferences WHERE `name`='$preference_id'";
			$db_results = mysql_query($sql, dbh());
			$r = mysql_fetch_array($db_results);
			$preference_id = $r[0];
		} // end if it's not numeric

		$sql = "INSERT user_preference SET `user`='$username' , `value`='$value' , `preference`='$preference_id'";
		$db_results = mysql_query($sql, dbh());

	} // add_preference

	/*!
		@function update_username
		@discussion updates their username
	*/
	function update_username($new_username) {

		$new_username = sql_escape($new_username);
		$sql = "UPDATE user SET username='$new_username' WHERE username='$this->username'";
		$this->username = $new_username;
		$db_results = mysql_query($sql, dbh());

	} // update_username

	/*!
		@function update_fullname
		@discussion updates their fullname
	*/
	function update_fullname($new_fullname) {
		
		$new_fullname = sql_escape($new_fullname);
		$sql = "UPDATE user SET fullname='$new_fullname' WHERE username='$this->username'";
		$db_results = mysql_query($sql, dbh());

	} // update_username

	/*!
		@function update_email
		@discussion updates their email address
	*/
	function update_email($new_email) {

		$new_email = sql_escape($new_email);
		$sql = "UPDATE user SET email='$new_email' WHERE username='$this->username'";
		$db_results = mysql_query($sql, dbh());

	} // update_email

	/*!
		@function update_offset
		@discussion this updates the users offset_limit
	*/
	function update_offset($new_offset) { 

		$new_offset = sql_escape($new_offset);
		$sql = "UPDATE user SET offset_limit='$new_offset' WHERE username='$this->username'";
		$db_results = mysql_query($sql, dbh());

	} // update_offset

	/**
	 * update_access
	 * updates their access level
	 * @todo Remove References to the named version of access
	 */
	function update_access($new_access) { 

		/* Check for all disable */
		if ($new_access == 'disabled') { 
			$sql = "SELECT username FROM user WHERE disabled != '1' AND username != '$this->username'";
			$db_results = mysql_query($sql,dbh());
			if (!mysql_num_rows($db_results)) { return false; }
		}
		
		/* Prevent Only User accounts */
		if ($new_access == 'user') { 
			$sql = "SELECT username FROM user WHERE (access='admin' OR access='100') AND username != '$this->username'";
			$db_results = mysql_query($sql, dbh());
			if (!mysql_num_rows($db_results)) { return false; }
		}

		if ($new_access == 'enabled') {
			$new_access = sql_escape($new_access);
			$sql = "UPDATE user SET disabled='0' WHERE username='$this->username'";
			$db_results = mysql_query($sql, dbh());
			
		} 
		elseif ($new_access == 'disabled') {
			$new_access = sql_escape($new_access);
			$sql = "UPDATE user SET disabled='1' WHERE username='$this->username'";
			$db_results = mysql_query($sql, dbh());
			$sql = "DELETE FROM session WHERE username='" . sql_escape($this->username) . "'";
			$db_results = mysql_query($sql, dbh());
		} else {
			$new_access = sql_escape($new_access);
			$sql = "UPDATE user SET access='$new_access' WHERE username='$this->username'";
			$db_results = mysql_query($sql, dbh());
		}

	} // update_access

	/*!
		@function update_last_seen
		@discussion updates the last seen data for this user
	*/
	function update_last_seen() { 
		
		$sql = "UPDATE user SET last_seen='" . time() . "' WHERE username='$this->username'";
		$db_results = mysql_query($sql, dbh());
	
	} // update_last_seen

	/*!
		@function update_user_stats
		@discussion updates the playcount mojo for this 
			specific user
	*/
	function update_stats($song_id) {

		$song_info = new Song($song_id);
		$user = $this->username;
		$dbh = dbh();

		if (!$song_info->file) { return false; }

		$time = time();

                // Play count for this song
                $sql = "UPDATE object_count" .
                        " SET date = '$time', count=count+1" .
                        " WHERE object_type = 'song'" .
                        " AND object_id = '$song_id' AND userid = '$user'";
                $db_result = mysql_query($sql, $dbh);

                $rows = mysql_affected_rows();
                if (!$rows) {
                        $sql = "INSERT INTO object_count (object_type,object_id,date,count,userid)" .
                                " VALUES ('song','$song_id','$time','1','$user')";
                        $db_result = mysql_query($sql, $dbh);
                }

                // Play count for this artist
                $sql = "UPDATE object_count" .
                        " SET date = '$time', count=count+1" .
                        " WHERE object_type = 'artist'" .
                        " AND object_id = '" . $song_info->artist . "' AND userid = '$user'";
                $db_result = mysql_query($sql, $dbh);

                $rows = mysql_affected_rows();
                if (!$rows) {
                        $sql = "INSERT INTO object_count (object_type,object_id,date,count,userid)" .
                                " VALUES ('artist','".$song_info->artist."','$time','1','$user')";
                        $db_result = mysql_query($sql, $dbh);
                }

                // Play count for this album
                $sql = "UPDATE object_count" .
                        " SET date = '$time', count=count+1" .
                        " WHERE object_type = 'album'" .
                        " AND object_id = '".$song_info->album."' AND userid = '$user'";
                $db_result = mysql_query($sql, $dbh);

                $rows = mysql_affected_rows();
                if (!$rows) {
                        $sql = "INSERT INTO object_count (object_type,object_id,date,count,userid)" .
                                "VALUES ('album','".$song_info->album."','$time','1','$user')";
                        $db_result = mysql_query($sql, $dbh);
                }

		// Play count for this genre
		$sql = "UPDATE object_count" . 
			" SET date = '$time', count=count+1" . 
			" WHERE object_type = 'genre'" . 
			" AND object_id = '" . $song_info->genre."' AND userid='$user'";
		$db_results = mysql_query($sql, $dbh);

		$rows = mysql_affected_rows();
		if (!$rows) { 
			$sql = "INSERT INTO object_count (`object_type`,`object_id`,`date`,`count`,`userid`)" . 
				" VALUES ('genre','" . $song_info->genre."','$time','1','$user')";
			$db_results = mysql_query($sql, $dbh);
		}


	} // update_stats

	/*!
		@function create
		@discussion inserts a new user into ampache
	*/
	function create($username, $fullname, $email, $password, $access) { 

		/* Lets clean up the fields... */
		$username	= sql_escape($username);
		$fullname	= sql_escape($fullname);
		$email		= sql_escape($email);

		/* Now Insert this new user */
		$sql = "INSERT INTO user (username, fullname, email, password, access) VALUES" .
			" ('$username','$fullname','$email',PASSWORD('$password'),'$access')";
		$db_results = mysql_query($sql, dbh());
		if (!$db_results) { return false; }

		/* Populates any missing preferences, in this case all of them */
		$this->fix_preferences($username);

		return $username;

	} // new
	
	/*!
		@function update_password
		@discussion updates a users password
	*/
	function update_password($new_password) { 
		
		$sql = "UPDATE user SET password=PASSWORD('$new_password') WHERE username='$this->username'";
		$db_results = mysql_query($sql, dbh());

		return true;
	} // update_password 


	/*!
		@function format_favorites
		@discussion takes an array of objects and formats them corrrectly
			and returns a simply array with just <a href values
	*/
	function format_favorites($items) { 

		// The length of the longest item
		$maxlen = strlen($items[0]->count);

		// Go through the favs
		foreach ($items as $data) { 
			
			// Make all number lengths equal	
			$len = strlen($data->count);
			while ($len < $maxlen) { 
				$data->count = "0" . $data->count;
				$len++;
			}

			$results[] = "<li>[$data->count] - $data->f_name</li>\n";

		} // end foreach items

		return $results;	

	} // format_favorites

	/**
	 * fix_preferences
	 * this makes sure that the specified user
	 *		has all the correct preferences. This function 
	 *		should be run whenever a system preference is run
	 *		it's a cop out... FIXME!
	 * @todo Fix it so this isn't a hack
	 * @package User
	 * @catagory Class
	 */
	function fix_preferences($user_id=0) { 
	
		if (!$user_id) { 
			$user_id = $this->username;
		}
		/* Get All Preferences */
		$sql = "SELECT * FROM user_preference WHERE user='$user_id'";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_object($db_results)) {
			/* Check for duplicates */
			if (isset($results[$r->preference])) { 
				$r->value = sql_escape($r->value);
				$sql = "DELETE FROM user_preference WHERE user='$user_id' AND preference='$r->preference' AND value='$r->value'";
				$delete_results = mysql_query($sql, dbh());
			} // duplicate
			else { 
				$results[$r->preference] = $r;
			}
		} // while results

		/* 
		  If we aren't the -1 user before we continue then grab the 
		  -1 user's values 
		*/
		if ($user_id != '-1') { 
			$sql = "SELECT user_preference.preference,user_preference.value FROM user_preference,preferences " . 
				"WHERE user_preference.preference = preferences.id AND user_preference.user='-1' AND preferences.type='user'";
			$db_results = mysql_query($sql, dbh());
			while ($r = mysql_fetch_object($db_results)) { 
				$zero_results[$r->preference] = $r->value;
			}
		} // if not user -1


		$sql = "SELECT * FROM preferences";
		if ($user_id != '-1') { 
			$sql .= " WHERE type='user'";
		}
		$db_results = mysql_query($sql, dbh());


		while ($r = mysql_fetch_object($db_results)) { 
			
			/* Check if this preference is set */
			if (!isset($results[$r->id])) { 
				if (isset($zero_results[$r->id])) { 
					$r->value = $zero_results[$r->id];
				}
				$sql = "INSERT INTO user_preference (`user`,`preference`,`value`) VALUES ('$user_id','$r->id','$r->value')";
				$insert_db = mysql_query($sql, dbh());
			}
		} // while preferences

		/* Let's also clean out any preferences garbage left over */
		$sql = "SELECT DISTINCT(user_preference.user) FROM user_preference " . 
			"LEFT JOIN user ON user_preference.user = user.username " . 
			"WHERE user_preference.user!='-1' AND user.username IS NULL";
		$db_results = mysql_query($sql, dbh());

		$results = array();
	
		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r['user'];
		}
		
		foreach ($results as $data) { 
			$sql = "DELETE FROM user_preference WHERE user='$data'";
			$db_results = mysql_query($sql, dbh());
		}

	} // fix_preferences

	/** 
	 * This function is specificly for the update script
	 * it's maintained simply because we have to in order to previous updates to 
	 * work correctly
	 * @package Update
	 * @catagory Legacy Function
	 * @depreciated If working with a new db please use the fix_preferences
	 */
	function old_fix_preferences($user_id = 0) { 

                if (!$user_id) { 
                        $user_id = $this->id;
                }

                /* Get All Preferences */
                $sql = "SELECT * FROM user_preference WHERE user='$user_id'";
                $db_results = mysql_query($sql, dbh());

                while ($r = mysql_fetch_object($db_results)) {
                        /* Check for duplicates */
                        if (isset($results[$r->preference])) { 
                                $r->value = sql_escape($r->value);
                                $sql = "DELETE FROM user_preference WHERE user='$user_id' AND preference='$r->preference' AND value='$r->value'";
                                $delete_results = mysql_query($sql, dbh());
                        } // duplicate
                        else { 
                                $results[$r->preference] = $r;
                        }
                } // while results

                /* 
                  If we aren't the 0 user before we continue then grab the 
                  0 user's values 
                */
                if ($user_id != '0') { 
                        $sql = "SELECT user_preference.preference,user_preference.value FROM user_preference,preferences " . 
                                "WHERE user_preference.preference = preferences.id AND user_preference.user='0' AND preferences.type='user'";
                        $db_results = mysql_query($sql, dbh());
                        while ($r = mysql_fetch_object($db_results)) { 
                                $zero_results[$r->preference] = $r->value;
                        }
                } // if not user 0


                $sql = "SELECT * FROM preferences";
                if ($user_id != '0') { 
                        $sql .= " WHERE type='user'";
                }
                $db_results = mysql_query($sql, dbh());


                while ($r = mysql_fetch_object($db_results)) { 
                        
                        /* Check if this preference is set */
                        if (!isset($results[$r->id])) { 
                                if (isset($zero_results[$r->id])) { 
                                        $r->value = $zero_results[$r->id];
                                }
                                $sql = "INSERT INTO user_preference (`user`,`preference`,`value`) VALUES ('$user_id','$r->id','$r->value')";
                                $insert_db = mysql_query($sql, dbh());
                        }
                } // while preferences

	} // old_fix_preferences


	/*!
		@function delete_stats
		@discussion deletes the stats for this user 
	*/
	function delete_stats() { 

		$sql = "DELETE FROM object_count WHERE userid='" . $this->username . "'";
		$db_results = mysql_query($sql, dbh());

	} // delete_stats

	/*!
		@function delete
		@discussion deletes this user and everything assoicated with it
	*/
	function delete() { 

		/* 
		  Before we do anything make sure that they aren't the last 
		  admin
		*/
		if ($this->has_access(100)) { 
			$sql = "SELECT * FROM user WHERE (level='admin' OR level='100') AND username!='" . $this->username . "'";
			$db_results = mysql_query($sql, dbh());
			if (!mysql_num_rows($db_results)) { 
				return false;
			}
		} // if this is an admin check for others 

		// Delete their playlists
		$sql = "DELETE FROM playlist WHERE user='$this->username'";
		$db_results = mysql_query($sql, dbh());

		// Delete any stats they have
		$sql = "DELETE FROM object_count WHERE userid='$this->username'";
		$db_results = mysql_query($sql, dbh());

		// Delete their preferences
		$sql = "DELETE FROM preferences WHERE user='$this->username'";
		$db_results = mysql_query($sql, dbh());

		// Delete the user itself
		$sql = "DELETE FROM user WHERE username='$this->username'";
		$db_results = mysql_query($sql, dbh());

		return true;

	} // delete

	/*!
		@function is_online
		@parameter delay how long since last_seen in seconds default of 20 min
		@description  calcs difference between now and last_seen
			if less than delay, we consider them still online
	*/
	function is_online( $delay = 1200 ) {
		return time() - $this->last_seen <= $delay;
	}

} //end class
?>
