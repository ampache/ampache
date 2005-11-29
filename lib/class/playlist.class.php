<?
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
	@header Playlist Class
	This class handles all actual work in regards to playlists.
*/

class Playlist {

	// Variables from DB
	var $id;
	var $name;
	var $user;
	var $type;
	var $time;
	var $items;

	/*!
		@function Playlist
		@discussion Playlist class
		@param $playlist_id 	The ID of the playlist
	 */
	function Playlist($playlist_id = 0) {

		/* If we have an id then do something */
		if ($playlist_id) { 
			// Assign id
			$this->id = $playlist_id;

			// Get the information from the db
			$this->refresh_object();
		}
		
	}



	/*!
		@function refresh_object
		@discussion Reads playlist information from the db and updates the Playlist object with it
	*/
	function refresh_object() {

		$dbh = dbh();

		if ($this->id) {
			$sql = "SELECT name, user, type, date FROM playlist" .
				" WHERE id = '$this->id'";
			$db_results = mysql_query($sql, $dbh);

			if ($r = mysql_fetch_object($db_results)) {
				$this->name = $r->name;
				$this->user = $r->user;
				$this->type = $r->type;
				$this->time = $r->date;
				$this->items = array();

				// Fetch playlist items
				$sql = "SELECT song, track FROM playlist_data" .
					" WHERE playlist = '$this->id'" .
					" ORDER BY track";
				$db_results = mysql_query($sql, $dbh);

				while ($r = mysql_fetch_object($db_results)) {
					$this->items[] = array("song_id" => $r->song, "track" => $r->track);
				}
			}

			return TRUE;
		}

		return FALSE;

	}


	/*!
		@function create_playlist
		@discussion Creates an empty playlist, given a name, user_id, and type.
	*/
	function create_playlist($name, $user, $type) {

		$dbh = dbh();

		if (isset($name) && isset($user) && isset($type) && $this->check_type($type)) {
			$name = sql_escape($name);
			$sql = "INSERT INTO playlist" .
				" (name, user, type)" .
				" VALUES ('$name', '$user', '$type')";
			$db_results = mysql_query($sql, $dbh);
			if ($this->id = mysql_insert_id($dbh)) {
				$this->refresh_object();
				return TRUE;
			}
		}

		return FALSE;

	}


	/*!
		@function delete
		@discussion Deletes the playlist.
	*/
	function delete() {

		$dbh = dbh();

		if ($this->id) {
			$sql = "DELETE FROM playlist_data" .
				" WHERE playlist = '$this->id'";
			$db_results = mysql_query($sql, $dbh);

			$sql = "DELETE FROM playlist" .
				" WHERE id = '$this->id'";
			$db_results = mysql_query($sql, $dbh);

			// Clean up this object
			foreach (get_object_vars($this) as $var) {
				unset($var);
			}

			return TRUE;
		}

		return FALSE;

	}


	/*!
		@function update_track_numbers
		@discussion Reads an array of song_ids and track numbers to update
	*/
	function update_track_numbers($changes) {

		$dbh = dbh();

		if ($this->id && isset($changes) && is_array($changes)) {
			foreach ($changes as $change) {
				// Check for valid song_id
				$sql = "SELECT count(*) FROM song WHERE id = '" . $change['song_id'] . "'";
				$db_results = mysql_query($sql, $dbh);
				$r = mysql_fetch_row($db_results);
				if ($r[0] == 1) {
					$sql = "UPDATE playlist_data SET" .
						" track = '" . $change['track'] . "'" .
						" WHERE playlist = '$this->id'".
						" AND song = '" . $change['song_id'] . "'";
					$db_results = mysql_query($sql, $dbh);
				}
			}

			// Refresh the playlist object
			$this->refresh_object();

			return TRUE;
		}

		return FALSE;

	}


