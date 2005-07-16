<?
/*

 Copyright 2001 - 2005 Ampache.org
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
			$this->id 	= $genre_id;
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



	} // format_genre

	/**
	 * get_song_count
	 * This returns the number of songs in said genre
	 * @package Genre
	 * @catagory Class
	 */
	function get_song_count() { 



	} // get_song_count

	/**
	 * get_songs
	 * This gets all of the songs in this genre and returns an array of song objects
	 * @package Genre
	 * @catagory Class
	 */
	function get_songs() { 



	} // get_songs

	/**
	 * get_albums
	 * This gets all of the albums that have at least one song in this genre
	 * @package Genre
	 * @catagory Class
	 */
	function get_albums() { 



	} // get_albums

	/**
	 * get_artists
	 * This gets all of the artists who have at least one song in this genre
	 * @package Genre
	 * @catagory Class
	 */
	function get_artists() { 



	} // get_artists

} //end of genre class

?>
