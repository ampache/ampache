<?php
/*

Copyright (c) Ampache.org
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
 * Catalog Class
 * This class handles all actual work in regards to the catalog, it contains functions for creating/listing/updated the catalogs.
 * @package Catalog
 * @catagory Class
 */
class Catalog extends database_object {

	public $name;
	public $last_update;
	public $last_add;
	public $last_clean; 
	public $key;
	public $rename_pattern;
	public $sort_pattern;
	public $catalog_type;
	public $path;

	/* This is a private var that's used during catalog builds */
	private $_playlists = array();

	// Cache all files in catalog for quick lookup during add
	private $_filecache = array();

	// Used in functions
	private static $albums	= array();
	private static $artists	= array();
	private static $tags	= array();
	private static $_art_albums = array();

	/**
	 * Constructor
	 * Catalog class constructor, pulls catalog information
	 * $catalog_id 	The ID of the catalog you want to build information from
	 */
	public function __construct($catalog_id = '') {

		if (!$catalog_id) { return false; }

		/* Assign id for use in get_info() */
		$this->id = intval($catalog_id);

		/* Get the information from the db */
		$info = $this->get_info($catalog_id);

		foreach ($info as $key=>$value) {
			$this->$key = $value;
		}

	} //constructor

	/**
	 * _create_filecache
	 * This poplates an array (filecache) on this object from the database
	 * it is used to speed up the add process
	 */
	private function _create_filecache() {

		if (count($this->_filecache) == 0) {
			$catalog_id = Dba::escape($this->id);
			// Get _EVERYTHING_
			$sql = "SELECT `id`,`file` FROM `song` WHERE `catalog`='$catalog_id'";
			$db_results = Dba::read($sql);

			// Populate the filecache
			while ($results = Dba::fetch_assoc($db_results)) {
				$this->_filecache[strtolower($results['file'])] = $results['id'];
			}

			$sql = "SELECT `id`,`file` FROM `video` WHERE `catalog`='$catalog_id'"; 
			$db_results = Dba::read($sql); 

			while ($results = Dba::fetch_assoc($db_results)) { 
				$this->_filecache[strtolower($results['file'])] = 'v_' . $results['id']; 
			} 
		} // end if empty filecache

		return true; 

	} // _create_filecache

	/**
 	 * get_from_path
	 * Try to figure out which catalog path most closely resembles this one
	 * This is useful when creating a new catalog to make sure we're not doubling up here
	 */
	public static function get_from_path($path) { 

		// First pull a list of all of the paths for the different catalogs
		$sql = "SELECT `id`,`path` FROM `catalog` WHERE `catalog_type`='local'"; 
		$db_results = Dba::read($sql); 

		$catalog_paths = array(); 
		$component_path = $path; 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$catalog_paths[$row['path']] = $row['id'];
		} 


		// Break it down into its component parts and start looking for a catalog
		do { 
			if ($catalog_paths[$component_path]) { 
				return $catalog_paths[$component_path]; 
			} 

			// Keep going until the path stops changing
			$old_path = $component_path; 	
			$component_path = realpath($component_path . '/../'); 

		} while (strcmp($component_path,$old_path) != 0); 

