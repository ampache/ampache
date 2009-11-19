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
 * Query Class
 * This handles all of the sql/filtering for the ampache database
 * this was seperated out from browse, to accomodate Dynamic Playlists
 */
class Query { 

	// Public static vars that are cached
	public static $sql; 
	public static $start;
	public static $offset; 
	public static $total_objects; 
	public static $type; 

	// Static Content, this is defaulted to false, if set to true then when we can't
	// apply any filters that would change the result set. 
	public static $static_content = false; 

	// Private cache information
	private static $_cache = array();  
	private static $_state = array(); 

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
					unset(self::$_state['filter'][self::$type][$key]);
				} 
				else { 
				        self::$_state['filter'][self::$type][$key] = 1; 
				}
			break;
			case 'tag':
				if (is_array($value)) { 
					self::$_state['filter'][self::$type][$key] = $value;
				} 
				elseif (is_numeric($value)) { 
					self::$_state['filter'][self::$type][$key] = array($value);
				} 
				else { 
					self::$_state['filter'][self::$type][$key] = array();
				} 
			break;
			case 'artist':
			case 'album':
				self::$_state['filter'][self::$type][$key] = $value;
			break;
			case 'min_count':
			case 'unplayed':
			case 'rated':

			break; 
			case 'add_lt': 
			case 'add_gt': 
			case 'update_lt': 
			case 'update_gt':
				self::$_state['filter'][self::$type][$key] = intval($value); 	
			break; 
			case 'exact_match': 
			case 'alpha_match':
			case 'starts_with': 
				if (self::$static_content) { return false; }
				self::$_state['filter'][self::$type][$key] = $value; 
			break;
			case 'playlist_type': 
				// They must be content managers to turn this off
				if (self::$_state['filter'][self::$type][$key] AND Access::check('interface','50')) { unset(self::$_state['filter'][self::$type][$key]); } 
				else { self::$_state['filter'][self::$type][$key] = '1'; } 
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
	 * Reset everything, this should only be called when we are starting fresh
	 */
	public static function reset() { 

		self::reset_base(); 
		self::reset_filters(); 
		self::reset_total(); 
		self::reset_join(); 
		self::reset_select(); 
		self::reset_having(); 
		self::set_is_simple(0); 
		self::set_start(0); 

	} // reset

	/**
	 * reset_base
	 * this resets the base string
	 */
	public static function reset_base() { 

		self::$_state['base'][self::$type] = NULL; 

	} // reset_base

	/**
	 * reset_select
	 * This resets the select fields that we've added so far
	 */
	public static function reset_select() { 

		self::$_state['select'][self::$type] = array(); 

	} // reset_select 

	/**
	 * reset_having
	 * Null out the having clause
	 */
	public static function reset_having() { 

		unset(self::$_state['having'][self::$type]); 

	} // reset_having

	/**
	 * reset_join
	 * clears the joins if there are any
	 */
	public static function reset_join() { 

		unset(self::$_state['join'][self::$type]); 

	} // reset_join

	/**
	 * reset_filter
	 * This is a wrapper function that resets the filters 
	 */
	public static function reset_filters() { 

		self::$_state['filter'] = array(); 

	} // reset_filters

	/**
	 * reset_total
	 * This resets the total for the browse type
	 */
	public static function reset_total() { 

		unset(self::$_state['total'][self::$type]); 

	} // reset_total

	/**
	 * get_filter
	 * returns the specified filter value
	 */
	public static function get_filter($key) { 
	
		// Simple enough, but if we ever move this crap 
		return self::$_state['filter'][self::$type][$key]; 

	} // get_filter

	/**
	 * get_start
	 * This returns the current value of the start
	 */ 
	public static function get_start() { 

		return self::$start;
	
	} // get_start

	/**
	 * get_offset
	 * This returns the current offset
	 */
	public static function get_offset() { 

		return self::$offset;

	} // get_offset

	/**
	 * get_total
	 * This returns the toal number of obejcts for this current sort type. If it's already cached used it!
	 * if they pass us an array then use that!
	 */
	public static function get_total($objects=false) { 
		
		// If they pass something then just return that
		if (is_array($objects) and !self::is_simple()) { 
			return count($objects); 
		} 

		// See if we can find it in the cache
		if (isset(self::$_state['total'][self::$type])) { 
			return self::$_state['total'][self::$type]; 
		} 

		$db_results = Dba::read(self::get_sql(false)); 
		$num_rows = Dba::num_rows($db_results); 

		self::$_state['total'][self::$type] = $num_rows; 

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
				$valid_array = array('add_lt','add_gt','update_lt','update_gt','show_art',
						'starts_with','exact_match','alpha_match'); 
			break; 
			case 'artist': 
			case 'song': 
				$valid_array = array('add_lt','add_gt','update_lt','update_gt','exact_match','alpha_match','starts_with'); 
			break; 
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
				$valid_array = array('object_type','exact_match','alpha_match'); 
			break; 
			case 'video': 
				$valid_array = array('starts_with','exact_match','alpha_match'); 
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
			case 'video': 
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
			case 'democratic': 
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
			case 'video': 
				$valid_array = array('title','resolution','length','codec'); 
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
			self::$_state['sort'][self::$type] = array(); 
			self::$_state['sort'][self::$type][$sort] = $order; 
		} 	 
		elseif (self::$_state['sort'][self::$type][$sort] == 'DESC') { 
			// Reset it till I can figure out how to interface the hotness
			self::$_state['sort'][self::$type] = array(); 
			self::$_state['sort'][self::$type][$sort] = 'ASC'; 
		}
		else { 
			// Reset it till I can figure out how to interface the hotness
			self::$_state['sort'][self::$type] = array(); 
			self::$_state['sort'][self::$type][$sort] = 'DESC'; 
		} 
		
		self::resort_objects(); 

	} // set_sort

	/**
	 * set_offset
	 * This sets the current offset of this query
	 */
	public static function set_offset($offset) { 

		self::$offset = abs($offset);

	} // set_offset

	/**
	 * set_select
	 * This appends more information to the select part of the SQL statement, we're going to move to the
	 * %%SELECT%% style queries, as I think it's the only way to do this.... 
	 */
	public static function set_select($field) { 

		self::$_state['select'][self::$type][] = $field; 

	} // set_select

	/**
	 * set_join 
	 * This sets the joins for the current browse object
	 */
	public static function set_join($type,$table,$source,$dest,$priority=100) { 

		self::$_state['join'][self::$type][$priority][$table] = strtoupper($type) . ' JOIN ' . $table . ' ON ' . $source . '=' . $dest; 

	} // set_join

	/**
	 * set_having
	 * This sets the "HAVING" part of the query, we can only have one.. god this is ugly
	 */
	public static function set_having($condition) { 

		self::$_state['having'][self::$type] = $condition; 

	} // set_having 

	/**
	 * set_start
	 * This sets the start point for our show functions
	 * We need to store this in the session so that it can be pulled
	 * back, if they hit the back button
	 */
	public static function set_start($start) { 

		if (!self::$static_content) { 
			self::$_state[self::$type]['start'] = intval($start); 
		} 
		self::$start = intval($start);  

	} // set_start

	/**
	 * set_is_simple
	 * This sets the current browse object to a 'simple' browse method
	 * which means use the base query provided and expand from there
	 */
	public static function set_is_simple($value) { 

		$value = make_bool($value); 
		self::$_state['simple'][self::$type] = $value;  

	} // set_is_simple

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

		self::$_state[self::$type]['static'] = $value; 

	} // set_static_content

	/**
	 * is_simple
	 * this returns true or false if the current browse type is set to static
	 */
	public static function is_simple() { 

		return self::$_state['simple'][self::$type]; 

	} // is_simple

	/**
	 * load_start
	 * This returns a stored start point for the browse mojo
	 */
	public static function load_start() { 

		self::$start = intval(self::$_state[self::$type]['start']); 

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

		if (!self::is_simple()) { 
			// If not then we're going to need to read from the database :(
			$sid = session_id();
			$type = Dba::escape(self::$type); 

			$sql = "SELECT `data` FROM `tmp_browse` WHERE `sid`='$sid' AND `type`='$type'"; 
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
	 * set_base_sql
	 * This saves the base sql statement we are going to use.
	 */
	private static function set_base_sql() { 

		// Only allow it to be set once
		if (strlen(self::$_state['base'][self::$type])) { return true; } 

                switch (self::$type) {
                        case 'album':
				self::set_select("DISTINCT(`album`.`id`)"); 
                                $sql = "SELECT %%SELECT%% FROM `album` ";
                        break;
                        case 'artist':
				self::set_select("DISTINCT(`artist`.`id`)"); 
                                $sql = "SELECT %%SELECT%% FROM `artist` ";
                        break;
                        case 'user':
				self::set_select("`user`.`id`"); 
                                $sql = "SELECT %%SELECT%% FROM `user` ";
                        break;
                        case 'live_stream':
				self::set_select("`live_stream`.`id`");
                                $sql = "SELECT %%SELECT%% FROM `live_stream` ";
                        break;
                        case 'playlist':
				self::set_select("`playlist`.`id`"); 
                                $sql = "SELECT %%SELECT%% FROM `playlist` ";
                        break;
			case 'flagged': 
				self::set_select("`flagged`.`id`"); 
				$sql = "SELECT %%SELECT%% FROM `flagged` ";
			break;
			case 'shoutbox': 
				self::set_select("`user_shout`.`id`"); 
				$sql = "SELECT %%SELECT%% FROM `user_shout` "; 
			break; 
			case 'video': 
				self::set_select("`video`.`id`"); 
				$sql = "SELECT %%SELECT%% FROM `video` ";
			break; 
			case 'tag': 
				self::set_select("DISTINCT(`tag`.`id`)"); 
				self::set_join('left','tag_map','`tag_map`.`tag_id`','`tag`.`id`',1); 
				$sql = "SELECT %%SELECT%% FROM `tag` "; 
			break; 
			case 'playlist_song': 
                        case 'song':
                        default:
				self::set_select("DISTINCT(`song`.`id`)"); 
                                $sql = "SELECT %%SELECT%% FROM `song` ";
                        break;
                } // end base sql

		self::$_state['base'][self::$type] = $sql; 

	} // set_base_sql

	/**
	 * get_select
	 * This returns the selects in a format that is friendly for a sql statement
	 */
	private static function get_select() { 

		$select_string = implode(self::$_state['select'][self::$type],", "); 
		return $select_string; 

	} // get_select

	/**
	 * get_base_sql
	 * This returns the base sql statement all parsed up, this should be called after all set operations
	 */
	private static function get_base_sql() { 
		
		// Legacy code, should be removed once other code is updated
		//FIXME: REMOVE
		if (!self::$_state['base'][self::$type]) { self::set_base_sql(); } 

		$sql = str_replace("%%SELECT%%",self::get_select(),self::$_state['base'][self::$type]); 

		return $sql; 

	} // get_base_sql

	/**
	 * get_filter_sql
	 * This returns the filter part of the sql statement
	 */
	private static function get_filter_sql() { 

		if (!is_array(self::$_state['filter'][self::$type])) { 
			return ''; 
		} 

		$sql = "WHERE 1=1 AND ";

		foreach (self::$_state['filter'][self::$type] as $key=>$value) { 
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
		
		if (!is_array(self::$_state['sort'][self::$type])) { return ''; } 

		$sql = 'ORDER BY '; 

		foreach (self::$_state['sort'][self::$type] as $key=>$value) { 
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

		if (!self::is_simple()) { return ''; } 

		$sql = ' LIMIT ' . intval(self::$start) . ',' . intval(self::$offset); 

		return $sql; 

	} // get_limit_sql 

	/**
	 * get_join_sql
	 * This returns the joins that this browse may need to work correctly
	 */
	private static function get_join_sql() { 
		
		if (!is_array(self::$_state['join'][self::$type])) { 
			return ''; 
		} 

		$sql = ''; 

		// We need to itterate through these from 0 - 100 so that we add the joins in the right order
		foreach (self::$_state['join'][self::$type] as $joins) {
			foreach ($joins as $join) { 
				$sql .= $join . ' '; 
			} // end foreach joins at this level
		} // end foreach of this level of joins

		return $sql; 	

	} // get_join_sql

	/**
	 * get_having_sql
	 * this returns the having sql stuff, if we've got anything
	 */
	public static function get_having_sql() { 

		$sql = self::$_state['having'][self::$type]; 

		return $sql; 

	} // get_having_sql

	/**
	 * get_sql
	 * This returns the sql statement we are going to use this has to be run
	 * every time we get the objects because it depends on the filters and the
	 * type of object we are currently browsing
	 */
	public static function get_sql($limit=true) { 

		$sql = self::get_base_sql(); 

		$filter_sql = self::get_filter_sql(); 
		$join_sql = self::get_join_sql(); 
		$having_sql = self::get_having_sql(); 
		$order_sql = self::get_sort_sql(); 
		$limit_sql = $limit ? self::get_limit_sql() : ''; 

		$final_sql = $sql . $join_sql . $filter_sql . $having_sql . $order_sql . $limit_sql;  

		return $final_sql;

	} // get_sql 

	/**
  	 * post_process
	 * This does some additional work on the results that we've received before returning them
	 */
	private static function post_process($results) {

		$tags = self::$_state['filter']['tag'];

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
		
		switch (self::$type) { 
		case 'song': 
			switch($filter) { 
				case 'exact_match': 
					$filter_sql = " `song`.`title` = '" . Dba::escape($value) . "' AND "; 
				break; 
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
				case 'add_gt': 
					$filter_sql = " `song`.`addition_time` >= '" . Dba::escape($value) . "' AND "; 
				break; 
				case 'add_lt': 
					$filter_sql = " `song`.`addition_time` <= '" . Dba::escape($value) . "' AND "; 
				break; 
				case 'update_gt': 
					$filter_sql = " `song`.`update_time` >= '" . Dba::escape($value) . "' AND "; 
				break; 
				case 'update_lt': 
					$filter_sql = " `song`.`update_time` <= '" . Dba::escape($value) . "' AND "; 
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
		break; 
		case 'album': 
			switch($filter) { 
				case 'exact_match': 
					$filter_sql = " `album`.`name` = '" . Dba::escape($value) . "' AND "; 
				break; 
				case 'alpha_match':
					$filter_sql = " `album`.`name` LIKE '%" . Dba::escape($value) . "%' AND "; 
				break;
				case 'starts_with': 
					$filter_sql = " `album`.`name` LIKE '" . Dba::escape($value) . "%' AND "; 
				break; 
			        case 'artist':
					$filter_sql = " `artist`.`id` = '". Dba::escape($value) . "' AND ";
				break;
				case 'add_lt': 
					self::set_join('left','`song`','`song`.`album`','`album`.`id`');	
					$filter_sql = " `song`.`addition_time` <= '" . Dba::escape($value) . "' AND "; 
				break;
				case 'add_gt': 
					self::set_join('left','`song`','`song`.`album`','`album`.`id`'); 
					$filter_sql = " `song`.`addition_time` >= '" . Dba::escape($value) . "' AND "; 
				break;
				case 'update_lt': 
					self::set_join('left','`song`','`song`.`album`','`album`.`id`');
					$filter_sql = " `song`.`update_time` <= '" . Dba::escape($value) . "' AND "; 
				break;
				case 'update_gt': 
					self::set_join('left','`song`','`song`.`album`','`album`.`id`'); 
					$filter_sql = " `song`.`update_time` >= '" . Dba::escape($value) . "' AND "; 
				break; 
				default: 
					// Rien a faire
				break;
			} 
		break;
		case 'artist': 
			switch($filter) { 
				case 'exact_match': 
					$filter_sql = " `artist`.`name` = '" . Dba::escape($value) . "' AND "; 
				break; 
				case 'alpha_match':
					$filter_sql = " `artist`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
				break;
				case 'starts_with': 
					$filter_sql = " `artist`.`name` LIKE '" . Dba::escape($value) . "%' AND "; 
				break;
				case 'add_lt': 
					self::set_join('left','`song`','`song`.`artist`','`artist`.`id`'); 
					$filter_sql = " `song`.`addition_time` <= '" . Dba::escape($value) . "' AND "; 
				break;
				case 'add_gt': 
					self::set_join('left','`song`','`song`.`artist`','`artist`.`id`'); 
					$filter_sql = " `song`.`addition_time` >= '" . Dba::escape($value) . "' AND "; 
				break;
				case 'update_lt':
					self::set_join('left','`song`','`song`.`artist`','`artist`.`id`'); 
					$filter_sql = " `song`.`update_time` <= '" . Dba::escape($value) . "' AND "; 
				break; 
				case 'update_gt': 	
					self::set_join('left','`song`','`song`.`artist`','`artist`.`id`'); 
					$filter_sql = " `song`.`update_time` >= '" . Dba::escape($value) . "' AND "; 
				break; 
				default:
					// Rien a faire
				break;
			} // end filter
		break;
		case 'live_stream': 
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
		break; 
		case 'playlist': 
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
		break; 
		case 'tag': 
			switch ($filter) { 
				case 'alpha_match': 
					$filter_sql = " `tag`.`name` LIKE '%" . Dba::escape($value) . "%' AND "; 
				break;
				case 'exact_match': 
					$filter_sql = " `tag`.`name` = '" . Dba::escape($value) . "' AND "; 
				break;
				default: 
					// Rien a faire
				break;
			} // end filter
		break; 
		case 'video': 
			switch ($filter) { 
				case 'alpha_match': 
					$filter_sql = " `video`.`title` LIKE '%" . Dba::escape($value) . "%' AND "; 
				break; 
				case 'starts_with': 
					$filter_sql = " `video`.`title` LIKE '" . Dba::escape($value) . "%' AND "; 
				break;
				default: 
					// Rien a faire
				break; 
			} // end filter
		break; 
		} // end switch on type 

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
			case 'video': 
				switch ($field) { 
					case 'title': 
						$sql = "`video`.`title`"; 
					break; 
					case 'resolution': 
						$sql = "`video`.`resolution_x`"; 
					break;
					case 'length': 
						$sql = "`video`.`time`"; 
					break;
					case 'codec': 
						$sql = "`video`.`video_codec`"; 
					break; 
				} // end switch on field
			break; 
			default: 
				// Rien a faire
			break;
		} // end switch

		if ($sql) { $sql_sort = "$sql $order,"; } 
		
		return $sql_sort; 

	} // sql_sort

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
		if (self::is_simple()) { 
			$sql = self::get_sql(); 
		} 
		else { 
			// First pull the objects
			$objects = self::get_saved(); 

			// If there's nothing there don't do anything
			if (!count($objects) or !is_array($objects)) {
				return false;
			} 
			$type = self::$type;
			$where_sql = "WHERE `$type`.`id` IN (";

			foreach ($objects as $object_id) {
				$object_id = Dba::escape($object_id);
				$where_sql .= "'$object_id',";
			} 
			$where_sql = rtrim($where_sql,','); 

			$where_sql .= ")";

			$sql = self::get_base_sql();

			$order_sql = " ORDER BY ";

	                foreach (self::$_state['sort'][self::$type] as $key=>$value) {
	                        $order_sql .= self::sql_sort($key,$value);
	                } 
	                // Clean her up
	                $order_sql = rtrim($order_sql,"ORDER BY ");
	                $order_sql = rtrim($order_sql,",");
	                
	                $sql = $sql . self::get_join_sql() . $where_sql . $order_sql;
		} // if not simple
		
		$db_results = Dba::read($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id']; 
		} 
		
		self::save_objects($results); 

		return true; 

	} // resort_objects

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
                if (!self::is_simple()) {
                        $sid = Dba::escape(session_id()); 
                        $data = Dba::escape(serialize($object_ids));
			$type = Dba::escape(self::$type); 

                        $sql = "REPLACE INTO `tmp_browse` SET `data`='$data', `sid`='$sid',`type`='$type'";
                        $db_results = Dba::write($sql);

                        self::$total_objects = count($object_ids);
                } // save it 

                return true;

        } // save_objects

	/**
	 * _auto_init
	 * this function reloads information back from the session 
	 * it is called on creation of the class
	 */
	public static function _auto_init() { 

		self::$offset = Config::get('offset_limit') ? Config::get('offset_limit') : '25';
		self::$_state = &$_SESSION['browse']; 

	} // _auto_init

	/**
	 * get_state
	 * This is a debug only function
	 */
	public static function get_state() { 

		return self::$_state; 

	} // get_state 

} // query
