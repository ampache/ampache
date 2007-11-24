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
         */
        public function get_items() {

                $order          = "GROUP BY tmp_playlist_data.id ORDER BY `count` DESC, user_vote.date ASC";
        	$vote_select 	= ", COUNT(user_vote.user) AS `count`";
                $vote_join 	= "INNER JOIN user_vote ON user_vote.object_id=tmp_playlist_data.id";

                /* Select all objects from this playlist */
                $sql = "SELECT tmp_playlist_data.object_type, tmp_playlist_data.id, tmp_playlist_data.object_id $vote_select " .
                        "FROM tmp_playlist_data $vote_join " .
                        "WHERE tmp_playlist_data.tmp_playlist='" . Dba::escape($this->id) . "' $order";
                $db_results = Dba::query($sql);

                /* Define the array */
                $items = array();
                while ($results = Dba::fetch_assoc($db_results)) {
                        $key            = $results['id'];
                        $items[$key]    = array($results['object_id'],$results['object_type']);
                }

                return $items;

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
        
                $tmp_id = Dba::escape($this->id);
		
		// Format the limit statement
		$limit_sql = $offset ? intval($offset) . ',1' : '1'; 
                
		/* Add conditions for voting */
                $vote_select = ", COUNT(user_vote.user) AS `count`";
                $order = " GROUP BY tmp_playlist_data.id ORDER BY `count` DESC, user_vote.date ASC";
                $vote_join = "INNER JOIN user_vote ON user_vote.object_id=tmp_playlist_data.id";

                $sql = "SELECT tmp_playlist_data.object_id $vote_select FROM tmp_playlist_data $vote_join " .
                        "WHERE tmp_playlist_data.tmp_playlist = '$tmp_id' $order LIMIT $limit_sql";
                $db_results = Dba::query($sql);

                $results = Dba::fetch_assoc($db_results);

                /* If nothing was found and this is a voting playlist then get from base_playlist */
                if (!$results) {

                        /* Check for a playlist */
                        if ($this->base_playlist != '0') {
                                /* We need to pull a random one from the base_playlist */
                                $base_playlist = new Playlist($this->base_playlist);
                                $data = $base_playlist->get_random_songs(1);
                                $results['object_id'] = $data['0'];
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
         * vote
         * This function is called by users to vote on a system wide playlist
         * This adds the specified objects to the tmp_playlist and adds a 'vote' 
         * by this user, naturally it checks to make sure that the user hasn't
         * already voted on any of these objects
         */
        public function vote($items) {

                /* Itterate through the objects if no vote, add to playlist and vote */
                foreach ($items as $type=>$object_id) {
                        if (!$this->has_vote($object_id,$type)) {
                                $this->add_vote($object_id,$this->id,$type);
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
                $sql = "SELECT tmp_playlist_data.id FROM `user_vote` " .
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
        public function add_vote($object_id,$tmp_playlist,$object_type='') {
        
                $object_id      = Dba::escape($object_id);
                $tmp_playlist   = Dba::escape($tmp_playlist);
		$object_type	= $object_type ? Dba::escape($object_type) : 'song'; 
                
                /* If it's on the playlist just vote */
                $sql = "SELECT `id` FROM `tmp_playlist_data` " .
                        "WHERE `tmp_playlist_data`.`object_id`='$object_id'";
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
        public function remove_vote($object_id) {

                $object_id      = Dba::escape($object_id);
                $user_id        = Dba::escape($GLOBALS['user']->id);

                $sql = "DELETE FROM user_vote USING user_vote INNER JOIN tmp_playlist_data ON tmp_playlist_data.id=user_vote.object_id " .
                        "WHERE user='$user_id' AND tmp_playlist_data.object_id='$object_id' " .
                        "AND tmp_playlist_data.tmp_playlist='" . Dba::escape($this->id) . "'";
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


} // Democratic class
?>
