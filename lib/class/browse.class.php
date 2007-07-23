<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

/**
 * Browse Class
 * This handles all of the sql/filtering
 * on the data before it's thrown out to the templates
 * it also handles pulling back the object_ids and then
 * calling the correct template for the object we are displaying
 */
class Browse { 

	// Public static vars that are cached
	public static $sql; 

	/**
	 * constructor
	 * This should never be called
	 */
	private function __construct() { 

		// Rien a faire

	} // __construct


	/**
	 * set_filter
	 * This saves the filter data we pass it into the session
	 * This is done here so that it's easy to modify where the data
	 * is saved should I change my mind in the future. It also makes
	 * a single point for whitelist tweaks etc
	 */
	public static function set_filter($key,$value) { 

		switch ($key) { 
                        case 'show_art':
                        case 'min_count':
                        case 'unplayed':
                        case 'rated':
                                $key = $_REQUEST['key'];
				if ($_SESSION['browse']['filter'][$key]) { 
					unset($_SESSION['browse']['filter'][$key]);
				} 
				else { 
	                                $_SESSION['browse']['filter'][$key] = 1;
				}
                        break;
			case 'alpha_match':
				$_SESSION['browse']['filter'][$key] = $value; 
			break;
                        default:
                                // Rien a faire
                        break;
                } // end switch
	
	} // set_filter

	/**
 	 * set_type
	 * This sets the type of object that we want to browse by
	 * we do this here so we only have to maintain a single whitelist
	 * and if I want to change the location I only have to do it here
	 */
	public static function set_type($type) { 

		switch($type) { 
			case 'user':
			case 'song':
			case 'album':
			case 'artist':
			case 'genre':
			case 'live_stream':
				$_SESSION['browse']['type'] = $type;
			break;
			default:
				// Rien a faire
			break;
		} // end type whitelist

	} // set_type

	/**
	 * set_sort
	 * This sets the current sort(s)
	 */
	public static function set_sort($sort) { 

		if ($_SESSION['browse']['type'] == 'song') { 
			switch ($sort) { 
				case'title':
					if ($_SESSION['browse']['sort'][$sort] == 'DESC') { 
						$_SESSION['browse']['sort'][$sort] = 'ASC'; 
					}
					else { 
						$_SESSION['browse']['sort'][$sort] = 'DESC'; 
					} 
				break;
			} 
		} 

	} // set_sort

	/**
	 * get_objects
	 * This gets an array of the ids of the objects that we are
	 * currently browsing by it applies the sql and logic based
	 * filters
	 */
	public static function get_objects() { 

		// First we need to get the SQL statement we are going to run
		// This has to run against any possible filters (dependent on type)
		$sql = self::get_sql(); 
		$db_results = Dba::query($sql); 

		$results = array(); 

		while ($data = Dba::fetch_assoc($db_results)) { 
			// If we've hit our offset limit
			if (count($results) >= $GLOBALS['user']->prefs['offset_limit']) { return $results; } 

			// Make sure that this object passes the logic filter
			if (self::logic_filter($data['id'])) { 
				$results[] = $data['id']; 
			} 
		} // end while
		
		return $results; 

	} // get_objects

	/**
	 * get_sql
	 * This returns the sql statement we are going to use this has to be run
	 * every time we get the objects because it depends on the filters and the
	 * type of object we are currently browsing
	 */
	public static function get_sql() { 

		// Get our base SQL must always return ID 
		switch ($_SESSION['browse']['type']) { 
			case 'album':
				$sql = "SELECT `album`.`id` FROM `album` "; 
			break;
			case 'artist':
				$sql = "SELECT `artist`.`id` FROM `artist` "; 
			break;
			case 'genre':
				$sql = "SELECT `genre`.`id` FROM `genre` ";
			break;
			case 'user': 
				$sql = "SELECT `user`.`id` FROM `user` ";
			break;
			case 'live_stream':
				$sql = "SELECT `live_stream`.`id` FROM `live_stream` "; 
			break;
			case 'song':
			default:
				$sql = "SELECT `song`.`id` FROM `song` ";  
			break;
		} // end base sql

		// No sense to go further if we don't have filters
		if (!is_array($_SESSION['browse']['filter'])) { return $sql; } 

		// Foreach the filters and see if any of them can be applied
		// as part of a where statement in this sql (type dependent)
		$where_sql = "WHERE 1=1 AND ";
			
		foreach ($_SESSION['browse']['filter'] as $key=>$value) { 
			$where_sql .= self::sql_filter($key,$value); 	
		} // end foreach

		$where_sql = rtrim($where_sql,'AND ');

		$sql .= $where_sql;

		// Now Add the Order 
		$order_sql = "ORDER BY "; 	

		// If we don't have a sort, then go ahead and return it now
		if (!is_array($_SESSION['browse']['sort'])) { return $sql; }

		foreach ($_SESSION['browse']['sort'] as $key=>$value) { 			
			$order_sql .= self::sql_sort($key,$value); 
		} 
		// Clean her up
		$order_sql = rtrim($order_sql,"ORDER BY "); 
		$order_sql = rtrim($order_sql,","); 

		$sql = $sql . $order_sql; 
		
		return $sql;

	} // get_sql 

