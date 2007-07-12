<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All Rights Reserved

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
 * Catalog Class
 * This class handles all actual work in regards to the catalog, it contains functions for creating/listing/updated the catalogs.
 * @package Catalog
 * @catagory Class
 */
class Catalog {

	public $name;
	public $last_update;
	public $last_add;
	public $key;
	public $rename_pattern;
	public $sort_pattern;
	public $catalog_type;

	/* This is a private var that's used during catalog builds */
	private $_playlists = array();

	// Used in functions
	private static $albums	= array();
	private static $artists	= array();
	private static $genres	= array();
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
		$info = $this->_get_info();

		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} 

	} //constructor

	/**
	 * _get_info
	 * get's the vars for $this out of the database requires an id
	 */
	private function _get_info() {

		/* Grab the basic information from the catalog and return it */
		$sql = "SELECT * FROM `catalog` WHERE `id`='$this->id'";
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);

		return $results;

	} // _get_info

	/**
	 * get_catalogs
	 *Pull all the current catalogs
	 */
	public static function get_catalogs() {

		$sql = "SELECT `id` FROM `catalog`";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) {
			$results[] = new Catalog($r['id']);
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

		$sql = "UDPATE `song` SET `player`='0'"; 
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

		$path = Dba::escape($data['path']); 

		// Make sure the path is readable/exists
		if (!is_readable($data['path'])) { 
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
		$gather_types	= ' '; //FIXME 
		$key		= Dba::escape($data['key']); 

		if (!$key) { $key = ' '; } //FIXME 

		// Ok we're good to go ahead and insert this record
		$sql = "INSERT INTO `catalog` (`name`,`path`,`catalog_type`,`rename_pattern`,`sort_pattern`,`gather_types`,`key`) " . 
			"VALUES ('$name','$path','$catalog_type','$rename_pattern','$sort_pattern','$gather_types','$key')";
		$db_results = Dba::query($sql); 

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

		// Catalog Add start
		$start_time = time(); 

		require Config::get('prefix') . '/templates/show_adds_catalog.inc.php'; 
		flush(); 	

		// Prevent the script from timing out and flush what we've got
		set_time_limit(0);

		$this->add_files($this->path,$options); 

		return true;  

	} // run_add

	/**
	 * count_songs
	 * This returns the current # of songs, albums, artists, genres
	 * in this catalog
	 */
	public static function count_songs($catalog_id='') { 

		if ($catalog_id) { $catalog_search = "WHERE `catalog`='" . Dba::escape($catalog_id) . "'"; } 

		$sql = "SELECT `id`,`album`,`artist`,`genre`,`file`,`size`,`time` FROM `song` $catalog_search"; 
		$db_results = Dba::query($sql); 

		while ($data = Dba::fetch_assoc($db_results)) { 
			$albums[$data['album']] = true; 
			$artists[$data['artist']] = true; 
			$genres[$data['genre']] = true; 
			$dir = basename($data['file']); 
			$folders[$dir] = true; 
			$size += $data['size'];
			$time += $data['time'];
			$songs++; 
		} 

		$results['songs'] 	= $songs; 
		$results['albums']	= count($albums); 
		$results['artists']	= count($artists); 
		$results['genres']	= count($genres); 
		$results['folders']	= count($folders); 
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


	/*!
		@function add_file
		@discussion adds a single file
	*/
	function add_file($filename) { 

		$file_size = filesize($filename);
		$pattern = "/\.[" . conf('catalog_file_pattern') . "]$/i";	
		
		if ( preg_match($pattern ,$filename) && ($file_size > 0) && (!preg_match('/\.AppleDouble/', $filename))  ) {
			if(!$this->check_local_mp3($filename,$gather_type)) { 
				$this->insert_local_song($filename,$file_size);
			}
			debug_event('add_file', "Error: File exists",'2','ampache-catalog');
		} // if valid file

		debug_event('add_file', "Error: File doesn't match pattern",'2','ampache-catalog');


	} // add_file


	/**
	 * add_files
	 * Recurses throught $this->path and pulls out all mp3s and returns the full
	 * path in an array. Passes gather_type to determin if we need to check id3
	 * information against the db.
	 */
	public function add_files($path,$options) {

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
		}

		/* Recurse through this dir and create the files array */
		while ( false !== ( $file = readdir($handle) ) ) {

			/* Skip to next if we've got . or .. */
			if ($file == '.' || $file == '..') { continue; } 

			debug_event('read',"Starting work on $file inside $path",'5','ampache-catalog');
			
			/* Change the dir so is_dir works correctly */
			if (!chdir($path)) {
				debug_event('read',"Unable to chdir $path",'2','ampache-catalog'); 
				Error::add('catalog_add',_('Error: Unable to change to directory') . ' ' . $path); 
			}

			/* Create the new path */
			$full_file = $path.$slash_type.$file;
			
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
					
			/* see if this is a valid audio file or playlist file */
			if (preg_match($pattern ,$file)) {

				/* Now that we're sure its a file get filesize  */
				$file_size = @filesize($full_file);

				if (!$file_size) { 
					debug_event('read',"Unable to get filesize for $full_file",'2','ampache-catalog'); 
					Error::add('catalog_add',_('Error: Unable to get filesize for') . ' ' . $full_file); 
				} // file_size check

				if (!is_readable($full_file)) { 
					// not readable, warn user
		                        debug_event('read',"$full_file is not readable by ampache",'2','ampache-catalog'); 
					Error::add('catalog_add',"$full_file " . _('is not readable by ampache'));
					continue; 
				} 
		
				if (substr($file,-3,3) == 'm3u' AND $parse_m3u > 0) { 
					$this->_playlists[] = $full_file;
				} // if it's an m3u

				else {
					
					/* see if the current song is in the catalog */
					$found = $this->check_local_mp3($full_file);

					/* If not found then insert, gets id3 information
					 * and then inserts it into the database
					 */
					if (!$found) {
						$this->insert_local_song($full_file,$file_size);

						/* Stupid little cutesie thing */
						$this->count++;
						if ( !($this->count%10)) {
			                                $file = str_replace(array('(',')','\''),'',$full_file);
			                                echo "<script type=\"text/javascript\">";
			                                echo "update_txt('" . $this->count ."','add_count_" . $this->id . "');";
			                                echo "update_txt('" . htmlentities($file) . "','add_dir_" . $this->id . "');"; 
			                                echo "</script>\n";    	
						} // update our current state

					} // not found

				} // if it's not an m3u
						
			} //if it matches the pattern
			else {
				debug_event('read',"$full_file ignored, non audio file or 0 bytes",'5','ampache-catalog');
			} // else not an audio file

		} // end while reading directory 

		debug_event('closedir',"Finished reading $path closing handle",'5','ampache-catalog');

		/* Close the dir handle */
		@closedir($handle);

	} // add_files

	/*!
		@function get_albums
		@discussion This gets albums for all songs passed in an array
	*/
	function get_albums($songs=array()) { 

		foreach ($songs as $song_id) { 
			$sql = "SELECT album FROM song WHERE id='$song_id'";
			$db_results = mysql_query($sql, dbh());
			$results = mysql_fetch_array($db_results);
			$albums[] = new Album($results[0]);
		} // files

		return $albums;

	} // get_albums

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

		// Prevent the script from timing out
		set_time_limit(0);

		if (!$catalog_id) { $catalog_id = $this->id; }

		if (!empty($all)) { 	
			$albums = $this->get_album_ids(); 
		}
		else { 
			$albums = array_keys($this->_art_albums); 
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
				'album_name' 	=> $album->name,
				'artist' 	=> $album->artist_name,
				'keyword' 	=> $album->artist_name . ' ' . $album->name 
				); 

			// Return results
			$results = $album->find_art($options,1); 

			if (count($results)) { 
				// Pull the string representation from the source
				$image = get_image_from_source($results['0']);  
				$album->insert_art($image,$results['0']['mime']); 
				$art_found++; 
			} 

			/* Stupid little cutesie thing */
                        $search_count++;
                        if ( !($search_count%5)) {
				echo "<script type=\"text/javascript\">";
                                echo "update_txt('" . $search_count ."','count_art_" . $this->id . "');";
				echo "update_txt('" . $album->name . "','read_art_" . $this->id . "');"; 
                                echo "</script>\n"; 
	                        flush();
                        } //echos song count
			

			unset($found);

		} // foreach albums

	} // get_album_art

	/*!
		@function get_catalog_albums()
		@discussion Returns an array of the albums from a catalog
	*/
	function get_catalog_albums($catalog_id=0) { 

		$results = array();

		/* Use $this->id if nothing is passed */
		if (!$catalog_id) { $catalog_id = $this->id; }
		
		$sql = "SELECT DISTINCT(album.id) FROM album,song WHERE song.catalog='$catalog_id' AND song.album=album.id";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_object($db_results)) { 
			$results[] = new Album($r->id);
		}

		return $results;

	} // get_catalog_albums
	

	/*!
		@function get_catalog_files
		@discussion Returns an array of song objects from a catalog
		@param $catalog_id=0	Specify the catalog ID you want to get the files of
	*/
	function get_catalog_files($catalog_id=0) {

		$results = array();

		/* Use $this->id if nothing passed */
		if (!$catalog_id) { $catalog_id = $this->id; }

		$sql = "SELECT id FROM song WHERE catalog='$catalog_id' AND enabled='1'";
		$db_results = mysql_query($sql, dbh());

                $results = array(); // return an emty array instead of nothing if no objects
		while ($r = mysql_fetch_object($db_results)) {
			$results[] = new Song($r->id);
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

		$sql = "SELECT id FROM song WHERE enabled='0' $limit_clause";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) {
			$results[] = new Song($r['id']);
		}

		return $results;

	} // get_disabled

	/*!
		@function get_files
		@discussion  Get's an array of .mp3s and returns the filenames
		@param $path 	Get files starting at root $path
	*/
	function get_files($path) {

		/* Set it as an empty array */
		$files = array();

		/* Open up the directory */
		$handle = @opendir($path);

		if (!is_resource($handle)) { echo "<font class=\"error\">" . _("Error: Unable to open") . " $path</font><br />\n"; }

		/* Change dir so we can tell if it's a directory */
		if (!@chdir($path)) {
			echo "<font class=\"error\">Error: Unable to change to $path directory</font><br />\n";
		}

		// Determine the slash type and go from there
		if (strstr($path,"/")) { 
			$slash_type = '/';
		} 
		else { 
			$slash_type = '\\';
		} 

		/* Recurse through this dir and create the files array */
		while ( FALSE !== ($file = @readdir($handle)) ) {

			$full_file = $path . $slash_type . $file; 

			/* Incase this is the second time through, unset it before checking */
			unset($failed_check);

			if (conf('no_symlinks')) {
				if (is_link($full_file)) { $failed_check = true; }
			}

			/* It's a dir */
			if (is_dir($full_file) AND $file != "." AND $file != ".." AND !$failed_check) {
				/* Merge the results of the get_files with the current results */
				$files = array_merge($files,$this->get_files($full_file));
			} //isdir

			/* Get the file information */
			$file_info = filesize($full_file);

			$pattern = "/\.[" . conf('catalog_file_pattern') . "]$/i";

			// REMOVE SECOND PREG_MATCH
			if ( preg_match($pattern ,$file) && ($file_info > 0) && (!preg_match("/\.AppleDouble/", $file)) ) {
				$files[] = $full_file;
			} //is mp3 of at least some size

		} //end while

		/* Close the dir handle */
		@closedir($handle);

		/* Return the files array */
		return $files;

	} //get_files

	/*!
		@function dump_album_art (Added by Cucumber 20050216)
		@discussion This runs through all of the albums and trys to dump the
			art for them into the 'folder.jpg' file in the appropriate dir
	*/
	function dump_album_art($catalog_id=0,$methods=array()) {
	        if (!$catalog_id) { $catalog_id = $this->id; }

	        // Get all of the albums in this catalog
	        $albums = $this->get_catalog_albums($catalog_id);

		echo "Starting Dump Album Art...\n";

		// Run through them an get the art!
		foreach ($albums as $album) {
			// If no art, skip 
			if (!$album->has_art) { continue; } 

                	$image = $album->get_db_art();

			/* Get the first song in the album */
                        $songs = $album->get_songs(1);
                        $song = $songs[0];
                        $dir = dirname($song->file);
			$extension = substr($image->art_mime,strlen($image->art_mime)-3,3);

			// Try the preferred filename, if that fails use folder.???
	                $preferred_filename = conf('album_art_preferred_filename');
	                if (!$preferred_filename) { $preferred_filename = "folder.$extension"; }

	                $file = "$dir/$preferred_filename";
	                if ($file_handle = @fopen($file,"w")) {
		        	if (fwrite($file_handle, $image->art)) {
			        	$i++;
        	                        if ( !($i%conf('catalog_echo_count')) ) {
	                	        	echo "Written: $i. . .\n";
	                                        } //echos song count
						debug_event('art_write',"$album->name Art written to $file",'5'); 
	                                }
	                                        fclose($file_handle);
	                       	} // end if fopen
				else {
					debug_event('art_write',"Unable to open $file for writting",'5'); 
					echo "Error unable to open file for writting [$file]\n";
				}


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
		$db_results = Dba::query($sql);

	} // update_last_update

	/**
	 * update_last_add
	 * updates the last_add of the catalog
	 * @package Catalog
	 */
	function update_last_add() {

		$date = time();
		$sql = "UPDATE `catalog` SET `last_add`='$date' WHERE `id`='$this->id'";
		$db_results = Dba::query($sql);

	} // update_last_add

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
	 * new_catalog
	 * The Main array for making a new catalog calls many other child functions within this class
	 * @package Catalog
	 * @catagory Create
	 * @param $path Root path to start from for catalog
	 * @param $name Name of the new catalog
	 */
	function new_catalog($path,$name, $key=0, $ren=0, $sort=0, $type=0,$gather_art=0,$parse_m3u=0,$art=array()) {

		/* Record the time.. time the catalog gen */
		$start_time = time();

		/* Flush anything that has happened so they don't think it's locked */
		flush();

		/*
		 * Step one Add this to the catalog table if it's not
		 * already there returns the new catalog_id
		 */
		$catalog_id = $this->check_catalog($path);

		if (!$catalog_id) {
			$catalog_id = $this->create_catalog_entry($path,$name,$key, $ren, $sort, $type);
		}

		// Make sure they don't have a trailing / or \ on their path
		$path = rtrim($path,"/"); 
		$path = rtrim($path,"\\"); 

		/* Setup the $this with the new information */
		$this->id 		= $catalog_id;
		$this->path 		= $path;
		$this->name 		= $name;
		$this->key		= $key;
		$this->rename_pattern 	= ($ren)?$ren:'';
		$this->sort_pattern 	= ($sort)?$sort:'';
		$this->catalog_type 	= $type;

		/* Fluf */
		echo _('Starting Catalog Build') . " [$name]<br />\n";


	       if ($this->catalog_type == 'remote') {
                        echo _("Running Remote Sync") . ". . .<br /><br />";
                        $this->get_remote_catalog($type=0);
                        return true;
                }
		
		echo _('Found') . ": <span id=\"count_add_" . $this->id . "\">" . _('None') . "</span><br />\n";
		flush();
	
                // Make sure the path doesn't end in a / or \
                $this->path = rtrim($this->path,'/');
                $this->path = rtrim($this->path,'\\');
	
		/* Get the songs and then insert them into the db */
		$this->add_files($this->path,$type,$parse_m3u);

                echo "<script type=\"text/javascript\">";
                echo "update_txt('" . $this->count . "','count_add_" . $this->id ."');";
                echo "</script>\n";
                flush();


		foreach ($this->_playlists as $full_file) { 
	                if ($this->import_m3u($full_file)) {
				$file = basename($full_file);
	                        echo "&nbsp;&nbsp;&nbsp;" . _("Added Playlist From") . " $file . . . .<br />\n";
		                flush();
			} // end if import worked
                } // end foreach playlist files

		/* Now Adding Album Art? */
		if ($gather_art) { 
                        echo "<br />\n<b>" . _('Starting Album Art Search') . ". . .</b><br />\n";
			echo _('Searched') . ": <span id=\"count_art_" . $this->id . "\">" . _('None') . "</span>";
                        flush();
                        $this->get_album_art();
		} // if we want to gather album art

		/* Do a little stats mojo here */
		$current_time = time();

		$time_diff = $current_time - $start_time;
		if ($time_diff) { $song_per_sec = intval($this->count/$time_diff); }
		echo _("Catalog Finished") . ". . . " . _("Total Time") . " [" . date("i:s",$time_diff) . "] " . _("Total Songs") . " [" . $this->count . "] " . 
			_("Songs Per Seconds") . " [" . $song_per_sec . "]<br />\n";

		return $catalog_id;

	} //new_catalog

	/**
	 * update_single_item
	 * updates a single album,artist,song from the tag data
	 * this can be done by 75+
	 */
	public static function update_single_item($type,$id) { 

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
				$songs[0] = new Song($id);
				break;
		} // end switch type

		foreach($songs as $song_id) { 
			$song = new Song($song_id); 
			$info = self::update_song_from_tags($song);

                        if ($info['change']) {
				$file = scrub_out($song->file);
                                echo "<dl>\n\t<dd>";
                                echo "<b>$file " . _('Updated') . "</b>\n";
                                echo $info['text'];
                                echo "\t</dd>\n</dl><hr align=\"left\" width=\"50%\" />";
                        	flush();
	                } // if change
			else {
				echo"<dl>\n\t<dd>";
				echo "<b>$song->file</b><br />" . _('No Update Needed') . "\n";
				echo "\t</dd>\n</dl><hr align=\"left\" width=\"50%\" />";
				flush();
			}
		} // foreach songs

	} // update_single_item

        /**
         * update_song_from_tags
         * updates the song info based on tags, this is called from a bunch of different places
	 * and passes in a full fledged song object, so it's a static function
	 */
        public static function update_song_from_tags($song,$sort_pattern,$rename_pattern) {

		/* Record the reading of these tags */
		debug_event('tag-read',"Reading Tags from $song->file",'5','ampache-catalog');
		
		$vainfo = new vainfo($song->file,'',$sort_pattern,$rename_pattern);
		$vainfo->get_info();

                /* Find the correct key */
                $key = get_tag_type($vainfo->tags);

                /* Clean up the tags */
                $results = clean_tag_info($vainfo->tags,$key,$song->file);

                /* Setup the vars */
		$new_song 		= new Song();
                $new_song->file         = $results['file'];
                $new_song->title        = $results['title'];
                $new_song->year         = $results['year'];
                $new_song->comment      = $results['comment'];
                $new_song->bitrate      = $results['bitrate'];
                $new_song->rate         = $results['rate'];
                $new_song->mode         = $results['mode'];
                $new_song->size         = $results['size'];
                $new_song->time         = $results['time'];
                $new_song->track        = $results['track'];
                $artist                 = $results['artist'];
                $album                  = $results['album'];
                $genre                  = $results['genre'];

		/* Clean up Old Vars */
		unset($vainfo,$key);

                /*
                * We have the artist/genre/album name need to check it in the tables
                * If found then add & return id, else return id
                */
                $new_song->artist       = self::check_artist($artist);
                $new_song->f_artist     = $artist;
                $new_song->genre        = self::check_genre($genre);
                $new_song->f_genre      = $new_song->get_genre_name();
                $new_song->album        = self::check_album($album,$new_song->year);
                $new_song->f_album      = $album . " - " . $new_song->year;
                $new_song->title        = self::check_title($new_song->title,$new_song->file);

		/* Since we're doing a full compare make sure we fill the extended information */
		$song->fill_ext_info();

                $info = Song::compare_song_information($song,$new_song);

                if ($info['change']) {
			debug_event('update',"$song->file difference found, updating database",'5','ampache-catalog'); 
                        $song->update_song($song->id,$new_song);
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
			echo _('Running Remote Update') . ". . .<br />";
			$this->get_remote_catalog($type=0);
			return true;
		} 
	
		require Config::get('prefix') . '/templates/show_adds_catalog.inc.php'; 
		flush();

		/* Set the Start time */
		$start_time = time();

		// Make sure the path doesn't end in a / or \
		$this->path = rtrim($this->path,'/'); 
		$this->path = rtrim($this->path,'\\');

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
		$this->get_album_art(0,1); 

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
	 * get a remote catalog and runs update if needed
	 * @package XMLRPC
	 * @catagory Client
	 * @author Karl Vollmer
	 * @todo Add support for something besides port 80
	 * @todo Add a Pub/Private Key swap in here for extra security
	 */
	function get_remote_catalog($type=0) { 

		/* Make sure the xmlrpc lib is loaded */
		if (!class_exists('xmlrpc_client')) { 
                        debug_event('xmlrpc',"Unable to load XMLRPC library",'1'); 
			echo "<span class=\"error\"><b>" . _("Error") . "</b>: " . _('Unable to load XMLRPC library, make sure XML-RPC is enabled') . "</span><br />\n";
			return false;
		} // end check for class

	        // first, glean out the information from the path about the server and remote path
		// this can't contain the http
	        preg_match("/http:\/\/([^\/]+)\/*(.*)/", $this->path, $match);
	        $server = $match[1];
	        $path   = $match[2];
	
	        if ( ! $path ) {
	                $client = new xmlrpc_client("/server/xmlrpc.server.php", $server, 80);
	        }
	        else {
	                $client = new xmlrpc_client("/$path/server/xmlrpc.server.php", $server, 80);
	        }
	        
		/* encode the variables we need to send over */
		$encoded_key	= new xmlrpcval($this->key,"string");
		$encoded_path	= new xmlrpcval(conf('web_path'),"string");
		
		$f = new xmlrpcmsg('remote_catalog_query', array($encoded_key,$encoded_path));
		
	        if (conf('debug')) { $client->setDebug(1); }
		
	        $response = $client->send($f,30);
	        $value = $response->value();

	        if ( !$response->faultCode() ) {
	                $data = php_xmlrpc_decode($value);
			
			// Print out the catalogs we are going to sync
	                foreach ($data as $vars) { 
				$catalog_name 	= $vars[0];
				$count		= $vars[1];
	                        print("<b>Reading Remote Catalog: $catalog_name ($count Songs)</b> [$this->path]<br />\n");
				$total += $count;
	                } 
			// Flush the output
			flush();

	        } // if we didn't get an error
	        else {
			$error_msg = _("Error connecting to") . " " . $server . " " . _("Code") . ": " . $response->faultCode() . " " . _("Reason") . ": " . $response->faultString();
			debug_event('xmlrpc-client',$error_msg,'1','ampache-catalog');
			echo "<p class=\"error\">$error_msg</p>";
	                return;
	        }

		// Hardcoded for now
		$step = '500';
		$current = '0';

		while ($total > $current) { 
			$start 	= $current;
			$current += $step;
			$this->get_remote_song($client,$start,$step);
		}

	        echo "<p>" . _('Completed updating remote catalog(s)') . ".</p><hr />\n";
		flush();

		return true;

	} // get_remote_catalog

	/** 
	 * get_remote_song
	 * This functions takes a start and end point for gathering songs from a remote server. It is broken up
	 * in attempt to get around the problem of very large target catalogs
	 * @package XMLRPC
	 * @catagory Client
	 * @todo Allow specificion of single catalog
	 */
	function get_remote_song($client,$start,$end) { 

		$encoded_start 	= new xmlrpcval($start,"int");
		$encoded_end	= new xmlrpcval($end,"int");
		$encoded_key	= new xmlrpcval($this->key,"string");

		$query_array = array($encoded_key,$encoded_start,$encoded_end); 

                $f = new xmlrpcmsg('remote_song_query',$query_array);
                /* Depending upon the size of the target catalog this can be a very slow/long process */
                set_time_limit(0);
                        
		// Sixty Second time out per chunk
                $response = $client->send($f,60);
                $value = $response->value();

                if ( !$response->faultCode() ) {
                        $data = php_xmlrpc_decode($value);
                        $this->update_remote_catalog($data,$this->path);
			$total = $start + $end;
			echo _('Added') . " $total...<br />";
			flush();
                }
                else {
                        $error_msg = _('Error connecting to') . " " . $server . " " . _("Code") . ": " . $response->faultCode() . " " . _("Reason") . ": " . $response->faultString();
                        debug_event('xmlrpc-client',$error_msg,'1','ampache-catalog');
                        echo "<p class=\"error\">$error_msg</p>";
                }

		return;

	} // get_remote_song


	/**
	 * update_remote_catalog
	 * actually updates from the remote data, takes an array of songs that are base64 encoded and parses them
	 * @package XMLRPC
	 * @catagory Client
	 * @todo This should be based off of seralize
	 * @todo some kind of cleanup of dead songs? 
	 */
	function update_remote_catalog($songs,$root_path) {

		/* 
		   We need to check the incomming songs
		   to see which ones need to be added
		*/
		foreach ($songs as $song) {
	
			// Prevent a timeout
                        set_time_limit(0);
			
	                $song = base64_decode($song);

	                $data = explode("::", $song);

			$new_song->artist 	= $this->check_artist($data[0]);
			$new_song->album	= $this->check_album($data[1],$data[4]);
			$new_song->title	= $data[2];
			$new_song->year		= $data[4];
			$new_song->bitrate	= $data[5];
			$new_song->rate		= $data[6];
			$new_song->mode		= $data[7];
			$new_song->size		= $data[8];
			$new_song->time		= $data[9];
			$new_song->track	= $data[10];
			$new_song->genre	= $this->check_genre($data[11]);
			$new_song->file		= $root_path . "/play/index.php?song=" . $data[12];
			$new_song->catalog	= $this->id;
	     
			if (!$this->check_remote_song($new_song->file)) { 
				$this->insert_remote_song($new_song);
			} 

		} // foreach new Songs

	        // now delete invalid entries
		self::clean($this->id); 

	} // update_remote_catalog


	/**
	 * clean_catalog
	 * Cleans the Catalog of files that no longer exist grabs from $this->id or $id passed 
  	 * Doesn't actually delete anything, disables errored files, and returns them in an array
	 */
	public function clean_catalog() {

		/* Define the Arrays we will need */
		$dead_files = array();

		require_once Config::get('prefix') . '/templates/show_clean_catalog.inc.php';
		flush(); 

		/* Get all songs in this catalog */
		$sql = "SELECT `id`,`file` FROM `song` WHERE `catalog`='$this->id' AND enabled='1'";
		$db_results = Dba::query($sql);

		/* Recurse through files, put @ to prevent errors poping up */
		while ($results = Dba::fetch_assoc($db_results)) {

                        /* Stupid little cutesie thing */
                        $count++;
                        if (!($count%10)) {
				$file = str_replace(array('(',')','\''),'',$results['file']);
			        echo "<script type=\"text/javascript\">";
			        echo "update_txt('" . $count ."','clean_count_" . $this->id . "');";
				echo "update_txt('" . htmlentities($file) . "','clean_dir_" . $this->id . "');"; 
			        echo "</script>\n";	
	                        flush();
                        } //echos song count

			/* Also check the file information */
			$file_info = filesize($results['file']);

			/* If it errors somethings splated, or the files empty */
			if (!file_exists($results['file']) OR $file_info < 1) {
			
				/* Add Error */
				Error::add('general',"Error File Not Found or 0 Bytes: " . $results['file']); 

				/* Add this file to the list for removal from the db */
				$dead_files[] = $results['id'];
			} //if error

		} //while gettings songs
	
		/* Unfortuantly it has to be done this way 
		 * you can't delete a row at the same time you're 
		 * doing a select on said table 
		 */
		if (count($dead_files)) {
			foreach ($dead_files as $data) {

				$sql = "DELETE FROM `song` WHERE `id`='$data'";
				$db_results = Dba::query($sql);

			} //end foreach

		} // end if dead files

		/* Step two find orphaned Arists/Albums
		 * This finds artists and albums that no
		 * longer have any songs associated with them
		 */
		self::clean($catalog_id); 
		
		/* Return dead files, so they can be listed */
                echo "<script type=\"text/javascript\">";
                echo "update_txt('" . $count ."','clean_count_" . $this->id . "');";
                echo "</script>\n";
		show_box_top(); 
		echo "<b>" . _('Catalog Clean Done') . " [" . count($dead_files) . "] " . _('files removed') . "</b><br />\n";
		show_box_bottom(); 
		flush();

	} //clean_catalog

	/**
	 * clean_single_song
	 * This function takes the elements of a single song object
	 * And checks to see if those specific elements are now orphaned
	 * this is often used in flagging, and is a faster way then calling
	 * the normal clean functions. The assumption is made that this is
	 * an old song object whoes information has already been updated in the 
	 * database
	 */
	function clean_single_song($song) { 

		$results = array();

		/* A'right let's check genre first */
		$sql = "SELECT song.genre FROM song WHERE genre='" . $song->genre . "'";
		$db_results = mysql_query($sql, dbh());

		if (!mysql_num_rows($db_results)) { 
			$sql = "DELETE FROM genre WHERE id='" . $song->genre . "'";
			$db_results = mysql_query($sql, dbh());
			$results['genre'] = true;
		}

		/* Now for the artist */
		$sql = "SELECT song.artist FROM song WHERE artist='" . $song->artist . "'";
		$db_results = mysql_query($sql, dbh());

		if (!mysql_num_rows($db_results)) { 
			$sql = "DELETE FROM artist WHERE id='" . $song->artist . "'";
			$db_results = mysql_query($sql, dbh());
			$results['artist'] = true;
		}

		/* Now for the album */
		$sql = "SELECT song.album FROM song WHERE album='" . $song->album . "'";
		$db_results = mysql_query($sql, dbh());

		if (!mysql_num_rows($db_results)) { 
			$sql = "DELETE FROM album WHERE id='" . $song->album . "'";
			$db_results = mysql_query($sql, dbh());
			$results['album'] = true;
		}

		return $results;

	} // clean_single_song

	/**
	 * clean_genres
	 * This functions cleans up unused genres
	 */
	public static function clean_genres() { 

                /* Do a complex delete to get albums where there are no songs */
                $sql = "DELETE FROM genre USING genre LEFT JOIN song ON song.genre = genre.id WHERE song.id IS NULL";
                $db_results = Dba::query($sql);

	} // clean_genres

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
	public static function clean_playlists($catalog_id) { 

		/* Do a complex delete to get playlist songs where there are no songs */
		$sql = "DELETE FROM playlist_data USING playlist_data LEFT JOIN song ON song.id = playlist_data.song WHERE song.file IS NULL";
		$db_results = Dba::query($sql);

		// Clear TMP Playlist information as well
		$sql = "DELETE FROM tmp_playlist_data USING tmp_playlist_data LEFT JOIN song ON tmp_playlist_data.object_id = song.id WHERE song.id IS NULL"; 
		$db_results = Dba::query($sql); 

	} // clean_playlists

	/**
	 * clean_ext_info
	 * This function clears any ext_info that no longer has a parent
	 */
	public static function clean_ext_info($catalog_id) { 

		$sql = "DELETE FROM song_ext_data USING song_ext_data LEFT JOIN song ON song.id = song_ext_data.song_id WHERE song.id IS NULL"; 
		$db_results = Dba::query($sql); 

	} // clean_ext_info

	/**
	 * clean_stats
	 * This functions removes stats for songs/albums that no longer exist
	 */
	public static function clean_stats($catalog_id) {

		// Crazy SQL Mojo to remove stats where there are no songs 
		$sql = "DELETE FROM object_count USING object_count LEFT JOIN song ON song.id=object_count.object_id WHERE object_type='song' AND song.id IS NULL";
		$db_results = Dba::query($sql);
		
		// Crazy SQL Mojo to remove stats where there are no albums 
		$sql = "DELETE FROM object_count USING object_count LEFT JOIN album ON album.id=object_count.object_id WHERE object_type='album' AND album.id IS NULL";
		$db_results = Dba::query($sql);
		
		// Crazy SQL Mojo to remove stats where ther are no artists 
		$sql = "DELETE FROM object_count USING object_count LEFT JOIN artist ON artist.id=object_count.object_id WHERE object_type='artist' AND artist.id IS NULL";
		$db_results = Dba::query($sql);

		// Delete genre stat information 
		$sql = "DELETE FROM object_count USING object_count LEFT JOIN genre ON genre.id=object_count.object_id WHERE object_type='genre' AND genre.id IS NULL";
		$db_results = Dba::query($sql); 

		// Delete the live_stream stat information
		$sql = "DELETE FROM object_count USING object_count LEFT JOIN live_stream ON live_stream.id=object_count.object_id WHERE object_type='live_stream' AND live_stream.id IS NULL";
		$db_results = Dba::query($sql); 

		// Delete Song Ratings information
		$sql = "DELETE FROM ratings USING ratings LEFT JOIN song ON song.id=ratings.object_id WHERE object_type='song' AND song.id IS NULL"; 
		$db_results = Dba::query($sql); 

		// Delete Genre Ratings Information
		$sql = "DELETE FROM ratings USING ratings LEFT JOIN genre ON genre.id=ratings.object_id WHERE object_type='genre' AND genre.id IS NULL"; 
		$db_results = Dba::query($sql); 

		// Delete Album Rating Information
		$sql = "DELETE FROM ratings USING ratings LEFT JOIN album ON album.id=ratings.object_id WHERE object_type='album' AND album.id IS NULL"; 
		$db_results = Dba::query($sql); 

		// Delete Artist Rating Information
		$sql = "DELETE FROM ratings USING ratings LEFT JOIN artist ON artist.id=ratings.object_id WHERE object_type='artist' AND artist.id IS NULL"; 
		$db_results = Dba::query($sql); 

	} // clean_stats
	
	/**
	 * verify_catalog
	 * This function compares the DB's information with the ID3 tags
	 */
	public function verify_catalog($catalog_id) {

		// Create the object so we have some information on it
		$catalog = new Catalog($catalog_id); 

		/* First get the filenames for the catalog */
		$sql = "SELECT `id` FROM `song` WHERE `catalog`='$catalog_id'";
		$db_results = Dba::query($sql);
		$number = Dba::num_rows($db_results);
	
		require_once Config::get('prefix') . '/templates/show_verify_catalog.inc.php';
		flush();

		/* Magical Fix so we don't run out of time */
		set_time_limit(0);

		/* Recurse through this catalogs files
		 * and get the id3 tage information,
		 * if it's not blank, and different in
		 * in the file then update!
		 */
		while ($results = Dba::fetch_assoc($db_results)) {

			/* Create the object from the existing database information */
			$song = new Song($results['id']);

			debug_event('verify',"Starting work on $song->file",'5','ampache-catalog');
			
			if (is_readable($song->file)) {
				unset($skip);

				/* Make sure the song isn't flagged, we don't update flagged stuff */
				if ($song->has_flag()) { 
					$skip = true; 
				} 
		
				// if the file hasn't been modified since the last_update
				if (!$skip) {

					$info = self::update_song_from_tags($song,$this->sort_pattern,$this->rename_pattern);
					$album_id = $song->album;
					if ($info['change']) {
						$update_string .= $info['text'] . "<br />\n"; 

						$album = new Album($song->album);
						if (!$album->has_art) { 
							$found = $album->find_art($options,1);
							if (count($found)) {
								$image = get_image_from_source($found['0']); 
								$album->insert_art($image,$found['mime']); 
								$is_found = _(' FOUND');
							} 
							$update_string .= "<b>" . _('Searching for new Album Art') . ". . .$is_found</b><br />\n";
							unset($found,$is_found);
						} 
						flush();
						$total_updated++;
					}

					unset($info);

				} // end skip

				if ($skip) { 
					debug_event('skip',"$song->file has been skipped due to newer local update or file mod time",'5','ampache-catalog'); 
				}
	
                                /* Stupid little cutesie thing */
                                $count++;
                                if (!($count%10) ) {
					$file = str_replace(array('(',')','\''),'',$song->file); 
                                        echo "<script type=\"text/javascript\">";
                                        echo "update_txt('" . $count . "','verify_count_" . $catalog_id . "');";
					echo "update_txt('" . scrub_out($file) . "','verify_dir_" . $catalog_id . "');"; 
                                        echo "</script>\n";
                                        flush();
                                } //echos song count
				
			} // end if file exists

			else {
				Error::add('general',"$song->file does not exist or is not readable"); 
				debug_event('read-error',"$song->file does not exist or is not readable",'5','ampache-catalog'); 
			}

		} //end foreach

		/* After we have updated all the songs with the new information clear any empty albums/artists */
		self::clean($catalog_id); 

		// Update the last_update
		$this->update_last_update();

                // One final time!
		echo "<script type=\"text/javascript\">";
                echo "update_txt('" . $this->count . "','count_verify_" . $this->id . "');";
                echo "</script>\n";
                flush();

		show_box_top(); 
		echo _('Update Finished.') . _('Checked') . " $count. $total_updated " . _('songs updated.') . "<br /><br />";
		show_box_bottom(); 

		return true;

	} // verify_catalog

	/**
	 * clean
	 * This is a wrapper function for all of the different cleaning
	 * functions, it runs them in the correct order and takes a catalog_id
	 */
	public static function clean($catalog_id) { 

		self::clean_albums($catalog_id); 
		self::clean_artists($catalog_id); 
		self::clean_genres($catalog_id); 
		self::clean_flagged($catalog_id); 
		self::clean_stats($catalog_id); 
		self::clean_ext_info($catalog_id); 
		self::clean_playlists($catalog_id); 

	} // clean

	/**
	 * check_artist
	 * $artist checks if there then return id else insert and return id
	 */
	public function check_artist($artist) {

		// Only get the var ones.. less func calls
		$cache_limit = Config::get('artist_cache_limit');

		/* Clean up the artist */
		$artist = trim($artist);
		$artist = Dba::escape($artist);


		/* Ohh no the artist has lost it's mojo! */
		if (!$artist) {
			$artist = _('Unknown (Orphaned)');
		}

		// Remove the prefix so we can sort it correctly
		preg_match("/^(The\s|An\s|A\s|Die\s|Das\s|Ein\s|Eine\s)(.*)/i",$artist,$matches);

		if (count($matches)) {
			$artist = $matches[2];
			$prefix = $matches[1];
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
		else {

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

		if ($cache_limit) {

			$artist_count = count($this->artists);
			if ($artist_count == $cache_limit) {
				self::$artists = array_slice(self::$artists,3);
			}
			debug_event('cache',"Adding $artist with $artist_id to Cache",'5','ampache-catalog'); 
			$array = array($artist => $artist_id);
			self::$artists = array_merge(self::$artists, $array);
			unset($array);

		} // if cache limit is on..

		return $artist_id;

	} // check_artist

	/**
	 * check_album
	 * Takes $album and checks if there then return id else insert and return id 
	 */
	public function check_album($album,$album_year=0) {

		/* Clean up the album name */
		$album = trim($album);
		$album = Dba::escape($album);
		$album_year = intval($album_year);

		// Set it once to reduce function calls
		$cache_limit = Config::get('album_cache_limit');

		/* Ohh no the album has lost it's mojo */
		if (!$album) {
			$album = _('Unknown (Orphaned)');
			unset($album_year); 
		}

		// Remove the prefix so we can sort it correctly
		preg_match("/^(The\s|An\s|A\s|Die\s|Das\s|Ein\s|Eine\s)(.*)/i",$album,$matches);

		if (count($matches)) {
			$album = $matches[2];
			$prefix = $matches[1];
		}

		// Check to see if we've seen this album before
		if (isset(self::$albums[$album])) {
			return self::$albums[$album];
		}

		/* Setup the Query */
		$sql = "SELECT `id` FROM `album` WHERE `name` = '$album'";
		if ($album_year) { $sql .= " AND `year`='$album_year'"; }
		$db_results = Dba::query($sql);

		/* If it's found */
		if ($r = Dba::fetch_assoc($db_results)) {
			$album_id = $r['id'];

			// If we don't have art put it in the 'needs me some art' array
			if (!strlen($r['art'])) { 
				$key = $r['id']; 
				self::$_art_albums[$key] = 1;
			}
		
		} //if found

		/* If not found create */
		else {
                        $prefix_txt = 'NULL';

                        if ($prefix) {
                                $prefix_txt = "'$prefix'";
                        }

			$sql = "INSERT INTO `album` (`name`, `prefix`,`year`) VALUES ('$album',$prefix_txt,'$album_year')";
			$db_results = Dba::query($sql);
			$album_id = Dba::insert_id();

			if (!$db_results) {
				debug_event('album',"Error Unable to insert Album:$album",'2'); 
			}

			// Add it to the I needs me some album art array
			self::$_art_albums[$album_id] = 1; 

		} //not found

                if ($cache_limit > 0) {

                        $albums_count = count(self::$albums);

                        if ($albums_count == $cache_limit) {
        	                self::$albums = array_slice(self::$albums,3);
                        }
			$array = array($album => $album_id);
			self::$albums = array_merge(self::$albums,$array);	               
			unset($array);

                } // if cache limit is on..

		return $album_id;

	} // check_album

	/**
	 * check_genre
	 * Finds the Genre_id from the text name
	 */
	public function check_genre($genre) {
	
		/* If a genre isn't specified force one */
		if (strlen(trim($genre)) < 1) {
			$genre = _('Unknown (Orphaned)');
		}

		if (self::$genres[$genre]) {
			return self::$genres[$genre];
		}

		/* Look in the genre table */
		$genre = Dba::escape($genre);
		$sql = "SELECT `id` FROM `genre` WHERE `name` = '$genre'";	
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);

		if (!$results['id']) { 
			$sql = "INSERT INTO `genre` (`name`) VALUES ('$genre')";
			$db_results = Dba::query($sql);
			$insert_id = Dba::insert_id();
		}
		else { $insert_id = $results['id']; }

		self::$genres[$genre] = $insert_id;

		return $insert_id;

	} // check_genre
	
	/**
	 * check_title
	 * this checks to make sure something is
	 * set on the title, if it isn't it looks at the
	 * filename and trys to set the title based on that
	 */
	public function check_title($title,$file=0) {

		if (strlen(trim($title)) < 1) {
			preg_match("/.+\/(.*)\.....?$/",$file,$matches);
			$title = Dba::escape($matches[1]);
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
		$vainfo		= new vainfo($file,'',$this->sort_pattern,$this->rename_pattern);
		$vainfo->get_info();

		$key = get_tag_type($vainfo->tags);

		/* Clean Up the tags */
		$results = clean_tag_info($vainfo->tags,$key,$file);
	
		/* Set the vars here... so we don't have to do the '" . $blah['asd'] . "' */
		$title 		= Dba::escape($results['title']);
		$artist 	= $results['artist'];
		$album 		= $results['album'];
		$genre 		= $results['genre'];
		$bitrate 	= $results['bitrate'];
		$rate	 	= $results['rate'];
		$mode 		= $results['mode'];
		$size	 	= $results['size'];
		$song_time 	= $results['time'];
		$track	 	= $results['track'];
		$year		= $results['year'];
		$comment	= $results['comment'];
		$current_time 	= time();
		$lyrics 	= ' ';

		/*
		 * We have the artist/genre/album name need to check it in the tables
		 * If found then add & return id, else return id
		 */
		$artist_id	= $this->check_artist($artist);
		$genre_id	= $this->check_genre($genre);
		$album_id	= $this->check_album($album,$year);
		$title		= $this->check_title($title,$file);
		$add_file	= Dba::escape($file);

		$sql = "INSERT INTO `song` (file,catalog,album,artist,title,bitrate,rate,mode,size,time,track,genre,addition_time,year)" .
			" VALUES ('$add_file','$this->id','$album_id','$artist_id','$title','$bitrate','$rate','$mode','$size','$song_time','$track','$genre_id','$current_time','$year')";
		$db_results = Dba::query($sql);

		if (!$db_results) { 
			debug_event('insert',"Unable to insert $file -- $sql",'5','ampache-catalog'); 
			Error::add('catalog_add','Error Adding ' . $file . ' SQL:' . $sql); 
		} 
			
		$song_id = Dba::insert_id(); 

		/* Add the EXT information */
		$sql = "INSERT INTO `song_data` (`song_id`,`comment`,`lyrics`) " . 
			" VALUES ('$song_id','$comment','$lyrics')"; 
		$db_results = Dba::query($sql); 

		if (!$db_results) {
			debug_event('insert',"Unable to insert EXT Info for $file -- $sql",'5','ampache-catalog'); 
		}

	} // insert_local_song

	/*!
		@function insert_remote_song
		@discussion takes the information gotten from XML-RPC and 
			inserts it into the local database. The filename
			ends up being the url.
	*/
	function insert_remote_song($song) {

		$url 		= sql_escape($song->file);
		$title		= $this->check_title($song->title);
		$title		= sql_escape($title);
		$current_time	= time();	
		
		$sql = "INSERT INTO song (file,catalog,album,artist,title,bitrate,rate,mode,size,time,track,genre,addition_time,year)" .
			" VALUES ('$url','$song->catalog','$song->album','$song->artist','$title','$song->bitrate','$song->rate','$song->mode','$song->size','$song->time','$song->track','$song->genre','$current_time','$song->year')";
		$db_results = mysql_query($sql, dbh());

		if (!$db_results) { 
                        debug_event('insert',"Unable to Add Remote $url -- $sql",'5','ampache-catalog');
			echo "<span style=\"color: #FOO;\">Error Adding Remote $url </span><br />$sql<br />\n";
			flush();
		}

	} // insert_remote_song

	/*!
		@function check_remote_song
		@discussion checks to see if a remote song exists in the database or not
			if it find a song it returns the UID
	*/
	function check_remote_song($url) { 

		$url = sql_escape($url);

		$sql = "SELECT id FROM song WHERE file='$url'";
		
		$db_results = mysql_query($sql, dbh());

		if (mysql_num_rows($db_results)) { 
			return true;
		}
		
		return false;

	} // check_remote_song


	/**
	 * check_local_mp3
	 * Checks the song to see if it's there already returns true if found, false if not 
	 */
	function check_local_mp3($full_file, $gather_type='') {

		if ($gather_type == 'fast_add') {
			$file_date = filemtime($full_file);
			if ($file_date < $this->last_add) {
				return true;
			}
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

	/*!
		@function import_m3u
		@discussion this takes m3u filename and then attempts
			to create a Public Playlist based on the filenames
			listed in the m3u
	*/
	function import_m3u($filename) { 

		$m3u_handle = @fopen($filename,'r');
		
		$data = @fread($m3u_handle,filesize($filename));
		
		$results = explode("\n",$data);

                $pattern = "/\.(" . conf('catalog_file_pattern');
                $pattern .= ")$/i";

		foreach ($results as $value) {
			// Remove extra whitespace
			$value = trim($value);
			if (preg_match($pattern,$value)) { 
				/* Translate from \ to / so basename works */
				$value = str_replace("\\","/",$value);
				$file = basename($value);
				/* Search for this filename, cause it's a audio file */
				$sql = "SELECT id FROM song WHERE file LIKE '%" . sql_escape($file) . "'";
				$db_results = mysql_query($sql, dbh());
				$results = mysql_fetch_assoc($db_results);
				$song_id = $results['id'];
				if ($song_id) { $songs[] = $song_id; }
			} // if it's a file

		} // end foreach line

		debug_event('m3u_parse',"Parsing $filename - Found: " . count($songs) . " Songs",'5'); 
		if (count($songs)) { 
			$playlist = new Playlist();
			$value = str_replace("\\","/",$filename);
			$playlist_name = "M3U - " . basename($filename,'.m3u');
			
			$playlist_id = $playlist->create($playlist_name,'public');

			/* Recreate the Playlist */
			$playlist = new Playlist($playlist_id);
			$playlist->add_songs($songs);
			return true;
		}

		return false;

	} // import_m3u

        /*!
                @function merge_stats
                @discussion merge stats entries
                @param $type the object_type row in object_count to use
                @param $oldid the old object_id
                @param $newid the new object_id to merge to
                @return the number of stats changed
                @todo move this to the right file
        */
        function merge_stats ($type,$oldid,$newid) {

                //check data
                $accepted_types = array ("artist");
                if (!in_array($type,$accepted_types)) { return false; } 

                //now retrieve all of type and oldid
                $stats_qstring = "SELECT id,count,userid," . 
			"(SELECT id FROM object_count WHERE object_type = '$type' AND object_id = '$newid' AND userid=o.userid) AS existingid " .
			"FROM object_count AS o WHERE object_type = '$type' AND object_id = '$oldid'";

                $stats_query = mysql_query($stats_qstring,dbh());
                $oldstats = array();
                //now collect needed data into a array
                while ($stats_result = mysql_fetch_assoc($stats_query)) {
                        $userid = $stats_result['userid'];
                        $oldstats[$userid]['id'] = $stats_result['id'];
                        $oldstats[$userid]['count'] = $stats_result['count'];
                        $oldstats[$userid]['existingid'] = $stats_result['existingid'];
                }
                //now foreach that array, changeing/updateing object_count and if needed deleting old row
                $num_changed = 0;
                foreach ($oldstats as $userid => $stats) {
                        //first check if it is a update or insert
                        if (is_numeric($stats['existingid'])) {
			
                                $stats_count_change_qstring = "UPDATE object_count SET count = count + '" . $stats['count'] . "' WHERE id = '" . $stats['existingid'] . "'";
                                mysql_query($stats_count_change_qstring,dbh());
				
                                //then, delete old row
                                $old_stats_delete_qstring = "DELETE FROM object_count WHERE id ='" . $stats['id'] . "'";
                                mysql_query($old_stats_delete_qstring,dbh());
				
                                $num_changed++;
                        } else {
                                //hasn't yet listened, just change object_id
                                $stats_artist_change_qstring = "UPDATE object_count SET object_id = '$newid' WHERE id ='" . $stats['id'] . "'";
                                mysql_query($stats_artist_change_qstring,dbh());
                                //done!
                                $num_changed++;
                        }
                }
                return $num_changed;

        } // merge_stats

	/**
	 * delete
	 * Deletes the catalog and everything assoicated with it
	 * it takes the catalog id
	 */
	public static function delete($catalog_id) {

		$catalog_id = Dba::escape($catalog_id); 

		// First remove the songs in this catalog
		$sql = "DELETE FROM `song` WHERE `catalog` = '$catalog_id'";
		$db_results = Dba::query($sql);

		// Next Remove the Catalog Entry it's self
		$sql = "DELETE FROM `catalog` WHERE `id` = '$catalog_id'";
		$db_results = Dba::query($sql);

		// Run the Aritst/Album Cleaners...
		self::clean($catalog_id); 

	} // delete

	/*!
		@function remove_songs
		@discussion removes all songs sent in $songs array from the
			database, it doesn't actually delete them...
	*/
	function remove_songs($songs) {

		foreach($songs as $song) {
			$sql = "DELETE FROM song WHERE id = '$song'";
			$db_results = mysql_query($sql, dbh());
		}

	} // remove_songs

	/*!
		@function exports the catalog
		@discussion it exports all songs in the database to the given export type.
	*/
	function export($type){
	
	    if ($type=="itunes"){
		
		$sql = "SELECT id FROM song ORDER BY album";
		$db_results = mysql_query($sql, dbh());

		while ($results = mysql_fetch_array($db_results)) { 
		$song = new Song($results['id']);
		$song->format_song();
		
		$xml = array();
		$xml['key']= $results['id'];
		$xml['dict']['Track ID']= $results['id'];
		$xml['dict']['Name'] = utf8_encode($song->title);
		$xml['dict']['Artist'] = utf8_encode($song->f_artist_full);
		$xml['dict']['Album'] = utf8_encode($song->f_album_full);
		$xml['dict']['Genre'] = $song->f_genre;
		$xml['dict']['Total Time'] = $song->time;
		$xml['dict']['Track Number'] = $song->track;
		$xml['dict']['Year'] = $song->year;
		$xml['dict']['Date Added'] = date("Y-m-d\TH:i:s\Z",$song->addition_time);
		$xml['dict']['Bit Rate'] = intval($song->bitrate/1000);
		$xml['dict']['Sample Rate'] = $song->rate;
		$xml['dict']['Play Count'] = $song->played;
		$xml['dict']['Track Type'] = "URL";
		$xml['dict']['Location'] = $song->get_url();

		$result .= xml_from_array($xml,1,'itunes');
		}
	return $result;

	    }
	
	} // export

} // end of catalog class

?>
