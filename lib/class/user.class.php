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

/*!
	@header User Object
	View object that is thrown into their session

*/


class User {

	//Basic Componets
	var $id;
	var $uid; // HACK ALERT
	var $username;
	var $fullname;
	var $access;
	var $disabled;
	var $offset_limit=25;
	var $email;
	var $last_seen;
	var $create_date;
	var $validation;

	/**
	 * Constructor
	 * This function is the constructor object for the user
	 * class, it currently takes a username
	 * //FIXME take UID
	 */	
	function User($username=0) {

		if (!$username) { 
			return true;
		}

		$this->username 	= sql_escape($username);
		$info 			= $this->_get_info();

		if (!count($info)) { return false; } 

		$this->id		= $this->username;
		$this->uid		= $info->id;
		$this->username 	= $info->username;
		$this->fullname 	= $info->fullname;
		$this->access 		= $info->access;
		$this->disabled		= $info->disabled;
		$this->offset_limit 	= $info->offset_limit;
		$this->email		= $info->email;
		$this->last_seen	= $info->last_seen;
		$this->create_date	= $info->create_date;
		$this->validation	= $info->validation;
		$this->set_preferences();

		// Make sure the Full name is always filled
		if (strlen($this->fullname) < 1) { $this->fullname = $this->username; }

	} // User

	/**
	 * _get_info
	 * This function returns the information for this object
	 */
	function _get_info() {

		/* Hack during transition back to UID for user creation */
		if (is_numeric($this->username)) { 
			$sql = "SELECT * FROM user WHERE id='" . $this->username . "'";
		}
		else { 
			$sql = "SELECT * FROM user WHERE username='$this->username'";
		}
		
		$db_results = mysql_query($sql, dbh());

		return mysql_fetch_object($db_results);

	} // _get_info

	/**
	 * get_preferences
	 * This is a little more complicate now that we've got many types of preferences
	 * This funtions pulls all of them an arranges them into a spiffy little array
	 * You can specify a type to limit it to a single type of preference
	 * []['title'] = ucased type name
	 * []['prefs'] = array(array('name','display','value'));
	 * []['admin'] = t/f value if this is an admin only section
	 */
	function get_preferences($user_id=0,$type=0) { 
		
		if (!$user_id) { 
			$user_id = $this->username;
		}

		if (!conf('use_auth')) { $user_id = '-1'; }

		if ($user_id != '-1') { 
			$user_limit = "AND preferences.catagory != 'system'";
		}
		
		if ($type != '0') { 
			$user_limit = "AND preferences.catagory = '" . sql_escape($type) . "'";
		}

	
		$sql = "SELECT preferences.name, preferences.description, preferences.catagory, user_preference.value FROM preferences,user_preference " .
			"WHERE user_preference.user='$user_id' AND user_preference.preference=preferences.id $user_limit";
		$db_results = mysql_query($sql, dbh());

		/* Ok this is crapy, need to clean this up or improve the code FIXME */
		while ($r = mysql_fetch_assoc($db_results)) { 
			$type = $r['catagory'];
			$admin = false;
			if ($type == 'system') { $admin = true; }
			$type_array[$type][] = array('name'=>$r['name'],'description'=>$r['description'],'value'=>$r['value']);
			$results[$type] = array ('title'=>ucwords($type),'admin'=>$admin,'prefs'=>$type_array[$type]);
		} // end while


		return $results;
	
	} // get_preferences

	/*!
		@function set_preferences
		@discussion sets the prefs for this specific 
			user
	*/
	function set_preferences() {

		$sql = "SELECT preferences.name,user_preference.value FROM preferences,user_preference WHERE user_preference.user='$this->id' " .
			"AND user_preference.preference=preferences.id AND preferences.type != 'system'";
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

	        $web_path = conf('web_path');

		$stats = new Stats();
		$results = $stats->get_user(conf('popular_threshold'),$type,$this->uid,1);

		$items = array();

		foreach ($results as $r) { 
			/* If its a song */
			if ($type == 'song') { 
				$data = new Song($r['object_id']);
				$data->count = $r['count'];
				$data->format_song();
				$data->f_name = $data->f_link;
				$items[] = $data;
			}
			/* If its an album */
			elseif ($type == 'album') { 
				$data = new Album($r['object_id']);
				$data->count = $r['count'];
				$data->format_album();
				$items[] = $data;
			} 
			/* If its an artist */
			elseif ($type == 'artist') { 
				$data = new Artist($r['object_id']);
				$data->count = $r['count'];
				$data->format_artist();
				$data->f_name = $data->link;
				$items[] = $data;
			} 		 
			/* If it's a genre */
			elseif ($type == 'genre') { 
				$data = new Genre($r['object_id']);
				$data->count = $r['count'];
				$data->format_genre();
				$data->f_name = $data->link;
				$items[] = $data;
			}

		} // end foreach
		
		return $items;

	} // get_favorites

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

