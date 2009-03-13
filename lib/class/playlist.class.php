<?php
/*

 Copyright (c) Ampache.org
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
class Playlist extends database_object { 

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

		$info = $this->get_info($id);

		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} 
	
	} // Playlist

	/**
	 * build_cache
	 * This is what builds the cache from the objects
	 */
	public static function build_cache($ids) { 
		
		if (!count($ids)) { return false; } 

		$idlist = '(' . implode(',',$ids) . ')'; 

		$sql = "SELECT * FROM `playlist` WHERE `id` IN $idlist"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			parent::add_to_cache('playlist',$row['id'],$row); 
		} 

	} // build_cache

	/**
	 * format
	 * This takes the current playlist object and gussies it up a little
	 * bit so it is presentable to the users
	 */
	public function format() { 

		$this->f_name =  truncate_with_ellipsis($this->name,Config::get('ellipse_threshold_title'));
		$this->f_link = '<a href="' . Config::get('web_path') . '/playlist.php?action=show_playlist&amp;playlist_id=' . $this->id . '">' . $this->f_name . '</a>'; 

		$this->f_type = ($this->type == 'private') ? get_user_icon('lock',_('Private')) : ''; 

		$client = new User($this->user); 

		$this->f_user = $client->fullname; 

	} // format

	/**
 	 * has_access
	 * This function returns true or false if the current user
	 * has access to this playlist
	 */
	public function has_access() { 

		if (!Access::check('interface','25')) { 
			return false; 
		} 
		if ($this->user == $GLOBALS['user']->id) { 
			return true; 
		} 
		else {
			return Access::check('interface','100'); 
		} 	

		return false; 

	} // has_access

	/**
	 * get_track
	 * Returns the single item on the playlist and all of it's information, restrict
	 * it to this Playlist
	 */
	public function get_track($track_id) { 

		$track_id = Dba::escape($track_id); 
		$playlist_id = Dba::escape($this->id); 

		$sql = "SELECT * FROM `playlist_data` WHERE `id`='$track_id' AND `playlist`='$playlist_id'";
		$db_results = Dba::query($sql);

		$row = Dba::fetch_assoc($db_results);

		return $row; 

	} // get_track

	/**
	 * get_items
	 * This returns an array of playlist songs that are in this playlist. Because the same
	 * song can be on the same playlist twice they are key'd by the uid from playlist_data
	 */
	public function get_items() { 

		$results = array(); 

		$sql = "SELECT `id`,`object_id`,`object_type`,`track` FROM `playlist_data` WHERE `playlist`='" . Dba::escape($this->id) . "' ORDER BY `track`";
		$db_results = Dba::query($sql);

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = array('type'=>$row['object_type'],'object_id'=>$row['object_id'],'track'=>$row['track'],'track_id'=>$row['id']); 
		} // end while

		return $results;

	} // get_items

	/**
	 * get_random_items
	 * This is the same as before but we randomize the buggers!
	 */
	public function get_random_items($limit='') { 

		$results = array(); 

		$limit_sql = $limit ? 'LIMIT ' . intval($limit) : ''; 

		$sql = "SELECT `object_id`,`object_type` FROM `playlist_data` " . 
			"WHERE `playlist`='" . Dba::escape($this->id) . "' ORDER BY RAND() $limit_sql"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 

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
	 * get_song_count
	 * This simply returns a int of how many song elements exist in this playlist
	 * For now let's consider a dyn_song a single entry
	 */
	public function get_song_count() { 

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

		$sql = "SELECT `id` FROM `playlist` WHERE `user`='$user_id' ORDER BY `name`"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id']; 
		} 

		return $results; 

	} // get_users

	/**
 	 * update
	 * This function takes a key'd array of data and runs updates
	 */
	public function update($data) { 

		if ($data['name'] != $this->name) { 
			$this->update_name($data['name']); 
		} 
		if ($data['pl_type'] != $this->type) { 
			$this->update_type($data['pl_type']); 
		} 

	} // update

	/**
	 * update_type
	 * This updates the playlist type, it calls the generic update_item function 
	 */
	private function update_type($new_type) { 

		if ($this->_update_item('type',$new_type,'50')) { 
			$this->type = $new_type;
		}

	} // update_type

	/**
	 * update_name
	 * This updates the playlist name, it calls the generic update_item function
	 */
	private function update_name($new_name) { 

		if ($this->_update_item('name',$new_name,'50')) { 
			$this->name = $new_name;
		}

	} // update_name

	/**
	 * _update_item
	 * This is the generic update function, it does the escaping and error checking
	 */
	private function _update_item($field,$value,$level) { 

		if ($GLOBALS['user']->id != $this->user AND !Access::check('interface',$level)) { 
			return false; 
		}

		$value = Dba::escape($value);

		$sql = "UPDATE `playlist` SET $field='$value' WHERE `id`='" . Dba::escape($this->id) . "'";
		$db_results = Dba::query($sql);

		return $db_results;

	} // update_item

	/**
	 * update_track_number
	 * This takes a playlist_data.id and a track (int) and updates the track value
	 */
	public function update_track_number($track_id,$track) { 

		$playlist_id	= Dba::escape($this->id); 
		$track_id	= Dba::escape($track_id); 
		$track		= Dba::escape($track); 

		$sql = "UPDATE `playlist_data` SET `track`='$track' WHERE `id`='$track_id' AND `playlist`='$playlist_id'"; 
		$db_results = Dba::query($sql); 

	} // update_track_number

	/**
	 * add_songs
	 * This takes an array of song_ids and then adds it to the playlist
	 * if you want to add a dyn_song you need to use the one shot function
	 * add_dyn_song
	 */
	public function add_songs($song_ids=array()) { 

		/* We need to pull the current 'end' track and then use that to
		 * append, rather then integrate take end track # and add it to 
		 * $song->track add one to make sure it really is 'next'
		 */
		$sql = "SELECT `track` FROM `playlist_data` WHERE `playlist`='" . $this->id . "' ORDER BY `track` DESC LIMIT 1";
		$db_results = Dba::read($sql);
		$data = Dba::fetch_assoc($db_results);
		$base_track = $data['track'];
		debug_event('add_songs', 'Track number: '.$base_track, '5');

		foreach ($song_ids as $song_id) { 
			/* We need the songs track */
			$song = new Song($song_id);
			
			$track	= Dba::escape($song->track+$base_track);
			$id	= Dba::escape($song->id);
			$pl_id	= Dba::escape($this->id);

			/* Don't insert dead songs */
			if ($id) { 
				$sql = "INSERT INTO `playlist_data` (`playlist`,`object_id`,`object_type`,`track`) " . 
					" VALUES ('$pl_id','$id','song','$track')";
				$db_results = Dba::write($sql);
			} // if valid id

		} // end foreach songs

	} // add_songs

	/**
	 * create
	 * This function creates an empty playlist, gives it a name and type
	 * Assumes $GLOBALS['user']->id as the user
	 */
	public static function create($name,$type) { 

		$name = Dba::escape($name);
		$type = Dba::escape($type);
		$user = Dba::escape($GLOBALS['user']->id);
		$date = time();

		$sql = "INSERT INTO `playlist` (`name`,`user`,`type`,`date`) " . 
			" VALUES ('$name','$user','$type','$date')";
		$db_results = Dba::write($sql);

		$insert_id = Dba::insert_id();

		return $insert_id;

	} // create

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
        public function normalize_tracks() { 

                /* First get all of the songs in order of their tracks */
                $sql = "SELECT `id` FROM `playlist_data` WHERE `playlist`='" . Dba::escape($this->id) . "' ORDER BY `track` ASC";
                $db_results = Dba::query($sql);

                $i = 1;
		$results = array();

                while ($r = Dba::fetch_assoc($db_results)) { 
                        $new_data = array();
                        $new_data['id']         = $r['id'];
                        $new_data['track']      = $i;
                        $results[] = $new_data;
                        $i++;
                } // end while results

                foreach($results as $data) { 
                        $sql = "UPDATE `playlist_data` SET `track`='" . $data['track'] . "' WHERE" . 
                                        " `id`='" . $data['id'] . "'";
                        $db_results = Dba::query($sql);
                } // foreach re-ordered results

                return true;

        } // normalize_tracks
	
	/**
	 * delete_track
	 * this deletes a single track, you specify the playlist_data.id here
	 */
	public function delete_track($id) { 

		$this_id = Dba::escape($this->id); 
		$id = Dba::escape($id); 
	
		$sql = "DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist`='$this_id' AND `playlist_data`.`id`='$id' LIMIT 1"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // delete_track 

	/**
	 * delete
	 * This deletes the current playlist and all assoicated data
	 */
	public function delete() { 

		$id = Dba::escape($this->id); 
	
		$sql = "DELETE FROM `playlist_data` WHERE `playlist` = '$id'";
		$db_results = Dba::query($sql);

		$sql = "DELETE FROM `playlist` WHERE `id`='$id'";
		$db_results = Dba::query($sql);

		$sql = "DELETE FROM `object_count` WHERE `object_type`='playlist' AND `object_id`='$id'"; 
		$db_results = Dba::query($sql); 
 
		return true;
	
	} // delete

} // class Playlist
