<?php
/*

 Copyright 2001 - 2007 Ampache.org
 All Rights Reserved

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
 * Random Class
 * All of the 'random' type events, elements, voodoo done by ampache is done
 * by this class, there isn't a table for this class so most of it's functions
 * are static
 */
class Random {

	/**
	 * Constructor
	 * nothing to see here, move along
	 */
	public function __construct() { 

		// Rien a faire

	} // constructor

	/**
	 * album
	 * This returns the ID of a random album, nothing special
	 */
	public static function album() { 

		$sql = "SELECT `id` FROM `album` ORDER BY RAND() LIMIT 1"; 
		$db_results = Dba::query($sql); 

		$results = Dba::fetch_assoc($db_results); 

		return $results['id']; 

	} // album

	/**
	 * artist
	 * This returns the ID of a random artist, nothing special here for now
	 */
	public static function artist() { 

		$sql = "SELECT `id` FROM `artist` ORDER BY RAND() LIMIT 1"; 
		$db_results = Dba::query($sql); 

		$results = Dba::fetch_assoc($db_results); 

		return $results['id']; 

	} // artist

	/**
	 * playlist
	 * This returns a random Playlist with songs little bit of extra
	 * logic require
	 */
	public static function playlist() { 

		$sql = "SELECT `playlist`.`id` FROM `playlist` LEFT JOIN `playlist_data` " . 
			" ON `playlist`.`id`=`playlist_data`.`playlist` WHERE `playlist_data`.`object_id` IS NOT NULL " . 
			" ORDER BY RAND()"; 
		$db_results = Dba::query($sql); 

		$results = Dba::fetch_assoc($db_results); 

		return $results['id']; 

	} // playlist

	/**
	 * play_url
	 * This generates a random play url based on the passed type
	 * and returns it
	 */
	public static function play_url($type) { 

		if (!$type = self::validate_type($type)) { 
			return false; 
		} 
	
		if (Config::get('require_session')) { 
			$session_string = '&sid=' . session_id(); 
		} 

                $web_path = Config::get('web_path');

                if (Config::get('force_http_play') OR !empty($force_http)) {
                        $port = Config::get('http_port');
                        if (preg_match("/:\d+/",$web_path)) {
                                $web_path = str_replace("https://", "http://",$web_path);
                        }
                        else {
                                $web_path = str_replace("https://", "http://",$web_path);
                        }
                }
		
		$uid = $GLOBALS['user']->id; 
	
		$url = $web_path . "/play/index.php?random=1&type=$type&uid=$uid$session_string";

		return $url; 

	} // play_url 

	/**
	 * get_single_song
	 * This returns a single song pulled based on the passed random method
	 */
	public static function get_single_song($type) { 

		if (!$type = self::validate_type($type)) { 
			return false; 
		} 

		$method_name = 'get_' . $type; 

		if (method_exists('Random',$method_name)) { 
			$song_ids = self::$method_name(1); 
			$song_id = array_pop($song_ids);  
		} 
		
		return $song_id; 

	} // get_single_song

	/**
	 * get_default
	 * This just randomly picks a song at whim from all catalogs
	 * nothing special here...
	 */ 
	public static function get_default($limit) { 

		$results = array(); 

		$sql = "SELECT `id` FROM `song` ORDER BY RAND() LIMIT $limit"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id']; 
		} 
	
