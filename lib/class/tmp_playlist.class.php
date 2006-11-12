<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
	var $id;
	var $session;
	var $type;
	var $object_type;
	var $base_playlist;

	/* Generated Elements */
	var $items = array(); 


	/**
	 * Constructor 
	 * This takes a playlist_id as an optional argument and gathers the information
	 * if not playlist_id is passed returns false (or if it isn't found 
	 */
	function tmpPlaylist($playlist_id='') { 

		if (!$playlist_id) { return false; }
		
		$this->id 	= intval($playlist_id);
		$info 		= $this->_get_info();

		/* If we get something back */
		if (count($info)) { 
			$this->session		= $info['session'];
			$this->type		= $info['type'];
			$this->object_type 	= $info['object_type'];
			$this->base_playlist	= $info['base_playlist'];
		} 

		return true;

	} // tmpPlaylist

	/** 
	 * _get_info
	 * This is an internal (private) function that gathers the information for this object from the 
	 * playlist_id that was passed in. 
	 */
	function _get_info() { 

		$sql = "SELECT * FROM tmp_playlist WHERE id='" . sql_escape($this->id) . "'";	
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_assoc($db_results);

		return $results;

	} // _get_info

	/**
	 * get_items
	 * This returns an array of all object_ids currently in this tmpPlaylist
	 */
	function get_items() { 

		$order = 'ORDER BY id ASC';
		
		if ($this->type == 'vote') { 
			$order 		= "GROUP BY tmp_playlist_data.id ORDER BY `count` DESC";
			$vote_select = ", COUNT(user_vote.user) AS `count`";
			$vote_join = "LEFT JOIN user_vote ON user_vote.object_id=tmp_playlist_data.id";
		}

		/* Select all objects from this playlist */
		$sql = "SELECT tmp_playlist_data.id, tmp_playlist_data.object_id $vote_select FROM tmp_playlist_data $vote_join " . 
			"WHERE tmp_playlist_data.tmp_playlist='" . sql_escape($this->id) . "' $order";
		$db_results = mysql_query($sql, dbh());
		
		/* Define the array */
		$items = array();

		while ($results = mysql_fetch_assoc($db_results)) { 
			$key		= $results['id'];
			$items[$key] 	= $results['object_id'];
		}

		return $items;

	} // get_items

	/**
	 * get_next_object
	 * This returns the next object in the tmp_playlist most of the time this 
	 * will just be the top entry, but if there is a base_playlist and no
	 * items in the playlist then it returns a random entry from the base_playlist
	 */
	function get_next_object() { 

		$tmp_id = sql_escape($this->id);
		$order = " ORDER BY tmp_playlist_data.id DESC";

		/* Check for an item on the playlist, account for voting */
		if ($this->type == 'vote') { 
			/* Add conditions for voting */	
			$vote_select = ", COUNT(user_vote.user) AS `count`";
			$order = " GROUP BY tmp_playlist_data.id ORDER BY `count` DESC";
			$vote_join = "LEFT JOIN user_vote ON user_vote.object_id=tmp_playlist_data.id";
		}

		$sql = "SELECT tmp_playlist_data.object_id $vote_select FROM tmp_playlist_data $vote_join " . 
			"WHERE tmp_playlist_data.tmp_playlist = '$tmp_id' $order LIMIT 1";
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_assoc($db_results);

		/* If nothing was found and this is a voting playlist then get from base_playlist */
		if ($this->type == 'vote' AND !$results) { 
			/* We need to pull a random one from the base_playlist */
			$base_playlist = new playlist($this->base_playlist);
			$data = $base_playlist->get_random_songs(1);
			$results['object_id'] = $data['0'];	
		}

		return $results['object_id'];

	} // get_next_object

	/**
	 * get_vote_url
	 * This returns the special play URL for democratic play, only open to ADMINs
	 */
	function get_vote_url() { 

		$link = conf('web_path') . '/play/index.php?tmp_id=' . scrub_out($this->id) . 
			'&amp;sid=' . scrub_out(session_id()) . '&amp;uid=' . scrub_out($GLOBALS['user']->id);
		
		return $link;

	} // get_vote_url

	/** 
	 * create
	 * This function initializes a new tmpPlaylist it is assoicated with the current
	 * session rather then a user, as you could have same user multiple locations
	 */
	function create($sessid,$type,$object_type,$base_playlist) { 

		$sessid 	= sql_escape($sessid);
		$type		= sql_escape($type);
		$object_type	= sql_escape($object_type);
		$base_playlist	= sql_escape($base_playlist);

		$sql = "INSERT INTO tmp_playlist (`session`,`type`,`object_type`,`base_playlist`) " . 
			" VALUES ('$sessid','$type','$object_type','$base_playlist')";
		$db_results = mysql_query($sql, dbh());

		$id = mysql_insert_id(dbh());

		/* Prune dead tmp_playlists */
		$this->prune_playlists();

		/* Clean any other playlists assoicated with this session */
		$this->delete($sessid,$id);

		return $id;

	} // create 

	/**
	 * update_playlist
	 * This updates the base_playlist on this tmp_playlist
	 */
	function update_playlist($playlist_id) { 

		$playlist_id 	= sql_escape($playlist_id);
		$tmp_id		= sql_escape($this->id);

		$sql = "UPDATE tmp_playlist SET tmp_playlist.base_playlist='$playlist_id' WHERE id='$tmp_id'";
		$db_results = mysql_query($sql,dbh());

		return true;

	} // update_playlist

	/**
	 * delete
	 * This deletes any other tmp_playlists assoicated with this
	 * session 
	 */
	function delete($sessid,$id) { 

		$sessid = sql_escape($sessid);
		$id	= sql_escape($id);

		$sql = "DELETE FROM tmp_playlist WHERE session='$sessid' AND id != '$id'";
		$db_results = mysql_query($sql,dbh());

		/* Remove assoicated tracks */
		$this->prune_tracks();

		return true;

	} // delete

	/**
	 * prune_playlists
	 * This deletes and playlists that don't have an assoicated session
	 */
	function prune_playlists() { 

		/* Just delete if no matching session row */
		$sql = "DELETE FROM tmp_playlist USING tmp_playlist " . 
			"LEFT JOIN session ON session.id=tmp_playlist.session " . 
			"WHERE session.id IS NULL AND tmp_playlist.session != '-1'";
		$db_results = mysql_query($sql,dbh());

		return true;

	} // prune_playlists

	/**
	 * prune_tracks
	 * This prunes tracks that don't have playlists or don't have votes 
	 */
	function prune_tracks() { 

		$sql = "DELETE FROM tmp_playlist_data USING tmp_playlist_data " . 
			"LEFT JOIN tmp_playlist ON tmp_playlist_data.tmp_playlist=tmp_playlist.id " . 
			"WHERE tmp_playlist.id IS NULL";
		$db_results = mysql_query($sql,dbh());

		$sql = "DELETE FROM tmp_playlist_data USING tmp_playlist_data " . 
			"LEFT JOIN user_vote ON tmp_playlist_data.id=user_vote.object_id " . 
			"WHERE user_vote.object_id IS NULL";
		$db_results = mysql_query($sql,dbh());

		return true; 

	} // prune_tracks

	/**
	 * add_object
	 * This adds the object of $this->object_type to this tmp playlist
	 */
	function add_object($object_id) { 

		$object_id 	= sql_escape($object_id);
		$playlist_id 	= sql_escape($this->id);

		$sql = "INSERT INTO tmp_playlist_data (`object_id`,`tmp_playlist`) " . 
			" VALUES ('$object_id','$playlist_id')";
		$db_results = mysql_query($sql, dbh());

		return true;

	} // add_object

	/**
	 * vote
	 * This function is called by users to vote on a system wide playlist
	 * This adds the specified objects to the tmp_playlist and adds a 'vote' 
	 * by this user, naturally it checks to make sure that the user hasn't
	 * already voted on any of these objects
	 */
	function vote($items) { 

		/* Itterate through the objects if no vote, add to playlist and vote */
		foreach ($items as $object_id) { 
			if (!$this->has_vote($object_id)) { 
				$this->add_vote($object_id,$this->id);
			}
		} // end foreach


	} // vote

	/**
	 * add_vote
	 * This takes a object id and user and actually inserts the row
	 */
	function add_vote($object_id,$tmp_playlist) { 

		$object_id 	= sql_escape($object_id);
		$tmp_playlist	= sql_escape($tmp_playlist);

		/* If it's on the playlist just vote */
		$sql = "SELECT id FROM tmp_playlist_data " . 
			"WHERE tmp_playlist_data.object_id='$object_id'";
		$db_results = mysql_query($sql, dbh());

		/* If it's not there, add it and pull ID */
		if (!$results = mysql_fetch_assoc($db_results)) { 
			$sql = "INSERT INTO tmp_playlist_data (`tmp_playlist`,`object_id`) " . 
				"VALUES ('$tmp_playlist','$object_id')";
			$db_results = mysql_query($sql, dbh());
			$results['id'] = mysql_insert_id(dbh());
		} 

		/* Vote! */
		$sql = "INSERT INTO user_vote (`user`,`object_id`) " . 
			"VALUES ('" . sql_escape($GLOBALS['user']->id) . "','" . $results['id'] . "')";
		$db_results = mysql_query($sql, dbh());

		return true;

	} // add_vote
	
	/**
	 * has_vote
	 * This checks to see if the current user has already voted on this object
	 */
	function has_vote($object_id) { 

		$tmp_id = sql_escape($this->id);

		/* Query vote table */
		$sql = "SELECT tmp_playlist_data.id FROM user_vote " . 
			"INNER JOIN tmp_playlist_data ON tmp_playlist_data.id=user_vote.object_id " . 
			"WHERE user_vote.user='" . sql_escape($GLOBALS['user']->id) . "' " . 
			"AND tmp_playlist_data.object_id='" . sql_escape($object_id) . "' " . 
			"AND tmp_playlist_data.tmp_playlist='$tmp_id'";
		$db_results = mysql_query($sql, dbh());
		
		/* If we find  row, they've voted!! */
		if (mysql_num_rows($db_results)) { 
			return true; 
		}

		return false;		

	} // has_vote

	/**
	 * get_vote
	 * This returns the current count for a specific song on this tmp_playlist
	 */
	function get_vote($object_id) { 

		$object_id = sql_escape($object_id);

		$sql = "SELECT COUNT(`user`) AS `count` FROM user_vote " . 
			" WHERE object_id='$object_id'";
		$db_results = mysql_query($sql,dbh());

		$results = mysql_fetch_assoc($db_results);

		return $results['count'];

	} // get_vote

	/**
	 * vote_active
	 * This checks to see if this playlist is a voting playlist
	 * and if it is active 
	 */
	function vote_active() { 

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
	function remove_vote($object_id) { 

		$object_id 	= sql_escape($object_id);
		$user_id	= sql_escape($GLOBALS['user']->id); 	

		$sql = "DELETE FROM user_vote USING user_vote INNER JOIN tmp_playlist_data ON tmp_playlist_data.id=user_vote.object_id " . 
			"WHERE user='$user_id' AND tmp_playlist_data.object_id='$object_id' " . 
			"AND tmp_playlist_data.tmp_playlist='" . sql_escape($this->id) . "'";
		$db_results = mysql_query($sql,dbh());
		
		/* Clean up anything that has no votes */
		$this->prune_tracks();

		return true;

	} // remove_vote

	/**
	 * delete_track
	 * This deletes a track and any assoicated votes, we only check for
	 * votes if it's vote playlist, id is a object_id
	 */
	function delete_track($id) { 

		$id 	= sql_escape($id);
		$tmp_id = sql_escape($this->id);

		/* delete the track its self */
		$sql = "DELETE FROM tmp_playlist_data " . 
			" WHERE tmp_playlist='$tmp_id' AND object_id='$id'";
		$db_results = mysql_query($sql,dbh());

		/* If this is a voting playlit prune votes */
		if ($this->type == 'vote') { 
			$sql = "DELETE FROM user_vote USING user_vote " . 
				"LEFT JOIN tmp_playlist_data ON user_vote.object_id = tmp_playlist_data.id " .
				"WHERE tmp_playlist_data.id IS NULL";
			$db_results = mysql_query($sql,dbh());
		} 

		return true;

	} // delete_track


} // class tmpPlaylist
