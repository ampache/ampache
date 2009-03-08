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

	// Static Content, this is defaulted to false, if set to true then when we can't
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

		Query::set_filter($key,$value); 

		return true; 
	
	} // set_filter

	/**
	 * reset
	 * Reset everything
	 */
	public static function reset() { 

		Query::reset(); 

	} // reset

	/**
	 * get_filter
	 * returns the specified filter value
	 */
	public static function get_filter($key) { 
	
		return Query::get_filter($key); 

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
				$valid_array = array('show_art','starts_with','exact_match','alpha_match','add','update'); 
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

		Query::set_type($type); 
		Query::load_start(); 
	
	} // set_type

	/**
	 * set_sort
	 * This sets the current sort(s)
	 */
	public static function set_sort($sort,$order='') { 

		Query::set_sort($sort,$order); 

		return true; 

	} // set_sort

	/**
	 * set_simple_browse
	 * This sets the current browse object to a 'simple' browse method
	 * which means use the base query provided and expand from there
	 */
	public static function set_simple_browse($value) { 

		Query::set_is_simple($value); 

	} // set_simple_browse

	/**
	 * set_static_content
	 * This sets true/false if the content of this browse
	 * should be static, if they are then content filtering/altering
	 * methods will be skipped
	 */
	public static function set_static_content($value) { 

		Query::set_static_content($value); 

	} // set_static_content

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

		if (!Query::is_simple()) { 
			// If not then we're going to need to read from the database :(
			$sid = session_id() . '::' . self::$type; 

			$sql = "SELECT `data` FROM `tmp_browse` WHERE `sid`='$sid'"; 
			$db_results = Dba::read($sql); 

			$row = Dba::fetch_assoc($db_results); 

			$objects = unserialize($row['data']); 
		} 
		else { 
			$objects = Query::get_objects(); 
		} 

		return $objects; 

	} // get_saved

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
	 * show_objects
	 * This takes an array of objects
	 * and requires the correct template based on the
	 * type that we are currently browsing
	 */
	public static function show_objects($object_ids=false) { 
		
		if (Query::is_simple()) { 
			$object_ids = Query::get_saved(); 
		} 
		else { 
			$object_ids = is_array($object_ids) ? $object_ids : Query::get_saved();
			Query::save_objects($object_ids); 
		} 
		
		// Reset the total items
		self::$total_objects = Query::get_total($object_ids); 
		
		// Limit is based on the users preferences if this is not a simple browse because we've got too much here
		if (count($object_ids) > Query::get_start() AND !Query::is_simple()) { 
			$object_ids = array_slice($object_ids,Query::get_start(),Query::get_offset(),TRUE); 
		} 

		// Format any matches we have so we can show them to the masses
		if ($filter_value = Query::get_filter('alpha_match')) { 
			$match = ' (' . $filter_value . ')'; 
		}
		elseif ($filter_value = Query::get_filter('starts_with')) { 
			$match = ' (' . $filter_value . ')'; 
		} 

		// Set the correct classes based on type
    		$class = "box browse_".self::$type;

		Ajax::start_container('browse_content');
		// Switch on the type of browsing we're doing
		switch (Query::get_type()) { 
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
				require_once Config::get('prefix') . '/templates/show_live_stream.inc.php'; 
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
			case 'video': 
				show_box_top(_('Videos'),$class); 
				require_once Config::get('prefix') . '/templates/show_videos.inc.php'; 
				show_box_bottom(); 
			break; 
			case 'democratic': 
				show_box_top(_('Democratic Playlist'),$class); 
				require_once Config::get('prefix') . '/templates/show_democratic_playlist.inc.php'; 
				show_box_bottom(); 
			default: 
				// Rien a faire
			break;
		} // end switch on type

		Ajax::end_container(); 

	} // show_object

	/**
	 * get_objects
	 * This really should not be called anymore, but it's here for legacy shit
	 * call the query get objects method. 
	 */
	public static function get_objects() { 

		return Query::get_objects(); 

	} // get_objects

	/**
	 * get_start
	 * Returns the current start point
	 */
	public static function get_start() { 
	
		return Query::get_start(); 

	} // get_start

	/**
	 * get_type
	 * this is a wrapper function just returns the current type
	 */
	public static function get_type() { 

		return Query::get_type(); 

	} // get_type

	/**
	 * set_start
	 * This sets the start of the browse, really calls the query functions
	 */
	public static function set_start($value) { 

		Query::set_start($value); 

	} // set_start

	/**
	 * _auto_init
	 * this function reloads information back from the session 
	 * it is called on creation of the class
	 */
	public static function _auto_init() { 

		$offset = Config::get('offset_limit') ? Config::get('offset_limit') : '25';
		Query::set_offset($offset); 

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
