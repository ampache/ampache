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
	private function __construct($id) { 

		// Rien a faire

	} // constructor

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
	 * validiate_type
	 * this validates the random type, this is a private function
	 */
	private static function validate_type($type) { 

                switch ($type) {
                        case 'special':
                                $type = $GLOBALS['user']->prefs['random_method'];
			break;
                        case 'genre':
                        case 'album':
                        case 'artist':
                        case 'rated':
                        break;
                        default:
                                return false;
                        break;
                } // end switch
		
		return $type; 

	} // validate_type

} //end of random class

?>
