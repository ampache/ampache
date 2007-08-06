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
 * User Class
 * This class handles all of the user related functions includingn the creationg
 * and deletion of the user objects from the database by defualt you constrcut it
 * with a user_id from user.id
 */
class User {

	//Basic Componets
	public $id;
	public $username;
	public $fullname;
	public $access;
	public $disabled;
	public $email;
	public $last_seen;
	public $create_date;
	public $validation;

	// Constructed variables
	public $prefs = array(); 

	/**
	 * Constructor
	 * This function is the constructor object for the user
	 * class, it currently takes a username
	 */	
	public function __construct($user_id=0) {

		$this->id		= intval($user_id);
		$info 			= $this->_get_info();

		foreach ($info as $key=>$value) { 
			// Let's not save the password in this object :S
			if ($key == 'password') { continue; } 
			$this->$key = $value; 
		} 
		
		// Set the preferences for thsi user
		$this->set_preferences();

		// Make sure the Full name is always filled
		if (strlen($this->fullname) < 1) { $this->fullname = $this->username; }

	} // Constructor

	/**
	 * _get_info
	 * This function returns the information for this object
	 */
	private function _get_info() {

		$id = Dba::escape($this->id);

		$sql = "SELECT * FROM `user` WHERE `id`='" . $id . "'";
		
		$db_results = Dba::query($sql);

		return Dba::fetch_assoc($db_results);

	} // _get_info

	/**
	 * load_playlist
	 * This is called once per page load it makes sure that this session
	 * has a tmp_playlist, creating it if it doesn't, then sets $this->playlist
	 * as a tmp_playlist object that can be fiddled with later on
	 */
	public function load_playlist() { 

		$session_id = session_id(); 

		$this->playlist = tmpPlaylist::get_from_session($session_id); 

	} // load_playlist

	/**
	 * get_from_username
	 * This returns a built user from a username. This is a 
	 * static function so it doesn't require an instance
	 */
	public static function get_from_username($username) { 

		$username = Dba::escape($username); 
		
		$sql = "SELECT `id` FROM `user` WHERE `username`='$username'"; 
		$db_results = Dba::query($sql);
		$results = Dba::fetch_assoc($db_results); 
		
		$user = new User($results['id']); 

		return $user; 

	} // get_from_username

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
	
		// Fill out the user id
		$user_id = $user_id ? Dba::escape($user_id) : Dba::escape($this->id); 

		if (!Config::get('use_auth')) { $user_id = '-1'; }

		if ($user_id != '-1') { 
			$user_limit = "AND preference.catagory != 'system'";
		}
			
		if ($type != '0') { 
			$user_limit = "AND preference.catagory = '" . Dba::escape($type) . "'";
		}

	
		$sql = "SELECT preference.name, preference.description, preference.catagory, preference.level, user_preference.value " . 
			"FROM preference INNER JOIN user_preference ON user_preference.preference=preference.id " .
			"WHERE user_preference.user='$user_id' $user_limit";
		$db_results = Dba::query($sql);