		return $results; 

	} // get_default

	/**
	 * get_genre
	 * This looks at the last object played by the current user and
	 * then picks a song of the same genre at random...
	 */
	public static function get_genre($limit) { 

		$results = array(); 

		// Get the last genre played by us
		$data = $GLOBALS['user']->get_recently_played('1','genre'); 
		if ($data['0']) { 
			$where_sql = " WHERE `genre`='" . $data['0'] . "' "; 	
		} 

		$sql = "SELECT `id` FROM `song` $where_sql ORDER BY RAND() LIMIT $limit"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
	 		$results[] = $row['id']; 
		} 

		return $results; 

	} // get_genre

	/**
	 * get_album
	 * This looks at the last album played by the current user and
	 * picks something else in the same album
	 */
	public static function get_album($limit) { 

		$results = array(); 

		// Get the last album playbed by us
		$data = $GLOBALS['user']->get_recently_played('1','album'); 
		if ($data['0']) { 
			$where_sql = " WHERE `album`='" . $data['0'] . "' ";
		} 

		$sql = "SELECT `id` FROM `song` $where_sql ORDER BY RAND() LIMIT $limit"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id']; 
		} 

		return $results; 

	} // get_album

	/**
	 * get_artist
	 * This looks at the last artist played and then randomly picks a song from the
	 * same artist
	 */
	public static function get_artist($limit) { 

		$results = array(); 

		$data = $GLOBALS['user']->get_recently_played('1','artist'); 
		if ($data['0']) { 
			$where_sql = " WHERE `artist`='" . $data['0'] . "' "; 
		} 

		$sql = "SELECT `id` FROM `song` $where_sql ORDER BY RAND() LIMIT $limit"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id']; 
		} 
		
		return $results; 

	} // get_artist

	/**
	 * advanced
	 * This processes the results of a post from a form and returns an 
	 * array of song items that were returned from said randomness 
	 */
	public static function advanced($data) { 

		/* Figure out our object limit */
		$limit = intval($data['random']); 

		// Generate our matchlist
		if ($data['catalog'] != '-1') {
			$matchlist['catalog'] = $data['catalog']; 
		}
		if ($data['genre'][0] != '-1') { 
			$matchlist['genre'] = $data['genre']; 
		} 	
print_r($_POST);
	        /* If they've passed -1 as limit then don't get everything */
	        if ($data['limit'] == "-1") { unset($data['limit']); }
	        elseif ($data['random_type'] == 'length') { /* Rien a faire */ }
	        else { $limit_sql = "LIMIT " . $limit; }

	        $where = "1=1 ";
	        if (is_array($matchlist)) { 
	            foreach ($matchlist as $type => $value) {
	                        if (is_array($value)) {
	                                foreach ($value as $v) {
	                                        $v = Dba::escape($v);
	                                        if ($v != $value[0]) { $where .= " OR $type='$v' "; }
	                                        else { $where .= " AND ( $type='$v'"; }
	                                }
        	                        $where .= " ) ";
	                        }
	                        elseif (strlen($value)) {
	                                $value = Dba::escape($value);
	                                $where .= " AND $type='$value' ";
	                        }
	            } // end foreach
		} // end if matchlist

		
	        if ($data['random_type'] == 'full_album') {
	                $query = "SELECT` album`.`id` FROM `song` INNER JOIN `album` ON `song`.`album`=`album`.`id` " . 
				"WHERE $where GROUP BY `song`.`album` ORDER BY RAND() $limit_sql";
	                $db_results = Dba::query($query);
	                while ($row = Dba::fetch_row($db_results)) {
	                        $albums_where .= " OR `song`.`album`=" . $row[0];
	                }
	                $albums_where = ltrim($albums_where," OR");
	                $sql = "SELECT `song`.`id`,`song`.`size`,`song`.`time` FROM `song` WHERE $albums_where ORDER BY `song`.`album`,`song`.`track` ASC";
	        }
	        elseif ($data['random_type'] == 'full_artist') {
	                $query = "SELECT `artist`.`id` FROM `song` INNER JOIN `artist` ON `song`.`artist`=`artist`.`id` " . 
				"WHERE $where GROUP BY `song`.`artist` ORDER BY RAND()  $limit_sql";
	                $db_results = Dba::query($query);
	                while ($row = Dba::fetch_row($db_results)) {
	                        $artists_where .= " OR song.artist=" . $row[0];
	                }
	                $artists_where = ltrim($artists_where," OR");
	                $sql = "SELECT song.id,song.size,song.time FROM song WHERE $artists_where ORDER BY RAND()";
	        }
/* TEMP DISABLE */
//        elseif ($options['random_type'] == 'unplayed') {
//                $uid = $GLOBALS['user']->id;
//                $query = "SELECT song.id,song.size FROM song LEFT JOIN object_count ON song.id = object_count.object_id " .
//                         "WHERE ($where) AND ((object_count.object_type='song' AND user = '$uid') OR object_count.count IS NULL ) " .
//                         "ORDER BY CASE WHEN object_count.count IS NULL THEN RAND() WHEN object_count.count > 4 THEN RAND()*RAND()*object_count.count " .
//                         "ELSE RAND()*object_count.count END " . $limit_sql;
//        } // If unplayed
		else { 
			$sql = "SELECT `id`,`size`,`time` FROM `song` WHERE $where ORDER BY RAND() $limit_sql"; 
		} 

		// Run the query generated above so we can while it
		$db_results = Dba::query($sql); 
		$results = array(); 	

		while ($row = Dba::fetch_assoc($db_results)) { 

			// If size limit is specified
			if ($data['size_limit']) { 
				// Convert
				$new_size = ($row['size'] / 1024) / 1024; 
	
				// Only fuzzy 10 times
				if ($fuzzy_size > 10) { return $results; } 

				// Add and check, skip if over don't return incase theres a smaller one commin round
				if (($size_total + $new_size) > $data['size_limit']) { $fuzzy_size++; continue; } 
				
				$size_total = $size_total + $new_size; 
				$results[] = $row['id']; 

				// If we are within 4mb of target then jump ship
				if (($data['size_limit'] - floor($size_total)) < 4) { return $results; } 
			} // if size_limit

			// If length really does matter
			if ($data['random_type'] == 'length') { 
				// base on min, seconds are for chumps and chumpettes
				$new_time = floor($row['time'] / 60); 

				if ($fuzzy_time > 10) { return $results; } 

				// If the new one would go voer skip!
				if (($time_total + $new_time) > $data['limit']) { $fuzzy_time++; continue; } 

				$time_total = $time_total + $new_time; 
				$results[] = $row['id']; 

				if (($data['limit'] - $time_total) < 2) { return $results; } 

			} // if length does matter 

			if (!$data['size_limit'] AND $data['random_type'] != 'length') { 
				$results[] = $row['id']; 
			} 

		} // end while results


		return $results; 

	} // advanced

	/**
	 * get_type_name
	 * This returns a 'purrty' name for the differnt random types
	 */
	public static function get_type_name($type) { 

		switch ($type) { 
			case 'album':
				return _('Related Album'); 
			break;
			case 'genre': 
				return _('Related Genre'); 
			break;
			case 'artist': 
				return _('Related Artist'); 
			break;
			default: 
				return _('Pure Random'); 
			break;
		} // end switch 

	} // get_type_name

	/**
	 * validiate_type
	 * this validates the random type
	 */
	public static function validate_type($type) { 

                switch ($type) {
			case 'default':
                        case 'genre':
                        case 'album':
                        case 'artist':
                        case 'rated':
				return $type; 
                        break;
                        default:
                                return 'default';
                        break;
                } // end switch
		
		return $type; 

	} // validate_type

} //end of random class

?>