	/**
	 * sql_filter
	 * This takes a filter name and value and if it is possible
	 * to filter by this name on this type returns the approiate sql
	 * if not returns nothing
	 */
	private static function sql_filter($filter,$value) { 

		$filter_sql = ''; 

		if ($_SESSION['browse']['type'] == 'song') { 
			switch($filter) { 
				case 'alpha_match':
					$filter_sql = " `song`.`title` LIKE '" . Dba::escape($value) . "%' AND ";
				break;
				case 'unplayed':
					$filter_sql = " `song`.`played`='0' AND "; 
				break;
				default: 
					// Rien a faire
				break;
			} // end list of sqlable filters
		} // if it is a song
		elseif ($_SESSION['browse']['type'] == 'album') { 
			switch($filter) { 
				case 'alpha_match':
					$filter_sql = " `album`.`name` LIKE '" . Dba::escape($value) . "%' AND "; 
				break;
				case 'min_count': 

				break;
				default: 
					// Rien a faire
				break;
			} 
		} // end album 
		elseif ($_SESSION['browse']['type'] == 'artist') { 
			switch($filter) { 
				case 'alpha_match':
					$filter_sql = " `artist`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
				break;
				default:
					// Rien a faire
				break;
			} // end filter
		} // end artist
		elseif ($_SESSION['browse']['type'] == 'live_stream') { 
			switch ($filter) { 
				case 'alpha_match':
					$filter_sql = " `live_stream`.`name` LIKE '" . Dba::escape($value) . "%' AND "; 
				break;
				default: 
					// Rien a faire
				break;
			} // end filter
		} // end live_stream
	
		return $filter_sql; 

	} // sql_filter

	/**
	 * logic_filter
	 * This runs the filters that we can't easily apply
	 * to the sql so they have to be done after the fact
	 * these should be limited as they are often intensive and
	 * require additional queries per object... :(
	 */
	private static function logic_filter($object_id) { 

		return true; 

	} // logic_filter

	/**
	 * sql_sort
	 * This builds any order bys we need to do
	 * to sort the results as best we can, there is also
	 * a logic based sort that will come later as that's
	 * a lot more complicated
	 */
	private static function sql_sort($field,$order) { 

		if ($order != 'DESC') { $order == 'ASC'; } 

		
		if ($_SESSION['browse']['type'] == 'song') { 
			switch($field) { 
				case 'title';
					$sql = "`song`.`title`"; 
				break;
				case 'year':
					$sql = "`song`.`year`"; 
				break;
				default: 
					// Rien a faire
				break;
			} // end switch
		} // end if song 

		return "$sql $order,"; 

	} // sql_sort

	/**
	 * show_objects
	 * This takes an array of objects
	 * and requires the correct template based on the
	 * type that we are currently browsing
	 */
	public static function show_objects($object_ids) { 

		switch ($_SESSION['browse']['type']) { 
			case 'song': 
				show_box_top(_('Songs')); 
				require_once Config::get('prefix') . '/templates/show_songs.inc.php'; 
				show_box_bottom(); 
			break;
			case 'album': 
				show_box_top(_('Albums')); 
				require_once Config::get('prefix') . '/templates/show_albums.inc.php';
				show_box_bottom(); 
			break;
			case 'genre':
				show_box_top(_('Genres')); 
				require_once Config::get('prefix') . '/templates/show_genres.inc.php'; 
				show_box_bottom(); 
			break;
			case 'user':
				show_box_top(_('Manage Users')); 
				require_once Config::get('prefix') . '/templates/show_users.inc.php'; 
				show_box_bottom(); 
			break;
			case 'artist':
				show_box_top(_('Artists')); 
				require_once Config::get('prefix') . '/templates/show_artists.inc.php'; 
				show_box_bottom(); 
			break;
			case 'live_stream': 
				show_box_top(_('Radion Stations')); 
				require_once Config::get('prefix') . '/templates/show_live_streams.inc.php';
				show_box_bottom(); 
			break;
			default: 
				// Rien a faire
			break;
		} // end switch on type

	} // show_object

} // browse
