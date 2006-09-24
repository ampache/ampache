<?php
/*

 Copyright 2001 - 2006 Ampache.org
 All Rights Reserved

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

/**
 *	Genre Class
 * 	This class takes care of the genre object
 */
class Genre {

	/* Variables */
	var $id;
	var $name;

	/** 
	 * Constructor
	 * @package Genre
	 * @catagory Constructor
	 */
	function Genre($genre_id=0) { 

		if ($genre_id > 0) { 
			$this->id 	= intval($genre_id);
			$info 		= $this->_get_info();
			$this->name 	= $info['name'];
		}


	} // Genre

	/** 
	 * Private Get Info 
	 * This simply returns the information for this genre
	 * @package Genre
	 * @catagory Class
	 */
	function _get_info() { 

		$sql = "SELECT * FROM " . tbl_name('genre') . " WHERE id='$this->id'";
		$db_results = mysql_query($sql, dbh());
		
		$results = mysql_fetch_assoc($db_results);

		return $results;

	} // _get_info()

	/** 
	 * format_genre
	 * this reformats the genre object so it's all purdy and creates a link var
	 * @package Genre
	 * @catagory Class
	 */
	function format_genre() { 

		$this->link 		= "<a href=\"" . conf('web_path') . "/genre.php?action=show_genre&amp;genre_id=" . $this->id . "\">" . $this->name . "</a>";
		
		$this->play_link 	= conf('web_path') . "/song.php?action=genre&amp;genre=" . $this->id;
		$this->random_link 	= conf('web_path') . "/song.php?action=random_genre&amp;genre=" . $this->id; 
		
	} // format_genre

	/**
	 * get_song_count
	 * This returns the number of songs in said genre
	 * @package Genre
	 * @catagory Class
	 */
	function get_song_count() { 

		$sql = "SELECT count(song.id) FROM song WHERE genre='" . $this->id . "'";
		$db_results = mysql_query($sql, dbh());

		$total_items = mysql_fetch_array($db_results);

		return $total_items[0];

	} // get_song_count

	/**
	 * get_album_count
	 * Returns the number of albums that contain a song of this genre
	 * @package Genre
	 * @catagory Class
	 */
	function get_album_count() { 

		$sql = "SELECT COUNT(DISTINCT(song.album)) FROM song WHERE genre='" . $this->id . "'";
		$db_results = mysql_query($sql, dbh());

		$total_items = mysql_fetch_array($db_results); 

		return $total_items[0];

	} // get_album_count

	/**
	 * get_artist_count
	 * Returns the number of artists who have at least one song in this genre
	 * @package Genre
	 * @catagory Class
	 */
	function get_artist_count() { 

		$sql = "SELECT COUNT(DISTINCT(song.artist)) FROM song WHERE genre='" . $this->id . "'";
		$db_results = mysql_query($sql, dbh());

		$total_items = mysql_fetch_array($db_results);

		return $total_items[0];

	} // get_artist_count

	/**
	 * get_songs
	 * This gets all of the songs in this genre and returns an array of song objects
	 * @package Genre
	 * @catagory Class
	 */
	function get_songs() { 

		$sql = "SELECT song.id FROM song WHERE genre='" . $this->id . "'";
		$db_results = mysql_query($sql, dbh());

		$results = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;

	} // get_songs

	/**
	 * get_random_songs
	 * This is the same as get_songs except it returns a random assortment of songs from this
	 * genre
	 * @package Genre
	 * @catagory Class
	 */
	function get_random_songs() { 

		$limit = rand(1,$this->get_song_count());

		$sql = "SELECT song.id FROM song WHERE genre='" . $this->id . "' ORDER BY RAND() LIMIT $limit";
		$db_results = mysql_query($sql, dbh());

		$results = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;

	} // get_random_songs

	/**
	 * get_albums
	 * This gets all of the albums that have at least one song in this genre
	 * @package Genre
	 * @catagory Class
	 */
	function get_albums() { 

		$sql = "SELECT DISTINCT(song.album) FROM song WHERE genre='" . $this->id . "'";
		$db_results = mysql_query($sql,dbh());

		$results = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$album = new Album($r['album']);
			$album->format_album();
			$results[] = $album;
		}

		return $results;

	} // get_albums

	/**
	 * get_artists
	 * This gets all of the artists who have at least one song in this genre
	 * @package Genre
	 * @catagory Class
	 */
	function get_artists() { 

		$sql = "SELECT DISTINCT(song.artist) FROM song WHERE genre='" . $this->id . "'";
		$db_results = mysql_query($sql, dbh());

		$results = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = get_artist_info($r['artist']);
		}
		
		return $results;

	} // get_artists

	/**
	 * get_genres
	 * this returns an array of genres based on a sql statement that's passed
	 * @package Genre
	 * @catagory Class
	 */
	function get_genres($sql) { 

		$db_results = mysql_query($sql, dbh());
		
		$results = array();

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = new Genre($r['id']);
		}

		return $results;

	} // get_genres

	/**
	 * get_sql_from_match
	 * This is specificly for browsing it takes the match and returns the sql call that we want to use
	 * @package Genre
	 * @catagory Class
	 */
	function get_sql_from_match($match) { 

		switch ($match) { 
			case 'Show_All':
			case 'show_all':
				$sql = "SELECT id FROM genre";
			break;
			case 'Browse':
			case 'show_genres':
				$sql = "SELECT id FROM genre";
			break;
			default:
				$sql = "SELECT id FROM genre WHERE name LIKE '" . sql_escape($match) . "%'";
			break;
		} // end switch on match
				
		return $sql;

	} // get_sql_from_match
	 

	/**
	 * show_match_list
	 * This shows the Alphabet list and any other 'things' that genre by need at the top of every browse
	 * page
	 * @package Genre
	 * @catagory Class
	 */
	function show_match_list($match) { 

		require (conf('prefix') . '/templates/show_box_top.inc.php');
		show_alphabet_list('genre','browse.php',$match,'genre');
		/* Detect if it's Browse, and if so don't fill it in */
		if ($match == 'Browse') { $match = ''; } 
		show_alphabet_form($match,_('Show Genres starting with'),"browse.php?action=genre&amp;match=$match");
		require (conf('prefix') . '/templates/show_box_bottom.inc.php');

	} // show_match_list

} //end of genre class

?>