	/*!
		@function add_songs
		@discussion Reads an array of song_ids to add to the playlist
		@param $song_ids the array of song_ids
		@param $is_ordered boolean, if true insert in order submitted, not by track number
	*/
	function add_songs($song_ids, $is_ordered = false) {

		$dbh = dbh();

		if ($this->id && isset($song_ids) && is_array($song_ids)) {
			$count = 0;
			foreach ($song_ids as $song_id) {
				if( $is_ordered ) {
					$track_num = $count++;
				} else {
					$track_num = $song->track;
				}
				$song = new Song($song_id);
				if (isset($song->id)) {
					$sql = "INSERT INTO playlist_data" .
						" (playlist, song, track)" .
						" VALUES ('$this->id', '$song->id', '$track_num')";
					$db_results = mysql_query($sql, $dbh);
				}
			}

			// Refresh the playlist object
			$this->refresh_object();

			return TRUE;
		}

		return FALSE;

	}


	/*!
		@function remove_songs
		@discussion Reads an array of song_ids to remove from the playlist
	*/
	function remove_songs($song_ids) {

		$dbh = dbh();

		if ($this->id && isset($song_ids) && is_array($song_ids)) {
			foreach ($song_ids as $song_id) {
				$sql = "DELETE FROM playlist_data" .
					" WHERE song = '$song_id'" .
					" AND playlist = '$this->id'";
				$db_results = mysql_query($sql, $dbh);
			}

			// Refresh the playlist object
			$this->refresh_object();

			return TRUE;
		}

		return FALSE;

	}


	/*!
		@function check_type
		@discussion Checks for a valid playlist type
	*/
	function check_type($type) {

		if (isset($type)) {
			if ($type === 'public' || $type === 'private') {
				return TRUE;
			}
		}

		return FALSE;

	}


	/*!
		@function update_type
		@discussion Updates the playlist type
	*/
	function update_type($type) {

		$dbh = dbh();

		if ($this->id && isset($type) && $this->check_type($type)) {
			$sql = "UPDATE playlist SET type = '$type'" .
				" WHERE id = '$this->id'";
			$db_results = mysql_query($sql, $dbh);

			// Refresh the playlist object
			$this->refresh_object();

			return TRUE;
		}

		return FALSE;

	}


	/*!
		@function update_name
		@discussion Updates the playlist name
	*/
	function update_name($name) {

		$dbh = dbh();

		if ($this->id && isset($name)) {
			$name = sql_escape($name);
			$sql = "UPDATE playlist SET name = '$name'" .
				" WHERE id = '$this->id'";
			$db_results = mysql_query($sql, $dbh);

			// Refresh the playlist object
			$this->refresh_object();

			return TRUE;
		}

		return FALSE;

	}


	/*!
		@function get_songs
		@discussion Returns an array of song_ids for the playlist
	*/
	function get_songs() {

		$song_ids = array();

		if ($this->id && is_array($this->items)) {
			foreach ($this->items as $item) {
				$song_ids[] = $item['song_id'];
			}
		}

		return $song_ids;

	} // get_songs

	/*!
		@function get_random_songs
		@discussion gets a random set of the songs in this
			playlist
	*/
	function get_random_songs() { 

		$sql = "SELECT COUNT(song) FROM playlist_data WHERE playlist = '$this->id'";
		$db_results = mysql_query($sql, dbh());

		$total_songs = mysql_fetch_row($db_results);
		
	        // Fetch playlist items
                $sql = "SELECT song, track FROM playlist_data" .
        	        " WHERE playlist = '$this->id'" .
                        " ORDER BY RAND()";
                $db_results = mysql_query($sql, dbh());
                while ($r = mysql_fetch_object($db_results)) {
	                $song_ids[] = $r->song;
                }

		return $song_ids;
	} // get_random_songs

	/*!
		@function show_import
		@discussion shows the import from file template
	*/
	function show_import() { 

		require (conf('prefix') . "/templates/show_import_playlist.inc.php");

	} // show_import


} //end of playlist class

?>
