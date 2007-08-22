<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
/**
 * Playlist Class
 * This class handles playlists in ampache. it references the playlist* tables
 */
class Playlist { 

	/* Variables from the Datbase */
	public $id;
	public $name;
	public $user;
	public $type;
	public $genre; 
	public $date;

	/* Generated Elements */
	public $items = array();

	/**
	 * Constructor 
	 * This takes a playlist_id as an optional argument and gathers the information
	 * if not playlist_id is passed returns false (or if it isn't found 
	 */
	public function __construct($id) { 

		$this->id 	= intval($id);
		$info 		= $this->_get_info();

		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} 
	
	} // Playlist

	/** 
	 * _get_info
	 * This is an internal (private) function that gathers the information for this object from the 
	 * playlist_id that was passed in. 
	 */
	private function _get_info() { 

		$sql = "SELECT * FROM `playlist` WHERE `id`='" . Dba::escape($this->id) . "'";	
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);

		return $results;

	} // _get_info

	/**
	 * format
	 * This takes the current playlist object and gussies it up a little
	 * bit so it is presentable to the users
	 */
	public function format() { 

		$this->f_name =  truncate_with_ellipsis($this->name,Config::get('ellipse_threshold_title'));
		$this->f_link = '<a href="' . Config::get('web_path') . '/playlist.php?action=show_playlist&amp;playlist_id=' . $this->id . '">' . $this->f_name . '</a>'; 

		$client = new User($this->user); 

		$this->f_user = $client->fullname; 

	} // format

	/**
	 * get_track
	 * Takes a playlist_data.id and returns the current track value for said entry
	 */
	function get_track($id) { 

		$sql = "SELECT track FROM playlist_data WHERE id='" . sql_escape($id) . "'";
		$db_results = mysql_query($sql, dbh());

		$result = mysql_fetch_assoc($db_results);

		return $result['track'];

	} // get_track

	/**
	 * get_items
	 * This returns an array of playlist songs that are in this playlist. Because the same
	 * song can be on the same playlist twice they are key'd by the uid from playlist_data
	 */
	public function get_items() { 

		$results = array(); 

		$sql = "SELECT `object_id`,`object_type`,`dynamic_song`,`track` FROM `playlist_data` WHERE `playlist`='" . Dba::escape($this->id) . "' ORDER BY `track`";
		$db_results = Dba::query($sql);

		while ($row = Dba::fetch_assoc($db_results)) { 

			if (strlen($row['dynamic_song'])) { 
				// Do something here FIXME!
			} 

			$results[] = array('type'=>$row['object_type'],'object_id'=>$row['object_id'],'track'=>$row['track']); 
		} // end while

		return $results;

	} // get_items

	/**
	 * get_random_items
	 * This is the same as before but we randomize the buggers!
	 */
	public function get_random_items() { 

		$results = array(); 

		$sql = "SELECT `object_id`,`object_type`,`dynamic_song` FROM `playlist_data` " . 
			"WHERE `playlist`='" . Dba::escape($this->id) . "' ORDER BY RAND()"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 

			if (strlen($row['dynamic_song'])) { 
				// Do something here FIXME!!!
			} 

                        $results[] = array('type'=>$row['object_type'],'object_id'=>$row['object_id']);
                } // end while

                return $results;

	} // get_random_items

	/**
	 * get_songs
	 * This is called by the batch script, because we can't pass in Dynamic objects they pulled once and then their
	 * target song.id is pushed into the array
	 */
	function get_songs() { 

		$results = array();

		$sql = "SELECT * FROM `playlist_data` WHERE `playlist`='" . Dba::escape($this->id) . "' ORDER BY `track`";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) { 
			if ($r['dyn_song']) { 
				$array = $this->get_dyn_songs($r['dyn_song']);
				$results = array_merge($array,$results);
			}
			else { 
				$results[] = $r['object_id'];
			} 

		} // end while

		return $results;

	} // get_songs

	/**
 	 * get_dyn_songs
	 * This returns an array of song_ids for a single dynamic playlist entry
	 */
	function get_dyn_songs($dyn_string) { 

		/* Ok honestly I know this is risky, so we have to be
		 * 100% sure that the user never got to touch this. This
		 * Query has to return id which must be a song.id
		 */
		$db_results = mysql_query($dyn_string, dbh());
		$results = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		} // end while

		return $results;

	} // get_dyn_songs

	/**
	 * get_song_count
	 * This simply returns a int of how many song elements exist in this playlist
	 * For now let's consider a dyn_song a single entry
	 */
	function get_song_count() { 

		$sql = "SELECT COUNT(`id`) FROM `playlist_data` WHERE `playlist`='" . Dba::escape($this->id) . "'";
		$db_results = Dba::query($sql);

		$results = Dba::fetch_row($db_results);

		return $results['0'];

	} // get_song_count

	/**
	 * get_users
	 * This returns the specified users playlists as an array of
	 * playlist ids
	 */
	public static function get_users($user_id) { 

		$user_id = Dba::escape($user_id); 
		$results = array(); 

		$sql = "SELECT `id` FROM `playlist` WHERE `user`='$user_id'"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id']; 
		} 

		return $results; 

	} // get_users

	/**
	 * update_type
	 * This updates the playlist type, it calls the generic update_item function 
	 */
	function update_type($new_type) { 

		if ($this->_update_item('type',$new_type,'100')) { 
			$this->type = $new_type;
		}

	} // update_type

	/**
	 * update_name
	 * This updates the playlist name, it calls the generic update_item function
	 */
	function update_name($new_name) { 

		if ($this->_update_item('name',$new_name,'100')) { 
			$this->name = $new_name;
		}

	} // update_name

	/**
	 * _update_item
	 * This is the generic update function, it does the escaping and error checking
	 */
	function _update_item($field,$value,$level) { 

		if ($GLOBALS['user']->id != $this->user AND !$GLOBALS['user']->has_access($level)) { 
			return false; 
		}

		$value = sql_escape($value);

		$sql = "UPDATE playlist SET $field='$value' WHERE id='" . sql_escape($this->id) . "'";
		$db_results = mysql_query($sql, dbh());

		return $db_results;

	} // update_item

	/**
	 * update_track_numbers
	 * This function takes an array of $array['song_id'] $array['track'] where song_id is really the
	 * playlist_data.id and updates them
	 */
	function update_track_numbers($data) { 

		foreach ($data as $change) { 
		
			$track 	= sql_escape($change['track']);
			$id	= sql_escape($change['song_id']);

			$sql = "UPDATE playlist_data SET track='$track' WHERE id='$id'";
			$db_results = mysql_query($sql, dbh());

		} // end foreach

	} // update_track_numbers

	/**
	 * add_songs
	 * This takes an array of song_ids and then adds it to the playlist
	 * if you want to add a dyn_song you need to use the one shot function
	 * add_dyn_song
	 */
	function add_songs($song_ids=array()) { 

		/* We need to pull the current 'end' track and then use that to
		 * append, rather then integrate take end track # and add it to 
		 * $song->track add one to make sure it really is 'next'
		 */
		$sql = "SELECT `track` FROM playlist_data WHERE `playlist`='" . $this->id . "' ORDER BY `track` DESC LIMIT 1";
		$db_results = mysql_query($sql, dbh());
		$data = mysql_fetch_assoc($db_results);
		$base_track = $data['track'];

		foreach ($song_ids as $song_id) { 
			/* We need the songs track */
			$song = new Song($song_id);
			
			$track	= sql_escape($song->track+$base_track);
			$id	= sql_escape($song->id);
			$pl_id	= sql_escape($this->id);

			/* Don't insert dead songs */
			if ($id) { 
				$sql = "INSERT INTO playlist_data (`playlist`,`song`,`track`) " . 
					" VALUES ('$pl_id','$id','$track')";
				$db_results = mysql_query($sql, dbh());
			} // if valid id

		} // end foreach songs

	} // add_songs

	/**
	 * add_dyn_song
	 * This adds a dynamic song to a specified playlist this is just called as the
	 * song its self is stored in the session to keep it away from evil users
	 */
	function add_dyn_song() { 
	
		$dyn_song = $_SESSION['userdata']['stored_search'];

		if (strlen($dyn_song) < 1) { echo "FAILED1"; return false; }

		if (substr($dyn_song,0,6) != 'SELECT') { echo "$dyn_song"; return false; }

		/* Test the query before we put it in */
		$db_results = @mysql_query($dyn_song, dbh());

		if (!$db_results) { return false; }

		/* Ok now let's add it */
		$sql = "INSERT INTO playlist_data (`playlist`,`dyn_song`,`track`) " . 
			" VALUES ('" . sql_escape($this->id) . "','" . sql_escape($dyn_song) . "','0')";
		$db_results = mysql_query($sql, dbh());

		return true;

	} // add_dyn_song

	/**
	 * create
	 * This function creates an empty playlist, gives it a name and type
	 * Assumes $GLOBALS['user']->id as the user
	 */
	function create($name,$type) { 

		$name = sql_escape($name);
		$type = sql_escape($type);
		$user = sql_escape($GLOBALS['user']->id);
		$date = time();

		$sql = "INSERT INTO playlist (`name`,`user`,`type`,`date`) " . 
			" VALUES ('$name','$user','$type','$date')";
		$db_results = mysql_query($sql, dbh());

		$insert_id = mysql_insert_id(dbh());

		return $insert_id;

	} //create_paylist

	/**
	 * set_items
	 * This calles the get_items function and sets it to $this->items which is an array in this object
	 */
	function set_items() { 

		$this->items = $this->get_items();

	} // set_items

        /**
         * normalize_tracks
         * this takes the crazy out of order tracks
         * and numbers them in a liner fashion, not allowing for
	 * the same track # twice, this is an optional funcition
	 */
        function normalize_tracks() { 

                /* First get all of the songs in order of their tracks */
                $sql = "SELECT id FROM playlist_data WHERE playlist='" . sql_escape($this->id) . "' ORDER BY track ASC";
                $db_results = mysql_query($sql, dbh());

                $i = 1;
		$results = array();

                while ($r = mysql_fetch_assoc($db_results)) { 
                        $new_data = array();
                        $new_data['id']         = $r['id'];
                        $new_data['track']      = $i;
                        $results[] = $new_data;
                        $i++;
                } // end while results

                foreach($results as $data) { 
                        $sql = "UPDATE playlist_data SET track='" . $data['track'] . "' WHERE" . 
                                        " id='" . $data['id'] . "'";
                        $db_results = mysql_query($sql, dbh());
                } // foreach re-ordered results

                return true;

        } // normalize_tracks
	
	/**
	 * check_type
	 * This validates a type to make sure it's legit
	 */
	function check_type($type) { 

		if ($type == 'public' || $type == 'private') { return true; }
		
		return false; 

	} // check_type

	/**
	 * remove_songs
	 * This is the polar opposite of the add_songs function... with one little 
	 * change. it works off of the playlist_data.id rather then song_id
	 */
	function remove_songs($data) { 

		foreach ($data as $value) { 
		
			$id = sql_escape($value);
			
			$sql = "DELETE FROM playlist_data WHERE id='$id'";
			$db_results = mysql_query($sql, dbh());

		} // end foreach dead songs

	} // remove_songs

	/**
	 * delete
	 * This deletes the current playlist and all assoicated data
	 */
	function delete() { 

		$id = sql_escape($this->id);

		$sql = "DELETE FROM playlist_data WHERE playlist = '$id'";
		$db_results = mysql_query($sql, dbh());

		$sql = "DELETE FROM playlist WHERE id='$id'";
		$db_results = mysql_query($sql, dbh());

		return true;
	
	} // delete

} // class Playlist
