<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

	var $name;
	var $last_update;
	var $last_add;
	var $id3_set_command;
	var $rename_pattern;
	var $sort_pattern;
	var $catalog_type;

	/* This is a private var that's used during catalog builds */
	var $_playlists = array();

	// Used in functions
	var $albums	= array();
	var $artists	= array();
	var $genres	= array();

	/**
	 * Catalog
	 * Catalog class constructor, pulls catalog information
	 * @catagory Catalog
	 * @param $catalog_id 	The ID of the catalog you want to build information from
	 */
	function Catalog($catalog_id = 0) {

		/* If we have passed an id then do something */
		if ($catalog_id) {
			/* Assign id for use in get_info() */
			$this->id = $catalog_id;

			/* Get the information from the db */
			$info = $this->get_info();

			/* Assign Vars */
			$this->path        	= $info->path;
			$this->name        	= $info->name;
			$this->last_update 	= $info->last_update;
			$this->last_add	   	= $info->last_add;
			$this->id3_set_command 	= $info->id3_set_command;
			$this->rename_pattern 	= $info->rename_pattern;
			$this->sort_pattern 	= $info->sort_pattern;
			$this->catalog_type	= $info->catalog_type;
		} //catalog_id

	} //constructor


	/*!
		@function get_info
		@discussion get's the vars for $this out of the database
		@param $this->id	Taken from the object
	*/
	function get_info() {

		/* Grab the basic information from the catalog and return it */
		$sql = "SELECT * FROM catalog WHERE id='$this->id'";
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_object($db_results);

		return $results;

	} //get_info


	/*!
		@function get_catalogs
		@discussion Pull all the current catalogs
	*/
	function get_catalogs() {

		$sql = "SELECT id FROM catalog";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_object($db_results)) {
			$results[] = new Catalog($r->id);
		}

		return $results;

	} // get_catalogs


	/*!
		@function get_catalog_stats
		@discussion Pulls information about number of songs etc for a specifc catalog, or all catalogs
			    calls many other internal functions, returns an object containing results
		@param $catalog_id If set tells us to pull from a single catalog, rather than all catalogs
	*/
	function get_catalog_stats($catalog_id=0) {

		$results->songs 	= $this->count_songs($catalog_id);
		$results->albums 	= $this->count_albums($catalog_id);
		$results->artists	= $this->count_artists($catalog_id);
		$results->size		= $this->get_song_size($catalog_id);
		$results->time		= $this->get_song_time($catalog_id);

	} // get_catalog_stats


	/*!
		@function get_song_time
		@discussion Get the total amount of time (song wise) in all or specific catalog
		@param $catalog_id If set tells ut to pick a specific catalog
	*/
	function get_song_time($catalog_id=0) {

		$sql = "SELECT SUM(song.time) FROM song";
		if ($catalog_id) {
			$sql .= " WHERE catalog='$catalog_id'";
		}

		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_field($db_results);

		/* Do some conversion to get Hours Min Sec */


		return $results;

	} // get_song_time


	/*!
		@function get_song_size
		@discussion Get the total size of songs in all or a specific catalog
		@param $catalog_id If set tells us to pick a specific catalog
	*/
	function get_song_size($catalog_id=0) {

		$sql = "SELECT SUM(song.size) FROM song";
		if ($catalog_id) {
			$sql .= " WHERE catalog='$catalog_id'";
		}

		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_field($db_results);

		/* Convert it into MB */
		$results = ($results / 1048576);

		return $results;

	} // get_song_size


	/*!
		@function count_artists
		@discussion Count the number of artists in all catalogs or in a specific one
		@param $catalog_id If set tells us to pick a specific catalog
	*/
	function count_artists($catalog_id=0) {

		$sql = "SELECT DISTINCT(song.artist) FROM song";
		if ($catalog_id) {
			$sql .= " WHERE catalog='$catalog_id'";
		}

		$db_results = mysql_query($sql,dbh());

		$results = mysql_num_rows($db_results);

		return $results;

	} // count_artists


	/*!
		@function count_albums
		@discussion Count the number of albums in all catalogs or in a specific one
		@param $catalog_id If set tells us to pick a specific catalog
	*/
	function count_albums($catalog_id=0) {

		$sql = "SELECT DISTINCT(song.album) FROM song";
		if ($catalog_id) {
			$sql .=" WHERE catalog='$catalog_id'";
		}

		$db_results = mysql_query($sql, dbh());

		$results = mysql_num_rows($db_results);

		return $results;

	} // count_albums


	/*!
		@function count_songs
		@discussion Count the number of songs in all catalogs, or a specific one
		@param $catalog_id If set tells us to pick a specific catalog
	*/
	function count_songs($catalog_id=0) {

		$sql = "SELECT count(*) FROM song";
		if ($catalog_id) {
			$sql .= " WHERE catalog='$catalog_id'";
		}

		$db_results = mysql_query($sql, dbh());
		$results = mysql_fetch_field($db_results);

		return $results;

	} // count_songs

	/*!
		@function add_file
		@discussion adds a single file
	*/
	function add_file($filename) { 

		$file_size = @filesize($filename);
		$pattern = "/\.[" . conf('catalog_file_pattern') . "]$/i";	
		
		if ( preg_match($pattern ,$filename) && ($file_size > 0) && (!preg_match('/\.AppleDouble/', $filename))  ) {
			if(!$this->check_local_mp3($filename,$gather_type)) { 
				$this->insert_local_song($filename,$file_size);
			}
			elseif (conf('debug')) { 
				log_event($GLOBALS['user']->username, 'add_file', "Error: File exists",'ampache-catalog');
			}
		} // if valid file
		elseif (conf('debug')) { 
			log_event($GLOBALS['user']->username, 'add_file', "Error: File doesn't match pattern",'ampache-catalog');
		}


	} // add_file


	/*!
		@function add_files
		@discussion  Recurses throught $this->path and pulls out all mp3s and returns the full
			     path in an array. Passes gather_type to determin if we need to check id3
			     information against the db.
		@param $path 		The root path you want to start grabing files from
		@param $gather_type=0   Determins if we need to check the id3 tags of the file or not
	 */
	function add_files($path,$gather_type='',$parse_m3u='') {
		/* Strip existing escape slashes and then add them again 
		   This is done because we keep adding to the dir (slashed) + (non slashed)
		   and a double addslashes would pooch things
		*/

		// Prevent the script from timing out
		set_time_limit(0);
			
		/* Open up the directory */
		$handle = opendir(stripslashes($path));

		if (!is_resource($handle)) {
                        debug_event('read',"Unable to Open $path",'5','ampache-catalog'); 
			echo "<font class=\"error\">" . _("Error: Unable to open") . " $path</font><br />\n";
		}

		/* Recurse through this dir and create the files array */
		while ( false !== ( $file = readdir($handle) ) ) {

			// Fix Found by Naund
			// Needed to protect from ' in filenames
			$file = sql_escape($file);

			/* Skip to next if we've got . or .. */
			if ($file == '.' || $file == '..') { continue; } 

			debug_event('read',"Starting work on $file inside $path",'5','ampache-catalog');
			
			/* Change the dir so is_dir works correctly */
			if (!@chdir(stripslashes($path))) {
				debug_event('read',"Unable to chdir $path",'2','ampache-catalog'); 
				echo "<font class=\"error\">" . _('Error: Unable to change to directory') . " $path</font><br />\n";
			}

			/* Create the new path */
			$full_file = stripslashes($path."/".$file);
			$full_file = str_replace("//","/",$full_file);
			
			// Incase this is the second time through clear this variable 
			// if it was set the day before
			unset($failed_check);
				
			if (conf('no_symlinks')) {
				if (is_link($full_file)) { 
					debug_event('read',"Skipping Symbolic Link $path",'5','ampache-catalog'); 
					continue;
				}
			}

			/* If it's a dir run this function again! */
			if (is_dir($full_file)) {
				$this->add_files($full_file,$gather_type,$parse_m3u);
				/* Skip to the next file */
				continue;
			} //it's a directory

			/* If it's not a dir let's roll with it 
			 * next we need to build the pattern that we will use
			 * to detect if it's a audio file for now the source for
			 * this is in the /modules/init.php file
			 */
			$pattern = "/\.(" . conf('catalog_file_pattern');
			if ($parse_m3u) { 
				$pattern .= "|m3u)$/i";
			}
			else { 
				$pattern .= ")$/i";
			}
					
			/* see if this is a valid audio file or playlist file */
			if (preg_match($pattern ,$file)) {

				/* Once we're sure that it is a valid file 
				 * we need to check to see if it's new, only
				 * if we're doing a fast add
				 */
				if ($gather_type == 'fast_add') { 
					$file_time = filemtime($full_file);
					if ($file_time < $this->last_add) {
						debug_event('fast_add',"Skipping $full_file because last add is newer then file mod time",'5','ampache-catalog'); 
						continue;
					} 
				} // if fast_add
			
				/* Now that we're sure its a file get filesize  */
				$file_size = @filesize($full_file);

				if (!$file_size) { 
					debug_event('read',"Unable to get filesize for $full_file",'2','ampache-catalog'); 
					echo "<font class=\"error\">" . _("Error: Unable to get filesize for") . " $full_file <br />";
				} // file_size check
		
				if (is_readable($full_file)) {

					if (substr($file,-3,3) == 'm3u') { 
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
							if ( !($this->count%conf('catalog_echo_count')) ) {
								echo _("Added") . " $this->count. . . . <br />\n";
								flush();
							} //echos song count

						} // not found

						} // if it's not an m3u
						
					} // is readable
					else {
						// not readable, warn user
			                        debug_event('read',"$full_file is not readable by ampache",'2','ampache-catalog'); 
						echo "$full_file " . _("is not readable by ampache") . ".<br />\n";

					}

				} //if it's a mp3 and is greater than 0 bytes

				else {
					debug_event('read',"$full_file ignored, non audio file or 0 bytes",'5','ampache-catalog');
				} // else not an audio file or 0 size

		} // end while reading directory 

		debug_event('closedir',"Finished reading $path closing handle",'5','ampache-catalog');

		/* Close the dir handle */
		@closedir($handle);

	} //add_files

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

	/*!
		@function get_album_art
		@discussion This runs through all of the albums and trys to find the 
			art for them from the mp3s
		//FIXME: Make the display a table so it all lines up
	*/
	function get_album_art($catalog_id=0,$methods=array()) { 

		if (!$catalog_id) { $catalog_id = $this->id; }

		// Get all of the albums in this catalog
		$albums = $this->get_catalog_albums($catalog_id);	
		
		// Run through them an get the art!
		foreach ($albums as $album) { 
			flush();
			if ($debug) { echo "&nbsp;&nbsp;&nbsp;&nbsp;" . $album->name . " -- "; }

			if ($methods['id3']) { 
				$found = $album->get_id3_art(); 
				if ($found && $debug) { echo _("Found in ID3") . "<br />\n"; }
			}
			if ($methods['amazon'] && !$found) { 
				$found = $album->get_amazon_art(); 	
				if ($found && $debug) { echo _("Found on Amazon") . "<br />\n"; }
			}
			if ($methods['folder'] && !$found) { 
				$found = $album->get_folder_art(); 
				if ($found && $debug) { echo _("Found in Folder") . "<br />\n"; }
			}
			if (count($methods) == '0' && !$found) { 
				$found = $album->get_art();
				if ($found && $debug) { echo _("Found") . "<br />\n"; }
			} 
	
			if (!$found && $debug) { echo "<font class=\"error\">" . _("Not Found") . "</font><br />\n"; }
			
			if ($found) { $art_found++; }	
			

			/* Stupid little cutesie thing */
                        $search_count++;
                        if ( !($search_count%conf('catalog_echo_count')) ) {
                                echo _("Searched") . " $search_count. . . . <br />\n";
	                        flush();
                        } //echos song count

			
			// Prevent the script from timing out
			set_time_limit(0);

			unset($found);

		} // foreach albums
		
		echo "$art_found album's with art. . .<br />\n";
		flush();

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


	/*!
		@function get_disabled
		@discussion Gets an array of the disabled songs for all catalogs
			and returns full song objects with them
	*/
	function get_disabled() {
		global $conf;

		$results = array();

		$sql = "SELECT id FROM song WHERE enabled='0'";
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_array($db_results)) {
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

		$path = stripslashes($path);

		/* Open up the directory */
		$handle = @opendir($path);

		if (!is_resource($handle)) { echo "<font class=\"error\">" . _("Error: Unable to open") . " $path</font><br />\n"; }

		/* Change dir so we can tell if it's a directory */
		if (!@chdir($path)) {
			echo "<font class=\"error\">Error: Unable to change to $path directory</font><br />\n";
		}

		/* Recurse through this dir and create the files array */
		while ( FALSE !== ($file = @readdir($handle)) ) {

			$full_file = stripslashes($path . "/" . $file);
			$full_file = str_replace("//","/",$full_file);

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

		echo "<br /><b>" . _("Starting Dump Album Art") . ". . .</b><br /><br />\n";

		// Run through them an get the art!
		foreach ($albums as $album) {
	                flush();
                	if ($image = $album->get_db_art()) {
				/* Get the first song in the album */
                                $songs = $album->get_songs(1);
                                $song = $songs[0];
                                $dir = dirname($song->file);
				$extension = substr($image->art_mime,strlen($image->art_mime)-3,3);

	                        $preferred_filename = conf('album_art_preferred_filename');
	                        if (!$preferred_filename) { $preferred_filename = "folder.$extension"; }

	                        $file = "$dir/$preferred_filename";
	                        if ($file_handle = @fopen($file,"w")) {
		                        if (fwrite($file_handle, $image->art)) {
			                        $i++;
        	                                if ( !($i%conf('catalog_echo_count')) ) {
	                	                        echo _("Written") . " $i. . . <br />\n";
				                        flush();
	                                        } //echos song count
						if (conf('debug')) { log_event($_SESSION['userdata']['username'],'art_write',"$album->name Art written to $file"); }
	                                }
	                                        fclose($file_handle);
	                       	} // end if fopen
				else {
					if (conf('debug')) { log_event($_SESSION['userdata']['username'],'art_write',"Unable to open $file for writting"); }
					echo "<font class=\"error\">" . _("Error unable to open file for writting") . " [$file] </font><br />\n";
				}

                	} // end if image

	        } // end foreach

	        echo "<br /><b>" . _("Album Art Dump Complete") . "</b> &nbsp;&nbsp;";
	 	echo "<a href=\"" . conf('web_path') . "/admin/catalog.php" . "\">[" . _("Return") . "]</a>";

		flush();

	} // dump_album_art 

	/*!
		@function update_last_update
		@discussion updates the last_update of the catalog
	*/
	function update_last_update() {

		$date = time();
		$sql = "UPDATE catalog SET last_update='$date' WHERE id='$this->id'";
		$db_results = mysql_query($sql, dbh());

	} // update_last_update


	/**
	 * update_last_add
	 * updates the last_add of the catalog
	 * @package Catalog
	 */
	function update_last_add() {

		$date = time();
		$sql = "UPDATE catalog SET last_add='$date' WHERE id='$this->id'";
		$db_results = mysql_query($sql, dbh());

	} // update_last_add


	/**
	 * new_catalog
	 * The Main array for making a new catalog calls many other child functions within this class
	 * @package Catalog
	 * @catagory Create
	 * @param $path Root path to start from for catalog
	 * @param $name Name of the new catalog
	 */
	function new_catalog($path,$name, $id3cmd=0, $ren=0, $sort=0, $type=0,$gather_art=0,$parse_m3u=0,$art=array()) {

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
			$catalog_id = $this->create_catalog_entry($path,$name,$id3cmd, $ren, $sort, $type);
		}

		/* Setup the $this with the new information */
		$this->id = $catalog_id;
		$this->path = $path;
		$this->name = $name;
		$this->id3_set_command = ($id3cmd)?$id3cmd:'';
		$this->rename_pattern = ($ren)?$ren:'';
		$this->sort_pattern = ($sort)?$sort:'';
		$this->catalog_type = $type;

		/* Fluf */
		echo _("Starting Catalog Build") . " [$name]<br />\n";
		flush();


	       if ($this->catalog_type == 'remote') {
                        echo _("Running Remote Sync") . ". . .<br /><br />";
                        flush();
                        $this->get_remote_catalog($type=0);
                        return true;
                }
		
		/* Get the songs and then insert them into the db */
		$this->add_files($this->path,$type,$parse_m3u);

		foreach ($this->_playlists as $full_file) { 
	                if ($this->import_m3u($full_file)) {
				$file = basename($full_file);
	                        echo "&nbsp;&nbsp;&nbsp;" . _("Added Playlist From") . " $file . . . .<br />\n";
		                flush();
			} // end if import worked
                } // end foreach playlist files

		/* Now Adding Album Art? */
		if ($gather_art) { 
                        echo "<br />\n<b>" . _("Starting Album Art Search") . ". . .</b><br />\n";
                        flush();
                        $this->get_album_art(0,$art);
		} // if we want to gather album art

		/* Do a little stats mojo here */
		$current_time = time();

		$time_diff = $current_time - $start_time;
		if ($time_diff) { $song_per_sec = intval($this->count/$time_diff); }
		echo _("Catalog Finished") . ". . . " . _("Total Time") . " [" . date("i:s",$time_diff) . "] " . _("Total Songs") . " [" . $this->count . "] " . 
			_("Songs Per Seconds") . " [" . $song_per_sec . "]<br />\n";

		return $catalog_id;

	} //new_catalog

	/*!
		@function update_single_item
		@discussion updates a single album,artist,song
	*/
	function update_single_item($type,$id) { 

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

		foreach($songs as $song) { 

			$info = $this->update_song_from_tags($song);

                        if ($info['change']) {
                                echo "<dl>\n\t<dd>";
                                echo "<b>$song->file " . _("Updated") . "</b>\n";
                                echo $info['text'];
                                echo "\t</dd>\n</dl><hr align=\"left\" width=\"50%\" />";
                        	flush();
	                } // if change
			else {
				echo"<dl>\n\t<dd>";
				echo "<b>$song->file</b><br />" . _("No Update Needed") . "\n";
				echo "\t</dd>\n</dl><hr align=\"left\" width=\"50%\" />";
				flush();
			}
		} // foreach songs

	} // update_single_item

        /*!
                @function update_song_from_tags
                @discussion updates the song info based on tags
        */
        function update_song_from_tags($song) {


		if (conf('debug')) { log_event($_SESSION['userdata']['username'],' tag-read ',"Reading Tags from $song->file",'ampache-catalog'); }
		
                $info = new Audioinfo();
                $results = $info->Info($song->file);

                /* Find the correct key */
                $key = get_tag_type($results);

		/* Fill Missing Information */
		$results = $song->fill_info($results,$this->sort_pattern . "/" . $this->rename_pattern, $this->id, $key);

                /* Clean up the tags */
                $results = clean_tag_info($results,$key,$song->file);

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
		unset($results,$key,$info);

                /*
                * We have the artist/genre/album name need to check it in the tables
                * If found then add & return id, else return id
                */
                $new_song->artist       = $this->check_artist($artist);
                $new_song->f_artist     = $artist;
                $new_song->genre        = $this->check_genre($genre);
                $new_song->f_genre      = $new_song->get_genre_name();
                $new_song->album        = $this->check_album($album,$new_song->year);
                $new_song->f_album      = $album . " - " . $new_song->year;
                $new_song->title        = $this->check_title($new_song->title,$new_song->file);

                $info = $song->compare_song_information($song,$new_song);

                if ($info['change']) {
			if (conf('debug')) { log_event($_SESSION['userdata']['username'],' update ',"$song->file difference found, updating database",'ampache-catalog'); }
                        $song->update_song($song->id,$new_song);
                }
		else { 
			if (conf('debug')) { log_event($_SESSION['userdata']['username'],' update ',"$song->file no difference found returning",'ampache-catalog'); }
		}

                return $info;

        } // update_song_from_tags

	/*!
		@function add_to_catalog
		@discussion this function adds new files to an
			existing catalog
	*/
	function add_to_catalog($type='') { 

		echo "\n" . _('Starting New Song Search on') . " <b>[$this->name]</b> " . _('catalog') . "<br /><br />\n";
		flush();

		if ($this->catalog_type == 'remote') { 
			echo _('Running Remote Update') . ". . .<br /><br />";
			flush();
			$this->get_remote_catalog($type=0);
			return true;
		} 

		/* Set the Start time */
		$start_time = time();

		/* Get the songs and then insert them into the db */
		$this->add_files($this->path,$type);

                foreach ($this->_playlists as $full_file) {
                        if ($this->import_m3u($full_file)) {
				$file = basename($full_file);
                                echo "&nbsp;&nbsp;&nbsp;" . _('Added Playlist From') . " $file . . . .<br />\n";
                                flush();
                        } // end if import worked
                } // end foreach playlist files

		/* Do a little stats mojo here */
		$current_time = time();
		
		if ($type != 'fast_add') { 	
			echo "\n<b>" . _('Starting Album Art Search') . ". . .</b><br />\n"; 
			flush();
			$this->get_album_art(); 
		} 

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

		echo "\n<br />" . _("Catalog Update Finished") . "... " . _("Total Time") . " [" . date("i:s",$time_diff) . "] " .
			_("Total Songs") . " [" . $this->count . "] " . _("Songs Per Seconds") . " [" . $song_per_sec . "]<br /><br />";

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
                        if (conf('debug')) { log_event($_SESSION['userdata']['username'],'xmlrpc',"Unable to load XMLRPC library"); }
			echo "<font class=\"error\"><b>" . _("Error") . "</b>: " . _("Unable to load XMLRPC library, make sure XML-RPC is enabled") . "<br />\n";
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
	        
		$f = new xmlrpcmsg('remote_catalog_query', array(new xmlrpcval( conf('web_path'), "string")) );
		
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
			if (conf('debug')) { log_event($_SESSION['userdata']['username'],' xmlrpc-client ',$error_msg,'ampache-catalog'); }
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

	        echo "<p>" . _("Completed updating remote catalog(s)") . ".</p><hr />\n";
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

		$query_array = array(new xmlrpcval($start, "int"),new xmlrpcval($end,"int")); 

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
			echo "Added $total...<br />";
			flush();
                }
                else {
                        $error_msg = _("Error connecting to") . " " . $server . " " . _("Code") . ": " . $response->faultCode() . " " . _("Reason") . ": " . $response->faultString();
                        if (conf('debug')) { log_event($_SESSION['userdata']['username'],' xmlrpc-client ',$error_msg,'ampache-catalog'); }
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
			$new_song->comment	= $data[3];
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
		$this->clean_albums();
		$this->clean_stats();
		$this->clean_artists();
		$this->clean_genres();
		$this->clean_flagged();

	} // update_remote_catalog


	/*!
		@function clean_catalog
		@discussion  Cleans the Catalog of files that no longer exist grabs from $this->id or $id passed 
  	  		     Doesn't actually delete anything, disables errored files, and returns them in an array
		@param $catalog_id=0	Take the ID of the catalog you want to clean
		@param $action=0	Delete/Disable, default is disable
	*/
	function clean_catalog($catalog_id=0,$action=0) {

		/* Define the Arrays we will need */
		$dead_files = array();

		if (!$catalog_id) { $catalog_id = $this->id; }

		echo "\nCleaning the <b>[" . $this->name . "]</b> Catalog...<br /><br />\n";
		flush();

		/* Get all songs in this catalog */
		$sql = "SELECT id,file FROM song WHERE catalog='$catalog_id' AND enabled='1'";
		$db_results = mysql_query($sql, dbh());

		/* Recurse through files, put @ to prevent errors poping up */
		while ($results = mysql_fetch_object($db_results)) {

			/* Remove slashes while we are checking for its existance */
			$results->file = stripslashes($results->file);

                        /* Stupid little cutesie thing */
                        $this->count++;
                        if ( !($this->count%conf('catalog_echo_count')) ) {
                                echo _('Checking') . " $this->count. . . . <br />\n";
	                        flush();
                        } //echos song count

			/* Also check the file information */
			$file_info = @filesize($results->file);

			/* If it errors somethings splated, or the files empty */
			if (!file_exists($results->file) OR $file_info < 1) {
			
				/* Add Error */
				echo "<font class=\"error\">Error File Not Found or 0 Bytes: " . $results->file . "</font><br />";
				flush();

				/* Add this file to the list for removal from the db */
				$dead_files[] = $results;
			} //if error

		} //while gettings songs

		/* Incase there's been a snafo with a mount point on something
		 * don't actually delete from DB here, simply disable and list
		 */
		if (count($dead_files)) {
			foreach ($dead_files as $data) {

				//FIXME: Until I fix the form, assume delete
				//if ($action === 'delete_dead') { 
					$sql = "DELETE FROM song WHERE id='$data->id'";
				//} 
				//
				//else {
				//	$sql = "UPDATE song SET status='disabled' WHERE id='$data->id'";
				//}

				$db_results = mysql_query($sql, dbh());

				/* DB Error occured */
				if (!$db_results) {
					/* Add Error */
				} //if error

			} //end foreach

		} // end if dead files

		/* Step two find orphaned Arists/Albums
		 * This finds artists and albums that no
		 * longer have any songs associated with them
		 */
		$this->clean_albums();
		$this->clean_artists();
		$this->clean_stats();
		$this->clean_playlists();
		$this->clean_flagged();
		;$this->clean_genres();
		
		/* Return dead files, so they can be listed */
		echo "<b>" . _("Catalog Clean Done") . " [" . count($dead_files) . "] " . _("files removed") . "</b><br />\n";
		flush();
		return $dead_files;

		$this->count = 0;

	} //clean_catalog

	/**
	 * clean_genres
	 * This functions cleans up unused genres
	 * @package Catalog
	 * @catagory Clean
	 */
	function clean_genres() { 

                /* Mysql 3.23 doesn't support our cool query so we have to do it a different way */
                if (preg_match("/^3\./",mysql_get_server_info())) {
                        $sql = "SELECT genre.id FROM genre LEFT JOIN song ON song.genre = genre.id WHERE song.id IS NULL";
                        $db_results = mysql_query($sql, dbh());

                        $results = array();

                        while ($r = mysql_fetch_row($db_results)) {
                                $results[] = $r;
                        }

                        foreach ($results as $dead) {

                                $sql = "DELETE FROM genre WHERE id='$dead[0]'";
                                $db_results = mysql_query($sql,dbh());
                        }
                        return true;
                }

                /* Do a complex delete to get albums where there are no songs */
                $sql = "DELETE FROM genre USING genre LEFT JOIN song ON song.genre = genre.id WHERE song.id IS NULL";
                $db_results = mysql_query($sql, dbh());

	} // clean_genres


	/*!
		@function clean_albums
		@discussion  This function cleans out unused albums
		@param $this->id Depends on the current object
	*/
	function clean_albums() {

		/* Mysql 3.23 doesn't support our cool query so we have to do it a different way */
		if (preg_match("/^3\./",mysql_get_server_info())) { 
			$sql = "SELECT album.id FROM album LEFT JOIN song ON song.album = album.id WHERE song.id IS NULL";
			$db_results = mysql_query($sql, dbh());

			$results = array();

			while ($r = mysql_fetch_row($db_results)) { 
				$results[] = $r;
			}

			foreach ($results as $dead) { 

				$sql = "DELETE FROM album WHERE id='$dead[0]'";
				$db_results = mysql_query($sql,dbh());
			} 
			return true;
		}

		/* Do a complex delete to get albums where there are no songs */
		$sql = "DELETE FROM album USING album LEFT JOIN song ON song.album = album.id WHERE song.id IS NULL";
		$db_results = mysql_query($sql, dbh());

	} //clean_albums

	/*!
		@function clean_flagged
		@discussion This functions cleans ou unused flagged items
	*/
	function clean_flagged() { 

		/* Mysql 3.23 doesn't support our cool query so we have to do it a different way */
		if (preg_match("/^3\./",mysql_get_server_info())) { 
			$sql = "SELECT flagged.id FROM flagged LEFT JOIN song ON song.id=flagged.song WHERE song.id IS NULL";
			$db_results = mysql_query($sql, dbh());

			$results = array();

			while ($r = mysql_fetch_row($db_results)) { 
				$results[] = $r;
			} 

			foreach ($results as $dead) { 
				$sql = "DELETE FROM flagged WHERE id='$dead[0]'";
				$db_results = mysql_query($sql, dbh());
			}
			return true;
		}
		
		/* Do a complex delete to get flagged items where the songs are now gone */
		$sql = "DELETE FROM flagged USING flagged LEFT JOIN song ON song.id = flagged.song WHERE song.id IS NULL";
		$db_results = mysql_query($sql, dbh());

	} // clean_flagged


	/*!
		@function clean_artists
		@discussion This function cleans out unused artists
		@param $this->id Depends on the current object
	*/
	function clean_artists() {

                /* Mysql 3.23 doesn't support our cool query so we have to do it a different way */
                if (preg_match("/^3\./",mysql_get_server_info())) {
                        $sql = "SELECT artist.id FROM artist LEFT JOIN song ON song.artist = artist.id WHERE song.id IS NULL";
                        $db_results = mysql_query($sql, dbh());

			$results = array();

                        while ($r = mysql_fetch_row($db_results)) {
                                $results[] = $r;
                        }

                        foreach ($results as $dead) {

                                $sql = "DELETE FROM artist WHERE id='$dead[0]'";
                                $db_results = mysql_query($sql,dbh());
                        }                                           
                        return true;                                                
                }                                                                   


		/* Do a complex delete to get artists where there are no songs */
		$sql = "DELETE FROM artist USING artist LEFT JOIN song ON song.artist = artist.id WHERE song.id IS NULL";
		$db_results = mysql_query($sql, dbh());

	} //clean_artists

	/*
		@function clean_playlists
		@discussion cleans out dead files from playlists
		@param $this->id depends on the current object
	*/
	function clean_playlists() { 

		/* Mysql 3.23 doesn't support our cool query so we have to do it a different way */
		if (preg_match("/^3\./",mysql_get_server_infO())) { 
			$sql = "SELECT playlist_data.song FROM playlist_data LEFT JOIN song ON song.id = playlist_data.song WHERE song.file IS NULL";
			$db_results = mysql_query($sql, dbh());

			$results = array();

			while ($r = mysql_fetch_row($db_results)) { 
				$results[] = $r;
			}

			foreach ($results as $dead) { 
				$sql = "DELETE FROM playlist_data WHERE song='$dead[0]'";
				$db_results = mysql_query($sql, dbh());
			}
			return true;
		}

		/* Do a complex delete to get playlist songs where there are no songs */
		$sql = "DELETE FROM playlist_data USING playlist_data LEFT JOIN song ON song.id = playlist_data.song WHERE song.file IS NULL";
		$db_results = mysql_query($sql, dbh());

	} // clean_playlists

	/*!
		@function clean_stats
		@discussion This functions removes stats for songs/albums that no longer exist
		@param $catalog_id The ID of the catalog to clean
	*/
	function clean_stats() {

		$version = mysql_get_server_info();

                /* Mysql 3.23 doesn't support our cool query so we have to do it a different way */
                if (preg_match("/^3\./",$version)) {
                        $sql = "SELECT object_count.id FROM object_count LEFT JOIN song ON song.id = object_count.object_id WHERE object_type='song' AND song.id IS NULL";
                        $db_results = mysql_query($sql, dbh());

			$results = array();

                        while ($r = mysql_fetch_row($db_results)) {
                                $results[] = $r;
                        }

                        foreach ($results as $dead) {

                                $sql = "DELETE FROM object_count WHERE id='$dead[0]'";
                                $db_results = mysql_query($sql,dbh());
                        }                                           
                        
                }                                                                   
		// We assume this will be 4.0+
		else {
			/* Crazy SQL Mojo to remove stats where there are no songs */
			$sql = "DELETE FROM object_count USING object_count LEFT JOIN song ON song.id=object_count.object_id WHERE object_type='song' AND song.id IS NULL";
			$db_results = mysql_query($sql, dbh());
		}

                /* Mysql 3.23 doesn't support our cool query so we have to do it a different way */
                if (preg_match("/^3\./",$version)) {
                        $sql = "SELECT object_count.id FROM object_count LEFT JOIN album ON album.id = object_count.object_id WHERE object_type='album' AND album.id IS NULL";
                        $db_results = mysql_query($sql, dbh());

			$results = array();

                        while ($r = mysql_fetch_row($db_results)) {
                                $results[] = $r;
                        }

                        foreach ($results as $dead) {

                                $sql = "DELETE FROM object_count WHERE id='$dead[0]'";
                                $db_results = mysql_query($sql,dbh());
                        }                                           
                }                                                                   
		// We assume 4.0+ Here
		else {
			/* Crazy SQL Mojo to remove stats where there are no albums */
			$sql = "DELETE FROM object_count USING object_count LEFT JOIN album ON album.id=object_count.object_id WHERE object_type='album' AND album.id IS NULL";
			$db_results = mysql_query($sql, dbh());
		}
		
                /* Mysql 3.23 doesn't support our cool query so we have to do it a different way */
                if (preg_match("/^3\./",$version)) {
                        $sql = "SELECT object_count.id FROM object_count LEFT JOIN artist ON artist.id = object_count.object_id WHERE object_type='artist' AND artist.id IS NULL";
                        $db_results = mysql_query($sql, dbh());
	
			$results = array();

                        while ($r = mysql_fetch_row($db_results)) {
                                $results[] = $r;
                        }

                        foreach ($results as $dead) {

                                $sql = "DELETE FROM object_count WHERE id='$dead[0]'";
                                $db_results = mysql_query($sql,dbh());
                        }                                           
                }                                                                   
		// We assume 4.0+ here
		else { 
			/* Crazy SQL Mojo to remove stats where ther are no artists */
			$sql = "DELETE FROM object_count USING object_count LEFT JOIN artist ON artist.id=object_count.object_id WHERE object_type='artist' AND artist.id IS NULL";
			$db_results = mysql_query($sql, dbh());
		}


	} // clean_stats
	

	/*!
		@function verify_catalog
		@discussion This function compares the DB's information with the ID3 tags
		@param $catalog_id The ID of the catalog to compare
	*/
	function verify_catalog($catalog_id=0,$gather_type='') {

		/* Create and empty song for us to use */
		$total_updated = 0;

		/* Set it to this if they don't pass anything */
		if (!$catalog_id) {
			$catalog_id = $this->id;
		}

		/* First get the filenames for the catalog */
		$sql = "SELECT id FROM song WHERE catalog='$catalog_id' ORDER BY id";
		$db_results = mysql_query($sql, dbh());
		$number = mysql_num_rows($db_results);
		
		echo _("Updating the") . " <b>[ $this->name ]</b> " . _("Catalog") . "<br />\n";
		echo $number . " " . _("songs found checking tag information.") . "<br /><br />\n\n";
		flush();

		/* Magical Fix so we don't run out of time */
		set_time_limit(0);

		/* Recurse through this catalogs files
		 * and get the id3 tage information,
		 * if it's not blank, and different in
		 * in the file then update!
		 */
		while ($results = mysql_fetch_object($db_results)) {

			/* Create the object from the existing database information */
			$song = new Song($results->id);

			if (conf('debug')) { log_event($_SESSION['userdata']['username'],' verify ',"Starting work on $song->file",'ampache-catalog'); }
			
			if (is_readable($song->file)) {
				unset($skip);

				/* If they have specified fast_update check the file
				   filemtime to make sure the file has actually 
				   changed
				*/
				if ($gather_type == 'fast_update') {
					$file_date = filemtime($song->file);
					if ($file_date < $this->last_update) { $skip = true; }
				} // if gather_type
				
				if ($song->update_time >= $this->last_update) { 
					$skip = true;
					$song->update_utime($song->id,time()+86400);
				}

				// if the file hasn't been modified since the last_update
				if (!$skip) {

					$info = $this->update_song_from_tags($song);
					$album_id = $song->album;
					if ($info['change']) {
						echo "<dl style=\"list-style-type:none;\">\n\t<li>";
						echo "<b>$song->file " . _("Updated") . "</b>\n";
						echo $info['text'];
					/* If we aren't doing a fast update re-gather album art */
						if ($gather_type != 'fast_update' AND !isset($searched_albums[$album_id])) { 
							$album = new Album($song->album);
							$searched_albums[$album_id] = 1;
							$found = $album->get_art();
							unset($album);
							if ($found) { $is_found = _(" FOUND"); }
							echo "<br /><b>" . _("Searching for new Album Art") . ". . .$is_found</b><br />\n";
							unset($found,$is_found);
						}
						elseif (isset($searched_albums[$album_id])) { 
							echo "<br /><b>" . _("Album Art Already Found") . ". . .</b><br />\n";
						}
						echo "\t</li>\n</dl>\n<hr align=\"left\" width=\"50%\" />\n";
						flush();
						$total_updated++;
					}

					unset($info);

				} // end skip

				if ($skip) { 
					if (conf('debug')) { log_event($_SESSION['userdata']['username'],' skip ',"$song->file has been skipped due to newer local update or file mod time",'ampache-catalog'); }
				}
	
                                /* Stupid little cutesie thing */
                                $this->count++;
                                if ( !($this->count%conf('catalog_echo_count')) ) {
                                	echo "Checked $this->count. . . . <br />\n";
                                        flush();
                                } //echos song count
				
			} // end if file exists

			else {
				echo "<dl>\n  <li>";
				echo "<b>$song->file does not exist or is not readable</b>\n";
				echo "  </li>\n</dl>\n<hr align=\"left\" width=\"50%\" />\n";
				
				if (conf('debug')) { log_event($_SESSION['userdata']['username'],' read-error ',"$song->file does not exist or is not readable",'ampache-catalog'); }
				
				// Should we remove it from catalog?
			}


		} //end foreach


		/* After we have updated all the songs with the new information clear any empty albums/artists */
		$this->clean_albums();
		$this->clean_artists();
		$this->clean_stats();
		$this->clean_flagged();

		// Update the last_update
		$this->update_last_update();

		echo "Update Finished. Checked $this->count. $total_updated songs updated.<br /><br />";

		$this->count = 0;

		return true;

	} //verify_catalog


	/*!
		@function create_catalog_entry
		@discussion Creates a new catalog from path and type
		@param $path The root path for this catalog
		@param $name The name of the new catalog
	*/
	function create_catalog_entry($path,$name,$id3cmd=0,$ren=0,$sort=0, $type='local') {

		// Current time
		$date = time();

		$path = sql_escape($path);
		$name = sql_escape($name);

		if($id3cmd && $ren && $sort) {
			$sql = "INSERT INTO catalog (path,name,last_update,id3_set_command,rename_pattern,sort_pattern,catalog_type) " .
				" VALUES ('$path','$name','$date', '$id3cmd', '$ren', '$sort','$type')";
		}
		else {
			$sql = "INSERT INTO catalog (path,name,last_update) VALUES ('$path','$name','$date')";
		}

		$db_results = mysql_query($sql, dbh());
		$catalog_id = mysql_insert_id(dbh());

	        return $catalog_id;

	} //create_catalog_entry


	/*!
		@function check_catalog
		@discussion  Checks for the $path already in the catalog table
		@param $path The root path for the catalog we are checking
	*/
	function check_catalog($path) {
		
		$path = sql_escape($path);

		$sql = "SELECT id FROM catalog WHERE path='$path'";
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_object($db_results);

		return $results->id;

	} //check_catalog


	/*!
		@function check_artist
		@discussion Takes $artist checks if there then return id else insert and return id
		@param $artist The name of the artist
	*/
	function check_artist($artist) {

		// Only get the var ones.. less func calls
		$cache_limit = conf('artist_cache_limit');

		/* Clean up the artist */
		$artist = trim($artist);
		$artist = sql_escape($artist);


		/* Ohh no the artist has lost it's mojo! */
		if (!$artist) {
			$artist = "Unknown (Orphaned)";
		}

		// Remove the prefix so we can sort it correctly
		preg_match("/^(The\s|An\s|A\s)(.*)/i",$artist,$matches);

		if (count($matches)) {
			$artist = $matches[2];
			$prefix = $matches[1];
		}

		// Check to see if we've seen this artist before
		if (isset($this->artists[$artist])) {
			return $this->artists[$artist];
		} // if we've seen this artist before

		/* Setup the checking sql statement */
		$sql = "SELECT id FROM artist WHERE name LIKE '$artist' ";
		$db_results = mysql_query($sql, dbh());

		/* If it's found */
		if ($r = mysql_fetch_object($db_results)) {
			$artist_id = $r->id;
		} //if found

		/* If not found create */
		else {

			$prefix_txt = 'NULL';

			if ($prefix) {
				$prefix_txt = "'$prefix'";
			}
		
			$sql = "INSERT INTO artist (name, prefix) VALUES ('$artist', $prefix_txt)";
			$db_results = mysql_query($sql, dbh());
			$artist_id = mysql_insert_id(dbh());


			if (!$db_results) {
				echo "Error Inserting Artist:$artist <br />";
				flush();
			}

		} //not found

		if ($cache_limit) {

			$artist_count = count($this->artists);
			if ($artist_count == $cache_limit) {
				$this->artists = array_slice($this->artists,1);
			}
			if (conf('debug')) { log_event($_SESSION['userdata']['username'],'cache',"Adding $artist with $artist_id to Cache",'ampache-catalog'); }
			$array = array($artist => $artist_id);
			$this->artists = array_merge($this->artists, $array);
			unset($array);

		} // if cache limit is on..

		return $artist_id;

	} //check_artist


	/*!
		@function check_album
		@disucssion Takes $album and checks if there then return id else insert and return id 
		@param $album The name of the album
	*/
	function check_album($album,$album_year=0) {

		/* Clean up the album name */
		$album = trim($album);
		$album = sql_escape($album);
		$album_year = intval($album_year);

		// Set it once to reduce function calls
		$cache_limit = conf('album_cache_limit');

		/* Ohh no the album has lost it's mojo */
		if (!$album) {
			$album = "Unknown (Orphaned)";
		}

		// Remove the prefix so we can sort it correctly
		preg_match("/^(The\s|An\s|A\s)(.*)/i",$album,$matches);

		if (count($matches)) {
			$album = $matches[2];
			$prefix = $matches[1];
		}

		// Check to see if we've seen this album before
		if (isset($this->albums[$album])) {
			return $this->albums[$album];
		}

		/* Setup the Query */
		$sql = "SELECT id FROM album WHERE name LIKE '$album'";
		if ($album_year) { $sql .= " AND year='$album_year'"; }
		$db_results = mysql_query($sql, dbh());

		/* If it's found */
		if ($r = mysql_fetch_object($db_results)) {
			$album_id = $r->id;

		} //if found

		/* If not found create */
		else {
                        $prefix_txt = 'NULL';

                        if ($prefix) {
                                $prefix_txt = "'$prefix'";
                        }

			$sql = "INSERT INTO album (name, prefix,year) VALUES ('$album',$prefix_txt,'$album_year')";
			$db_results = mysql_query($sql, dbh());
			$album_id = mysql_insert_id(dbh());

			if (!$db_results) {
				echo "Error Inserting Album:$album <br />";
				flush();
			}

		} //not found

                if ($cache_limit > 0) {

                        $albums_count = count($this->albums);

                        if ($albums_count == $cache_limit) {
        	                $this->albums = array_slice($this->albums,1);
                        }
			$array = array($album => $album_id);
			$this->albums = array_merge($this->albums,$array);	               
			unset($array);

                } // if cache limit is on..

		return $album_id;

	} //check_album


	/*!
		@function check_genre
		@discussion Finds the Genre_id from the text name
		@param $genre The name of the genre
	*/
	function check_genre($genre) {
	
		/* If a genre isn't specified force one */
		if (strlen(trim($genre)) < 1) {
			$genre = "Unknown (Orphaned)";
		}

		if ($this->genres[$genre]) {
			return $this->genres[$genre];
		}

		/* Look in the genre table */
		$genre = sql_escape($genre);
		$sql = "SELECT id FROM genre WHERE name LIKE '$genre'";	
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_object($db_results);

		if (!$results->id) { 
			$sql = "INSERT INTO genre (name) VALUES ('$genre')";
			$db_results = mysql_query($sql, dbh());
			$results->id = mysql_insert_id(dbh());
		}

		$this->genres[$genre] = $results->id;

		return $results->id;

	} //check_genre


	/*!
		@function check_title
		@discussion this checks to make sure something is
			set on the title, if it isn't it looks at the
			filename and trys to set the title based on that
	*/
	function check_title($title,$file=0) {

		if (strlen(trim($title)) < 1) {
			preg_match("/.+\/(.*)\.....?$/",$file,$matches);
			$title = sql_escape($matches[1]);
		}

		return $title;


	} //check_title


	/*!
		@function insert_local_song
		@discussion Insert a song that isn't already in the database this
			    function is in here so we don't have to create a song object
		@param $file The file name we are adding (full path)
		@param $file_info The information of the file, size etc taken from stat()
	*/
	function insert_local_song($file,$file_info) {

		/* Create the Audioinfo object and get info */
		$audio_info 	 = new Audioinfo();
		$song_obj	 = new Song();
		$results 	 = $audio_info->Info($file);
		$results['file'] = $file;

		$key = get_tag_type($results);

		/* Fill Empty info from filename/path */
		$results = $song_obj->fill_info($results,$this->sort_pattern . "/" . $this->rename_pattern,$this->id,$key);

		/* Clean Up the tags */
		$results = clean_tag_info($results,$key,$file);
	
		/* Set the vars here... so we don't have to do the '" . $blah['asd'] . "' */
		$title 		= sql_escape($results['title']);
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

		/*
		 * We have the artist/genre/album name need to check it in the tables
		 * If found then add & return id, else return id
		 */
		$artist_id	= $this->check_artist($artist);
		$genre_id	= $this->check_genre($genre);
		$album_id	= $this->check_album($album,$year);
		$title		= $this->check_title($title,$file);
		$add_file	= sql_escape($results['file']);

		$sql = "INSERT INTO song (file,catalog,album,artist,title,bitrate,rate,mode,size,time,track,genre,addition_time,year,comment)" .
			" VALUES ('$add_file','$this->id','$album_id','$artist_id','$title','$bitrate','$rate','$mode','$size','$song_time','$track','$genre_id','$current_time','$year','$comment')";

		$db_results = mysql_query($sql, dbh());

		if (!$db_results) {
			if (conf('debug')) { log_event($_SESSION['userdata']['username'],'insert',"Unable to insert $file -- $sql",'ampache-catalog'); }
			echo "<span style=\"color: #F00;\">Error Adding $file </span><br />$sql<br />";
			flush();
		}

		/* Clear Variables */
		unset($results,$audio_info,$song_obj);

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
		$comment	= sql_escape($song->comment);
		$current_time	= time();	
		
		$sql = "INSERT INTO song (file,catalog,album,artist,title,bitrate,rate,mode,size,time,track,genre,addition_time,year,comment)" .
			" VALUES ('$url','$song->catalog','$song->album','$song->artist','$title','$song->bitrate','$song->rate','$song->mode','$song->size','$song->time','$song->track','$song->genre','$current_time','$song->year','$comment')";
		$db_results = mysql_query($sql, dbh());

		if (!$db_results) { 
                        if (conf('debug')) { log_event($_SESSION['userdata']['username'],'insert',"Unable to Add Remote $url -- $sql",'ampache-catalog'); }
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


	/*!
		@function check_local_mp3
		@discussion Checks the song to see if it's there already returns true if found, false if not 
		@param $full_file The full file name that we are checking
		@param $gather_type=0 If we need to check id3 tags or not
	*/
	function check_local_mp3($full_file, $gather_type='') {

		if ($gather_type == 'fast_add') {
			$file_date = filemtime($full_file);
			if ($file_date < $this->last_add) {
				return true;
			}
		}

		$full_file = sql_escape($full_file);

		$sql = "SELECT id FROM song WHERE file = '$full_file'";
		$db_results = mysql_query($sql, dbh());

		//If it's found then return true
		if (@mysql_fetch_row($db_results)) {
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

		foreach ($results as $value) {
			// Remove extra whitespace
			$value = trim($value);
			if (preg_match("/\.[A-Za-z0-9]{3,4}$/",$value)) { 
				$file[0] = str_replace("/","\\",$value);
				$file[1] = str_replace("\\","/",$value);
				/* Search for this filename, cause it's a audio file */
				$sql = "SELECT id FROM song WHERE file LIKE '%" . sql_escape($file[0]) . "' OR file LIKE '%" . sql_escape($file[1]) . "'";
				$db_results = mysql_query($sql, dbh());
				$song_id = mysql_result($db_results,'id');
				if ($song_id) { $songs[] = $song_id; }
			} // if it's a file

		} // end foreach line

		if (conf('debug')) { log_event($GLOBALS['user']->username,'m3u_parse',"Parsing $filename - Found: " . count($songs) . " Songs"); }

		if (count($songs)) { 
			$playlist = new Playlist();
			$playlist_name = "M3U - " . basename($filename);
			$playlist->create($playlist_name,'public');
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

	/*!
		@function delete_catalog
		@discussion Deletes the catalog and everything assoicated with it
			    assumes $this
	*/
	function delete_catalog() {

		// Do some crazyness to delete all the songs in this catalog
		// from playlists...
		$sql = "SELECT playlist_data.song FROM song,playlist_data,catalog WHERE catalog.id=song.catalog AND playlist_data.song=song.id AND catalog.id='$this->id'";
		$db_results = mysql_query($sql, dbh());

		$results = array();

		while ($r = mysql_fetch_object($db_results)) { 
			$results[] = $r;
		}

		foreach ($results as $r) { 
			// Clear Playlist Data
			$sql = "DELETE FROM playlist_data WHERE song='$r->song'";
			$db_results = mysql_query($sql, dbh());
			
		} // End Foreach

		// First remove the songs in this catalog
		$sql = "DELETE FROM song WHERE catalog = '$this->id'";
		$db_results = mysql_query($sql, dbh());

		// Next Remove the Catalog Entry it's self
		$sql = "DELETE FROM catalog WHERE id = '$this->id'";
		$db_results = mysql_query($sql, dbh());

		// Run the Aritst/Album Cleaners...
		$this->clean_albums();
		$this->clean_artists();
		$this->clean_stats();
		$this->clean_playlists();
		$this->clean_flagged();

	} // delete_catalog


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

} //end of catalog class

?>