		if (!conf('use_auth')) { $username = '-1'; }

		$value = sql_escape($value);
		$sql = "UPDATE user_preference SET value='$value' WHERE user='$username' AND preference='$preference_id'";

		$db_results = @mysql_query($sql, dbh());

	} // update_preference

	/**
	 * legacy_add_preference
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

	/**
	 * update_validation
	 * This is used by the registration mumbojumbo
	 * Use this function to update the validation key
	 * NOTE: crap this doesn't have update_item the humanity of it all 
	 */
	function update_validation($new_validation) { 

		$new_validation = sql_escape($new_validation);
		$sql = "UPDATE user SET validation='$new_validation',disabled='1' WHERE username='$this->username'";
		$this->validation = $new_validation;
		$db_results = mysql_query($sql, dbh());

		return $db_results;

	} // update_validation

	/*!
		@function update_fullname
		@discussion updates their fullname
	*/
	function update_fullname($new_fullname) {
		
		$new_fullname = sql_escape($new_fullname);
		$sql = "UPDATE user SET fullname='$new_fullname' WHERE username='$this->id'";
		$db_results = mysql_query($sql, dbh());

	} // update_username

	/*!
		@function update_email
		@discussion updates their email address
	*/
	function update_email($new_email) {

		$new_email = sql_escape($new_email);
		$sql = "UPDATE user SET email='$new_email' WHERE username='$this->id'";
		$db_results = mysql_query($sql, dbh());

	} // update_email

	/*!
		@function update_offset
		@discussion this updates the users offset_limit
	*/
	function update_offset($new_offset) { 

		$new_offset = sql_escape($new_offset);
		$sql = "UPDATE user SET offset_limit='$new_offset' WHERE username='$this->id'";
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
			$sql = "UPDATE user SET disabled='1' WHERE username='$this->username'";
			$db_results = mysql_query($sql, dbh());
			$sql = "DELETE FROM session WHERE username='" . sql_escape($this->username) . "'";
			$db_results = mysql_query($sql, dbh());
		} 
		else {
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

	/**
	 * update_user_stats
	 * updates the playcount mojo for this specific user
	 */
	function update_stats($song_id) {

		$song_info = new Song($song_id);
		//FIXME:: User uid reference
		$user = $this->uid;

		if (!$song_info->file) { return false; }

		$stats = new Stats();
		$stats->insert('song',$song_id,$user);
		$stats->insert('album',$song_info->album,$user);
		$stats->insert('artist',$song_info->artist,$user);
		$stats->insert('genre',$song_info->genre,$user);

	} // update_stats

	/**
	 * insert_ip_history
	 * This inserts a row into the IP History recording this user at this
	 * address at this time in this place, doing this thing.. you get the point
	 */
	function insert_ip_history() { 

		$ip = ip2int($_SERVER['REMOTE_ADDR']);
		$date = time(); 
		$user = $this->id;

		$sql = "INSERT INTO ip_history (`ip`,`user`,`date`) VALUES ('$ip','$user','$date')";
		$db_results = mysql_query($sql, dbh());

		/* Clean up old records */
		$date = time() - (86400*conf('user_ip_cardinality'));

		$sql = "DELETE FROM ip_history WHERE `date` < $date";
		$db_results = mysql_query($sql,dbh());

		return true;

	} // insert_ip_history

	/**
	 * create
	 * inserts a new user into ampache
	 */
	function create($username, $fullname, $email, $password, $access) { 

		/* Lets clean up the fields... */
		$username	= sql_escape($username);
		$fullname	= sql_escape($fullname);
		$email		= sql_escape($email);
		$access		= sql_escape($access);
		
		/* Now Insert this new user */
		$sql = "INSERT INTO user (username, fullname, email, password, access, create_date) VALUES" .
			" ('$username','$fullname','$email',PASSWORD('$password'),'$access','" . time() ."')";
		$db_results = mysql_query($sql, dbh());
		
		if (!$db_results) { return false; }

		/* Populates any missing preferences, in this case all of them */
		$this->fix_preferences($username);

		return $username;

	} // create
	
	/*!
		@function update_password
		@discussion updates a users password
	*/
	function update_password($new_password) { 
		
		$sql = "UPDATE user SET password=PASSWORD('$new_password') WHERE username='$this->username'";
		$db_results = mysql_query($sql, dbh());

		return true;
	} // update_password 

	/**
	 * format_user
	 * This function sets up the extra variables we need when we are displaying a
	 * user for an admin, these should not be normally called when creating a 
	 * user object
	 */
	function format_user() { 

		/* If they have a last seen date */
		if (!$this->last_seen) { $this->f_last_seen = "Never"; }
		else { $this->f_last_seen = date("m\/d\/Y - H:i",$this->last_seen); }

		/* If they have a create date */
        	if (!$this->create_date) { $this->f_create_date = "Unknown"; }
		else { $this->f_create_date = date("m\/d\/Y - H:i",$user->create_date); }

		/* Calculate their total Bandwidth Useage */
		$sql = "SELECT song.size FROM song LEFT JOIN object_count ON song.id=object_count.object_id " . 
			"WHERE object_count.user='$this->uid' AND object_count.object_type='song'";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_assoc($db_results)) { 
			$total = $total + $r['size'];
		}		

		$divided = 0;
	
		while (strlen(floor($total)) > 3) { 
			$total = ($total / 1024);
			$divided++;
		}

		switch ($divided) { 
			case '1': $name = "KB"; break;
			case '2': $name = "MB"; break;
			case '3': $name = "GB"; break;
			case '4': $name = "TB"; break;
			case '5': $name = "PB"; break;
		} // end switch

		$this->f_useage = round($total,2) . $name;
		
		/* Get Users Last ip */
		$data = $this->get_ip_history(1);
		$this->ip_history = int2ip($data['0']['ip']);	

	} // format_user

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
				"WHERE user_preference.preference = preferences.id AND user_preference.user='-1' AND preferences.catagory !='system'";
			$db_results = mysql_query($sql, dbh());
			while ($r = mysql_fetch_object($db_results)) { 
				$zero_results[$r->preference] = $r->value;
			}
		} // if not user -1


		$sql = "SELECT * FROM preferences";
		if ($user_id != '-1') { 
			$sql .= " WHERE catagory !='system'";
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
			$sql = "SELECT username FROM user WHERE (access='admin' OR access='100') AND username !='" . sql_escape($this->username) . "'";
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

		$sql = "DELETE FROM session WHERE username='$this->username'";
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

	/*!
		@function get_user_validation
		@check if user exists before activation can be done.
	*/
	function get_user_validation($username,$validation) {
	
		$usename = sql_escape($username);
	
		$sql = "SELECT validation FROM user where username='$username'";
		$db_results = mysql_query($sql, dbh());
		
		$row = mysql_fetch_assoc($db_results);
		$val = $row['validation'];

		return $val;

	} // get_user_validation

	/**
 	 * get_recent
	 * This returns users by thier last login date
	 */
	function get_recent($count=0) { 

		if ($count) { $limit_clause = " LIMIT $count"; } 
	
		$results = array();		

		$sql = "SELECT username FROM user ORDER BY last_seen $limit_clause";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r['username'];
		} 

		return $results;

	} // get_recent

        /**
         * get_ip_history 
         * This returns the ip_history from the
         * last conf('user_ip_cardinality') days
         */             
        function get_ip_history($count='',$distinct='') { 

		$username 	= sql_escape($this->username);

		if ($count) { 
			$limit_sql = "LIMIT " . intval($count);
		}
		if ($distinct) { 
			$group_sql = "GROUP BY ip";
		}
                        
                /* Select ip history */
                $sql = "SELECT ip,date FROM ip_history" .
                        " WHERE user='$username'" .
                        " $group_sql ORDER BY `date` DESC $limit_sql";
                $db_results = mysql_query($sql, dbh());

                $results = array();
         
                while ($r = mysql_fetch_assoc($db_results)) {
                        $results[] = $r;
                }
        
                return $results;
                
        } // get_ip_history

	/*!
		@function activate_user
		@activates the user from public_registration
	*/
	function activate_user($username) {
	
		$username = sql_escape($username);
	
		$sql = "UPDATE user SET disabled='0' WHERE username='$username'";
		$db_results = mysql_query($sql, dbh());
		
	} // activate_user

	
} //end user class

?>
