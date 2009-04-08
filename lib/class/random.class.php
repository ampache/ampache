<?php
/*

 Copyright Ampache.org
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
class Random implements media {

	public $type;
	public $id; 


	/**
	 * Constructor
	 * nothing to see here, move along
	 */
	public function __construct($id) { 

		$this->type = Random::get_id_type($id); 
		$this->id = intval($id); 

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
	public static function play_url($id,$sid='',$force_http='') { 

		if (!$type = self::get_id_type($id)) { 
			return false; 
		} 
	
		$uid = $GLOBALS['user']->id; 
	
		$url = Stream::get_base_url() . "random=1&type=$type&uid=$uid";

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
	        
		/* If they've passed -1 as limit then don't get everything */
	        if ($data['random'] == "-1") { unset($data['random']); }
	        else { $limit_sql = "LIMIT " . Dba::escape($limit); }

	        $where = "1=1 ";
	        if (is_array($matchlist)) { 
	            foreach ($matchlist as $type => $value) {
	                        if (is_array($value)) {
	                                foreach ($value as $v) {
						if (!strlen($v)) { continue; } 
	                                        $v = Dba::escape($v);
	                                        if ($v != $value[0]) { $where .= " OR $type='$v' "; }
	                                        else { $where .= " AND ( $type='$v'"; }
	                                }
					$where .= ")"; 
	                        }
	                        elseif (strlen($value)) {
	                                $value = Dba::escape($value);
	                                $where .= " AND $type='$value' ";
	                        }
	            } // end foreach
		} // end if matchlist
	
		switch ($data['random_type']) { 
			case 'full_album': 
	                	$query = "SELECT `album`.`id` FROM `song` INNER JOIN `album` ON `song`.`album`=`album`.`id` " . 
					"WHERE $where GROUP BY `song`.`album` ORDER BY RAND() $limit_sql";
		                $db_results = Dba::query($query);
		                while ($row = Dba::fetch_assoc($db_results)) {
		                        $albums_where .= " OR `song`.`album`=" . $row['id'];
		                }
		                $albums_where = ltrim($albums_where," OR");
		                $sql = "SELECT `song`.`id`,`song`.`size`,`song`.`time` FROM `song` WHERE $albums_where ORDER BY `song`.`album`,`song`.`track` ASC";
			break; 
			case 'full_artist': 
	                	$query = "SELECT `artist`.`id` FROM `song` INNER JOIN `artist` ON `song`.`artist`=`artist`.`id` " . 
					"WHERE $where GROUP BY `song`.`artist` ORDER BY RAND()  $limit_sql";
		                $db_results = Dba::query($query);
		                while ($row = Dba::fetch_row($db_results)) {
		                        $artists_where .= " OR song.artist=" . $row[0];
		                }
		                $artists_where = ltrim($artists_where," OR");
		                $sql = "SELECT song.id,song.size,song.time FROM song WHERE $artists_where ORDER BY RAND()";
		        break;
			case 'unplayed': 
				$uid = Dba::escape($GLOBALS['user']->id); 
				$sql = "SELECT object_id,COUNT(`id`) AS `total` FROM `object_count` WHERE `user`='$uid' GROUP BY `object_id`";
				$db_results = Dba::query($sql); 

				$in_sql = "`id` IN (";

				while ($row = Dba::fetch_assoc($db_results)) { 
					$row['object_id'] = Dba::escape($row['object_id']); 
					$in_sql .= "'" . $row['object_id'] . "',";  
				} 

				$in_sql = rtrim($in_sql,',') . ')';

				$sql = "SELECT song.id,song.size,song.time FROM song " .
					"WHERE ($where) AND $in_sql ORDER BY RAND() $limit_sql";
			break; 
			case 'high_rating': 
				$sql = "SELECT `rating`.`object_id`,`rating`.`rating` FROM `rating` " . 
					"WHERE `rating`.`object_type`='song' ORDER BY `rating` DESC"; 
				$db_results = Dba::query($sql); 

				// Get all of the ratings for songs
				while ($row = Dba::fetch_assoc($db_results)) { 
					$results[$row['object_id']][] = $row['rating']; 
				} 
				// Calculate the averages 
				foreach ($results as $key=>$rating_array) { 
					$average = intval(array_sum($rating_array) / count($rating_array)); 
					// We have to do this because array_slice doesn't maintain indexes
					$new_key = $average . $key; 
					$ratings[$new_key] = $key;
				} 

				// Sort it by the value and slice at $limit * 2 so we have a little bit of randomness
				krsort($ratings); 
				$ratings = array_slice($ratings,0,$limit*2); 
				
				$in_sql = "`song`.`id` IN ("; 
			
				// Build the IN query, cause if you're OUT it ain't cool
				foreach ($ratings as $song_id) { 
					$key = Dba::escape($song_id);
					$in_sql .= "'$key',"; 
				} 

				$in_sql = rtrim($in_sql,',') . ')'; 

				// Apply true limit and order by rand
				$sql = "SELECT song.id,song.size,song.time FROM song " . 
					"WHERE ($where) AND $in_sql ORDER BY RAND() $limit_sql"; 
			break;
			default: 
				$sql = "SELECT `id`,`size`,`time` FROM `song` WHERE $where ORDER BY RAND() $limit_sql"; 

			break;
		} // end switch on type of random play 

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
			if ($data['length']) { 
				// base on min, seconds are for chumps and chumpettes
				$new_time = floor($row['time'] / 60); 

				if ($fuzzy_time > 10) { return $results; } 
				
				// If the new one would go voer skip!
				if (($time_total + $new_time) > $data['length']) { $fuzzy_time++; continue; } 

				$time_total = $time_total + $new_time; 
				$results[] = $row['id']; 

				// If there are less then 2 min of free space return 
				if (($data['length'] - $time_total) < 2) { return $results; } 

			} // if length does matter 

			if (!$data['size_limit'] AND !$data['length']) { 
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
	 * get_type_id
	 * This takes random type and returns the ID
	 * MOTHER OF PEARL THIS MAKES BABY JESUS CRY
	 * HACK HACK HACK HACK HACK HACK HACK HACK
	 */
	public static function get_type_id($type) { 

		switch ($type) { 
			case 'album': 
				return '1'; 
			break; 
			case 'artist': 
				return '2'; 
			break; 
			case 'tag': 
				return '3'; 
			break; 
			default: 
				return '4'; 
			break; 
		} 

	} // get_type_id

	/**
	 * get_id_name
	 * This takes an ID and returns the 'name' of the random dealie
	 * HACK HACK HACK HACK HACK HACK HACK
	 * Can you tell I don't like this code? 
	 */
	public static function get_id_type($id) { 

		switch ($id) { 
			case '1': 
				return 'album';
			break;
			case '2': 
				return 'artist';
			break; 
			case '3': 
				return 'tag';
			break; 
			default: 
				return 'default'; 
			break; 
		} // end switch 

	} // get_id_name

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

	public function native_stream() { } 
	public function stream_cmd() { } 
	public function has_flag() { }
	public function format() { }

} //end of random class

?>