		return false; 

	} // get_from_path

	/**
	 * format
	 * This makes the object human readable
	 */
	public function format() {

		$this->f_name		= truncate_with_ellipsis($this->name,Config::get('ellipse_threshold_title'));
		$this->f_name_link	= '<a href="' . Config::get('web_path') . '/admin/catalog.php?action=show_customize_catalog&catalog_id=' . $this->id . '" title="' . scrub_out($this->name) . '">' . scrub_out($this->f_name) . '</a>';
		$this->f_path		= truncate_with_ellipsis($this->path,Config::get('ellipse_threshold_title'));
		$this->f_update		= $this->last_update ? date('d/m/Y h:i',$this->last_update) : _('Never');
		$this->f_add		= $this->last_add ? date('d/m/Y h:i',$this->last_add) : _('Never');
		$this->f_clean		= $this->last_clean ? date('d/m/Y h:i',$this->last_clean) : _('Never'); 

	} // format

	/**
	 * get_catalogs
	 * Pull all the current catalogs and return an array of ids
	 * of what you find
	 */
	public static function get_catalogs() {

		$sql = "SELECT `id` FROM `catalog` ORDER BY `name`";
		$db_results = Dba::query($sql);

		$results = array();

		while ($row = Dba::fetch_assoc($db_results)) {
			$results[] = $row['id'];
		}

		return $results;

	} // get_catalogs

	/**
	 * get_catalog_ids
	 * This returns an array of all catalog ids
	 */
	public static function get_catalog_ids() {

		$sql = "SELECT `id` FROM `catalog`";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) {
			$results[] = $r['id'];
		}

		return $results;

	} // get_catalog_ids

	/**
	 * get_stats
	 * This returns an hash with the #'s for the different
	 * objects that are assoicated with this catalog. This is used
	 * to build the stats box, it also calculates time
	 */
	public static function get_stats($catalog_id=0) {

		$results 		= self::count_songs($catalog_id);
		$results	 	= array_merge(self::count_users($catalog_id),$results);
		$results['tags']	= self::count_tags(); 
		$results 		= array_merge(self::count_video($catalog_id),$results); 

		$hours = floor($results['time']/3600);
                // Calculate catalog size in bytes, divided by 1000
                // We do so by first chopping the three least significant decimal digits off
                // This is needed in case catalog size exceeds 4 GB ( 2 << 31)
                // The precision lost here is not important, because during
                // presentation we do not care about less than 0.01 MB.
                //
                $sizeStr = (string)$results['size'];
                if ( strlen( $sizeStr ) > 3 ) {
			$size = (int)substr( $sizeStr, 0, -3 );
                }
                else {
			$size = 0;
                }
                // Now go to MB's, applying a correction for KB first.
                //
                $size = ($size / 1.024) / 1024;
		$days = floor($hours/24);
		$hours = $hours%24;

		$time_text = "$days ";
		$time_text .= ngettext('day','days',$days);
		$time_text .= ", $hours ";
		$time_text .= ngettext('hour','hours',$hours);

		$results['time_text'] = $time_text;

		if ($size > 1024) {
			$total_size = sprintf("%.2f",($size/1024));
			$size_unit = "GB";
		}
		else {
			$total_size = sprintf("%.2f",$size);
			$size_unit = "MB";
		}

		$results['total_size'] = $total_size;
		$results['size_unit'] = $size_unit;

		return $results;

	} // get_stats

	/**
	 * clear_stats
	 * This clears all stats for _everything_
	 */
	public static function clear_stats() {

		/* Whip out everything */
		$sql = "TRUNCATE `object_count`";
		$db_results = Dba::query($sql);

		$sql = "UDPATE `song` SET `played`='0'";
		$db_results = Dba::query($sql);

		return true;

	} // clear_stats

	/**
	 * create
	 * This creates a new catalog entry and then returns the insert id
	 * it checks to make sure this path is not already used before creating
	 * the catalog
	 */
	public static function create($data) {

		// Clean up the path just incase
		$data['path'] = rtrim(rtrim(trim($data['path']),'/'),'\\');

		$path = Dba::escape($data['path']);

		// Make sure the path is readable/exists
		if (!is_readable($data['path']) AND $data['type'] == 'local') {
			Error::add('general','Error: ' . scrub_out($data['path']) . ' is not readable or does not exist');
			return false;
		}

		// Make sure this path isn't already in use by an existing catalog
		$sql = "SELECT `id` FROM `catalog` WHERE `path`='$path'";
		$db_results = Dba::query($sql);

		if (Dba::num_rows($db_results)) {
			Error::add('general','Error: Catalog with ' . $path . ' already exists');
			return false;
		}

		$name		= Dba::escape($data['name']);
		$catalog_type	= Dba::escape($data['type']);
		$rename_pattern	= Dba::escape($data['rename_pattern']);
		$sort_pattern	= Dba::escape($data['sort_pattern']);
		$gather_types	= 'NULL';
		$key		= $data['key'] ? '\'' . Dba::escape($data['key']) . '\'' : 'NULL';

		// Ok we're good to go ahead and insert this record
		$sql = "INSERT INTO `catalog` (`name`,`path`,`catalog_type`,`rename_pattern`,`sort_pattern`,`gather_types`,`key`) " .
			"VALUES ('$name','$path','$catalog_type','$rename_pattern','$sort_pattern',$gather_types,$key)";
		$db_results = Dba::write($sql);

		$insert_id = Dba::insert_id();

		if (!$insert_id) {
			Error::add('general','Catalog Insert Failed check debug logs');
			debug_event('catalog','SQL Failed:' . $sql,'3');
			return false;
		}

		return $insert_id;

	} // create

	/**
	 * run_add
	 * This runs the add to catalog function
	 * it includes the javascript refresh stuff and then starts rolling
	 * throught the path for this catalog
	 */
	public function run_add($options) {

		if ($this->catalog_type == 'remote') {
			show_box_top(_('Running Remote Sync') . '. . .');
			$this->get_remote_catalog($type=0);
			show_box_bottom();
			return true;
		}

		// Catalog Add start
		$start_time = time();

		require Config::get('prefix') . '/templates/show_adds_catalog.inc.php';
		flush();

		// Prevent the script from timing out and flush what we've got
		set_time_limit(0);

		$this->add_files($this->path,$options);

		// If they have checked the box then go ahead and gather the art
		if ($options['gather_art']) {
			$catalog_id = $this->id;
			require Config::get('prefix') . '/templates/show_gather_art.inc.php';
			flush();
			$this->get_album_art('',1);
		}

		if ($options['parse_m3u'] AND count($this->_playlists)) { 
			foreach ($this->_playlists as $playlist_file) { 
				$result = $this->import_m3u($playlist_file); 
			} 
		} // if we need to do some m3u-age

		return true;

	} // run_add

	/**
	 * count_video
	 * This returns the current # of video files we've got in the db
	 */
	public static function count_video($catalog_id=0) { 

		$catalog_search = $catalog_id ? "WHERE `catalog`='" . Dba::escape($catalog_id) . "'" : ''; 

		$sql = "SELECT COUNT(`id`) AS `video` FROM `video` $catalog_search"; 
		$db_results = Dba::read($sql); 

		$row = Dba::fetch_assoc($db_results); 
	
		return $row; 

	} // count_video 

	/**
	 * count_tags
	 * This returns the current # of unique tags that exist in the database
	 */
	public static function count_tags($catalog_id=0) { 

//		$catalog_search = $catalog_id ? "WHERE `catalog`='" . Dba::escape($catalog_id) . "'" : ''; 

		$sql = "SELECT COUNT(`id`) FROM `tag`"; 
		$db_results = Dba::read($sql); 

		$info = Dba::fetch_row($db_results); 

		return $info['0']; 

	} // count_tags

	/**
	 * count_songs
	 * This returns the current # of songs, albums, artists, genres
	 * in this catalog
	 */
	public static function count_songs($catalog_id='') {

		$catalog_search = $catalog_id ? "WHERE `catalog`='" . Dba::escape($catalog_id) . "'" : ''; 

		$sql = "SELECT COUNT(`id`),SUM(`time`),SUM(`size`) FROM `song` $catalog_search";
		$db_results = Dba::query($sql);
		$data = Dba::fetch_row($db_results);
		$songs	= $data['0'];
		$time	= $data['1'];
		$size	= $data['2'];

		$sql = "SELECT COUNT(DISTINCT(`album`)) FROM `song` $catalog_search";
		$db_results = Dba::query($sql);
		$data = Dba::fetch_row($db_results);
		$albums = $data['0'];

		$sql = "SELECT COUNT(DISTINCT(`artist`)) FROM `song` $catalog_search";
		$db_results = Dba::query($sql);
		$data = Dba::fetch_row($db_results);
		$artists = $data['0'];

		$results['songs'] 	= $songs;
		$results['albums']	= $albums;
		$results['artists']	= $artists;
		$results['size']	= $size;
		$results['time']	= $time;

		return $results;

	} // count_songs

	/**
	 * count_users
	 * This returns the total number of users in the ampache instance
	 */
	public static function count_users($catalog_id='') {

		// Count total users
		$sql = "SELECT COUNT(id) FROM `user`";
		$db_results = Dba::query($sql);
		$data = Dba::fetch_row($db_results);
		$results['users'] = $data['0'];

		// Get the connected users
		$time = time();
		$last_seen_time = $time - 1200;
		$sql =  "SELECT count(DISTINCT s.username) FROM session AS s " .
	                "INNER JOIN user AS u ON s.username = u.username " .
	                "WHERE s.expire > " . $time . " " .
	                "AND u.last_seen > " . $last_seen_time;
		$db_results = Dba::query($sql);
		$data = Dba::fetch_row($db_results);

		$results['connected'] = $data['0'];

		return $results;

	} // count_users

	/**
	 * add_files
	 * Recurses throught $this->path and pulls out all mp3s and returns the full
	 * path in an array. Passes gather_type to determin if we need to check id3
	 * information against the db.
	 */
	public function add_files($path,$options) {

		// See if we want a non-root path for the add
		if (isset($options['subdirectory'])) { 
			$path = $options['subdirectory']; 
			unset($options['subdirectory']); 
		} 

		// Correctly detect the slash we need to use here
		if (strstr($path,"/")) {
			$slash_type = '/';
		}
		else {
			$slash_type = '\\';
		}

		/* Open up the directory */
		$handle = opendir($path);

		if (!is_resource($handle)) {
			debug_event('read',"Unable to Open $path",'5','ampache-catalog');
			Error::add('catalog_add',_('Error: Unable to open') . ' ' . $path);
			return false;
		}

		/* Change the dir so is_dir works correctly */
		if (!chdir($path)) {
			debug_event('read',"Unable to chdir $path",'2','ampache-catalog');
			Error::add('catalog_add',_('Error: Unable to change to directory') . ' ' . $path);
			return false;
		}

		// Ensure that we've got our cache
		$this->_create_filecache();

		/* Recurse through this dir and create the files array */
		while ( false !== ( $file = readdir($handle) ) ) {

			/* Skip to next if we've got . or .. */
			if (substr($file,0,1) == '.') { continue; }

			debug_event('read',"Starting work on $file inside $path",'5','ampache-catalog');

			/* Create the new path */
			$full_file = $path.$slash_type.$file;

			/* First thing first, check if file is already in catalog.
			 * This check is very quick, so it should be performed before any other checks to save time
			 */
			if (isset($this->_filecache[strtolower($full_file)])) {
				continue;
			}
				
			// Incase this is the second time through clear this variable
			// if it was set the day before
			unset($failed_check);

			if (Config::get('no_symlinks')) {
				if (is_link($full_file)) {
					debug_event('read',"Skipping Symbolic Link $path",'5','ampache-catalog');
					continue;
				}
			}

			/* If it's a dir run this function again! */
			if (is_dir($full_file)) {
				$this->add_files($full_file,$options);

				/* Change the dir so is_dir works correctly */
				if (!chdir($path)) {
					debug_event('read',"Unable to chdir $path",'2','ampache-catalog');
					Error::add('catalog_add',_('Error: Unable to change to directory') . ' ' . $path);
				}

				/* Skip to the next file */
				continue;
			} //it's a directory

			/* If it's not a dir let's roll with it
			 * next we need to build the pattern that we will use
			 * to detect if it's a audio file for now the source for
			 * this is in the /modules/init.php file
			 */
			$pattern = "/\.(" . Config::get('catalog_file_pattern');
			if ($options['parse_m3u']) {
				$pattern .= "|m3u)$/i";
			}
			else {
				$pattern .= ")$/i";
			}

			$is_audio_file = preg_match($pattern,$file); 
				
			// Define the Video file pattern
			if (!$is_audio_file AND Config::get('catalog_video_pattern')) {
				$video_pattern = "/\.(" . Config::get('catalog_video_pattern') . ")$/i"; 
				$is_video_file = preg_match($video_pattern,$file); 
			}

			/* see if this is a valid audio file or playlist file */
			if ($is_audio_file OR $is_video_file) {

				/* Now that we're sure its a file get filesize  */
				$file_size = filesize($full_file);

				if (!$file_size) {
					debug_event('read',"Unable to get filesize for $full_file",'2','ampache-catalog');
					Error::add('catalog_add',sprintf(_('Error: Unable to get filesize for %s'), $full_file));
				} // file_size check

				if (!is_readable($full_file)) {
					// not readable, warn user
					debug_event('read',"$full_file is not readable by ampache",'2','ampache-catalog');
					Error::add('catalog_add', sprintf(_('%s is not readable by ampache'), $full_file));
					continue;
				}

				// Check to make sure the filename is of the expected charset
				if (function_exists('iconv')) {
					if (strcmp($full_file,iconv(Config::get('site_charset'),Config::get('site_charset'),$full_file)) != '0') {
						debug_event('read',$full_file . ' has non-' . Config::get('site_charset') . ' characters and can not be indexed, converted filename:' . iconv(Config::get('site_charset'),Config::get('site_charset'),$full_file),'1');
						Error::add('catalog_add', sprintf(_('%s does not match site charset'), $full_file));
						continue;
					}
				} // end if iconv

				if ($options['parse_m3u'] AND substr($file,-3,3) == 'm3u') {
					$this->_playlists[] = $full_file;
				} // if it's an m3u

				else {
					if ($is_audio_file) { $this->insert_local_song($full_file,$file_size); }
					else { $this->insert_local_video($full_file,$file_size); } 

					/* Stupid little cutesie thing */
					$this->count++;
					if ( !($this->count%10)) {
						$file = str_replace(array('(',')','\''),'',$full_file);
						echo "<script type=\"text/javascript\">\n";
						echo "update_txt('" . $this->count ."','add_count_" . $this->id . "');";
						echo "update_txt('" . addslashes(htmlentities($file)) . "','add_dir_" . $this->id . "');";
						echo "\n</script>\n";
						flush();
					} // update our current state

				} // if it's not an m3u

			} //if it matches the pattern
			else {
				debug_event('read',"$full_file ignored, non audio file or 0 bytes",'5','ampache-catalog');
			} // else not an audio file

		} // end while reading directory

		debug_event('closedir',"Finished reading $path closing handle",'5','ampache-catalog');

		// This should only happen on the last run
		if ($path == $this->path) {
			echo "<script type=\"text/javascript\">\n";
			echo "update_txt('" . $this->count ."','add_count_" . $this->id . "');";
			echo "update_txt('" . addslashes(htmlentities($file)) . "','add_dir_" . $this->id . "');";
			echo "\n</script>\n";
			flush();
		}

		/* Close the dir handle */
		@closedir($handle);

	} // add_files

	/**
	 * get_album_ids
	 * This returns an array of ids of albums that have songs in this
	 * catalog
	 */
	public function get_album_ids() {

		$id = Dba::escape($this->id);
		$results = array();

		$sql = "SELECT DISTINCT(song.album) FROM `song` WHERE `song`.`catalog`='$id'";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) {
			$results[] = $r['album'];
		}

		return $results;

	} // get_album_ids

	/**
	 * get_album_art
	 * This runs through all of the needs art albums and trys
	 * to find the art for them from the mp3s
	 */
	public function get_album_art($catalog_id=0,$all='') {


		// Make sure they've actually got methods
		$album_art_order = Config::get('album_art_order');
		if (empty($album_art_order)) {
			return true;
		}

		// Prevent the script from timing out
		set_time_limit(0);

		// If not passed use this
		$catalog_id = $catalog_id ? $catalog_id : $this->id;

		if ($all) {
			$albums = $this->get_album_ids();
		}
		else {
			$albums = array_keys(self::$_art_albums);
		}

		// Run through them an get the art!
		foreach ($albums as $album_id) {

			// Create the object
			$album = new Album($album_id);
			// We're going to need the name here
			$album->format();
				
			debug_event('gather_art','Gathering art for ' . $album->name,'5');
				
			// Define the options we want to use for the find art function
			$options = array(
				'album_name' 	=> $album->full_name,
				'artist' 	=> $album->artist_name,
				'keyword' 	=> $album->artist_name . ' ' . $album->full_name 
			);

			// Return results
			$results = $album->find_art($options,1);
				
			if (count($results)) {
				// Pull the string representation from the source
				$image = Album::get_image_from_source($results['0']);
				if (strlen($image) > '5') {
					$album->insert_art($image,$results['0']['mime']);
				}
				else {
					debug_event('album_art','Image less then 5 chars, not inserting','3');
				}
				$art_found++;
			}

			/* Stupid little cutesie thing */
			$search_count++;
			if ( !($search_count%5)) {
				echo "<script type=\"text/javascript\">\n";
				echo "update_txt('" . $search_count ."','count_art_" . $this->id . "');";
				echo "update_txt('" . addslashes($album->name) . "','read_art_" . $this->id . "');";
				echo "\n</script>\n";
				flush();
			} //echos song count
				
			unset($found);
		} // foreach albums

		// One last time for good measure
		echo "<script type=\"text/javascript\">\n";
		echo "update_txt('" . $search_count ."','count_art_" . $this->id . "');";
		echo "update_txt('" . addslashes($album->name) . "','read_art_" . $this->id . "');";
		echo "\n</script>\n";
		flush();

		self::$_art_albums = array();

	} // get_album_art

	/**
	 * get_catalog_albums()
	 * Returns an array of the albums from a catalog
	 */
	public static function get_catalog_albums($catalog_id) {

		$results = array();

		$sql = "SELECT DISTINCT(`song`.`album`) FROM `song`  WHERE `song`.`catalog`='$catalog_id'";
		$db_results = Dba::query($sql);

		while ($row = Dba::fetch_assoc($db_results)) {
			$results[] = $row['album'];
		}

		return $results;

	} // get_catalog_albums


	/**
	 * get_catalog_files
	 * Returns an array of song objects from a catalog, used by sort_files script
	 */
	public function get_catalog_files($catalog_id=0) {

		$results = array();

		/* Use $this->id if nothing passed */
		$catalog_id = $catalog_id ? Dba::escape($catalog_id) : Dba::escape($this->id);

		$sql = "SELECT `id` FROM `song` WHERE `catalog`='$catalog_id' AND `enabled`='1'";
		$db_results = Dba::query($sql);

		$results = array(); // return an emty array instead of nothing if no objects
		while ($r = Dba::fetch_assoc($db_results)) {
			$results[] = new Song($r['id']);
		} //end while

		return $results;

	} //get_catalog_files

	/**
	 * get_disabled
	 * Gets an array of the disabled songs for all catalogs
	 * and returns full song objects with them
	 */
	public static function get_disabled($count=0) {

		$results = array();

		if ($count) { $limit_clause = " LIMIT $count"; }

		$sql = "SELECT `id` FROM `song` WHERE `enabled`='0' $limit_clause";
		$db_results = Dba::read($sql);

		while ($r = Dba::fetch_assoc($db_results)) {
			$results[] = new Song($r['id']);
		}

		return $results;

	} // get_disabled

	/**
	 * get_duplicate_songs
	 * This function takes a search type and returns a list of all song_ids that
	 * are likely to be duplicates based on teh search method selected.
	 */
	public static function get_duplicate_songs($search_method) {

		$where_sql = '';

		if (!$_REQUEST['search_disabled']) {
			$where_sql = 'WHERE enabled!=\'0\'';
		}

		// Setup the base SQL
		$sql = "SELECT song.id AS song,artist.id AS artist,album.id AS album,title,COUNT(title) AS ctitle".
	                " FROM `song` LEFT JOIN `artist` ON `artist`.`id`=`song`.`artist` " . 
			" LEFT JOIN `album` ON `album`.`id`=`song`.`album` $where_sql GROUP BY `song`.`title`";
		 
		// Add any Additional constraints
		if ($search_method == "artist_title" OR $search_method == "artist_album_title") {
			$sql = $sql.",artist.name";
		}

		if ($search_method == "artist_album_title") {
			$sql = $sql.",album.name";
		}

		// Final componets
		$sql = $sql." HAVING COUNT(title) > 1";
		$sql = $sql." ORDER BY `ctitle`";

		$db_results = Dba::query($sql);

		$results = array();

		while ($item = Dba::fetch_assoc($db_results)) {
			$results[] = $item;
		} // end while

		return $results;

	} // get_duplicate_songs

	/**
	 * get_duplicate_info
	 * This takes a song, search type and auto flag and returns the duplicate songs in the correct
	 * order, it sorts them by longest, higest bitrate, largest filesize, checking
	 * the last one as most likely bad
	 */
	public static function get_duplicate_info($item,$search_type) {
		// Build the SQL
		$sql = "SELECT `song`.`id`" .
	                " FROM song,artist,album".
	                " WHERE song.artist=artist.id AND song.album=album.id".
	                " AND song.title= '".Dba::escape($item['title'])."'";

		if ($search_type == "artist_title" || $search_type == "artist_album_title") {
			$sql .="  AND artist.id = '".Dba::escape($item['artist'])."'";
		}
		if ($search_type == "artist_album_title" ) {
			$sql .="  AND album.id = '".Dba::escape($item['album'])."'";
		}

		$sql .= " ORDER BY `time`,`bitrate`,`size` LIMIT 2";
		$db_results = Dba::query($sql);

		$results = array();

		while ($item = Dba::fetch_assoc($db_results)) {
			$results[] = $item['id'];
		} // end while

		return $results;

	} // get_duplicate_info

	/**
	 * dump_album_art (Added by Cucumber 20050216)
	 * This runs through all of the albums and trys to dump the
	 * art for them into the 'folder.jpg' file in the appropriate dir
	 */
	public static function dump_album_art($catalog_id,$methods=array()) {

		// Get all of the albums in this catalog
		$albums = self::get_catalog_albums($catalog_id);

		echo "Starting Dump Album Art...\n";

		// Run through them an get the art!
		foreach ($albums as $album_id) {

			$album = new Album($album_id);

			// If no art, skip
			if (!$album->has_art()) { continue; }

			$image = $album->get_db_art();

			/* Get the first song in the album */
			$songs = $album->get_songs(1);
			$song = new Song($songs[0]);
			$dir = dirname($song->file);

			if ($image['0']['mime'] == 'image/jpeg') {
				$extension = 'jpg';
			}
			else {
				$extension = substr($image['0']['mime'],strlen($image['0']['mime'])-3,3);
			}
				
			// Try the preferred filename, if that fails use folder.???
			$preferred_filename = Config::get('album_art_preferred_filename');
			if (!$preferred_filename || strstr($preferred_filename,"%")) { $preferred_filename = "folder.$extension"; }

			$file = "$dir/$preferred_filename";
			if ($file_handle = fopen($file,"w")) {
				if (fwrite($file_handle, $image['0']['raw'])) {

					// Also check and see if we should write out some meta data
					if ($methods['metadata']) {
						switch ($methods['metadata']) {
							case 'windows':
								$meta_file = $dir . '/desktop.ini';
								$string = "[.ShellClassInfo]\nIconFile=$file\nIconIndex=0\nInfoTip=$album->full_name";
								break;
							default:
							case 'linux':
								$meta_file = $dir . '/.directory';
								$string = "Name=$album->full_name\nIcon=$file";
								break;
						} // end switch

						$meta_handle = fopen($meta_file,"w");
						fwrite($meta_handle,$string);
						fclose($meta_handle);

					} // end metadata
					$i++;
					if (!($i%100)) {
						echo "Written: $i. . .\n";
						debug_event('art_write',"$album->name Art written to $file",'5');
					}
				} // end if fopen
				else {
					debug_event('art_write',"Unable to open $file for writting",'5');
					echo "Error unable to open file for writting [$file]\n";
				}
			} // end if fopen worked

			fclose($file_handle);


		} // end foreach

		echo "Album Art Dump Complete\n";

	} // dump_album_art

	/**
	 * update_last_update
	 * updates the last_update of the catalog
	 */
	private function update_last_update() {

		$date = time();
		$sql = "UPDATE `catalog` SET `last_update`='$date' WHERE `id`='$this->id'";
		$db_results = Dba::write($sql);

	} // update_last_update

	/**
	 * update_last_add
	 * updates the last_add of the catalog
	 * @package Catalog
	 */
	public function update_last_add() {

		$date = time();
		$sql = "UPDATE `catalog` SET `last_add`='$date' WHERE `id`='$this->id'";
		$db_results = Dba::write($sql);

	} // update_last_add

	/**
	 * update_last_clean
	 * This updates the last clean information
	 */
	public function update_last_clean() { 

		$date = time(); 
		$sql = "UPDATE `catalog` SET `last_clean`='$date' WHERE `id`='$this->id'"; 
		$db_results = Dba::write($sql);  

	} // update_last_clean

	/**
	 * update_settings
	 * This function updates the basic setting of the catalog
	 */
	public static function update_settings($data) {

		$id	= Dba::escape($data['catalog_id']);
		$name	= Dba::escape($data['name']);
		$key	= Dba::escape($data['key']);
		$rename	= Dba::escape($data['rename_pattern']);
		$sort	= Dba::escape($data['sort_pattern']);

		$sql = "UPDATE `catalog` SET `name`='$name', `key`='$key', `rename_pattern`='$rename', " .
			"`sort_pattern`='$sort' WHERE `id` = '$id'";
		$db_results = Dba::query($sql);

		return true;

	} // update_settings

	/**
	 * update_single_item
	 * updates a single album,artist,song from the tag data
	 * this can be done by 75+
	 */
	public static function update_single_item($type,$id) {

		// Because single items are large numbers of things too
		set_time_limit(0);

		$songs = array();

		switch ($type) {
			case 'album':
				$album = new Album($id);
				$songs = $album->get_songs();
				break;
			case 'artist':
				$artist = new Artist($id);
				$songs = $artist->get_songs();
				break;
			case 'song':
				$songs[] = $id;
				break;
		} // end switch type

		foreach($songs as $song_id) {
			$song = new Song($song_id);
			$info = self::update_media_from_tags($song,'','');

			if ($info['change']) {
				$file = scrub_out($song->file);
				echo "<dl>\n\t<dd>";
				echo "<strong>$file " . _('Updated') . "</strong>\n";
				echo $info['text'];
				echo "\t</dd>\n</dl><hr align=\"left\" width=\"50%\" />";
				flush();
			} // if change
			else {
				echo"<dl>\n\t<dd>";
				echo "<strong>" . scrub_out($song->file) . "</strong><br />" . _('No Update Needed') . "\n";
				echo "\t</dd>\n</dl><hr align=\"left\" width=\"50%\" />";
				flush();
			}
		} // foreach songs

		self::clean(); 

	} // update_single_item

	/**
	 * update_media_from_tags
	 * This is a 'wrapper' function calls the update function for the media type
	 * in question
	 */
	public static function update_media_from_tags(&$media,$sort_pattern='',$rename_pattern='') { 

		// Check for patterns
		if (!$sort_pattern OR !$rename_pattern) { 
			$catalog = new Catalog($media->catalog); 
			$sort_pattern = $catalog->sort_pattern; 
			$rename_pattern = $catalog->rename_pattern; 
		} 

		debug_event('tag-read','Reading tags from ' . $media->file,'5','ampache-catalog'); 

		$vainfo = new vainfo($media->file,'','','',$sort_pattern,$rename_pattern); 
		$vainfo->get_info(); 

		$key = vainfo::get_tag_type($vainfo->tags); 

		$results = vainfo::clean_tag_info($vainfo->tags,$key,$media->file); 

		// Figure out what type of object this is and call the right function
		// giving it the stuff we've figured out above
		$name = (get_class($media) == 'Song') ? 'song' : 'video'; 

		$function = 'update_' . $name . '_from_tags'; 

		$return = call_user_func(array('Catalog',$function),$results,$media); 	

		return $return; 

	} // update_media_from_tags

	/**
	 * update_video_from_tags
	 * updates the video info based on tags this is called from a bunch of different places
	 * and passes in a full song object and the vainfo results
	 */
	public static function update_video_from_tags($results,$video) { 

		// Pretty sweet function here
		return $results; 

	} // update_video_from_tags

        /**
         * update_song_from_tags
         * updates the song info based on tags, this is called from a bunch of different places
	 * and passes in a full fledged song object, so it's a static function
	 * FIXME: This is an ugly mess, this really needs to be consolidated and cleaned up
	 */
        public static function update_song_from_tags($results,$song) {

                /* Setup the vars */
		$new_song 		= new Song();
                $new_song->file         = $results['file'];
                $new_song->title        = $results['title'];
                $new_song->year         = $results['year'];
                $new_song->comment      = $results['comment'];
		$new_song->language	= $results['language']; 
		$new_song->lyrics	= $results['lyrics']; 
                $new_song->bitrate      = $results['bitrate'];
                $new_song->rate         = $results['rate'];
                $new_song->mode         = ($results['mode'] == 'cbr') ? 'cbr' : 'vbr'; 
                $new_song->size         = $results['size'];
                $new_song->time         = $results['time'];
		$new_song->mime		= $results['mime']; 
                $new_song->track        = intval($results['track']); 
                $artist                 = $results['artist'];
                $album                  = $results['album'];
		$disk			= $results['disk'];
		$tag			= $results['genre']; 

                /*
                * We have the artist/genre/album name need to check it in the tables
                * If found then add & return id, else return id
                */
                $new_song->artist       = self::check_artist($artist);
                $new_song->f_artist     = $artist;
                $new_song->album        = self::check_album($album,$new_song->year,$disk);
                $new_song->f_album      = $album . " - " . $new_song->year;
                $new_song->title        = self::check_title($new_song->title,$new_song->file);
		
		// Nothing to assign here this is a multi-value doodly
		self::check_tag($tag,$song->id); 
		self::check_tag($tag,$new_song->album,'album'); 
		self::check_tag($tag,$new_song->artist,'artist'); 

		/* Since we're doing a full compare make sure we fill the extended information */
		$song->fill_ext_info();

		$info = Song::compare_song_information($song,$new_song);

		if ($info['change']) {
			debug_event('update',"$song->file difference found, updating database",'5','ampache-catalog');
			$song->update_song($song->id,$new_song);
			// Refine our reference
			$song = $new_song;
		}
		else {
			debug_event('update',"$song->file no difference found returning",'5','ampache-catalog');
		}

		return $info;

	} // update_song_from_tags

	/**
	 * add_to_catalog
	 * this function adds new files to an
	 * existing catalog
	 */
	public function add_to_catalog() {

		if ($this->catalog_type == 'remote') {
			show_box_top(_('Running Remote Update') . '. . .');
			$this->get_remote_catalog($type=0);
			show_box_bottom();
			return true;
		}

		require Config::get('prefix') . '/templates/show_adds_catalog.inc.php';
		flush();

		/* Set the Start time */
		$start_time = time();

		// Make sure the path doesn't end in a / or \
		$this->path = rtrim($this->path,'/');
		$this->path = rtrim($this->path,'\\');

		// Prevent the script from timing out and flush what we've got
		set_time_limit(0);

		/* Get the songs and then insert them into the db */
		$this->add_files($this->path,$type,0,$verbose);

		// Foreach Playlists we found
		foreach ($this->_playlists as $full_file) {
			if ($this->import_m3u($full_file)) {
				$file = basename($full_file);
				if ($verbose) {
					echo "&nbsp;&nbsp;&nbsp;" . _('Added Playlist From') . " $file . . . .<br />\n";
					flush();
				}
			} // end if import worked
		} // end foreach playlist files

		/* Do a little stats mojo here */
		$current_time = time();

		$catalog_id = $this->id;
		require Config::get('prefix') . '/templates/show_gather_art.inc.php';
		flush();
		$this->get_album_art();

		/* Update the Catalog last_update */
		$this->update_last_add();

		$time_diff = $current_time - $start_time;
		if ($time_diff) {
			$song_per_sec = intval($this->count/$time_diff);
		}
		if (!$song_per_sec) {
			$song_per_sec = "N/A";
		}
		if (!$this->count) {
			$this->count = 0;
		}

		show_box_top();
		echo "\n<br />" . _('Catalog Update Finished') . "... " . _('Total Time') . " [" . date("i:s",$time_diff) . "] " .
		_('Total Songs') . " [" . $this->count . "] " . _('Songs Per Seconds') . " [" . $song_per_sec . "]<br /><br />";
		show_box_bottom();

	} // add_to_catalog

	/**
	 * get_remote_catalog
	 * get a remote catalog and runs update if needed this requires
	 * the XML RPC stuff and a key to be passed
	 */
	public function get_remote_catalog($type=0) {
	
		if (!class_exists('XML_RPC_Client')) {
			debug_event('xmlrpc',"Unable to load pear XMLRPC library",'1');
			echo "<span class=\"error\"><b>" . _("Error") . "</b>: " . _('Unable to load pear XMLRPC library, make sure XML-RPC is enabled') . "</span><br />\n";
			return false;
		}

		// Handshake and get our token for this little conversation
		$token = xmlRpcClient::ampache_handshake($this->path,$this->key);

		if (!$token) {
			debug_event('XMLCLIENT','Error No Token returned', 2);
			Error::display('general');
			return;
		} else {
			debug_event('xmlrpc',"token returned",'4');
		}

		// Figure out the host etc
		preg_match("/http:\/\/([^\/\:]+):?(\d*)\/*(.*)/", $this->path, $match);
		$server = $match['1'];
		$port   = $match['2'] ? intval($match['2']) : '80';
		$path   = $match['3'];

		$full_url = "/" . ltrim($path . "/server/xmlrpc.server.php",'/');
		if(Config::get('proxy_host') AND Config::get('proxy_port')) {
			$proxy_host = Config::get('proxy_host');
			$proxy_port = Config::get('proxy_port');
			$proxy_user = Config::get('proxy_user');
			$proxy_pass = Config::get('proxy_pass');
		}
		$client = new XML_RPC_Client($full_url,$server,$port,$proxy_host,$proxy_port,$proxy_user,$proxy_pass);

		/* encode the variables we need to send over */
		$encoded_key	= new XML_RPC_Value($token,'string');
		$encoded_path	= new XML_RPC_Value(Config::get('web_path'),'string');

		$xmlrpc_message = new XML_RPC_Message('xmlrpcserver.get_catalogs', array($encoded_key,$encoded_path));
		$response = $client->send($xmlrpc_message,30);

		if ($response->faultCode() ) {
			$error_msg = _("Error connecting to") . " " . $server . " " . _("Code") . ": " . $response->faultCode() . " " . _("Reason") . ": " . $response->faultString();
			debug_event('XMLCLIENT(get_remote_catalog)',$error_msg,'1');
			echo "<p class=\"error\">$error_msg</p>";
			return;
		}
		 
		$data = XML_RPC_Decode($response->value());
			
		// Print out the catalogs we are going to sync
		foreach ($data as $vars) {
			$catalog_name 	= $vars['name'];
			$count		= $vars['count'];
			print("<b>Reading Remote Catalog: $catalog_name ($count Songs)</b> [$this->path]<br />\n");
			$total += $count;
		}

		// Flush the output
		flush();

		// Hardcoded for now
		$step = '500';
		$current = '0';

		while ($total > $current) {
			$start 	= $current;
			$current += $step;
			$this->get_remote_song($client,$token,$start,$step);
		}

		echo "<p>" . _('Completed updating remote catalog(s)') . ".</p><hr />\n";
		flush();
		
		// Try to sync the album images from the remote catalog
		echo "<p>" . _('Starting synchronisation of album images') . ".</p><br />\n";
		$this->get_remote_album_images($client, $token, $path);
		echo "<p>" . _('Completed synchronisation of album images') . ".</p><hr />\n";
		flush();
		
		// Update the last update value
		$this->update_last_update();

		return true;

	} // get_remote_catalog

	/**
	 * get_remote_song
	 * This functions takes a start and end point for gathering songs from a remote server. It is broken up
	 * in attempt to get around the problem of very large target catalogs
	 */
	public function get_remote_song($client,$token,$start,$end) {

		$encoded_start 	= new XML_RPC_Value($start,'int');
		$encoded_end	= new XML_RPC_Value($end,'int');
		$encoded_key	= new XML_RPC_Value($token,'string');

		$query_array = array($encoded_key,$encoded_start,$encoded_end);

		$xmlrpc_message = new XML_RPC_Message('xmlrpcserver.get_songs',$query_array);
		/* Depending upon the size of the target catalog this can be a very slow/long process */
		set_time_limit(0);

		// Sixty Second time out per chunk
		$response = $client->send($xmlrpc_message,60);
		$value = $response->value();

		if ( !$response->faultCode() ) {
			$data = XML_RPC_Decode($value);
			$this->update_remote_catalog($data,$this->path);
			$total = $start + $end;
			echo _('Added') . " $total...<br />";
			flush();
		}
		else {
			$error_msg = _('Error connecting to') . " " . $server . " " . _("Code") . ": " . $response->faultCode() . " " . _("Reason") . ": " . $response->faultString();
			debug_event('XMLCLIENT(get_remote_song)',$error_msg,'1');
			echo "<p class=\"error\">$error_msg</p>";
		}

		return;

	} // get_remote_song

	/**
	 * get_album_images
	 * This function retrieves the album information from the remote server
	 */
	public function get_remote_album_images($client,$token,$path) {
		
		$encoded_key	= new XML_RPC_Value($token,'string');
		$query_array    = array($encoded_key);
		$xmlrpc_message = new XML_RPC_Message('xmlrpcserver.get_album_images',$query_array);
		
		/* Depending upon the size of the target catalog this can be a very slow/long process */
		set_time_limit(0);

		// Sixty Second time out per chunk
		$response = $client->send($xmlrpc_message,60);
		$value = $response->value();

		if ( !$response->faultCode() ) {
			$data = XML_RPC_Decode($value);
			$total = $this->update_remote_album_images($data, $client->server, $token, $path);
			echo _('images synchronized: ') . ' ' . $total . "<br />";
			flush();
		}
		else {
			$error_msg = _('Error connecting to') . " " . $server . " " . _("Code") . ": " . $response->faultCode() . " " . _("Reason") . ": " . $response->faultString();
			debug_event('XMLCLIENT(get_remote_album_images)',$error_msg,'1');
			echo "<p class=\"error\">$error_msg</p>";
		}

		return;

	} // get_album_images
	
	/**
	 * update_remote_catalog
	 * actually updates from the remote data, takes an array of songs that are base64 encoded and parses them
	 * @package XMLRPC
	 * @catagory Client
	 */
	public function update_remote_catalog($data,$root_path) {

		/*
		 We need to check the incomming songs
		 to see which ones need to be added
		 */
		foreach ($data as $serialized_song) {

			// Prevent a timeout
			set_time_limit(0);
				
			$song = unserialize($serialized_song);
			$song->artist	= self::check_artist($song->artist);
			$song->album	= self::check_album($song->album,$song->year);
			$song->file	= $root_path . "/play/index.php?song=" . $song->id;
			$song->catalog	= $this->id;

			// Clear out the song id
			unset($song->id);

			if (!$this->check_remote_song($song->file)) {
				$this->insert_remote_song($song);
			}

		} // foreach new Songs

		// now delete invalid entries
		self::clean($this->id);

	} // update_remote_catalog

	/*
	 * update_remote_album_images
	 * actually synchronize the album images
	 * @package XMLRPC
	 * @catagory Client
	 */
	public function update_remote_album_images($data, $remote_server, $auth, $path) {
		$label = "catalog.class.php::update_remote_album_images";
		
		$total_updated = 0;

		/* If album images don't exist, return value will be 0. */
		if(empty($data)) { return $total_updated; }

		/*
		 * We need to check the incomming albums to see which needs to receive an image
		 */
		foreach ($data as $serialized_album) {

			// Prevent a timeout
			set_time_limit(0);

			// Load the remote album
			$remote_album = new Album();
			$remote_album = unserialize($serialized_album);
			$remote_album->format(); //this will set the fullname
			
			$debug_text = "remote_album id, name, year: ";
			$debug_text.= $remote_album->id . ", " . $remote_album->name . ", " . $remote_album->year;			
			debug_event($label, $debug_text, '4');
			
			// check the album if it exists by checking the name and the year of the album
			$local_album_id = self::check_album($remote_album->name, $remote_album->year,"", true);
			debug_event($label, "local_album_id: " . $local_album_id, '4');	
			
			if ($local_album_id != 0) {
				// Local album found lets add the cover
				if(isset($path) AND !eregi("^/", $path)) { $path = "/".$path; }
				debug_event($label, "remote_server: " . $remote_server,'4');
				$server_path = "http://" . ltrim($remote_server, "http://");
				$server_path.= $path."/image.php?id=" . $remote_album->id;
				$server_path.= "&auth=" . $auth;
				debug_event($label, "image_url: " . $server_path,'4');
				$data['url'] = $server_path;
				
				$local_album = new Album($local_album_id);
				$image_data = $local_album->get_image_from_source($data);
				
				// If we got something back insert it
				if ($image_data) { 
					$local_album->insert_art($image_data,"");
					$total_updated++;
					debug_event($label, "adding album image succes", '4');
				} else { 
					debug_event($label, "adding album image failed ", '4');
				} 
			}
		}
		
		return $total_updated;
			
	} // update_remote_album_images

	/**
	 * clean_catalog
	 * Cleans the Catalog of files that no longer exist grabs from $this->id or $id passed
	 * Doesn't actually delete anything, disables errored files, and returns them in an array
	 */
	public function clean_catalog() {

		$dead_video = array(); 
		$dead_song = array(); 

		// Added set time limit because this runs out of time for some people
		set_time_limit(0);

		require_once Config::get('prefix') . '/templates/show_clean_catalog.inc.php';
		flush();

		/* Do a quick check to make sure that the root of the catalog is readable, error if not
		 * this will minimize the loss of catalog data if mount points fail
		 */
		if (!is_readable($this->path) AND $this->catalog_type == 'local') {
			debug_event('catalog','Catalog path:' . $this->path . ' unreadable, clean failed','1');
			Error::add('general',_('Catalog Root unreadable, stopping clean'));
			Error::display('general');
			return false;
		}

		/* Get all songs in this catalog */
		$sql = "SELECT `id`,`file`,'song' AS `type` FROM `song` WHERE `catalog`='$this->id' AND `enabled`='1' " . 
			"UNION ALL " . 
			"SELECT `id`,`file`,'video' AS `type` FROM `video` WHERE `catalog`='$this->id' AND `enabled`='1'"; 
		$db_results = Dba::read($sql);

		// Set to 0 our starting point
		$dead_files = 0;

		/* Recurse through files, put @ to prevent errors poping up */
		while ($results = Dba::fetch_assoc($db_results)) {

			/* Stupid little cutesie thing */
			$count++;
			if (!($count%10)) {
				$file = str_replace(array('(',')','\''),'',$results['file']);
				echo "<script type=\"text/javascript\">\n";
				echo "update_txt('" . $count ."','clean_count_" . $this->id . "');";
				echo "update_txt('" . addslashes(htmlentities($file)) . "','clean_dir_" . $this->id . "');";
				echo "\n</script>\n";
				flush();
			} //echos song count
			
			/* Also check the file information */
			if($this->catalog_type == 'local') {
				$file_info = filesize($results['file']);
				
				/* If it errors somethings splated, or the files empty */
				if (!file_exists($results['file']) OR $file_info < 1) {
					
					/* Add Error */
					Error::add('general',"Error File Not Found or 0 Bytes: " . $results['file']);

					$table = ($results['type'] == 'video') ? 'dead_video' : 'dead_song'; 

					// Store it in an array we'll delete it later... 
					${$table}[] = $results['id']; 

					// Count em!
					$dead_files++;

				} //if error
				if (!is_readable($results['file'])) { 
					debug_event('Clean','Error ' . $results['file'] . ' is not readable, but does exist','1'); 
				} 
			} // if localtype
			else {
				//do remote url check
				$file_info = $this->exists_remote_song($results['file']);

				/* If it errors somethings splated, or the files empty */
				if ($file_info == false) {
					/* Add Error */
					Error::add('general',"Error Remote File Not Found or 0 Bytes: " . $results['file']);


					$table = ($results['type'] == 'video') ? 'dead_video' : 'dead_song'; 

					// Store it in an array we'll delete it later... 
					${$table}[] = $results['id']; 

					// Count em!
					$dead_files++;

				} //if error
			} // remote catalog

		} //while gettings songs

		// Check and see if _everything_ has gone away, might indicate a dead mount
		// We call this the AlmightyOatmeal Sanity check
		if ($dead_files == $count) { 
			//UNTRANSLATED FIXME
			Error::add('general','Error All songs would be removed, doing nothing'); 
			return false; 
		} 
		else { 
			if (count($dead_video)) { 
				$idlist = '(' . implode(',',$dead_video) . ')'; 
				$sql = "DELETE FROM `video` WHERE `id` IN $idlist"; 
				$db_results = Dba::write($sql); 
			}
			if (count($dead_song)) { 
				$idlist = '(' . implode(',',$dead_song) . ')'; 		
				$sql = "DELETE FROM `song` WHERE `id` IN $idlist"; 
				$db_results = Dba::write($sql); 
			} 
		}

		/* Step two find orphaned Arists/Albums
		 * This finds artists and albums that no
		 * longer have any songs associated with them
		 */
		self::clean($catalog_id);

		/* Return dead files, so they can be listed */
		echo "<script type=\"text/javascript\">\n";
		echo "update_txt('" . $count ."','clean_count_" . $this->id . "');";
		echo "\n</script>\n";
		show_box_top();
		echo "<strong>" . _('Catalog Clean Done') . " [" . $dead_files . "] " . _('files removed') . "</strong><br />\n";
		echo "<strong>" . _('Optimizing Tables') . "...</strong><br />\n";
		self::optimize_tables();
		show_box_bottom();
		flush();

		// Set the last clean date
		$this->update_last_clean(); 

	} //clean_catalog

	/**
	 * clean_tags
	 * This cleans out tag_maps that are not assoicated with a 'living' object
	 * and then cleans the tags that have no maps
	 */
	public static function clean_tags() {

		$sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `song` ON `song`.`id`=`tag_map`.`object_id` " .
			"WHERE `tag_map`.`object_type`='song' AND `song`.`id` IS NULL"; 
		$db_results = Dba::write($sql);

		$sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `album` ON `album`.`id`=`tag_map`.`object_id` " .
                        "WHERE `tag_map`.`object_type`='album' AND `album`.`id` IS NULL";
		$db_results = Dba::write($sql);

		$sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `artist` ON `artist`.`id`=`tag_map`.`object_id` " .
                        "WHERE `tag_map`.`object_type`='artist' AND `artist`.`id` IS NULL";
		$db_results = Dba::write($sql);

		$sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `video` ON `video`.`id`=`tag_map`.`object_id` " . 
			"WHERE `tag_map`.`object_type`='video' AND `video`.`id` IS NULL"; 
		$db_results = Dba::write($sql); 

		// Now nuke the tags themselves
		$sql = "DELETE FROM `tag` USING `tag` LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` " .
			"WHERE `tag_map`.`id` IS NULL"; 
		$db_results = Dba::write($sql);

	} // clean_tags

	/**
	 * clean_shoutbox
	 * This cleans out any shoutbox items that are now orphaned
	 */
	public static function clean_shoutbox() {

		// Clean songs
		$sql = "DELETE FROM `user_shout` USING `user_shout` LEFT JOIN `song` ON `song`.`id`=`user_shout`.`object_id` " .
			"WHERE `song`.`id` IS NULL AND `user_shout`.`object_type`='song'"; 
		$db_results = Dba::query($sql);

		// Clean albums
		$sql = "DELETE FROM `user_shout` USING `user_shout` LEFT JOIN `album` ON `album`.`id`=`user_shout`.`object_id` " .
                        "WHERE `album`.`id` IS NULL AND `user_shout`.`object_type`='album'";
		$db_results = Dba::query($sql);

		// Clean artists
		$sql = "DELETE FROM `user_shout` USING `user_shout` LEFT JOIN `artist` ON `artist`.`id`=`user_shout`.`object_id` " .
                        "WHERE `artist`.`id` IS NULL AND `user_shout`.`object_type`='artist'";
		$db_results = Dba::query($sql);


	} // clean_shoutbox

	/**
	 * clean_albums
	 *This function cleans out unused albums
	 */
	public static function clean_albums() {

		/* Do a complex delete to get albums where there are no songs */
		$sql = "DELETE FROM album USING album LEFT JOIN song ON song.album = album.id WHERE song.id IS NULL";
		$db_results = Dba::query($sql);

		/* Now remove any album art that is now dead */
		$sql = "DELETE FROM `album_data` USING `album_data` LEFT JOIN `album` ON `album`.`id`=`album_data`.`album_id` WHERE `album`.`id` IS NULL";
		$db_results = Dba::query($sql);

		// This can save a lot of space so always optomize
		$sql = "OPTIMIZE TABLE `album_data`";
		$db_results = Dba::query($sql);

	} // clean_albums

	/**
	 * clean_flagged
	 * This functions cleans ou unused flagged items
	 */
	public static function clean_flagged() {

		/* Do a complex delete to get flagged items where the songs are now gone */
		$sql = "DELETE FROM flagged USING flagged LEFT JOIN song ON song.id = flagged.object_id WHERE song.id IS NULL AND object_type='song'";
		$db_results = Dba::query($sql);

	} // clean_flagged

	/**
	 * clean_artists
	 * This function cleans out unused artists
	 */
	public static function clean_artists() {

		/* Do a complex delete to get artists where there are no songs */
		$sql = "DELETE FROM artist USING artist LEFT JOIN song ON song.artist = artist.id WHERE song.id IS NULL";
		$db_results = Dba::query($sql);

	} //clean_artists

	/**
	 * clean_playlists
	 * cleans out dead files from playlists
	 */
	public static function clean_playlists() {

		/* Do a complex delete to get playlist songs where there are no songs */
		$sql = "DELETE FROM `playlist_data` USING `playlist_data` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` " .
			"WHERE `song`.`file` IS NULL AND `playlist_data`.`object_type`='song'";
		$db_results = Dba::query($sql);

		// Clear TMP Playlist information as well
		$sql = "DELETE FROM `tmp_playlist_data` USING `tmp_playlist_data` LEFT JOIN `song` ON `tmp_playlist_data`.`object_id` = `song`.`id` " .
			"WHERE `song`.`id` IS NULL"; 
		$db_results = Dba::query($sql);

	} // clean_playlists

	/**
	 * clean_ext_info
	 * This function clears any ext_info that no longer has a parent
	 */
	public static function clean_ext_info() {

		$sql = "DELETE FROM `song_data` USING `song_data` LEFT JOIN `song` ON `song`.`id` = `song_data`.`song_id` " .
			"WHERE `song`.`id` IS NULL"; 
		$db_results = Dba::query($sql);

	} // clean_ext_info

	/**
	 * clean_stats
	 * This functions removes stats for songs/albums that no longer exist
	 */
	public static function clean_stats() {

		// Crazy SQL Mojo to remove stats where there are no songs
		$sql = "DELETE FROM object_count USING object_count LEFT JOIN song ON song.id=object_count.object_id WHERE object_type='song' AND song.id IS NULL";
		$db_results = Dba::write($sql);

		// Crazy SQL Mojo to remove stats where there are no albums
		$sql = "DELETE FROM object_count USING object_count LEFT JOIN album ON album.id=object_count.object_id WHERE object_type='album' AND album.id IS NULL";
		$db_results = Dba::write($sql);

		// Crazy SQL Mojo to remove stats where ther are no artists
		$sql = "DELETE FROM object_count USING object_count LEFT JOIN artist ON artist.id=object_count.object_id WHERE object_type='artist' AND artist.id IS NULL";
		$db_results = Dba::write($sql);

		// Delete the live_stream stat information
		$sql = "DELETE FROM object_count USING object_count LEFT JOIN live_stream ON live_stream.id=object_count.object_id WHERE object_type='live_stream' AND live_stream.id IS NULL";
		$db_results = Dba::write($sql);

		// Clean the stats
		$sql = "DELETE FROM `object_count` USING `object_count` LEFT JOIN `video` ON `video`.`id`=`object_count`.`object_id` " . 
			"WHERE `object_count`.`object_type`='video' AND `video`.`id` IS NULL"; 
		$db_results = Dba::write($sql); 

		// Delete Song Ratings information
		$sql = "DELETE FROM rating USING rating LEFT JOIN song ON song.id=rating.object_id WHERE object_type='song' AND song.id IS NULL";
		$db_results = Dba::write($sql);

		// Delete Album Rating Information
		$sql = "DELETE FROM rating USING rating LEFT JOIN album ON album.id=rating.object_id WHERE object_type='album' AND album.id IS NULL";
		$db_results = Dba::write($sql);

		// Delete Artist Rating Information
		$sql = "DELETE FROM rating USING rating LEFT JOIN artist ON artist.id=rating.object_id WHERE object_type='artist' AND artist.id IS NULL";
		$db_results = Dba::write($sql);

		// Delete the Video Rating Informations
		$sql = "DELETE FROM `rating` USING `rating` LEFT JOIN `video` ON `video`.`id`=`rating`.`object_id` " . 
			"WHERE `rating`.`object_type`='video' AND `video`.`id` IS NULL"; 
		$db_results = Dba::write($sql);

	} // clean_stats

	/**
	 * verify_catalog
	 * This function compares the DB's information with the ID3 tags
	 */
	public function verify_catalog($catalog_id) {

		// Create the object so we have some information on it
		$catalog = new Catalog($catalog_id);

		$cache = array(); 
		$songs = array(); 

		// Record that we're caching this stuff so it makes debugging easier
		debug_event('Verify','Starting Verify of '. $catalog->name . ' caching data...','5'); 
	
		/* First get the filenames for the catalog */
		$sql = "SELECT `id`,`file`,`artist`,`album`,'song' AS `type` FROM `song` WHERE `song`.`catalog`='$catalog_id' ";
		$db_results = Dba::read($sql); 
		
		while ($row = Dba::fetch_assoc($db_results)) { 
			$cache[] = $row['id']; 
			$artists[] = $row['artist']; 
			$albums[] = $row['album']; 
			$songs[] = $row; 
		} 
		Song::build_cache($cache); 
		Flag::build_map_cache($cache,'song'); 
		Tag::build_map_cache('album',$albums); 
		Tag::build_map_cache('artist',$artists); 
		Tag::build_map_cache('song',$cache); 

		$cache = array(); 
		$videos = array(); 
		$sql = "SELECT `id`,`file`,'video' AS `type` FROM `video` WHERE `video`.`catalog`='$catalog_id'"; 
		$db_results = Dba::read($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$cache[] = $row['id'];
			$videos[] = $row; 
		} 
		Video::build_cache($cache); 
		Flag::build_map_cache($cache,'video'); 

		$cached_results = array_merge($songs,$videos); 

		$number = count($cached_results); 
		require_once Config::get('prefix') . '/templates/show_verify_catalog.inc.php';
		flush();

		/* Magical Fix so we don't run out of time */
		set_time_limit(0);

		// Caching array for album art, save us some time here
		$album_art_check_cache = array();

		/* Recurse through this catalogs files
		 * and get the id3 tage information,
		 * if it's not blank, and different in
		 * in the file then update!
		 */
		foreach ($cached_results as $results) { 

			debug_event('verify',"Starting work on " . $results['file'],'5','ampache-catalog');
			$type = ($results['type'] == 'video') ? 'video' : 'song';
				
			if (is_readable($results['file'])) {

				/* Create the object from the existing database information */
				$media = new $type($results['id']);

				unset($skip);

				/* Make sure the song isn't flagged, we don't update flagged stuff */
				if (Flag::has_flag($media->id,$type)) {
					$skip = true;
				}

				// if the file hasn't been modified since the last_update
				if (!$skip) {

					$info = self::update_media_from_tags($media,$this->sort_pattern,$this->rename_pattern);
					if ($info['change']) {
						$total_updated++;
					}
					unset($info);

				} // end skip

				if ($skip) {
					debug_event('skip',"$media->file has been skipped due to newer local update or file mod time",'5','ampache-catalog');
				}

				/* Stupid little cutesie thing */
				$count++;
				if (!($count%10) ) {
					$file = str_replace(array('(',')','\''),'',$media->file);
					echo "<script type=\"text/javascript\">\n";
					echo "update_txt('" . $count . "','verify_count_" . $catalog_id . "');";
					echo "update_txt('" . scrub_out($file) . "','verify_dir_" . $catalog_id . "');";
					echo "\n</script>\n";
					flush();
				} //echos song count

			} // end if file exists

			else {
				Error::add('general',"$media->file does not exist or is not readable");
				debug_event('read-error',"$media->file does not exist or is not readable, removing",'5','ampache-catalog');
				// Let's go ahead and remove it!
				$sql = "DELETE FROM `$type` WHERE `id`='" . Dba::escape($media->id) . "'";
				$del_results = Dba::write($sql);
			}

		} //end foreach

		/* After we have updated all the songs with the new information clear any empty albums/artists */
		self::clean($catalog_id);

		// Update the last_update
		$this->update_last_update();

		// One final time!
		echo "<script type=\"text/javascript\">\n";
		echo "update_txt('" . $this->count . "','count_verify_" . $this->id . "');";
		echo "\n</script>\n";
		flush();

		show_box_top();
		echo '<strong>' . _('Update Finished') . '</strong><br />' . _('Checked') . ' ' .   intval($count) . '.<br />' . _('Updated') . ' ' . intval($total_updated) .  '<br /><br />';
		show_box_bottom();

		return true;

	} // verify_catalog

	/**
	 * clean
	 * This is a wrapper function for all of the different cleaning
	 * functions, it runs them in the correct order and takes a catalog_id
	 */
	public static function clean() {

		self::clean_albums();
		self::clean_artists();
		self::clean_flagged();
		self::clean_stats();
		self::clean_ext_info();
		self::clean_playlists();
		self::clean_shoutbox();
		self::clean_tags();

	} // clean

	/**
	 * optimize_tables
	 * This runs an optomize on the tables and updates the stats to improve join speed
	 * this can be slow, but is a good idea to do from time to time. This is incase the dba
	 * isn't doing it... which we're going to assume they aren't
	 */
	public static function optimize_tables() {

		$sql = "OPTIMIZE TABLE `song_data`,`song`,`rating`,`catalog`,`session`,`object_count`,`album`,`album_data`" .
			",`artist`,`ip_history`,`flagged`,`now_playing`,`user_preference`,`tag`,`tag_map`,`tmp_playlist`" . 
			",`tmp_playlist_data`,`playlist`,`playlist_data`,`session_stream`,`video`"; 
		$db_results = Dba::query($sql);

		$sql = "ANALYZE TABLE `song_data`,`song`,`rating`,`catalog`,`session`,`object_count`,`album`,`album_data`" .
		        ",`artist`,`ip_history`,`flagged`,`now_playing`,`user_preference`,`tag`,`tag_map`,`tmp_playlist`" .
			",`tmp_playlist_data`,`playlist`,`playlist_data`,`session_stream`,`video`";
		$db_results = Dba::query($sql);

	} // optimize_tables;

	/**
	 * check_artist
	 * $artist checks if there then return id else insert and return id
	 * If readonly is passed then don't create, return false on not found
	 */
	public static function check_artist($artist,$readonly='') {

		/* Clean up the artist */
		$artist = trim($artist);
		$artist = Dba::escape($artist);


		/* Ohh no the artist has lost it's mojo! */
		if (!$artist) {
			$artist = _('Unknown (Orphaned)');
		}

		// Remove the prefix so we can sort it correctly
		$prefix_pattern = '/^(' . implode('\\s|',explode('|',Config::get('catalog_prefix_pattern'))) . '\\s)(.*)/i';
		preg_match($prefix_pattern,$artist,$matches);

		if (count($matches)) {
			$artist = trim($matches[2]);
			$prefix = trim($matches[1]);
		}

		// Check to see if we've seen this artist before
		if (isset(self::$artists[$artist])) {
			return self::$artists[$artist];
		} // if we've seen this artist before

		/* Setup the checking sql statement */
		$sql = "SELECT `id` FROM `artist` WHERE `name` LIKE '$artist' ";
		$db_results = Dba::query($sql);

		/* If it's found */
		if ($r = Dba::fetch_assoc($db_results)) {
			$artist_id = $r['id'];
		} //if found

		/* If not found create */
		elseif (!$readonly) {

			$prefix_txt = 'NULL';

			if ($prefix) {
				$prefix_txt = "'$prefix'";
			}

			$sql = "INSERT INTO `artist` (`name`, `prefix`) VALUES ('$artist',$prefix_txt)";
			$db_results = Dba::query($sql);
			$artist_id = Dba::insert_id();

			if (!$db_results) {
				Error::add('general',"Inserting Artist:$artist");
			}

		} // not found
		// If readonly, and not found return false
		else {
			return false;
		}

		$array = array($artist => $artist_id);
		self::$artists = array_merge(self::$artists, $array);
		unset($array);

		return $artist_id;

	} // check_artist

	/**
	 * check_album
	 * Takes $album and checks if there then return id else insert and return id
	 */
	public static function check_album($album,$album_year=0,$disk='',$readonly='') {

		/* Clean up the album name */
		$album = trim($album);
		$album = Dba::escape($album);
		$album_year = intval($album_year);
		$album_disk = intval($disk);

		/* Ohh no the album has lost it's mojo */
		if (!$album) {
			$album = _('Unknown (Orphaned)');
			unset($album_year);
		}

		// Remove the prefix so we can sort it correctly
		$prefix_pattern = '/^(' . implode('\\s|',explode('|',Config::get('catalog_prefix_pattern'))) . '\\s)(.*)/i';
		preg_match($prefix_pattern,$album,$matches);

		if (count($matches)) {
			$album = trim($matches[2]);
			$prefix = trim($matches[1]);
		}

		// Check to see if we've seen this album before
		if (isset(self::$albums[$album][$album_year][$disk])) {
			return self::$albums[$album][$album_year][$disk];
		}

		/* Setup the Query */
		$sql = "SELECT `id` FROM `album` WHERE trim(`name`) = '$album'";
		if ($album_year) { $sql .= " AND `year`='$album_year'"; }
		if ($album_disk) { $sql .= " AND `disk`='$album_disk'"; }
		if ($prefix) { $sql .= " AND `prefix`='" . Dba::escape($prefix) . "'"; }
		$db_results = Dba::query($sql);

		/* If it's found */
		if ($r = Dba::fetch_assoc($db_results)) {
			$album_id = $r['id'];

			// If we don't have art put it in the 'needs me some art' array
			if (!strlen($r['art'])) {
				$key = $r['id'];
				self::$_art_albums[$key] = $key;
			}

		} //if found

		/* If not found create */
		elseif (!$readonly) {

			$prefix_txt = $prefix ? "'$prefix'" : 'NULL';

			$sql = "INSERT INTO `album` (`name`, `prefix`,`year`,`disk`) VALUES ('$album',$prefix_txt,'$album_year','$album_disk')";
			$db_results = Dba::query($sql);
			$album_id = Dba::insert_id();

			if (!$db_results) {
				debug_event('album',"Error Unable to insert Album:$album",'2');
			}

			// Add it to the I needs me some album art array
			self::$_art_albums[$album_id] = $album_id;

		} //not found
		// If not readonly and not found
		else {
			return false;
		}

		// Save the cache
		self::$albums[$album][$album_year][$disk] = $album_id; 

		return $album_id;

	} // check_album

	/**
	 * check_tag
	 * This checks the tag we've been passed (name) 
	 * and sees if it exists, and if so if it's mapped
	 * to this object, this is only done for songs for now
	 */
	public static function check_tag($value,$object_id,$object_type='song') { 

		$map_id = Tag::add($object_type,$object_id,$value,'0'); 

		return $map_id;  

	} // check_tag
	
	/**
	 * check_title
	 * this checks to make sure something is
	 * set on the title, if it isn't it looks at the
	 * filename and trys to set the title based on that
	 */
	public static function check_title($title,$file=0) {

		if (strlen(trim($title)) < 1) {
			$title = Dba::escape($file);
		}

		return $title;

	} // check_title

	/**
	 * insert_local_song
	 * Insert a song that isn't already in the database this
	 * function is in here so we don't have to create a song object
	 */
	public function insert_local_song($file,$file_info) {

		/* Create the vainfo object and get info */
		$vainfo		= new vainfo($file,'','','',$this->sort_pattern,$this->rename_pattern);
		$vainfo->get_info();

		$key = vainfo::get_tag_type($vainfo->tags);

		/* Clean Up the tags */
		$results = vainfo::clean_tag_info($vainfo->tags,$key,$file);

		/* Set the vars here... so we don't have to do the '" . $blah['asd'] . "' */
		$title 		= Dba::escape($results['title']);
		$artist 	= $results['artist'];
		$album 		= $results['album'];
		$bitrate 	= $results['bitrate'];
		$rate	 	= $results['rate'];
		$mode 		= $results['mode'];
		$size	 	= $results['size'];
		$song_time 	= $results['time'];
		$track	 	= $results['track'];
		$disk	 	= $results['disk'];
		$year		= $results['year'];
		$comment	= $results['comment'];
		$tag		= $results['genre'];
		$current_time 	= time();
		$lyrics 	= ' ';

		/*
		 * We have the artist/genre/album name need to check it in the tables
		 * If found then add & return id, else return id
		 */
		$artist_id	= self::check_artist($artist);
		$album_id	= self::check_album($album,$year,$disk);
		$title		= self::check_title($title,$file);
		$add_file	= Dba::escape($file);

		$sql = "INSERT INTO `song` (file,catalog,album,artist,title,bitrate,rate,mode,size,time,track,addition_time,year)" .
			" VALUES ('$add_file','$this->id','$album_id','$artist_id','$title','$bitrate','$rate','$mode','$size','$song_time','$track','$current_time','$year')";
		$db_results = Dba::query($sql);

		if (!$db_results) {
			debug_event('insert',"Unable to insert $file -- $sql" . Dba::error(),'5','ampache-catalog');
			Error::add('catalog_add','SQL Error Adding ' . $file);
		}
			
		$song_id = Dba::insert_id();

		self::check_tag($tag,$song_id);
		self::check_tag($tag,$album_id,'album'); 
		self::check_tag($tag,$artist_id,'artist'); 

		/* Add the EXT information */
		$sql = "INSERT INTO `song_data` (`song_id`,`comment`,`lyrics`) " .
			" VALUES ('$song_id','$comment','$lyrics')"; 
		$db_results = Dba::query($sql);

		if (!$db_results) {
			debug_event('insert',"Unable to insert EXT Info for $file -- $sql",'5','ampache-catalog');
		}

	} // insert_local_song

	/**
	 * insert_remote_song
	 * takes the information gotten from XML-RPC and
	 * inserts it into the local database. The filename
	 * ends up being the url.
	 */
	public function insert_remote_song($song) {

		$url 		= Dba::escape($song->file);
		$title		= self::check_title($song->title);
		$title		= Dba::escape($title);
		$current_time	= time();

		$sql = "INSERT INTO song (file,catalog,album,artist,title,bitrate,rate,mode,size,time,track,addition_time,year)" .
			" VALUES ('$url','$song->catalog','$song->album','$song->artist','$title','$song->bitrate','$song->rate','$song->mode','$song->size','$song->time','$song->track','$current_time','$song->year')";
		$db_results = Dba::query($sql);

		if (!$db_results) {
			debug_event('insert',"Unable to Add Remote $url -- $sql",'5','ampache-catalog');
			echo "<span style=\"color: #FOO;\">Error Adding Remote $url </span><br />$sql<br />\n";
			flush();
		}
		
	} // insert_remote_song

	/**
	 * insert_local_video
	 * This inserts a video file into the video file table the tag
	 * information we can get is super sketchy so it's kind of a crap shoot
	 * here
	 */
	public function insert_local_video($file,$filesize) { 

                /* Create the vainfo object and get info */
                $vainfo         = new vainfo($file,'','','',$this->sort_pattern,$this->rename_pattern);
                $vainfo->get_info();

		$tag_name = vainfo::get_tag_type($vainfo->tags); 
		$results = vainfo::clean_tag_info($vainfo->tags,$tag_name,$file); 


		$file 		= Dba::escape($file); 
		$catalog_id 	= Dba::escape($this->id); 
		$title 		= Dba::escape($results['title']); 
		$vcodec 	= $results['video_codec']; 
		$acodec 	= $results['audio_codec']; 
		$rezx 		= intval($results['resolution_x']); 
		$rezy 		= intval($results['resolution_y']); 
		$filesize 	= Dba::escape($filesize); 
		$time 		= Dba::escape($results['time']); 
		$mime		= Dba::escape($results['mime']); 
		// UNUSED CURRENTLY
		$comment	= Dba::escape($results['comment']); 
		$year		= Dba::escape($results['year']); 
		$disk		= Dba::escape($results['disk']); 

		$sql = "INSERT INTO `video` (`file`,`catalog`,`title`,`video_codec`,`audio_codec`,`resolution_x`,`resolution_y`,`size`,`time`,`mime`) " . 
			" VALUES ('$file','$catalog_id','$title','$vcodec','$acodec','$rezx','$rezy','$filesize','$time','$mime')"; 
		$db_results = Dba::write($sql); 

		return true; 

	} // insert_local_video

	/**
	 * check_remote_song
	 * checks to see if a remote song exists in the database or not
	 * if it find a song it returns the UID
	 */
	public function check_remote_song($url) {

		$url = Dba::escape($url);

		$sql = "SELECT `id` FROM `song` WHERE `file`='$url'";
		$db_results = Dba::query($sql);

		if (Dba::num_rows($db_results)) {
			return true;
		}

		return false;

	} // check_remote_song

	/**
	 * exists_remote_song
	 * checks to see if a remote song exists in the remote file or not
	 * if it can't find a song it return the false
	 */
	public function exists_remote_song($url) {

		$url = parse_url(Dba::escape($url));

		list($arg,$value) = split('=', $url['query']);
		$token = xmlRpcClient::ampache_handshake($this->path,$this->key);
		if (!$token) {
			debug_event('XMLCLIENT','Error No Token returned', 2);
			Error::display('general');
			return;
		} else {
			debug_event('xmlrpc',"token returned",'4');
		}

		preg_match("/http:\/\/([^\/\:]+):?(\d*)\/*(.*)/", $this->path, $match);
		$server = $match['1'];
		$port   = $match['2'] ? intval($match['2']) : '80';
		$path   = $match['3'];

		$full_url = "/" . ltrim($path . "/server/xmlrpc.server.php",'/');
		if(Config::get('proxy_host') AND Config::get('proxy_port')) {
			$proxy_host = Config::get('proxy_host');
			$proxy_port = Config::get('proxy_port');
			$proxy_user = Config::get('proxy_user');
			$proxy_pass = Config::get('proxy_pass');
		}

		$client = new XML_RPC_Client($full_url,$server,$port,$proxy_host,$proxy_port,$proxy_user,$proxy_pass);

		/* encode the variables we need to send over */
		$encoded_key    = new XML_RPC_Value($token,'string');
		$encoded_path   = new XML_RPC_Value(Config::get('web_path'),'string');
		$song_id   = new XML_RPC_Value($value,'int');

		$xmlrpc_message = new XML_RPC_Message('xmlrpcserver.check_song', array($song_id,$encoded_key,$encoded_path));
		$response = $client->send($xmlrpc_message,30);

		if ($response->faultCode() ) {
			$error_msg = _("Error connecting to") . " " . $server . " " . _("Code") . ": " . $response->faultCode() . " " . _("Reason") . ": " . $response->faultString();
			debug_event('XMLCLIENT(exists_remote_song)',$error_msg,'1');
			echo "<p class=\"error\">$error_msg</p>";
			return;
		}

		$data = XML_RPC_Decode($response->value());

		if($data == '0') {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * check_local_mp3
	 * Checks the song to see if it's there already returns true if found, false if not
	 */
	public function check_local_mp3($full_file, $gather_type='') {

		$file_date = filemtime($full_file);
		if ($file_date < $this->last_add) {
			debug_event('Check','Skipping ' . $full_file . ' File modify time before last add run','3');
			return true;
		}

		$full_file = Dba::escape($full_file);

		$sql = "SELECT `id` FROM `song` WHERE `file` = '$full_file'";
		$db_results = Dba::query($sql);

		//If it's found then return true
		if (Dba::fetch_row($db_results)) {
			return true;
		}

		return false;

	} //check_local_mp3

	/**
	 * import_m3u
	 * this takes m3u filename and then attempts to create a Public Playlist based on the filenames
	 * listed in the m3u
	 */
	public function import_m3u($filename) {
		global $reason, $playlist_id;

		$m3u_handle = fopen($filename,'r');

		$data = fread($m3u_handle,filesize($filename));

		$results = explode("\n",$data);

		$pattern = '/\.(' . Config::get('catalog_file_pattern') . ')$/i';

		// Foreach what we're able to pull out from the file
		foreach ($results as $value) {

			// Remove extra whitespace
			$value = trim($value);
			if (preg_match($pattern,$value)) {

				/* Translate from \ to / so basename works */
				$value = str_replace("\\","/",$value);
				$file = basename($value);

				/* Search for this filename, cause it's a audio file */
				$sql = "SELECT `id` FROM `song` WHERE `file` LIKE '%" . Dba::escape($file) . "'";
				$db_results = Dba::query($sql);
				$results = Dba::fetch_assoc($db_results);

				if (isset($results['id'])) { $songs[] = $results['id']; }

			} // if it's a file
			// Check to see if it's a url from this ampache instance
			elseif (substr($value,0,strlen(Config::get('web_path'))) == Config::get('web_path')) {
				$song_id = intval(Song::parse_song_url($value));

				$sql = "SELECT COUNT(*) FROM `song` WHERE `id`='$song_id'";
				$db_results = Dba::query($sql);

				if (Dba::num_rows($db_results)) {
					$songs[] = $song_id;
				}

			} // end if it's an http url

		} // end foreach line

		debug_event('m3u_parse',"Parsing $filename - Found: " . count($songs) . " Songs",'5');

		if (count($songs)) {
			$name = "M3U - " . basename($filename,'.m3u');
			$playlist_id = Playlist::create($name,'public');

			if (!$playlist_id) { 
				$reason = _('Playlist creation error.');
				return false;
			}

			/* Recreate the Playlist */
			$playlist = new Playlist($playlist_id);
			$playlist->add_songs($songs);
			$reason = sprintf(_('Playlist Import and Recreate Successful. Total: %d Songs'), count($songs));
			return true;
		}

		$reason = sprintf(_('Parsing %s - Not Found: %d Songs. Please check your m3u file.'), $filename, count($songs));
		return false;

	} // import_m3u

	/**
	 * delete
	 * Deletes the catalog and everything assoicated with it
	 * it takes the catalog id
	 */
	public static function delete($catalog_id) {

		$catalog_id = Dba::escape($catalog_id);

		// First remove the songs in this catalog
		$sql = "DELETE FROM `song` WHERE `catalog` = '$catalog_id'";
		$db_results = Dba::write($sql);

		// Only if the previous one works do we go on
		if (!$db_results) { return false; }

		$sql = "DELETE FROM `video` WHERE `catalog` = '$catalog_id'"; 
		$db_results = Dba::write($sql); 

		if (!$db_results) { return false; } 

		// Next Remove the Catalog Entry it's self
		$sql = "DELETE FROM `catalog` WHERE `id` = '$catalog_id'";
		$db_results = Dba::write($sql);

		// Run the Aritst/Album Cleaners...
		self::clean($catalog_id);

	} // delete

	/**
	 * exports the catalog
	 * it exports all songs in the database to the given export type.
	 */
	public function export($type) {

		// Select all songs in catalog
		if($this->id) {
			$sql = "SELECT id FROM song WHERE catalog = '$this->id' ORDER BY album,track";
		} else {
			$sql = "SELECT id FROM song ORDER BY album,track";
		}
		$db_results = Dba::query($sql);

		switch ($type) {
			case 'itunes':
				echo xml_get_header('itunes');
					
				while ($results = Dba::fetch_assoc($db_results)) {
					$song = new Song($results['id']);
					$song->format();

					$xml = array();
					$xml['key']= $results['id'];
					$xml['dict']['Track ID']= intval($results['id']);
					$xml['dict']['Name'] = $song->title;
					$xml['dict']['Artist'] = $song->f_artist_full;
					$xml['dict']['Album'] = $song->f_album_full;
					$xml['dict']['Genre'] = $song->f_genre; // FIXME
					$xml['dict']['Total Time'] = intval($song->time) * 1000; // iTunes uses milliseconds
					$xml['dict']['Track Number'] = intval($song->track);
					$xml['dict']['Year'] = intval($song->year);
					$xml['dict']['Date Added'] = date("Y-m-d\TH:i:s\Z",$song->addition_time);
					$xml['dict']['Bit Rate'] = intval($song->bitrate/1000);
					$xml['dict']['Sample Rate'] = intval($song->rate);
					$xml['dict']['Play Count'] = intval($song->played);
					$xml['dict']['Track Type'] = "URL";
					$xml['dict']['Location'] = Song::play_url($song->id);
					echo xml_from_array($xml,1,'itunes');
					// flush output buffer
				} // while result
				echo xml_get_footer('itunes');

				break;
			case 'csv':
				echo "ID,Title,Artist,Album,Genre,Length,Track,Year,Date Added,Bitrate,Played,File\n";
				while ($results = Dba::fetch_assoc($db_results)) {
					$song = new Song($results['id']);
					$song->format();
					echo '"' . $song->id . '","' . $song->title . '","' . $song->f_artist_full . '","' . $song->f_album_full .
						'","' . $song->f_genre . '","' . $song->f_time . '","' . $song->f_track . '","' . $song->year . 
						'","' . date("Y-m-d\TH:i:s\Z",$song->addition_time) . '","' . $song->f_bitrate . 
						'","' . $song->played . '","' . $song->file . "\n"; 
				}
				break;
		} // end switch

	} // export

} // end of catalog class

?>
