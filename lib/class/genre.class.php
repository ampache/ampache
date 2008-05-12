<?php
/*

 Copyright 2001 - 2007 Ampache.org
 All Rights Reserved

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
 *	Genre Class
 * 	This class takes care of the genre object
 */
class Genre {

	/* Variables */
	public $id;
	public $name;

	/** 
	 * Constructor
	 */
	public function __construct($genre_id=0) { 
	
		if (!$genre_id) { return false; }
	
		$this->id 	= intval($genre_id);
		$info 		= $this->_get_info();
		$this->name 	= $info['name'];


	} // Genre
	public static function build_cache($ids, $fields='*') {
	  $idlist = '(' . implode(',', $ids) . ')';
	  $sql = "SELECT $fields FROM genre WHERE id in $idlist";
	  $db_results = Dba::query($sql);
	  global $genre_cache;
	  $genre_cache = array();
	  while ($results = Dba::fetch_assoc($db_results)) {
	    $genre_cache[intval($results['id'])] = $results;
	  }
	}
	/** 
	 * Private Get Info 
	 * This simply returns the information for this genre
	 */
	private function _get_info() { 
	  global $genre_cache;
		if (isset($genre_cache[intval($this->id)]))
		  return $genre_cache[intval($this->id)];
		$sql = "SELECT * FROM `genre`  WHERE `id`='$this->id'";
		$db_results = Dba::query($sql);
		
		$results = Dba::fetch_assoc($db_results);

		return $results;

	} // _get_info

	/** 
	 * format
	 * this reformats the genre object so it's all purdy and creates a link var
	 */
	public function format() { 

		$this->f_link 		= "<a href=\"" . Config::get('web_path') . "/genre.php?action=show_genre&amp;genre_id=" . $this->id . "\">" . scrub_out($this->name) . "</a>";
		
		$this->play_link 	= Config::get('web_path') . '/stream.php?action=genre&amp;genre=' . $this->id;
		$this->random_link 	= Config::get('web_path') . '/stream.php?action=random_genre&amp;genre=' . $this->id; 
		$this->download_link 	= Config::get('web_path') . '/batch.php?action=genre&amp;id=' . $this->id;
		
	} // format

	/**
	 * get_song_count
	 * This returns the number of songs in said genre
	 */
	public function get_song_count() { 

		$sql = "SELECT count(`song`.`id`) AS `total` FROM `song` WHERE `genre`='" . $this->id . "'";
		$db_results = Dba::query($sql);

		$total_items = Dba::fetch_assoc($db_results);
		
		return $total_items['total'];

	} // get_song_count

	/**
	 * get_album_count
	 * Returns the number of albums that contain a song of this genre
	 */
	public function get_album_count() { 

		$sql = "SELECT COUNT(DISTINCT(song.album)) FROM `song` WHERE `genre`='" . $this->id . "'";
		$db_results = Dba::query($sql);

		$total_items = Dba::fetch_row($db_results); 

		return $total_items['0'];

	} // get_album_count

	/**
	 * get_artist_count
	 * Returns the number of artists who have at least one song in this genre
	 */
	public function get_artist_count() { 

		$sql = "SELECT COUNT(DISTINCT(`song`.`artist`)) FROM `song` WHERE `genre`='" . $this->id . "'";
		$db_results = Dba::query($sql);

		$total_items = Dba::fetch_row($db_results);

		return $total_items['0'];

	} // get_artist_count

	/**
	 * get_songs
	 * This gets all of the songs in this genre and returns an array of song objects
	 */
	public function get_songs() { 

		$sql = "SELECT `song`.`id` FROM `song` WHERE `genre`='" . $this->id . "'";
		$db_results = Dba::query($sql);

		$results = array();

		while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;

	} // get_songs

	/**
	 * get_random_songs
	 * This is the same as get_songs except it returns a random assortment of songs from this
	 * genre
	 */
	public function get_random_songs() { 

		$sql = "SELECT `song`.`id` FROM `song` WHERE `genre`='" . $this->id . "' ORDER BY RAND()";
		$db_results = Dba::query($sql);

		$results = array();

		while ($r = Dba::fetch_assoc($db_results)) { 
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

		$sql = "SELECT DISTINCT(`song`.`album`) FROM `song` WHERE `genre`='" . $this->id . "'";
		$db_results = Dba::query($sql);

		$results = array();

		while ($r = Dba::fetch_row($db_results)) { 
			$results[] = $r['0'];
		}

		return $results;

	} // get_albums

	/**
	 * get_artists
	 * This gets all of the artists who have at least one song in this genre
	 */
	public function get_artists() { 

		$sql = "SELECT DISTINCT(`song`.`artist`) FROM `song` WHERE `genre`='" . $this->id . "'";
		$db_results = Dba::query($sql);

		$results = array();

		while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r['artist']; 
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
			case 'Show_all':
				$sql = "SELECT `id` FROM `genre`";
			break;
			case 'Browse':
			case 'show_genres':
				$sql = "SELECT `id` FROM `genre`";
			break;
			default:
				$sql = "SELECT `id` FROM `genre` WHERE `name` LIKE '" . Dba::escape($match) . "%'";
			break;
		} // end switch on match
				
		return $sql;

	} // get_sql_from_match
	 
} //end of genre class

?>
