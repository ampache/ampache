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
 * Artist Class
 */
class Artist {

	/* Variables from DB */
	public $id;
	public $name;
	public $songs;
	public $albums;
	public $prefix;

	// Constructed vars
	public $_fake = false; // Set if construct_from_array() used

	/**
	 * Artist
	 * Artist class, for modifing a artist
	 * Takes the ID of the artist and pulls the info from the db
	 */
	public function __construct($id='') {

		/* If they failed to pass in an id, just run for it */
		if (!$id) { return false; } 	

		/* Assign id for use in get_info() */
		$this->id = intval($id);

		/* Get the information from the db */
		$info = $this->_get_info();
			
		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} // foreach info

		return true; 

	} //constructor

	/**
	 * construct_from_array
	 * This is used by the metadata class specifically but fills out a Artist object
	 * based on a key'd array, it sets $_fake to true
	 */
	public static function construct_from_array($data) { 

		$artist = new Artist(0); 
		foreach ($data as $key=>$value) { 
			$artist->$key = $value; 
		} 

		//Ack that this is not a real object from the DB
		$artist->_fake = true; 

		return $artist;

	} // construct_from_array

	/**
	 * _get_info
	 * get's the vars for $this out of the database taken from the object
	*/
	private function _get_info() {

		/* Grab the basic information from the catalog and return it */
		$sql = "SELECT * FROM artist WHERE id='" . Dba::escape($this->id) . "'";
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);

		return $results;

	} // _get_info

	/**
	 * get_albums
	 * gets the album ids that this artist is a part
	 * of
	 */
	public function get_albums() { 

		$results = array();

		$sql = "SELECT `album`.`id` FROM album LEFT JOIN `song` ON `song`.`album`=`album`.`id` " . 
			"WHERE `song`.`artist`='$this->id' GROUP BY `album`.`id` ORDER BY `album`.`name`,`album`.`year`";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;
		
	} // get_albums

	/** 
	 * get_songs
	 * gets the songs for this artist
	 */
	public function get_songs() { 
	
		$sql = "SELECT `song`.`id` FROM `song` WHERE `song`.`artist`='" . Dba::escape($this->id) . "'";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;

	} // get_songs

	/**
	 * get_song_ids
	 * This gets an array of song ids that are assoicated with this artist. This is great for using
	 * with the show_songs function
	 */
	function get_song_ids() { 

		$sql = "SELECT id FROM song WHERE artist='" . sql_escape($this->id) . "' ORDER BY album, track";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;

	} // get_song_ids

        /**
         * get_random_songs
	 * Gets the songs from this artist in a random order
         */
        public function get_random_songs() {

                $results = array();

                $sql = "SELECT `id` FROM `song` WHERE `artist`='$this->id' ORDER BY RAND()";
                $db_results = Dba::query($sql);

                while ($r = Dba::fetch_assoc($db_results)) {
                        $results[] = $r['id'];
                }

                return $results;

        } // get_random_songs

	/*!
		@function get_count
		@discussion gets the album and song count of 
			this artist
	*/
	function get_count() { 

		/* Define vars */
		$songs = 0;
		$albums = 0;

		$sql = "SELECT COUNT(song.id) FROM song WHERE song.artist='$this->id' GROUP BY song.album";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_row($db_results)) { 
			$songs += $r[0];
			$albums++;
		}
		
		/* Set Object Vars */
		$this->songs = $songs;
		$this->albums = $albums;

		return true;

	} // get_count

	/**
	 * format
         * this function takes an array of artist
	 * information and reformats the relevent values
	 * so they can be displayed in a table for example
	 * it changes the title into a full link.
 	 */
	public function format() {

		/* Combine prefix and name, trim then add ... if needed */
                $name = truncate_with_ellipsis(trim($this->prefix . " " . $this->name));
		$this->f_name = $name;

		// If this is a fake object, we're done here
		if ($this->_fake) { return true; } 

	        $this->f_name_link = "<a href=\"" . Config::get('web_path') . "/artists.php?action=show&amp;artist=" . $this->id . "\" title=\"" . $this->full_name . "\">" . $name . "</a>";
		$this->f_link = Config::get('web_path') . '/artists.php?action=show&amp;artist=' . $this->id; 

		// Get the counts 
		$this->get_count(); 

		return true; 

	} // format

	/**
	 * update
	 * This takes a key'd array of data and updates the current artist
	 * it will flag songs as neeed
	 */
	public function update($data) { 

		// Save our current ID
		$current_id = $this->id; 

		$artist_id = Catalog::check_artist($data['name']); 

		// If it's changed we need to update
		if ($artist_id != $this->id) { 
			$songs = $this->get_songs(); 
			foreach ($songs as $song_id) { 
				Song::update_artist($artist_id,$song_id); 
			} 
			$updated = 1; 
			$current_id = $artist_id; 
			Catalog::clean_artists(); 
		} // end if it changed

		if ($updated) { 
			foreach ($songs as $song_id) { 
				Flag::add($song_id,'song','retag','Interface Artist Update'); 
				Song::update_utime($song_id); 
			} 
			Catalog::clean_stats(); 
		} // if updated

		return $current_id;

	} // update
	
} // end of artist class
?>
