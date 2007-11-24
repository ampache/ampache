<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/**
 * TempPlaylist Class
 * This class handles the temporary playlists in ampache, it handles the
 * tmp_playlist and tmp_playlist_data tables, and sneaks out at night to 
 * visit user_vote from time to time
 */
class tmpPlaylist { 

	/* Variables from the Datbase */
	public $id;
	public $session;
	public $type;
	public $object_type;
	public $base_playlist;

	/* Generated Elements */
	public $items = array(); 

	/**
	 * Constructor 
	 * This takes a playlist_id as an optional argument and gathers the information
	 * if not playlist_id is passed returns false (or if it isn't found 
	 */
	public function __construct($playlist_id='') { 

		if (!$playlist_id) { return false; }
		
		$this->id 	= intval($playlist_id);
		$info 		= $this->_get_info();

		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} 

		return true;

	} // __construct

	/** 
	 * _get_info
	 * This is an internal (private) function that gathers the information for this object from the 
	 * playlist_id that was passed in. 
	 */
	private function _get_info() { 

		$sql = "SELECT * FROM `tmp_playlist` WHERE `id`='" . Dba::escape($this->id) . "'";	
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);

		return $results;

	} // _get_info

	/**
	 * get_from_session
	 * This returns a playlist object based on the session that is passed to us
	 * this is used by the load_playlist on user for the most part
	 */
	public static function get_from_session($session_id) { 

		$session_id = Dba::escape($session_id); 

		$sql = "SELECT `id` FROM `tmp_playlist` WHERE `session`='$session_id'"; 
		$db_results = Dba::query($sql); 

		$results = Dba::fetch_row($db_results); 
		
		if (!$results['0']) { 
			$results['0'] = tmpPlaylist::create($session_id,'user','song','0'); 
		} 

		$playlist = new tmpPlaylist($results['0']); 

		return $playlist; 

	} // get_from_session

	/**
	 * get_from_userid
	 * This returns a tmp playlist object based on a userid passed
	 * this is used for the user profiles page
	 */
	public static function get_from_userid($user_id) { 

		// This is a little stupid, because we don't have the user_id in the session or
		// in the tmp_playlist table we have to do it this way.
		$client = new User($user_id); 
		$username = Dba::escape($client->username); 

		$sql = "SELECT `tmp_playlist`.`id` FROM `tmp_playlist` LEFT JOIN `session` ON `session`.`id`=`tmp_playlist`.`session` " . 
			" WHERE `session`.`username`='$username' ORDER BY `session`.`expire` DESC"; 
		$db_results = Dba::query($sql); 

		$data = Dba::fetch_assoc($db_results); 
		
		return $data['id']; 

	} // get_from_userid

	/**
	 * get_items
	 * This returns an array of all object_ids currently in this tmpPlaylist. This
	 * has gotten a little more complicated because of type, the values are an array
	 * 0 being ID 1 being TYPE
	 */
	public function get_items() { 

		$order = 'ORDER BY id ASC';
		
		/* Select all objects from this playlist */
		$sql = "SELECT tmp_playlist_data.object_type, tmp_playlist_data.id, tmp_playlist_data.object_id $vote_select " . 
			"FROM tmp_playlist_data $vote_join " . 
			"WHERE tmp_playlist_data.tmp_playlist='" . Dba::escape($this->id) . "' $order";
		$db_results = Dba::query($sql);
		
		/* Define the array */
		$items = array();

		while ($results = Dba::fetch_assoc($db_results)) { 
			$key		= $results['id'];
			$items[$key] 	= array($results['object_id'],$results['object_type']);
		}

		return $items;

	} // get_items

	/**
	 * get_next_object
	 * This returns the next object in the tmp_playlist most of the time this 
	 * will just be the top entry, but if there is a base_playlist and no
	 * items in the playlist then it returns a random entry from the base_playlist
	 */
	public function get_next_object() { 

		$tmp_id = Dba::escape($this->id);
		$order = " ORDER BY tmp_playlist_data.id DESC";

		/* Check for an item on the playlist, account for voting */
		if ($this->type == 'vote') { 
			/* Add conditions for voting */	
			$vote_select = ", COUNT(user_vote.user) AS `count`";
			$order = " GROUP BY tmp_playlist_data.id ORDER BY `count` DESC, user_vote.date ASC";
			$vote_join = "INNER JOIN user_vote ON user_vote.object_id=tmp_playlist_data.id";
		}

		$sql = "SELECT tmp_playlist_data.object_id $vote_select FROM tmp_playlist_data $vote_join " . 
			"WHERE tmp_playlist_data.tmp_playlist = '$tmp_id' $order LIMIT 1";
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);

		/* If nothing was found and this is a voting playlist then get from base_playlist */
		if ($this->type == 'vote' AND !$results) { 

			/* Check for a playlist */
			if ($this->base_playlist != '0') { 
				/* We need to pull a random one from the base_playlist */
				$base_playlist = new playlist($this->base_playlist);
				$data = $base_playlist->get_random_songs(1);
				$results['object_id'] = $data['0'];	
			}
			else { 
				$sql = "SELECT id as `object_id` FROM song WHERE enabled='1' ORDER BY RAND() LIMIT 1"; 
				$db_results = Dba::query($sql); 
				$results = Dba::fetch_assoc($db_results); 
			}
		}

		return $results['object_id'];

	} // get_next_object

	/**
	 * get_vote_url
	 * This returns the special play URL for democratic play, only open to ADMINs
	 */
	public function get_vote_url() { 

		$link = Config::get('web_path') . '/play/index.php?tmp_id=' . scrub_out($this->id) . 
			'&amp;sid=' . scrub_out(session_id()) . '&amp;uid=' . scrub_out($GLOBALS['user']->id);
		
		return $link;

	} // get_vote_url

	/**
	 * count_items
	 * This returns a count of the total number of tracks that are in this tmp playlist
	 */
	public function count_items() { 

		$sql = "SELECT COUNT(`id`) FROM `tmp_playlist_data` WHERE `tmp_playlist_data`.`tmp_playlist`='" . $this->id . "'"; 
		$db_results = Dba::query($sql); 

		$results = Dba::fetch_row($db_results); 

		return $results['0']; 

	} // count_items

	/**
 	 * clear
	 * This clears all the objects out of a single playlist
	 */
	public function clear() { 

		$sql = "DELETE FROM `tmp_playlist_data` WHERE `tmp_playlist_data`.`tmp_playlist`='" . $this->id . "'"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // clear

	/** 
	 * create
	 * This function initializes a new tmpPlaylist it is assoicated with the current
	 * session rather then a user, as you could have same user multiple locations
	 */
	public static function create($sessid,$type,$object_type,$base_playlist) { 

		$sessid 	= Dba::escape($sessid);
		$type		= Dba::escape($type);
		$object_type	= Dba::escape($object_type);
		$base_playlist	= Dba::escape($base_playlist);

		$sql = "INSERT INTO `tmp_playlist` (`session`,`type`,`object_type`,`base_playlist`) " . 
			" VALUES ('$sessid','$type','$object_type','$base_playlist')";
		$db_results = Dba::query($sql);

		$id = Dba::insert_id();

		/* Prune dead tmp_playlists */
		self::prune_playlists();

		/* Clean any other playlists assoicated with this session */
		self::delete($sessid,$id);

		return $id;

	} // create 

	/**
	 * update_playlist
	 * This updates the base_playlist on this tmp_playlist
	 */
	public function update_playlist($playlist_id) { 

		$playlist_id 	= Dba::escape($playlist_id);
		$tmp_id		= Dba::escape($this->id);

		$sql = "UPDATE `tmp_playlist` SET tmp_playlist.base_playlist='$playlist_id' WHERE `id`='$tmp_id'";
		$db_results = Dba::query($sql);

		return true;

	} // update_playlist

	/**
	 * delete
	 * This deletes any other tmp_playlists assoicated with this
	 * session 
	 */
	public static function delete($sessid,$id) { 

		$sessid = Dba::escape($sessid);
		$id	= Dba::escape($id);

		$sql = "DELETE FROM `tmp_playlist` WHERE `session`='$sessid' AND `id` != '$id'";
		$db_results = Dba::query($sql);

		/* Remove assoicated tracks */
		self::prune_tracks();

		return true;

	} // delete

	/**
	 * prune_playlists
	 * This deletes and playlists that don't have an assoicated session
	 */
	public static function prune_playlists() { 

		/* Just delete if no matching session row */
		$sql = "DELETE FROM `tmp_playlist` USING `tmp_playlist` " . 
			"LEFT JOIN session ON session.id=tmp_playlist.session " . 
			"WHERE session.id IS NULL AND tmp_playlist.session != '-1'";
		$db_results = Dba::query($sql);

		return true;

	} // prune_playlists

	/**
	 * prune_tracks
	 * This prunes tracks that don't have playlists or don't have votes 
	 */
	public static function prune_tracks() { 

		// This prue is always run clears data for playlists that don't have tmp_playlist anymore
		$sql = "DELETE FROM tmp_playlist_data USING tmp_playlist_data " . 
			"LEFT JOIN tmp_playlist ON tmp_playlist_data.tmp_playlist=tmp_playlist.id " . 
			"WHERE tmp_playlist.id IS NULL";
		$db_results = Dba::query($sql);

		// If we don't allow it, don't waste the time
		if (!Config::get('allow_democratic_playback')) { return true; } 
	
		// This deletes data without votes, if it's a voting democratic playlist
		$sql = "DELETE FROM tmp_playlist_data USING tmp_playlist_data " . 
			"LEFT JOIN user_vote ON tmp_playlist_data.id=user_vote.object_id " . 
			"LEFT JOIN tmp_playlist ON tmp_playlist.id=tmp_playlist.tmp_playlist " . 
			"WHERE user_vote.object_id IS NULL AND tmp_playlist.type = 'vote'";
		$db_results = Dba::query($sql);

		return true; 

	} // prune_tracks

	/**
	 * add_object
	 * This adds the object of $this->object_type to this tmp playlist
	 * it takes an optional type, default is song
	 */
	public function add_object($object_id,$object_type) { 

		$object_id 	= Dba::escape($object_id);
		$playlist_id 	= Dba::escape($this->id);
		$object_type	= $object_type ? Dba::escape($object_type) : 'song'; 

		$sql = "INSERT INTO `tmp_playlist_data` (`object_id`,`tmp_playlist`,`object_type`) " . 
			" VALUES ('$object_id','$playlist_id','$object_type')";
		$db_results = Dba::query($sql);

		return true;

	} // add_object

	/**
	 * get_vote
	 * This returns the current count for a specific song on this tmp_playlist
	 */
	public function get_vote($object_id) { 

		$object_id = Dba::escape($object_id);

		$sql = "SELECT COUNT(`user`) AS `count` FROM user_vote " . 
			" WHERE object_id='$object_id'";
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);

		return $results['count'];

	} // get_vote

	/**
	 * vote_active
	 * This checks to see if this playlist is a voting playlist
	 * and if it is active 
	 */
	public function vote_active() { 

		/* Going to do a little more here later */
		if ($this->type == 'vote') { return true; } 

		return false;

	} // vote_active

	/**
	 * remove_vote
	 * This is called to remove a vote by a user for an object, it uses the object_id
	 * As that's what we'll have most the time, no need to check if they've got an existing
	 * vote for this, just remove anything that is there
	 */
	public function remove_vote($object_id) { 

		$object_id 	= Dba::escape($object_id);
		$user_id	= Dba::escape($GLOBALS['user']->id); 	

		$sql = "DELETE FROM user_vote USING user_vote INNER JOIN tmp_playlist_data ON tmp_playlist_data.id=user_vote.object_id " . 
			"WHERE user='$user_id' AND tmp_playlist_data.object_id='$object_id' " . 
			"AND tmp_playlist_data.tmp_playlist='" . Dba::escape($this->id) . "'";
		$db_results = Dba::query($sql);
		
		/* Clean up anything that has no votes */
		self::prune_tracks();

		return true;

	} // remove_vote

	/**
	 * delete_track
	 * This deletes a track and any assoicated votes, we only check for
	 * votes if it's vote playlist, id is a object_id
	 */
	public function delete_track($id) { 

		$id 	= Dba::escape($id);

		/* delete the track its self */
		$sql = "DELETE FROM `tmp_playlist_data` " . 
			" WHERE `id`='$id'";
		$db_results = Dba::query($sql);

		/* If this is a voting playlit prune votes */
		if ($this->type == 'vote') { 
			$sql = "DELETE FROM user_vote USING user_vote " . 
				"LEFT JOIN tmp_playlist_data ON user_vote.object_id = tmp_playlist_data.id " .
				"WHERE tmp_playlist_data.id IS NULL";
			$db_results = Dba::query($sql);
		} 

		return true;

	} // delete_track

	/**
	 * clear_playlist
	 * This is really just a wrapper function, it clears the entire playlist
	 * including all votes etc. 
	 */
	public function clear_playlist() { 

		$tmp_id	= Dba::escape($this->id); 

		/* Clear all votes then prune */
		$sql = "DELETE FROM user_vote USING user_vote " . 
			"LEFT JOIN tmp_playlist_data ON user_vote.object_id = tmp_playlist_data.id " . 
			"WHERE tmp_playlist_data.tmp_playlist='$tmp_id'";
		$db_results = Dba::query($sql); 

		// Prune!
		self::prune_tracks(); 

		return true; 

	} // clear_playlist


} // class tmpPlaylist
