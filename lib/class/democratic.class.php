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
 * Democratic
 * This class handles democratic play, which is a fancy
 * name for voting based playback. This extends the tmpplaylist
 */
class Democratic extends tmpPlaylist {

	/**
	 * get_playlists
	 * This returns all of the current valid 'Democratic' Playlists
	 * that have been created.
	 */
	public static function get_playlists() { 

		// Pull all tmp playlsits with a session of < 0 (as those are fake) 
		// This is kind of hackish, should really think about tweaking the db
		// and doing this right. 
		$sql = "SELECT `id` FROM `tmp_playlist` WHERE `session`< '0'"; 
		$db_results = Dba::query($sql);  

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id']; 
		} 

		return $results; 

	} // get_playlists

	/**
	 * get_current_playlist
	 * This returns the curren users current playlist, or if specified
	 * this current playlist of the user
	 */
	public static function get_current_playlist($user_id='') { 

		// If not passed user global
		$user_id = $user_id ? $user_id : $GLOBALS['user']->id; 

		/* Find the - 1 one for now */
		$sql = "SELECT `id` FROM `tmp_playlist` WHERE `session`='-1'"; 
		$db_results = Dba::query($sql); 
		$row = Dba::fetch_assoc($db_results); 

		$object = new Democratic($row['id']); 

		return $object; 

	} // get_current_playlist

        /**
         * get_items
         * This returns an array of all object_ids currently in this tmpPlaylist. This
         * has gotten a little more complicated because of type, the values are an array
         * 0 being ID 1 being TYPE
	 * FIXME: This is too complex, it makes my brain hurt
	 * [VOTE COUNT] 
	 * 	[DATE OF NEWEST VOTE]
	 *		[ROW ID]
	 * 			[OBJECT_ID]
	 *			[OBJECT_TYPE]
	 *
	 * Sorting does the following
	 * sort largest VOTE COUNT to top
	 * sort smallest DATE OF NEWEST VOTE]
         */
        public function get_items() {

                $order          = "ORDER BY `user_vote`.`date` ASC";
                $vote_join 	= "INNER JOIN `user_vote` ON `user_vote`.`object_id`=`tmp_playlist_data`.`id`";

                /* Select all objects from this playlist */
                $sql = "SELECT `tmp_playlist_data`.`id`,`tmp_playlist_data`.`object_type`, `user_vote`.`date`, `tmp_playlist_data`.`object_id` " .
                        "FROM `tmp_playlist_data` $vote_join " .
                        "WHERE `tmp_playlist_data`.`tmp_playlist`='" . Dba::escape($this->id) . "' $order";
                $db_results = Dba::query($sql);

                /* Define the array */
                $items = array();
		$votes = array(); 
		// Itterate and build the sortable array
                while ($results = Dba::fetch_assoc($db_results)) {

			// First build a variable that holds the number of votes for an object
			$name		= 'vc_' . $results['object_id'];

			// Check if the vote is older then our current vote for this object
			if ($votes[$results['object_id']] < $results['date'] OR !isset($votes[$results['object_id']])) { 
				$votes[$results['object_id']] = $results['date']; 
			} 


			// Append oen to the vote
			${$name}++; 
			$primary_key 	= ${$name}; 
			$secondary_key	= $votes[$results['object_id']]; 
			$items[$primary_key][$secondary_key][$results['id']] = array($results['object_id'],$results['object_type'],$results['id']);
                }

		// Sort highest voted stuff to the top
		krsort($items); 

		// re-collapse the array
		foreach ($items as $vote_count=>$date_array) { 
			ksort($date_array); 
			foreach ($date_array as $object_array) { 
				foreach ($object_array as $key=>$sorted_array) { 
					$sorted_items[$key] = $sorted_array;
				} 
			} 
		} 

                return $sorted_items;

        } // get_items

        /**
         * get_url
         * This returns the special play URL for democratic play, only open to ADMINs
         */
        public function get_url() {

                $link = Config::get('web_path') . '/play/index.php?demo_id=' . scrub_out($this->id) .
                        '&sid=' . Stream::get_session() . '&uid=' . scrub_out($GLOBALS['user']->id);
                return $link;

        } // get_url

        /**             
         * get_next_object
         * This returns the next object in the tmp_playlist most of the time this 
         * will just be the top entry, but if there is a base_playlist and no
         * items in the playlist then it returns a random entry from the base_playlist
         */
        public function get_next_object($offset='') {
        
		$offset = $offset ? intval($offset) : '0'; 

		// We have to get all because of the pysco sorting
		$items = self::get_items(); 

		if (count($items)) { 
			$array = array_slice($items,$offset,1); 
			$item = array_shift($array); 
			$results['object_id'] = $item['0'];
		} 

                /* If nothing was found and this is a voting playlist then get from base_playlist */
                if (!$results['object_id']) {

                        /* Check for a playlist */
                        if ($this->base_playlist) {
                                /* We need to pull a random one from the base_playlist */
                                $base_playlist = new Playlist($this->base_playlist);
				$data = $base_playlist->get_random_items(1);
                                $results['object_id'] = $data['0']['object_id']; 
                        }
                        else {
                                $sql = "SELECT `id` as `object_id` FROM `song` WHERE `enabled`='1' ORDER BY RAND() LIMIT 1";
                                $db_results = Dba::query($sql);
                                $results = Dba::fetch_assoc($db_results);
                        }
                }

                return $results['object_id'];

        } // get_next_object

	/**
	 * get_uid_from_object_id
	 * This takes an object_id and an object type and returns the ID for the row
	 */
	public function get_uid_from_object_id($object_id,$object_type='') { 

		$object_id	= Dba::escape($object_id); 
		$object_type	= $object_type ? Dba::escape($object_type) : 'song'; 
		$tmp_id		= Dba::escape($this->id);

		$sql = "SELECT `tmp_playlist_data`.`id` FROM `tmp_playlist_data` WHERE `object_type`='$object_type' AND " . 
			"`tmp_playlist`='$tmp_id' AND `object_id`='$object_id'"; 
		$db_results = Dba::query($sql); 

		$row = Dba::fetch_assoc($db_results); 

		return $row['id']; 

	} // get_uid_from_object_id

	/**
         * vote
         * This function is called by users to vote on a system wide playlist
         * This adds the specified objects to the tmp_playlist and adds a 'vote' 
         * by this user, naturally it checks to make sure that the user hasn't
         * already voted on any of these objects
         */
        public function vote($items) {

                /* Itterate through the objects if no vote, add to playlist and vote */
                foreach ($items as $type=>$object_id) {
			//FIXME: This is a hack until we fix everything else
			if (intval($type) == $type) { $type = 'song'; } 
                        if (!$this->has_vote($object_id,$type)) {
                                $this->add_vote($object_id,$type);
                        }
                } // end foreach

        } // vote

        /**
         * has_vote
         * This checks to see if the current user has already voted on this object
         */
        public function has_vote($object_id,$type='') {

                $tmp_id		= Dba::escape($this->id);
		$object_id 	= Dba::escape($object_id); 
		$type		= $type ? Dba::escape($type) : 'song'; 
		$user_id	= Dba::escape($GLOBALS['user']->id); 

                /* Query vote table */
                $sql = "SELECT tmp_playlist_data.object_id FROM `user_vote` " .
                        "INNER JOIN tmp_playlist_data ON tmp_playlist_data.id=user_vote.object_id " .
                        "WHERE user_vote.user='$user_id' AND tmp_playlist_data.object_type='$type' " .
                        "AND tmp_playlist_data.object_id='$object_id' " .
                        "AND tmp_playlist_data.tmp_playlist='$tmp_id'";
                $db_results = Dba::query($sql);

                /* If we find  row, they've voted!! */
                if (Dba::num_rows($db_results)) {
                        return true;
                }

                return false;

        } // has_vote

        /**
         * add_vote
         * This takes a object id and user and actually inserts the row
         */
        public function add_vote($object_id,$object_type='') {
        
                $object_id      = Dba::escape($object_id);
                $tmp_playlist   = Dba::escape($this->id);
		$object_type	= $object_type ? Dba::escape($object_type) : 'song'; 
                
                /* If it's on the playlist just vote */
                $sql = "SELECT `id` FROM `tmp_playlist_data` " .
                        "WHERE `tmp_playlist_data`.`object_id`='$object_id' AND `tmp_playlist_data`.`tmp_playlist`='$tmp_playlist'";
                $db_results = Dba::query($sql);

                /* If it's not there, add it and pull ID */
                if (!$results = Dba::fetch_assoc($db_results)) {
                        $sql = "INSERT INTO `tmp_playlist_data` (`tmp_playlist`,`object_id`,`object_type`) " .
                                "VALUES ('$tmp_playlist','$object_id','$object_type')";
                        $db_results = Dba::query($sql);
                        $results['id'] = Dba::insert_id();
                }

                /* Vote! */
                $time = time();
                $sql = "INSERT INTO user_vote (`user`,`object_id`,`date`) " .
                        "VALUES ('" . Dba::escape($GLOBALS['user']->id) . "','" . $results['id'] . "','$time')";
                $db_results = Dba::query($sql);

                return true;

        } // add_vote

        /**
         * remove_vote
         * This is called to remove a vote by a user for an object, it uses the object_id
         * As that's what we'll have most the time, no need to check if they've got an existing
         * vote for this, just remove anything that is there
         */
        public function remove_vote($row_id) {

                $object_id      = Dba::escape($row_id);
                $user_id        = Dba::escape($GLOBALS['user']->id);

		$sql = "DELETE FROM `user_vote` WHERE `object_id`='$object_id'";
                $db_results = Dba::query($sql);

                /* Clean up anything that has no votes */
                self::prune_tracks();

                return true;

        } // remove_vote

	/**
	 * delete_votes
	 * This removes the votes for the specified object on the current playlist
	 */
	public function delete_votes($row_id) { 

		$row_id		= Dba::escape($row_id); 

		$sql = "DELETE FROM `user_vote` WHERE `object_id`='$row_id'"; 
		$db_results = Dba::query($sql); 

		$sql = "DELETE FROM `tmp_playlist_data` WHERE `id`='$row_id'"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // delete_votes

	/**
	 * prune_tracks
	 * This replaces the normal prune tracks and correctly removes the votes
	 * as well
	 */
	public static function prune_tracks() { 

                // This deletes data without votes, if it's a voting democratic playlist
                $sql = "DELETE FROM tmp_playlist_data USING tmp_playlist_data " .
                        "LEFT JOIN user_vote ON tmp_playlist_data.id=user_vote.object_id " .
                        "LEFT JOIN tmp_playlist ON tmp_playlist.id=tmp_playlist.tmp_playlist " .
                        "WHERE user_vote.object_id IS NULL AND tmp_playlist.type = 'vote'";
                $db_results = Dba::query($sql);

                return true;

	} // prune_tracks

} // Democratic class
?>
