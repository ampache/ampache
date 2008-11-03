<?php
/*

 Copyright (c) Ampache.org
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
 * Browse Class
 * This handles all of the sql/filtering
 * on the data before it's thrown out to the templates
 * it also handles pulling back the object_ids and then
 * calling the correct template for the object we are displaying
 */
class Browse { 

	// Public static vars that are cached
	public static $sql; 
	public static $start;
	public static $offset; 
	public static $total_objects; 
	public static $type; 

	// Boolean if this is a simple browse method (use different paging code)
	public static $simple_browse; 

	// Static Content, this is defaulted to false, if set to true then wen can't
	// apply any filters that would change the result set. 
	public static $static_content = false; 
	private static $_cache = array();  


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
				if (self::get_filter($key)) { 
					unset($_SESSION['browse']['filter'][self::$type][$key]);
				} 
				else { 
				        $_SESSION['browse']['filter'][self::$type][$key] = 1; 
				}
			break;
			case 'tag':
				if (is_array($value)) { 
					$_SESSION['browse']['filter'][self::$type][$key] = $value;
				} 
				elseif (is_numeric($value)) { 
					$_SESSION['browse']['filter'][self::$type][$key] = array($value);
				} 
				else { 
					$_SESSION['browse']['filter'][self::$type][$key] = array();
				} 
			break;
			case 'artist':
			case 'album':
				$_SESSION['browse']['filter'][self::$type][$key] = $value;
			break;
			case 'min_count':
	
			case 'unplayed':
			case 'rated':

