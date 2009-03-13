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
class Browse extends Query { 

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
	 * set_simple_browse
	 * This sets the current browse object to a 'simple' browse method
	 * which means use the base query provided and expand from there
	 */
	public static function set_simple_browse($value) { 

		parent::set_is_simple($value); 

	} // set_simple_browse

	/**
	 * add_supplemental_object
	 * Legacy function, need to find a better way to do that
	 */
	public static function add_supplemental_object($class,$uid) { 

		$_SESSION['browse']['supplemental'][$class] = intval($uid); 

		return true; 

	} // add_supplemental_object

	/**
	 * get_supplemental_objects
	 * This returns an array of 'class','id' for additional objects that need to be
	 * created before we start this whole browsing thing
	 */
	public static function get_supplemental_objects() { 

		$objects = $_SESSION['browse']['supplemental']; 
		
		if (!is_array($objects)) { $objects = array(); } 

		return $objects; 

	} // get_supplemental_objects


	/**
	 * show_objects
	 * This takes an array of objects
	 * and requires the correct template based on the
	 * type that we are currently browsing
	 */
	public static function show_objects($object_ids=false) { 
		
		if (parent::is_simple()) { 
			$object_ids = parent::get_saved(); 
		} 
		else { 
			$object_ids = is_array($object_ids) ? $object_ids : parent::get_saved();
			parent::save_objects($object_ids); 
		} 
		
		// Reset the total items
		self::$total_objects = parent::get_total($object_ids); 
		
		// Limit is based on the users preferences if this is not a simple browse because we've got too much here
		if (count($object_ids) > parent::get_start() AND !parent::is_simple()) { 
			$object_ids = array_slice($object_ids,parent::get_start(),parent::get_offset(),TRUE); 
		} 

		// Load any additional object we need for this
		$extra_objects = self::get_supplemental_objects(); 

		foreach ($extra_objects as $class_name => $id) { 
			${$class_name} = new $class_name($id); 
		} 

		// Format any matches we have so we can show them to the masses
		if ($filter_value = parent::get_filter('alpha_match')) { 
			$match = ' (' . $filter_value . ')'; 
		}
		elseif ($filter_value = parent::get_filter('starts_with')) { 
			$match = ' (' . $filter_value . ')'; 
		} 

		// Set the correct classes based on type
    		$class = "box browse_".self::$type;

		Ajax::start_container('browse_content');
		// Switch on the type of browsing we're doing
		switch (parent::get_type()) { 
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
				Video::build_cache($object_ids); 
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
	 * _auto_init
	 * this function reloads information back from the session 
	 * it is called on creation of the class
	 */
	public static function _auto_init() { 

		$offset = Config::get('offset_limit') ? Config::get('offset_limit') : '25';
		parent::set_offset($offset); 

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