		/* Ok this is crapy, need to clean this up or improve the code FIXME */
		while ($r = Dba::fetch_assoc($db_results)) { 
			$type = $r['catagory'];
			$admin = false;
			if ($type == 'system') { $admin = true; }
			$type_array[$type][$r['name']] = array('name'=>$r['name'],'level'=>$r['level'],'description'=>$r['description'],'value'=>$r['value']);
			ksort($type_array[$type]); 
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

		$sql = "SELECT preference.name,user_preference.value FROM preference,user_preference WHERE user_preference.user='$this->id' " .
			"AND user_preference.preference=preference.id AND preference.type != 'system'";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) {
			$key = $r['name'];
			$this->prefs[$key] = $r['value'];
		} 
	} // get_preferences

	/**
	 * get_favorites
	 * returns an array of your $type favorites
	 */
	function get_favorites($type) { 

	        $web_path = Config::get('web_path');

		$results = Stats::get_user(Config::get('popular_threshold'),$type,$this->id,1);

		$items = array();

		foreach ($results as $r) { 
			/* If its a song */
			if ($type == 'song') { 
				$data = new Song($r['object_id']);
				$data->count = $r['count'];
				$data->format();
				$data->f_name = $data->f_link;
				$items[] = $data;
			}
			/* If its an album */
			elseif ($type == 'album') { 
				$data = new Album($r['object_id']);
				$data->count = $r['count'];
				$data->format();
				$items[] = $data;
			} 
			/* If its an artist */
			elseif ($type == 'artist') { 
				$data = new Artist($r['object_id']);
				$data->count = $r['count'];
				$data->format();
				$data->f_name = $data->f_link;
				$items[] = $data;
			} 		 
			/* If it's a genre */
			elseif ($type == 'genre') { 
				$data = new Genre($r['object_id']);
				$data->count = $r['count'];
				$data->format();
				$data->f_name = $data->f_link;
				$items[] = $data;
			}

		} // end foreach
		
		return $items;

	} // get_favorites

	/**
	 * get_recommendations
	 * This returns recommended objects of $type. The recommendations
	 * are based on voodoo economics,the phase of the moon and my current BAL. 
	 */
	function get_recommendations($type) { 

		/* First pull all of your ratings of this type */ 
		$sql = "SELECT object_id,user_rating FROM ratings " . 
			"WHERE object_type='" . Dba::escape($type) . "' AND user='" . Dba::escape($this->id) . "'";
		$db_results = Dba::query($sql); 

		// Incase they only have one user
		$users = array(); 

		while ($r = Dba::fetch_assoc($db_results)) { 
			/* Store the fact that you rated this */
			$key = $r['object_id'];
			$ratings[$key] = true;

			/* Build a key'd array of users with this same rating */
			$sql = "SELECT user FROM ratings WHERE object_type='" . Dba::escape($type) . "' " . 
				"AND user !='" . Dba::escape($this->id) . "' AND object_id='" . Dba::escape($r['object_id']) . "' " . 
				"AND user_rating ='" . Dba::escape($r['user_rating']) . "'";
			$user_results = Dba::query($sql); 

			while ($user_info = Dba::fetch_assoc($user_results)) { 
				$key = $user_info['user'];
				$users[$key]++; 
			}

		} // end while 

		/* now we've got your ratings, and all users and the # of ratings that match your ratings 
		 * sort the users[$key] array by value and then find things they've rated high (4+) that you
		 * haven't rated
		 */
		$recommendations = array(); 
		asort($users);

		foreach ($users as $user_id=>$score) { 

			/* Find everything they've rated at 4+ */
			$sql = "SELECT object_id,user_rating FROM ratings " . 
				"WHERE user='" . Dba::escape($user_id) . "' AND user_rating >='4' AND " . 
				"object_type = '" . Dba::escape($type) . "' ORDER BY user_rating DESC"; 
			$db_results = Dba::query($sql); 

			while ($r = Dba::fetch_assoc($db_results)) { 
				$key = $r['object_id'];
				if (isset($ratings[$key])) { continue; } 

				/* Let's only get 5 total for now */
				if (count($recommendations) > 5) { return $recommendations; } 

				$recommendations[$key] = $r['user_rating'];

			} // end while


		} // end foreach users

		return $recommendations;

	} // get_recommendations

	/**
	 * is_logged_in
	 * checks to see if $this user is logged in returns their current IP if they
	 * are logged in 
	 */
	public function is_logged_in() { 

		$username = Dba::escape($this->username); 

		$sql = "SELECT `id`,`ip` FROM `session` WHERE `username`='$username'" .
			" AND `expire` > ". time();
		$db_results = Dba::query($sql);

		if ($row = Dba::fetch_assoc($db_results)) { 
			$ip = $row['ip'] ? $row['ip'] : '1'; 
			return $ip;
		}

		return false;

	} // is_logged_in

	/*!
		@function has_access
		@discussion this function checkes to see if this user has access
			to the passed action (pass a level requirement)
	*/
	function has_access($needed_level) { 

		if (!Config::get('use_auth') || Config::get('demo_mode')) { return true; }

		if ($this->access >= $needed_level) { return true; }

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
	function update_preference($preference_id, $value, $user_id=0) {
	
		if (!has_preference_access(get_preference_name($preference_id))) { 
			return false; 
		} 

		if (!$user_id) { 
			$user_id = $this->id;
		}

		if (!conf('use_auth')) { $user_id = '-1'; }

		$value 		= sql_escape($value);
		$preference_id 	= sql_escape($preference_id); 
		$user_id	= sql_escape($user_id);

		$sql = "UPDATE user_preference SET value='$value' WHERE user='$user_id' AND preference='$preference_id'";

		$db_results = mysql_query($sql, dbh());

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
			$sql = "SELECT id FROM preference WHERE `name`='$preference_id'";
			$db_results = mysql_query($sql, dbh());
			$r = mysql_fetch_array($db_results);
			$preference_id = $r[0];
		} // end if it's not numeric

		$sql = "INSERT user_preference SET `user`='$username' , `value`='$value' , `preference`='$preference_id'";
		$db_results = mysql_query($sql, dbh());

	} // add_preference

	/**
	 * update
	 * This function is an all encompasing update function that
	 * calls the mini ones does all the error checking and all that
	 * good stuff
	 */
	public function update($data) { 

		if (empty($data['username'])) { 
			Error::add('username',_('Error Username Required')); 
		} 

		if ($data['password1'] != $data['password2'] AND !empty($data['password1'])) { 
			Error::add('password',_("Error Passwords don't match")); 
		} 

		if (Error::$state) { 
			return false; 
		} 
		
		foreach ($data as $name=>$value) { 
			switch ($name) { 
				case 'password1'; 
					$name = 'password'; 
				case 'access':
				case 'email':
				case 'username': 
				case 'fullname'; 
					if ($this->$name != $value) { 
						$function = 'update_' . $name; 
						$this->$function($value);
					} 	
				break;
				default: 
					// Rien a faire
				break;
			} // end switch on field

		} // end foreach	

		return true; 

	} // update

	/**
	 * update_username
	 * updates their username
	 */
	public function update_username($new_username) {

		$new_username = Dba::escape($new_username);
		$sql = "UPDATE `user` SET `username`='$new_username' WHERE `id`='$this->id'";
		$this->username = $new_username;
		$db_results = Dba::query($sql);

	} // update_username

	/**
	 * update_validation
	 * This is used by the registration mumbojumbo
	 * Use this function to update the validation key
	 * NOTE: crap this doesn't have update_item the humanity of it all 
	 */
	function update_validation($new_validation) { 

		$new_validation = sql_escape($new_validation);
		$sql = "UPDATE user SET validation='$new_validation',disabled='1' WHERE `id`='$this->id'";
		$this->validation = $new_validation;
		$db_results = mysql_query($sql, dbh());

		return $db_results;

	} // update_validation

	/**
	 * update_fullname
	 * updates their fullname
	 */
	public function update_fullname($new_fullname) {
		
		$new_fullname = Dba::escape($new_fullname);
		$sql = "UPDATE `user` SET `fullname`='$new_fullname' WHERE `id`='$this->id'";
		$db_results = Dba::query($sql);

	} // update_fullname

	/**
	 * update_email
	 * updates their email address
	 */
	public function update_email($new_email) {

		$new_email = Dba::escape($new_email);
		$sql = "UPDATE `user` SET `email`='$new_email' WHERE `id`='$this->id'";
		$db_results = Dba::query($sql);

	} // update_email

	/** 
	 * disable
	 * This disables the current user
	 */
	public function disable() { 

		// Make sure we aren't disabling the last admin
		$sql = "SELECT `id` FROM `user` WHERE `disabled` = '0' AND `id` != '" . $this->id . "' AND `access`='100'"; 
		$db_results = Dba::query($sql); 
		
		if (!Dba::num_rows($db_results)) { return false; } 

		$sql = "UPDATE `user` SET `disabled`='1' WHERE id='" . $this->id . "'";
		$db_results = Dba::query($sql); 

		// Delete any sessions they may have
		$sql = "DELETE FROM `session` WHERE `username`='" . Dba::escape($this->username) . "'"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // disable

	/**
 	 * enable
	 * this enables the current user
	 */
	function enable() { 

		$sql = "UPDATE `user` SET `disabled`='0' WHERE id='" . $this->id . "'";
		$db_results = mysql_query($sql,dbh()); 

		return true; 

	} // enable

	/**
	 * update_access
	 * updates their access level
	 */
	public function update_access($new_access) { 

		/* Prevent Only User accounts */
		if ($new_access < '100') { 
			$sql = "SELECT `id` FROM user WHERE `access`='100' AND `id` != '$this->id'";
			$db_results = Dba::query($sql);
			if (!Dba::num_rows($db_results)) { return false; }
		}

		$new_access = Dba::escape($new_access);
		$sql = "UPDATE `user` SET `access`='$new_access' WHERE `id`='$this->id'";
		$db_results = Dba::query($sql);

	} // update_access

	/*!
		@function update_last_seen
		@discussion updates the last seen data for this user
	*/
	function update_last_seen() { 
		
		$sql = "UPDATE user SET last_seen='" . time() . "' WHERE `id`='$this->id'";
		$db_results = Dba::query($sql);
	
	} // update_last_seen

	/**
	 * update_user_stats
	 * updates the playcount mojo for this specific user
	 */
	public function update_stats($song_id) {

		$song_info = new Song($song_id);
		$user = $this->id;
		
		if (!strlen($song_info->file)) { return false; }

		Stats::insert('song',$song_id,$user);
		Stats::insert('album',$song_info->album,$user);
		Stats::insert('artist',$song_info->artist,$user);
		Stats::insert('genre',$song_info->genre,$user);

                /**
		 * Record this play to LastFM 
		 * because it lags like the dickens try twice on everything
		 */
                if (!empty($this->prefs['lastfm_user']) AND !empty($this->prefs['lastfm_pass'])) { 
                        $song_info->format();
                        $lastfm = new scrobbler($this->prefs['lastfm_user'],$this->prefs['lastfm_pass']);                       
                        /* Attempt handshake */
			$handshake = $lastfm->handshake(); 

			/* We failed, try again */			
			if (!$handshake) { sleep(1); $handshake = $lastfm->handshake(); } 

                        if ($handshake) { 
                                if (!$lastfm->queue_track($song_info->f_artist_full,$song_info->f_album_full,$song_info->title,time(),$song_info->time)) { 
					debug_event('LastFM','Error: Queue Failed: ' . $lastfm->error_msg,'3');
				}

				$submit = $lastfm->submit_tracks(); 
	
				/* Try again if it fails */
				if (!$submit) { sleep(1); $submit = $lastfm->submit_tracks(); } 

				if (!$submit) { 
					debug_event('LastFM','Error Submit Failed: ' . $lastfm->error_msg,'3'); 
				}
                        } // if handshake
                        else { 
                                debug_event('LastFM','Error: Handshake failed with LastFM: ' . $lastfm->error_msg,'3');
                        }
                } // record to LastFM
	} // update_stats

	/**
	 * insert_ip_history
	 * This inserts a row into the IP History recording this user at this
	 * address at this time in this place, doing this thing.. you get the point
	 */
	public function insert_ip_history() { 

		$ip = ip2int($_SERVER['REMOTE_ADDR']);
		$date = time(); 
		$user = $this->id;

		$sql = "INSERT INTO `ip_history` (`ip`,`user`,`date`) VALUES ('$ip','$user','$date')";
		$db_results = Dba::query($sql);

		/* Clean up old records */
		$date = time() - (86400*Config::get('user_ip_cardinality'));
		$sql = "DELETE FROM `ip_history` WHERE `date` < $date";
		$db_results = Dba::query($sql);

		return true;

	} // insert_ip_history

	/**
	 * create
	 * inserts a new user into ampache
	 */
	public static function create($username, $fullname, $email, $password, $access) { 

		/* Lets clean up the fields... */
		$username	= Dba::escape($username);
		$fullname	= Dba::escape($fullname);
		$email		= Dba::escape($email);
		$access		= Dba::escape($access);
	
		/* Now Insert this new user */
		$sql = "INSERT INTO `user` (`username`, `fullname`, `email`, `password`, `access`, `create_date`) VALUES" .
			" ('$username','$fullname','$email',PASSWORD('$password'),'$access','" . time() ."')";
		$db_results = Dba::query($sql);
		
		if (!$db_results) { return false; }

		// Get the insert_id
		$insert_id = Dba::insert_id(); 

		/* Populates any missing preferences, in this case all of them */
		self::fix_preferences($insert_id);

		return $insert_id;

	} // create
	
	/**
	 * update_password
	 * updates a users password
	 */
	public function update_password($new_password) { 

		$new_password = Dba::escape($new_password);
		$sql = "UPDATE `user` SET `password`=PASSWORD('$new_password') WHERE `id`='$this->id'";
		$db_results = Dba::query($sql);

	} // update_password 

	/**
	 * format
	 * This function sets up the extra variables we need when we are displaying a
	 * user for an admin, these should not be normally called when creating a 
	 * user object
	 */
	public function format() { 

		/* If they have a last seen date */
		if (!$this->last_seen) { $this->f_last_seen = _('Never'); }
		else { $this->f_last_seen = date("m\/d\/Y - H:i",$this->last_seen); }

		/* If they have a create date */
        	if (!$this->create_date) { $this->f_create_date = _('Unknown'); }
		else { $this->f_create_date = date("m\/d\/Y - H:i",$this->create_date); }

		/* Calculate their total Bandwidth Useage */
		$sql = "SELECT `song`.`size` FROM `song` LEFT JOIN `object_count` ON `song`.`id`=`object_count`.`object_id` " . 
			"WHERE `object_count`.`user`='$this->id' AND `object_count`.`object_type`='song'";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) { 
			$total = $total + $r['size'];
		}		

		$divided = 0;
	
		while (strlen(floor($total)) > 3) { 
			$total = ($total / 1024);
			$divided++;
		}

		switch ($divided) { 
			default:
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

			$item = "[$data->count] - $data->f_name";
			$results[]->f_name_link = $item;
		} // end foreach items

		return $results;

	} // format_favorites

	/**
	 * format_recommendations
	 * This takes an array of [object_id] = ratings
	 * and displays them in a semi-pretty format
	 */
	 function format_recommendations($items,$type) { 

		foreach ($items as $object_id=>$rating) { 

			switch ($type) { 
				case 'artist':
					$object = new Artist($object_id);
					$object->format_artist(); 
					$name = $object->link;
				break;
				case 'album':
					$object = new Album($object_id);
					$object->format_album(); 
					$name = $object->f_link;
				break;
				case 'song':
					$object = new Song($object_id);
					$object->format_song(); 
					$name = $object->f_link; 
				break;
			} // end switch on type
			$results[] = "<li>$name -- " . get_rating_name($rating) . "<br />\n</li>";

		} // end foreach items


		return $results; 

	 } // format_recommendations

	/**
 	 * fix_preferences
	 * This is the new fix_preferences function, it does the following
	 * Remove Duplicates from user, add in missing
	 * If -1 is passed it also removes duplicates from the `preferences`
	 * table. 
	 */
	public static function fix_preferences($user_id) { 

		$user_id = Dba::escape($user_id); 

		/* Get All Preferences for the current user */
		$sql = "SELECT * FROM `user_preference` WHERE `user`='$user_id'"; 
		$db_results = Dba::query($sql); 

		$results = array(); 

		while ($r = Dba::fetch_assoc($db_results)) { 
			$pref_id = $r['preference'];
			/* Check for duplicates */
			if (isset($results[$pref_id])) { 
				$r['value'] = Dba::escape($r['value']); 
				$sql = "DELETE FROM `user_preference` WHERE `user`='$user_id' AND `preference`='" . $r['preference'] . "' AND" . 
					" `value`='" . Dba::escape($r['value']) . "'"; 
				$delete_results = Dba::query($sql); 
			} // if its set
			else { 
				$results[$pref_id] = 1; 
			} 
		} // end while
	
		/* If we aren't the -1 user before we continue grab the -1 users values */
		if ($user_id != '-1') { 
                        $sql = "SELECT `user_preference`.`preference`,`user_preference`.`value` FROM `user_preference`,`preference` " .
                                "WHERE `user_preference`.`preference` = `preference`.`id` AND `user_preference`.`user`='-1' AND `preference`.`catagory` !='system'";
                        $db_results = Dba::query($sql);
			/* While through our base stuff */
                        while ($r = Dba::fetch_assoc($db_results)) {
				$key = $r['preference']; 
                                $zero_results[$key] = $r['value'];
                        }
                } // if not user -1

		// get me _EVERYTHING_ 
                $sql = "SELECT * FROM `preference`";

		// If not system, exclude system... *gasp*
                if ($user_id != '-1') {
                        $sql .= " WHERE catagory !='system'";
                }
                $db_results = Dba::query($sql);

                while ($r = Dba::fetch_assoc($db_results)) {

			$key = $r['id'];

                        /* Check if this preference is set */
                        if (!isset($results[$key])) {
                                if (isset($zero_results[$key])) {
                                        $r['value'] = $zero_results[$key];
                                }
				$value = Dba::escape($r['value']); 
                                $sql = "INSERT INTO user_preference (`user`,`preference`,`value`) VALUES ('$user_id','$key','$value')";
                                $insert_db = Dba::query($sql);
                        }
                } // while preferences

                /* Let's also clean out any preferences garbage left over */
                $sql = "SELECT DISTINCT(user_preference.user) FROM user_preference " .
                        "LEFT JOIN user ON user_preference.user = user.id " .
                        "WHERE user_preference.user!='-1' AND user.id IS NULL";
                $db_results = Dba::query($sql);

                $results = array();

                while ($r = Dba::fetch_assoc($db_results)) {
                        $results[] = $r['user'];
                }

                foreach ($results as $data) {
                        $sql = "DELETE FROM user_preference WHERE user='$data'";
                        $db_results = Dba::query($sql);
                }

	} // fix_preferences

	/*!
		@function delete_stats
		@discussion deletes the stats for this user 
	*/
	function delete_stats() { 

		$sql = "DELETE FROM object_count WHERE user='" . $this->id . "'";
		$db_results = mysql_query($sql, dbh());

	} // delete_stats

	/**
	 * delete
	 * deletes this user and everything assoicated with it. This will affect
	 * ratings and tottal stats
	 */
	public function delete() { 

		/* 
		  Before we do anything make sure that they aren't the last 
		  admin
		*/
		if ($this->has_access(100)) { 
			$sql = "SELECT `id` FROM `user` WHERE `access`='100' AND id !='" . Dba::escape($this->id) . "'";
			$db_results = mysql_query($sql);
			if (!Dba::num_rows($db_results)) { 
				return false;
			}
		} // if this is an admin check for others 

		// Delete their playlists
		$sql = "DELETE FROM `playlist` WHERE `user`='$this->id'";
		$db_results = Dba::query($sql);

		// Clean up the playlist data table
		$sql = "DELETE FROM `playlist_data` USING `playlist_data` " . 
			"LEFT JOIN `playlist` ON `playlist`.`id`=`playlist_data`.`playlist` " . 
			"WHERE `playlist`.`id` IS NULL"; 
		$db_results = Dba::query($sql); 

		// Delete any stats they have
		$sql = "DELETE FROM `object_count` WHERE `user`='$this->id'";
		$db_results = Dba::query($sql);

		// Clear the IP history for this user
		$sql = "DELETE FROM `ip_history` WHERE `user`='$this->id'"; 
		$db_results = Dba::query($sql); 

		// Nuke any access lists that are specific to this user
		$sql = "DELETE FROM `access_list` WHERE `user`='$this->id'"; 
		$db_results = Dba::query($sql); 

		// Delete their ratings
		$sql = "DELETE FROM `rating` WHERE `user`='$this->id'";
		$db_results = Dba::query($sql); 

		// Delete their tags
		$sql = "DELETE FROM `tag_map` WHERE `user`='$this->id'";
		$db_results = Dba::query($sql);

		// Clean out the tags
		$sql = "DELETE FROM `tags` USING `tag_map` LEFT JOIN `tag_map` ON tag_map.id=tags.map_id AND tag_map.id IS NULL";
		$db_results = Dba::query($sql); 

		// Delete their preferences
		$sql = "DELETE FROM `user_preference` WHERE `user`='$this->id'";
		$db_results = Dba::query($sql);

		// Delete their voted stuff in democratic play
		$sql = "DELETE FROM `user_vote` WHERE `user`='$this->id'";
		$db_results = Dba::query($sql); 

		// Delete the user itself
		$sql = "DELETE FROM `user` WHERE `id`='$this->id'";
		$db_results = Dba::query($sql);

		$sql = "DELETE FROM `session` WHERE `username`='" . Dba::escape($this->username) . "'";
		$db_results = Dba::query($sql);

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
	 * get_recently_played
	 * This gets the recently played items for this user respecting
	 * the limit passed
	 */
	public function get_recently_played($limit,$type='') { 

		if (!$type) { $type = 'song'; } 

		$sql = "SELECT * FROM `object_count` WHERE `object_type`='$type' AND `user`='$this->id' " . 
			"ORDER BY `date` DESC LIMIT $limit"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['object_id'];
		} 

		return $results; 

	} // get_recently_played

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
         * last Config::get('user_ip_cardinality') days
         */             
        public function get_ip_history($count='',$distinct='') { 

		$username 	= Dba::escape($this->id);

		if ($count) { 
			$limit_sql = "LIMIT " . intval($count);
		}
		else { 
			$limit_sql = "LIMIT " . intval(Config::get('user_ip_cardinality'));
		} 
		if ($distinct) { 
			$group_sql = "GROUP BY `ip`";
		}
                        
                /* Select ip history */
                $sql = "SELECT `ip`,`date` FROM `ip_history`" .
                        " WHERE `user`='$username'" .
                        " $group_sql ORDER BY `date` DESC $limit_sql";
                $db_results = Dba::query($sql);

                $results = array();
         
                while ($r = Dba::fetch_assoc($db_results)) {
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

       /*!
                @function is_xmlrpc
                @discussion checks to see if this is a valid
                        xmlrpc user
        */
        function is_xmlrpc() {

                /* If we aren't using XML-RPC return true */
                if (!Config::get('xml_rpc')) {
                        return false;
                }

                //FIXME: Ok really what we will do is check the MD5 of the HTTP_REFERER
                //FIXME: combined with the song title to make sure that the REFERER
                //FIXME: is in the access list with full rights
                return true;

        } // is_xmlrpc

	/**
	 * check_username
	 * This checks to make sure the username passed doesn't already
	 * exist in this instance of ampache
	 */
	public static function check_username($username) { 

		$usrename = Dba::escape($username); 

		$sql = "SELECT `id` FROM `user` WHERE `username`='$username'"; 
		$db_results = Dba::query($sql); 

		if (Dba::num_rows($db_results)) { 
			return false; 
		} 

		return true; 

	} // check_username

	/**
	 * rebuild_all_preferences
	 * This rebuilds the user preferences for all installed users, called by the plugin functions
	 */
	public static function rebuild_all_preferences() { 

		$sql = "SELECT * FROM `user`"; 
		$db_results = Dba::query($sql); 

		User::fix_preferences('-1'); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			User::fix_preferences($row['id']); 
		} 

		return true; 

	} // rebuild_all_preferences
	
} //end user class

?>
