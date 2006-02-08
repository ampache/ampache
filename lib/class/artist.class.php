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
	@header Artist Class
*/

class Artist {

	/* Variables from DB */
	var $id;
	var $name;
	var $songs;
	var $albums;
	var $prefix;

	/*!
		@function Artist
		@discussion Artist class, for modifing a artist
		@param $artist_id 	The ID of the artist
	 */
	function Artist($artist_id = 0) {

		
		/* If we have passed an id then do something */
		if ($artist_id) { 

			/* Assign id for use in get_info() */
			$this->id = intval($artist_id);

			/* Get the information from the db */
			if ($info = $this->get_info()) {

				/* Assign Vars */
				$this->name = $info->name;
				$this->prefix = $info->prefix;
			} // if info

		} // if artist_id

	} //constructor

	/*!
		@function get_info
		@discussion get's the vars for $this out of the database 
		@param $this->id	Taken from the object
	*/
	function get_info() {

		/* Grab the basic information from the catalog and return it */
		$sql = "SELECT * FROM artist WHERE id='" . sql_escape($this->id) . "'";
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_object($db_results);

		return $results;

	} //get_info

	/*!
		@function get_albums
		@discussion gets the albums for this artist
	*/
	function get_albums() { 

		$results = array();

		$sql = "SELECT DISTINCT(album.id) FROM song,album WHERE song.album=album.id AND song.artist='$this->id' ORDER BY album.name";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_object($db_results)) { 
			$results[] = new Album($r->id);
		}

		return $results;
		
	} // get_albums

	/*! 
		@function get_songs
		@discussion gets the songs for this artist
	*/
	function get_songs() { 
	
		$sql = "SELECT song.id FROM song WHERE song.artist='$this->id'";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_object($db_results)) { 
			$results[] = new Song($r->id);
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

        /*!
                @function get_random_songs
                @discussion gets a random number, and
                        a random assortment of songs from this
                        album
        */
        function get_random_songs() {

                $results = array();

                $sql = "SELECT id FROM song WHERE artist='$this->id' ORDER BY RAND() LIMIT " . rand(1,$this->songs);
                $db_results = mysql_query($sql, dbh());

                while ($r = mysql_fetch_array($db_results)) {
                        $results[] = $r[0];
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
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_array($db_results)) { 
			$songs += $r[0];
			$albums++;
		}
		
		/* Set Object Vars */
		$this->songs = $songs;
		$this->albums = $albums;

		return true;

	} // get_count

	/*!
		@function format_artist
        	@discussion this function takes an array of artist
	                information and reformats the relevent values
	                so they can be displayed in a table for example
	                it changes the title into a full link.
	*/
	function format_artist() {

		/* Combine prefix and name, trim then add ... if needed */
                $name = htmlspecialchars(truncate_with_ellipse(trim($this->prefix . " " . $this->name)));
		$this->f_name = $this->name;
		$this->full_name = htmlspecialchars(trim($this->prefix . " " . $this->name));
		//FIXME: This shouldn't be set like this, f_name should be like this
	        $this->link = "<a href=\"" . conf('web_path') . "/artists.php?action=show&amp;artist=" . $this->id . "\" title=\"" . $this->full_name . "\">" . $name . "</a>";
		$this->name = $this->link;
	        return $artist;

	} // format_artist
	
        /*!
                @function rename
                @discussion changes the name of the artist in the db,
                        and then merge()s songs
                @param $newname the artist's new name, either a new
                        artist will be created or songs added to existing
                        artist if name exists already
                @return the id of the new artist
        */
        function rename($newname) {

                /* 
		 * There is this nifty function called check_artists in catalog that does exactly what we want it to do
                 * to use it, we first have to hax us a catalog
		 */
                $catalog = new Catalog();

                /* now we can get the new artist id in question */
                $newid = $catalog->check_artist($newname);

                /* check that it wasn't just whitespace that we were called to change */
                if ($newid == $this->id) {
			$GLOBALS['error']->add_error('artist_name',_("Error: Name Identical"));
                        return $newid;
                }

                /* now we can just call merge */
                $this->merge($newid);

                //now return id
                return $newid;

        } // rename

        /*!
                @function merge
                @discussion changes the artist id of all songs by this artist
                        to the given id and deletes self from db
                @param $newid the new artist id that this artist's songs should have
        */
        function merge($newid) {

		$catalog = new Catalog();

		/* Make sure this is a valid ID */
                if (!is_numeric($newid)) {
			$GLOBALS['error']->add_error('general',"Error: Invalid Artist ID");
                        return false;
		} 

                // First check newid exists
                $check_exists_qstring = "SELECT name FROM artist WHERE id='" . sql_escape($newid) . "'";
                $check_exists_query = mysql_query($check_exists_qstring, dbh());

                if ($check_exists_results = mysql_fetch_assoc($check_exists_query)) {

                        $NewName = $check_exists_result['name'];

                        // Now the query
                        $sql = "UPDATE song SET artist='" . sql_escape($newid) . "' " . 
				"WHERE artist='" . sql_escape($this->id) . "'";
                        $db_results = mysql_query($sql, dbh());

                        $num_stats_changed = $catalog->merge_stats('artist',$this->id,$newid);
			
			/* If we've done the merege we need to clean up */
			$catalog->clean_artists();
			$catalog->clean_albums();
                } 
		else {
			$GLOBALS['error']->add_error('general',"Error: Invalid Artist ID");
                        return false;
                }
        } // merge
	

	/*!
		@function show_albums
		@discussion displays the show albums by artist page
	*/
	function show_albums() { 

	        /* Set Vars */
	        $web_path = conf('web_path');

	        $albums = $this->get_albums();
	        $this->format_artist();
		$artist = $this;

	        require (conf('prefix') . "/templates/show_artist.inc");

	} // show_albums

	
} //end of artist class

?>