			break; 
			case 'alpha_match':
			case 'starts_with': 
				if (self::$static_content) { return false; }
				$_SESSION['browse']['filter'][self::$type][$key] = $value; 
			break;
			case 'playlist_type': 
				// They must be content managers to turn this off
				if ($_SESSION['browse']['filter'][self::$type][$key] AND Access::check('interface','50')) { unset($_SESSION['browse']['filter'][self::$type][$key]); } 
				else { $_SESSION['browse']['filter'][self::$type][$key] = '1'; } 
			break; 
                        default:
                                // Rien a faire
				return false; 
                        break;
                } // end switch

		// If we've set a filter we need to reset the totals
		self::reset_total(); 
		self::set_start(0); 

		return true; 
	
	} // set_filter

	/**
	 * reset
	 * Reset everything
	 */
	public static function reset() { 

		self::reset_filters(); 
		self::reset_total(); 
		self::reset_join(); 
		self::reset_supplemental_objects(); 
		self::set_simple_browse(0); 
		self::set_start(0); 

	} // reset

	/**
	 * reset_join
	 * clears the joins if there are any
	 */
	public static function reset_join() { 

		unset($_SESSION['browse']['join'][self::$type]); 

	} // reset_join

	/**
	 * reset_filter
	 * This is a wrapper function that resets the filters 
	 */
	public static function reset_filters() { 

		$_SESSION['browse']['filter'] = array(); 

	} // reset_filters

	/**
	 * reset_supplemental_objects
	 * This clears any sup objects we've added, normally called on every set_type
	 */
	public static function reset_supplemental_objects() { 

		$_SESSION['browse'][self::$type]['supplemental'] = array(); 

	} // reset_supplemental_objects

	/**
	 * reset_total
	 * This resets the total for the browse type
	 */
	public static function reset_total() { 

		unset($_SESSION['browse']['total'][self::$type]); 

	} // reset_total

	/**
	 * get_filter
	 * returns the specified filter value
	 */
	public static function get_filter($key) { 
	
		// Simple enough, but if we ever move this crap 
		return $_SESSION['browse']['filter'][self::$type][$key]; 

	} // get_filter

	/**
	 * get_total
	 * This returns the toal number of obejcts for this current sort type. If it's already cached used it!
	 * if they pass us an array then use that!
	 */
	public static function get_total($objects=false) { 
		
		// If they pass something then just return that
		if (is_array($objects) and !self::is_simple_browse()) { 
			return count($objects); 
		} 

		// See if we can find it in the cache
		if (isset($_SESSION['browse']['total'][self::$type])) { 
			return $_SESSION['browse']['total'][self::$type]; 
		} 

		$db_results = Dba::query(self::get_base_sql() . self::get_filter_sql() . self::get_sort_sql()); 
		$num_rows = Dba::num_rows($db_results); 

		$_SESSION['browse']['total'][self::$type] = $num_rows; 

		return $num_rows; 

	} // get_total

	/**
	 * get_allowed_filters
	 * This returns an array of the allowed filters based on the type of object we are working
	 * with, this is used to display the 'filter' sidebar stuff, must be called post browse stuff
	 */
	public static function get_allowed_filters() { 

		switch (self::$type) { 
			case 'album': 
				$valid_array = array('show_art','starts_with','alpha_match'); 
			break; 
			case 'artist': 
			case 'song': 
			case 'live_stream': 
				$valid_array = array('alpha_match','starts_with'); 	
			break; 
			case 'playlist': 
				$valid_array = array('alpha_match','starts_with'); 
				if (Access::check('interface','50')) { 
					array_push($valid_array,'playlist_type'); 
				} 
			break; 
			case 'tag': 
				$valid_array = array('object_type'); 
			break; 
			default: 
				$valid_array = array(); 
			break; 
		} // switch on the browsetype

		return $valid_array; 

	} // get_allowed_filters

	/**
 	 * set_type
	 * This sets the type of object that we want to browse by
	 * we do this here so we only have to maintain a single whitelist
	 * and if I want to change the location I only have to do it here
	 */
	public static function set_type($type) { 

		switch($type) { 
			case 'user':
			case 'playlist':
			case 'playlist_song': 
			case 'song':
			case 'flagged':
			case 'catalog':
			case 'album':
			case 'artist':
			case 'tag':
			case 'playlist_localplay': 
			case 'shoutbox': 
			case 'live_stream':
				// Set it
				self::$type = $type; 
				self::load_start(); 
			break;
			default:
				// Rien a faire
			break;
		} // end type whitelist
	} // set_type

	/**
	 * get_type
	 * This returns the type of the browse we currently are using
	 */
	public static function get_type() { 

		return self::$type; 

	} // get_type

	/**
	 * set_sort
	 * This sets the current sort(s)
	 */
	public static function set_sort($sort,$order='') { 

		switch (self::get_type()) { 
			case 'playlist_song': 
			case 'song': 
				$valid_array = array('title','year','track','time','album','artist'); 
			break;
			case 'artist': 
				$valid_array = array('name','album'); 
			break;
			case 'tag': 
				$valid_array = array('tag'); 
			break; 
			case 'album': 
				$valid_array = array('name','year','artist'); 
			break;
			case 'playlist': 
				$valid_array = array('name','user');
			break; 
			case 'shoutbox': 
				$valid_array = array('date','user','sticky'); 
			break; 
			case 'live_stream': 
				$valid_array = array('name','call_sign','frequency'); 
			break;
                        case 'user':
                                $valid_array = array('fullname','username','last_seen','create_date');
                        break;
		} // end switch  

		// If it's not in our list, smeg off!
		if (!in_array($sort,$valid_array)) { 
			return false; 
		}

		if ($order) { 
			$order = ($order == 'DESC') ? 'DESC' : 'ASC'; 
			$_SESSION['browse']['sort'][self::$type] = array(); 
			$_SESSION['browse']['sort'][self::$type][$sort] = $order; 
		} 	 
		elseif ($_SESSION['browse']['sort'][self::$type][$sort] == 'DESC') { 
			// Reset it till I can figure out how to interface the hotness
			$_SESSION['browse']['sort'][self::$type] = array(); 
			$_SESSION['browse']['sort'][self::$type][$sort] = 'ASC'; 
		}
		else { 
			// Reset it till I can figure out how to interface the hotness
			$_SESSION['browse']['sort'][self::$type] = array(); 
			$_SESSION['browse']['sort'][self::$type][$sort] = 'DESC'; 
		} 
		
		self::resort_objects(); 

	} // set_sort

	/**
	 * set_join 
	 * This sets the joins for the current browse object
	 */
	public static function set_join($type,$table,$source,$dest) { 

		$_SESSION['browse']['join'][self::$type][$table] = strtoupper($type) . ' JOIN ' . $table . ' ON ' . $source . '=' . $dest; 

	} // set_join

	/**
	 * set_start
	 * This sets the start point for our show functions
	 * We need to store this in the session so that it can be pulled
	 * back, if they hit the back button
	 */
	public static function set_start($start) { 

		if (!self::$static_content) { 
			$_SESSION['browse'][self::$type]['start'] = intval($start); 
		} 
		self::$start = intval($start);  

	} // set_start

	/**
	 * set_simple_browse
	 * This sets the current browse object to a 'simple' browse method
	 * which means use the base query provided and expand from there
	 */
	public static function set_simple_browse($value) { 

		$value = make_bool($value); 
		$_SESSION['browse']['simple'][self::$type] = $value;  

	} // set_simple_browse

	/**
	 * set_static_content
	 * This sets true/false if the content of this browse
	 * should be static, if they are then content filtering/altering
	 * methods will be skipped
	 */
	public static function set_static_content($value) { 

		$value = make_bool($value); 
		self::$static_content = $value; 

		// We want to start at 0 it's static
		if ($value) { 
			self::set_start('0'); 
		} 

		$_SESSION['browse'][self::$type]['static'] = $value; 

	} // set_static_content

	/**
	 * is_simple_browse
	 * this returns true or false if the current browse type is set to static
	 */
	public static function is_simple_browse() { 

		return $_SESSION['browse']['simple'][self::$type]; 

	} // is_simple_browse

	/**
	 * load_start
	 * This returns a stored start point for the browse mojo
	 */
	public static function load_start() { 

		self::$start = intval($_SESSION['browse'][self::$type]['start']); 

	} // end load_start

	/**
	 * get_saved
	 * This looks in the session for the saved 
	 * stuff and returns what it finds
	 */
	public static function get_saved() { 

		// See if we have it in the local cache first
		if (is_array(self::$_cache['browse'][self::$type])) { 
			return self::$_cache['browse'][self::$type]; 
		} 

		if (!self::is_simple_browse()) { 
			// If not then we're going to need to read from the database :(
			$sid = session_id() . '::' . self::$type; 

			$sql = "SELECT `data` FROM `tmp_browse` WHERE `sid`='$sid'"; 
			$db_results = Dba::read($sql); 

			$row = Dba::fetch_assoc($db_results); 

			$objects = unserialize($row['data']); 
		} 
		else { 
			$objects = self::get_objects(); 
		} 

		return $objects; 

	} // get_saved

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
			$results[] = $data;
		}

		$results = self::post_process($results);
		$filtered = array();
		foreach ($results as $data) { 
			// Make sure that this object passes the logic filter
			if (self::logic_filter($data['id'])) { 
				$filtered[] = $data['id']; 
			} 
		} // end while
	
		// Save what we've found and then return it
		self::save_objects($filtered); 

		return $filtered; 

	} // get_objects

	/**
	 * get_supplemental_objects
	 * This returns an array of 'class','id' for additional objects that need to be
	 * created before we start this whole browsing thing
	 */
	public static function get_supplemental_objects() { 

		$objects = $_SESSION['browse']['supplemental'][self::$type]; 
		
		if (!is_array($objects)) { $objects = array(); } 

		return $objects; 

	} // get_supplemental_objects

	/**
	 * add_supplemental_object
	 * This will add a suplemental object that has to be created
	 */
	public static function add_supplemental_object($class,$uid) { 

		$_SESSION['browse']['supplemental'][self::$type][$class] = intval($uid); 

		return true; 

	} // add_supplemental_object

	/**
	 * get_base_sql
	 * This returns the base SQL (select + from) for the different types
	 */
	private static function get_base_sql() { 

                switch (self::$type) {
                        case 'album':
                                $sql = "SELECT DISTINCT `album`.`id` FROM `album` ";
                        break;
                        case 'artist':
                                $sql = "SELECT DISTINCT `artist`.`id` FROM `artist` ";
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
                        case 'playlist':
                                $sql = "SELECT `playlist`.`id` FROM `playlist` ";
                        break;
			case 'flagged': 
				$sql = "SELECT `flagged`.`id` FROM `flagged` ";
			break;
			case 'shoutbox': 
				$sql = "SELECT `user_shout`.`id` FROM `user_shout` "; 
			break; 
			case 'tag': 
				$sql = "SELECT `tag`.`id` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` "; 
			break; 
			case 'playlist_song': 
                        case 'song':
                        default:
                                $sql = "SELECT DISTINCT `song`.`id` FROM `song` ";
                        break;
                } // end base sql

		return $sql; 

	} // get_base_sql

	/**
	 * get_filter_sql
	 * This returns the filter part of the sql statement
	 */
	private static function get_filter_sql() { 

		if (!is_array($_SESSION['browse']['filter'][self::$type])) { 
			return ''; 
		} 

		$sql = "WHERE 1=1 AND ";

		foreach ($_SESSION['browse']['filter'][self::$type] as $key=>$value) { 
			$sql .= self::sql_filter($key,$value); 
		} 

		$sql = rtrim($sql,'AND ') . ' ';  

		return $sql; 

	} // get_filter_sql

	/**
	 * get_sort_sql
	 * Returns the sort sql part 
	 */
	private static function get_sort_sql() { 
		
		if (!is_array($_SESSION['browse']['sort'][self::$type])) { return ''; } 

		$sql = 'ORDER BY '; 

		foreach ($_SESSION['browse']['sort'][self::$type] as $key=>$value) { 
			$sql .= self::sql_sort($key,$value); 
		} 

		$sql = rtrim($sql,'ORDER BY '); 
		$sql = rtrim($sql,','); 

		return $sql; 	

	} // get_sort_sql

	/**
	 * get_limit_sql
	 * This returns the limit part of the sql statement
	 */
	private static function get_limit_sql() { 

		if (!self::is_simple_browse()) { return ''; } 

		$sql = ' LIMIT ' . intval(self::$start) . ',' . intval(self::$offset); 

		return $sql; 

	} // get_limit_sql 

	/**
	 * get_join_sql
	 * This returns the joins that this browse may need to work correctly
	 */
	private static function get_join_sql() { 
		
		if (!is_array($_SESSION['browse']['join'][self::$type])) { 
			return ''; 
		} 

		$sql = ''; 

		foreach ($_SESSION['browse']['join'][self::$type] AS $join) { 
			$sql .= $join . ' '; 
		} 

		return $sql; 	

	} // get_join_sql

	/**
	 * get_sql
	 * This returns the sql statement we are going to use this has to be run
	 * every time we get the objects because it depends on the filters and the
	 * type of object we are currently browsing
	 */
	public static function get_sql() { 

		$sql = self::get_base_sql(); 

		// No matter what we have to check the catalog based filters... maybe I'm not sure about this
		//$where_sql .= self::sql_filter('catalog',''); 

		$filter_sql = self::get_filter_sql(); 
		$join_sql = self::get_join_sql(); 
		$order_sql = self::get_sort_sql(); 
		$limit_sql = self::get_limit_sql(); 

		$final_sql = $sql . $join_sql . $filter_sql . $order_sql . $limit_sql;  

		return $final_sql;

	} // get_sql 

	/**
  	 * post_process
	 * This does some additional work on the results that we've received before returning them
	 */
	private static function post_process($results) {

		$tags = $_SESSION['browse']['filter']['tag'];

		if (!is_array($tags) || sizeof($tags) < 2) { 
			return $results;
		} 
		$cnt = sizeof($tags);
		$ar = array();

		foreach($results as $row) { 
			$ar[$row['id']]++;
		}

		$res = array();

		foreach($ar as $k=>$v) { 
			if ($v >= $cnt) { 
				$res[] = array('id' => $k);
			}
		} // end foreach 

		return $res;

	} // post_process

	/**
	 * sql_filter
	 * This takes a filter name and value and if it is possible
	 * to filter by this name on this type returns the approiate sql
	 * if not returns nothing
	 */
	private static function sql_filter($filter,$value) { 

		$filter_sql = ''; 
		
		if (self::$type == 'song') { 
			switch($filter) { 
				case 'alpha_match':
					$filter_sql = " `song`.`title` LIKE '%" . Dba::escape($value) . "%' AND ";
				break;
				case 'starts_with': 
					$filter_sql = " `song`.`title` LIKE '" . Dba::escape($value) . "%' AND "; 
				break; 
				case 'unplayed':
					$filter_sql = " `song`.`played`='0' AND "; 
				break;
			        case 'album':
					$filter_sql = " `song`.`album` = '". Dba::escape($value) . "' AND ";
				break;
				case 'artist':
					$filter_sql = " `song`.`artist` = '". Dba::escape($value) . "' AND ";
				break;
				case 'catalog': 
					$catalogs = $GLOBALS['user']->get_catalogs(); 
					if (!count($catalogs)) { break; } 
					$filter_sql .= " `song`.`catalog` IN (" . implode(',',$GLOBALS['user']->get_catalogs()) . ") AND "; 
				break; 
				default: 
					// Rien a faire
				break;
			} // end list of sqlable filters

		} // if it is a song
		elseif (self::$type == 'album') { 
			switch($filter) { 
				case 'alpha_match':
					$filter_sql = " `album`.`name` LIKE '%" . Dba::escape($value) . "%' AND "; 
				break;
				case 'starts_with': 
					$filter_sql = " `album`.`name` LIKE '" . Dba::escape($value) . "%' AND "; 
				break; 
				case 'min_count': 

				break;
			        case 'artist':
					$filter_sql = " `artist`.`id` = '". Dba::escape($value) . "' AND ";
				break;
				default: 
					// Rien a faire
				break;
			} 
		} // end album 
		elseif (self::$type == 'artist') { 
			switch($filter) { 
				case 'alpha_match':
					$filter_sql = " `artist`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
				break;
				case 'starts_with': 
					$filter_sql = " `artist`.`name` LIKE '" . Dba::escape($value) . "%' AND "; 
				break;
				default:
					// Rien a faire
				break;
			} // end filter
		} // end artist
		elseif (self::$type == 'live_stream') { 
			switch ($filter) { 
				case 'alpha_match':
					$filter_sql = " `live_stream`.`name` LIKE '%" . Dba::escape($value) . "%' AND "; 
				break;
				case 'starts_with': 
					$filter_sql = " `live_stream`.`name` LIKE '" . Dba::escape($value) . "%' AND "; 
				break; 
				default: 
					// Rien a faire
				break;
			} // end filter
		} // end live_stream
		elseif (self::$type == 'playlist') { 
			switch ($filter) { 
				case 'alpha_match': 
					$filter_sql = " `playlist`.`name` LIKE '%" . Dba::escape($value) . "%' AND "; 
				break;
				case 'starts_with': 
					$filter_sql = " `playlist`.`name` LIKE '" . Dba::escape($value) . "%' AND "; 
				break;
				case 'playlist_type': 
					$user_id = intval($GLOBALS['user']->id); 
					$filter_sql = " (`playlist`.`type` = 'public' OR `playlist`.`user`='$user_id') AND "; 
				break; 
				default; 
					// Rien a faire
				break;
			} // end filter
		} // end playlist

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

		// Depending on the type of browsing we are doing we can apply different filters that apply to different fields
		switch (self::$type) { 
			case 'song': 
				switch($field) { 
					case 'title';
						$sql = "`song`.`title`"; 
					break;
					case 'year':
						$sql = "`song`.`year`"; 
					break;
					case 'time': 
						$sql = "`song`.`time`"; 
					break;
					case 'track': 
						$sql = "`song`.`track`"; 
					break;
					case 'album': 
						$sql = '`album`.`name`'; 
						self::set_join('left','`album`','`album`.`id`','`song`.`album`'); 
					break; 
					case 'artist': 
						$sql = '`artist`.`name`'; 
						self::set_join('left','`artist`','`artist`.`id`','`song`.`artist`'); 
					break; 
					default: 
						// Rien a faire
					break;
				} // end switch
			break;
			case 'album': 
				switch($field) { 
					case 'name': 
						$sql = "`album`.`name` $order, `album`.`disk`"; 
					break;
					case 'artist': 
						$sql = "`artist`.`name`"; 
						self::set_join('left','`song`','`song`.`album`','`album`.`id`'); 
						self::set_join('left','`artist`','`song`.`artist`','`artist`.`id`'); 
					break;
					case 'year': 
						$sql = "`album`.`year`"; 
					break;
				} // end switch
			break;
			case 'artist': 
				switch ($field) { 
					case 'name': 
						$sql = "`artist`.`name`"; 
					break;
				} // end switch 
			break;
			case 'playlist': 
				switch ($field) { 
					case 'type':
						$sql = "`playlist`.`type`"; 
					break; 
					case 'name':
						$sql = "`playlist`.`name`"; 
					break;
					case 'user': 
						$sql = "`playlist`.`user`";
					break; 
				} // end switch
			break; 
			case 'live_stream': 
				switch ($field) { 
					case 'name':
						$sql = "`live_stream`.`name`"; 
					break;
					case 'call_sign':
						$sql = "`live_stream`.`call_sign`";
					break;
					case 'frequency': 
						$sql = "`live_stream`.`frequency`"; 
					break; 
				} // end switch
			break;
			case 'genre': 
				switch ($field) { 
					case 'name': 
						$sql = "`genre`.`name`"; 
					break;
				} // end switch
			break;
                        case 'user':
                                switch ($field) {
                                        case 'username':
                                                $sql = "`user`.`username`";
                                        break;
                                        case 'fullname':
                                                $sql = "`user`.`fullname`";
                                        break;
                                        case 'last_seen':
                                                $sql = "`user`.`last_seen`";
                                        break;
                                        case 'create_date':
                                                $sql = "`user`.`create_date`";
                                        break;
                                } // end switch
                        break;
			default: 
				// Rien a faire
			break;
		} // end switch

		if ($sql) { $sql_sort = "$sql $order,"; } 
		
		return $sql_sort; 

	} // sql_sort

	/**
	 * show_objects
	 * This takes an array of objects
	 * and requires the correct template based on the
	 * type that we are currently browsing
	 */
	public static function show_objects($object_ids=false) { 
		
		if (self::is_simple_browse()) { 
			$object_ids = self::get_saved(); 
		} 
		else { 
			$object_ids = is_array($object_ids) ? $object_ids : self::get_saved();
			self::save_objects($object_ids); 
		} 
	
		// Reset the total items
		self::$total_objects = self::get_total($object_ids); 

		// Limit is based on the users preferences if this is not a simple browse because we've got too much here
		if (count($object_ids) > self::$start AND !self::is_simple_browse()) { 
			$object_ids = array_slice($object_ids,self::$start,self::$offset); 
		} 

		// Format any matches we have so we can show them to the masses
		if ($filter_value = self::get_filter('alpha_match')) { 
			$match = ' (' . $filter_value . ')'; 
		}
		elseif ($filter_value = self::get_filter('starts_with')) { 
			$match = ' (' . $filter_value . ')'; 
		} 

		// Set the correct classes based on type
    		$class = "box browse_".self::$type;

		// Load any additional object we need for this
		$extra_objects = self::get_supplemental_objects(); 
		foreach ($extra_objects as $class_name => $id) { 
			${$class_name} = new $class_name($id); 
		} 
		
		Ajax::start_container('browse_content');
		// Switch on the type of browsing we're doing
		switch (self::$type) { 
			case 'song': 
				show_box_top(_('Songs') . $match, $class); 
				Song::build_cache($object_ids); 
				require_once Config::get('prefix') . '/templates/show_songs.inc.php'; 
				show_box_bottom(); 
			break;
			case 'album': 
				show_box_top(_('Albums') . $match, $class); 
				Album::build_cache($object_ids,'extra');
				require_once Config::get('prefix') . '/templates/show_albums.inc.php';
				show_box_bottom(); 
			break;
			case 'user':
				show_box_top(_('Manage Users') . $match, $class); 
				require_once Config::get('prefix') . '/templates/show_users.inc.php'; 
				show_box_bottom(); 
			break;
			case 'artist':
				show_box_top(_('Artists') . $match, $class); 
				Artist::build_cache($object_ids,'extra'); 
				require_once Config::get('prefix') . '/templates/show_artists.inc.php'; 
				show_box_bottom(); 
			break;
			case 'live_stream': 
				show_box_top(_('Radio Stations') . $match, $class); 
				require_once Config::get('prefix') . '/templates/show_live_streams.inc.php';
				show_box_bottom(); 
			break;
			case 'playlist': 
				Playlist::build_cache($object_ids); 
				show_box_top(_('Playlists') . $match, $class);
				require_once Config::get('prefix') . '/templates/show_playlists.inc.php'; 
				show_box_bottom(); 
			break;
			case 'playlist_song': 
				show_box_top(_('Playlist Songs') . $match,$class); 
				require_once Config::get('prefix') . '/templates/show_playlist_songs.inc.php'; 
				show_box_bottom(); 
			break; 
			case 'playlist_localplay': 
				show_box_top(_('Current Playlist')); 
				require_once Config::get('prefix') . '/templates/show_localplay_playlist.inc.php'; 
				show_box_bottom(); 
			break;
			case 'catalog': 
				show_box_top(_('Catalogs'), $class); 
				require_once Config::get('prefix') . '/templates/show_catalogs.inc.php';
				show_box_bottom(); 
			break;
			case 'shoutbox': 
				show_box_top(_('Shoutbox Records'),$class); 
				require_once Config::get('prefix') . '/templates/show_manage_shoutbox.inc.php'; 
				show_box_bottom(); 
			break; 
			case 'flagged':
				show_box_top(_('Flagged Records'),$class); 
				require_once Config::get('prefix') . '/templates/show_flagged.inc.php'; 
				show_box_bottom(); 
			break;
			case 'tag': 
				Tag::build_cache($tags); 
				show_box_top(_('Tag Cloud'),$class); 
				require_once Config::get('prefix') . '/templates/show_tagcloud.inc.php'; 
				show_box_bottom(); 
			break; 
			default: 
				// Rien a faire
			break;
		} // end switch on type

		Ajax::end_container(); 

	} // show_object

	/**
	 * save_objects
	 * This takes the full array of object ides, often passed into show and then
	 * if nessecary it saves them into the session
	 */
	public static function save_objects($object_ids) { 

		// Saving these objects has two operations, one hold it in 
		// a local variable and then second hold it in a row in the tmp_browse
		// table
		self::$_cache['browse'][self::$type] = $object_ids; 	

		// Only do this if it's a not a simple browse
		if (!self::is_simple_browse()) { 
			$sid = session_id() . '::' . self::$type; 
			$data = Dba::escape(serialize($object_ids)); 

			$sql = "REPLACE INTO `tmp_browse` SET `data`='$data', `sid`='$sid'"; 
			$db_results = Dba::write($sql); 

			self::$total_objects = count($object_ids); 
		} // save it 

		return true; 

	} // save_objects

	/**
	 * resort_objects
	 * This takes the existing objects, looks at the current
	 * sort method and then re-sorts them This is internally
	 * called by the set_sort() function 
	 */
	private static function resort_objects() { 

		// There are two ways to do this.. the easy way... 
		// and the vollmer way, hopefully we don't have to
		// do it the vollmer way
		if (self::is_simple_browse()) { 
			$sql = self::get_sql(); 
		} 
		else { 
			// First pull the objects
			$objects = self::get_saved(); 

			// If there's nothing there don't do anything
			if (!count($objects)) { return false; } 
			$type = self::$type;
			$where_sql = "WHERE `$type`.`id` IN (";

			foreach ($objects as $object_id) {
				$object_id = Dba::escape($object_id);
				$where_sql .= "'$object_id',";
			} 
			$where_sql = rtrim($where_sql,','); 

			$where_sql .= ")";

			$sql = self::get_base_sql();
			$sql .= $where_sql;

			$order_sql = " ORDER BY ";

	                foreach ($_SESSION['browse']['sort'][self::$type] as $key=>$value) {
	                        $order_sql .= self::sql_sort($key,$value);
	                } 
	                // Clean her up
	                $order_sql = rtrim($order_sql,"ORDER BY ");
	                $order_sql = rtrim($order_sql,",");
	                $sql = $sql . self::get_join_sql() . $order_sql;
		} // if not simple

		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id']; 
		} 
		
		self::save_objects($results); 

		return true; 

	} // resort_objects

	/**
	 * _auto_init
	 * this function reloads information back from the session 
	 * it is called on creation of the class
	 */
	public static function _auto_init() { 

		self::$offset = Config::get('offset_limit') ? Config::get('offset_limit') : '25';

	} // _auto_init
	
	public static function set_filter_from_request($r)
	{
	  foreach ($r as $k=>$v) {
	    //reinterpret v as a list of int
	    $vl = explode(',', $v);
	    $ok = 1;
	    foreach($vl as $i) {
	      if (!is_numeric($i)) {
		$ok = 0;
		break;
	      }
	    }
	    if ($ok)
	      if (sizeof($vl) == 1)
	        self::set_filter($k, $vl[0]);
	      else
	        self::set_filter($k, $vl);
	  }
	}

} // browse
