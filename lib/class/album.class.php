<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
 * Album Class
 * This is the class responsible for handling the Album object
 * it is related to the album table in the database.
 */
class Album {

	/* Variables from DB */
	public $id;
	public $name;
	public $year;
	public $prefix;

	/* Art Related Fields */
	public $art;
	public $art_mime; 
	public $thumb; 
	public $thumb_mime;

	// cached information
	public $_songs=array(); 

	/**
	 * __construct
	 * Album constructor it loads everything relating
	 * to this album from the database it does not
	 * pull the album or thumb art by default or
	 * get any of the counts.
	 */
	function __construct($album_id = 0) {

		if (!$album_id) { return false; } 

		/* Assign id for use in get_info() */
		$this->id = intval($album_id);

		/* Get the information from the db */
		$info = $this->_get_info();
	
		// Foreach what we've got
		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} 

		// Little bit of formating here
		$this->f_name = trim($info['prefix'] . ' ' . $info['name']); 

		// Additional data that we are going to need

		/*
			$this->songs		= $info['song_count'];
			$this->artist_count	= $info['artist_count'];
			$this->year		= $info['year'];
			$this->artist		= trim($info['artist_prefix'] . " " . $info['artist_name']);
			$this->artist_id	= $info['art_id'];
			$this->album		= $info['album_name'];
			$this->has_art		= $info['has_art'];
			$this->prefix 		= $info['prefix'];
		*/

		return true; 

	} //constructor

	/**
	 * _get_info
	 * This is a private function that pulls the album 
	 * from the database 
	 */
	private function _get_info() {

		// Just get the album information
		$sql = "SELECT * FROM `album` WHERE `id`='" . $this->id . "'"; 
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);

		return $results;

	} // _get_info

	/**
	 * _get_extra_info
	 * This pulls the extra information from our tables, this is a 3 table join, which is why we don't normally
	 * do it
	 */
	private function _get_extra_info() { 

		$sql = "SELECT COUNT(DISTINCT(song.artist)) as artist_count,COUNT(song.id) AS song_count,artist.name AS artist_name" . 
			",artist.prefix AS artist_prefix,album_data.art AS has_art,album_data.thumb AS has_thumb ".
			"FROM `song` " .
			"INNER JOIN `artist` ON `artist`.`id`=`song`.`artist` " .
			"LEFT JOIN `album_data` ON `album_data`.`album_id` = `song`.`album` " . 
			"WHERE `song`.`album`='$this->id' GROUP BY `song`.`album`";
		$db_results = Dba::query($sql); 

		$results = Dba::fetch_assoc($db_results); 

		if ($results['has_art']) { $results['has_art'] = 1; } 
		if ($results['has_thumb']) { $results['has_thumb'] = 1; } 

		return $results; 

	} // _get_extra_info

	/**
	 * get_songs
	 * gets the songs for this album takes an optional limit
	 * and an optional artist, if artist is passed it only gets
	 * songs with this album + specified artist
	 */
	public function get_songs($limit = 0,$artist='') { 

		$results = array();
	
		if ($artist) { 
			$artist_sql = "AND `artist`='" . Dba::escape($artist) . "'";
		} 

		$sql = "SELECT `id` FROM `song` WHERE `album`='$this->id' $artist_sql ORDER BY `track`, `title`";
		if ($limit) { $sql .= " LIMIT $limit"; }
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;

	} // get_songs

	/**
	 * has_art
	 * This returns true or false depending on if we find any art for this 
	 * album. 
	 */
	public function has_art() { 

		$sql = "SELECT `album_id` FROM `album_data` WHERE `album_id`='" . $this->id . "' AND art IS NOT NULL"; 
		$db_results = Dba::query($sql); 

		if (Dba::fetch_assoc($db_results)) { 
			$this->has_art = true; 
			return true; 
		} 

		return false; 

	} // has_art

	/**
	 * format
	 * This is the format function for this object. It sets cleaned up
	 * album information with the base required
	 * f_link, f_name
	 */
	public function format() { 

	        $web_path = Config::get('web_path');

		/* Pull the advanced information */
		$data = $this->_get_extra_info(); 
		foreach ($data as $key=>$value) { $this->$key = $value; } 
		
		/* Truncate the string if it's to long */
	        $this->f_name	= scrub_out(truncate_with_ellipse($this->name,Config::get('ellipse_threshold_album')));
		$this->f_name_link	= "<a href=\"$web_path/albums.php?action=show&amp;album=" . scrub_out($this->id) . "\" title=\"" . scrub_out($this->name) . "\">" . $this->f_name . "</a>";
		$this->f_title	= $name; 
		if ($this->artist_count == '1') { 
			$artist = scrub_out(truncate_with_ellipse(trim($this->artist_prefix . ' ' . $this->artist_name),Config::get('ellipse_threshold_album')));
		        $this->f_artist	= "<a href=\"$web_path/artists.php?action=show&amp;artist=" . $this->artist_id . "\">" . $artist . "</a>";
		}
		else {
			$this->f_artist = "<div title=\"$this->artist_count " . _('Artists') . "\">" . _('Various') . "</div>"; 
		}

		if ($this->year == '0') { 
			$this->year = "N/A";
		}

	} // format

	/**
	 * get_art
	 * This function only pulls art from the database, if thumb is passed
	 * it trys to pull the resized art instead, if resized art is found then
	 * it returns an additional resized=true in the array
	 */
	public function get_art() { 

		// Attempt to get the resized art first
		//$art = $this->get_resized_db_art(); 
		
		if (!is_array($art)) { 
			$art = $this->get_db_art(); 
		}

		return $art;

	} // get_art

	/**
	 * find_art
	 * This function searches for album art using all configured methods
	 * for the current album. There is an optional 'limit' passed that will
	 * gather up to the specified number of possible album covers.
	 * There is also an optional array of options the possible options are 
	 * ['keyword'] 		= STRING
	 * ['artist']  		= STRING
	 * ['album_name']	= STRING
	 */
	public function find_art($options=array(),$limit='') { 

		/* Create Base Vars */
		$results = array(); 

		/* Attempt to retrive the album art order */
		$config_value = Config::get('album_art_order');
                $class_methods = get_class_methods('Album');		
		
		/* If it's not set */
		if (empty($config_value)) { 
			// They don't want art!
			return array(); 
		}
		elseif (!is_array($config_value)) { 
			$config_value = array($config_value); 
		}
		
		foreach ($config_value AS $method) { 
	
			$data = array(); 
		
			$method_name = "get_" . $method . "_art";
			if (in_array($method_name,$class_methods)) { 
				// Some of these take options!
				switch ($method_name) { 
					case 'get_amazon_art':
						$data = $this->{$method_name}($options['keyword'],$limit); 
					break;
					case 'get_id3_art':
						$data = $this->{$method_name}($limit); 
					break; 
					default:
						$data = $this->{$method_name}($limit); 
					break; 
				} 

				// Add the results we got to the current set
				$total_results += count($data); 
				$results = array_merge($results,$data); 
				
				if ($total_results > $limit AND $limit > 0) { 
					return $results;
				}

			} // if the method exists

		} // end foreach

		return $results; 
		
	} // find_art

	/**
	 * get_lastfm_art
	 * This returns the art as pulled from lastFM. This doesn't require
	 * a special account, we just parse and run with it. 
	 */
	public function get_lastfm_art($limit) { 

		// Create the parser object
		$lastfm = new LastFMSearch(); 

		$raw_data = $lastfm->search($this->artist_name,$this->name); 

		if (!count($raw_data)) { return array(); } 

		$coverart = $raw_data['coverart']; 

		ksort($coverart); 
		foreach ($coverart as $key=>$value) { 
			$i++; 
			$url = $coverart[$key]; 
			$results = pathinfo($url); 
			$mime = 'image/' . $results['extension']; 
			$data[] = array('url'=>$url,'mime'=>$mime); 
			if ($i >= $limit) { return $data; } 
		} // end foreach

		return $data; 

	} // get_lastfm_art

	/*!
		@function get_id3_art
		@discussion looks for art from the id3 tags
	*/
	function get_id3_art($limit='') { 

		// grab the songs and define our results
		if (!count($this->_songs)) { 
			$this->_songs = $this->get_songs();	
		} 
		$data = array(); 

		// Foreach songs in this album
		foreach ($this->_songs as $song_id) { 
			$song = new Song($song_id); 
			// If we find a good one, stop looking
		        $getID3 = new getID3();
		        $id3 = $getID3->analyze($song->file);

			if ($id3['format_name'] == "WMA") { 
				$image = $id3['asf']['extended_content_description_object']['content_descriptors']['13'];
				$data[] = array('song'=>$song->file,'raw'=>$image['data'],'mime'=>$image['mime']);
			}
			elseif (isset($id3['id3v2']['APIC'])) { 
				// Foreach incase they have more then one 
				foreach ($id3['id3v2']['APIC'] as $image) { 
					$data[] = array('song'=>$song->file,'raw'=>$image['data'],'mime'=>$image['mime']);
				} 
			}

			if (!empty($limit) && $limit < count($data)) { 
				return $data; 
			}
			
		} // end foreach

		return $data;

	} // get_id3_art

	/**
	 * get_folder_art()
	 * returns the album art from the folder of the audio files
	 * If a limit is passed or the preferred filename is found the current results set
	 * is returned
	 */
	function get_folder_art($limit='') { 

		if (!count($this->_songs)) { 
			$this->_songs = $this->get_songs();
		} 
		$data = array(); 

		/* See if we are looking for a specific filename */
		$preferred_filename = Config::get('album_art_preferred_filename');

		// Init a horrible hack array of lameness
		$cache =array(); 
		
		/* Thanks to dromio for origional code */
		/* Added search for any .jpg, png or .gif - Vollmer */
		foreach($this->_songs as $song_id) { 
			$song = new Song($song_id);
			$dir = dirname($song->file);

			debug_event('folder_art',"Opening $dir and checking for Album Art",'3'); 

			/* Open up the directory */
	                $handle = @opendir($dir);

                	if (!is_resource($handle)) {
				Error::add('general',_('Error: Unable to open') . ' ' . $dir); 
				debug_event('read',"Error: Unable to open $dir for album art read",'2');
	                }

	                /* Recurse through this dir and create the files array */
	                while ( FALSE !== ($file = @readdir($handle)) ) {
				$extension = substr($file,strlen($file)-3,4);

				/* If it's an image file */
				if ($extension == "jpg" || $extension == "gif" || $extension == "png" || $extension == "jp2") { 

					// HACK ALERT this is to prevent duplicate filenames
					$full_filename	= $dir . '/' . $file; 
					$index		= md5($full_filename); 

					/* Make sure it's got something in it */
					if (!filesize($dir . '/' . $file)) { continue; } 

					if ($file == $preferred_filename) { 
						// If we found the preferred filename we're done, wipe out previous results
						$data = array(array('file' => $full_filename, 'mime' => 'image/' . $extension));
						return $data;
					}
					elseif (!isset($cache[$index])) {
						$data[] = array('file' => $full_filename, 'mime' => 'image/' . $extension);
					}
				
					$cache[$index] = '1'; 
				
				} // end if it's an image
				
			} // end while reading dir
			@closedir($handle);
			
			if (!empty($limit) && $limit < count($data)) { 
				return $data; 
			} 

		} // end foreach songs

		return $data;

	} // get_folder_art

	/**
	 * get_resized_db_art
	 * This looks to see if we have a resized thumbnail that we can load rather then taking
	 * the fullsized and resizing that
	 */
	public function get_resized_db_art() { 

		$id = Dba::escape($this->id); 

		$sql = "SELECT `thumb` AS `art`,`thumb_mime` AS `art_mime` FROM `album_data` WHERE `album_id`='$id'";
		$db_results = Dba::query($sql); 
		
		$results = Dba::fetch_assoc($db_results); 
		if (strlen($results['art_mime'])) { 
			$results['resized'] = true; 
		} 
		else { return false; } 

		return $results;

	} // get_resized_db_art

	/**
	 * get_db_art
	 * returns the album art from the db along with the mime type
	 */
	public function get_db_art() {

		$sql = "SELECT `art`,`art_mime` FROM `album_data` WHERE `album_id`='$this->id'";
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);

		if (!$results['art']) { return array(); } 
		
		return $results;

	} // get_db_art

	/**
	 * get_amazon_art
	 * This takes keywords and performs a search of the Amazon website
	 * for album art. It returns an array of found objects with mime/url keys
	 */
	function get_amazon_art($keywords = '',$limit='') {

		$images 	= array();
		$final_results 	= array();
		$possible_keys = array("LargeImage","MediumImage","SmallImage");
	
		// Prevent the script from timing out
		set_time_limit(0);

		if (empty($keywords)) { 		
			$keywords = $this->name;
			/* If this isn't a various album combine with artist name */
			if ($this->artist_count == '1') { $keywords .= ' ' . $this->artist; }
		}
			
		/* Create Base Vars */
		$amazon_base_urls = array();

		/* Attempt to retrive the album art order */
		$config_value = Config::get('amazon_base_urls');
               
		/* If it's not set */
		if (empty($config_value)) { 
			$amazon_base_urls = array('http://webservices.amazon.com');
		}
		elseif (!is_array($config_value)) { 
	        	array_push($amazon_base_urls,$config_value);
		}
		else { 
			$amazon_base_urls = array_merge($amazon_base_urls, Config::get('amazon_base_urls'));
		}

	       /* Foreach through the base urls that we should check */
               foreach ($amazon_base_urls AS $amazon_base) { 

		    	// Create the Search Object
	        	$amazon = new AmazonSearch(Config::get('amazon_developer_key'), $amazon_base);
			$search_results = array();

			/* Setup the needed variables */
			$max_pages_to_search = max(Config::get('max_amazon_results_pages'),$amazon->_default_results_pages);
			$pages_to_search = $max_pages_to_search; //init to max until we know better.

			// while we have pages to search 
			do {
				$raw_results = $amazon->search(array('artist'=>$artist,'album'=>$albumname,'keywords'=>$keywords)); 

				$total = count($raw_results) + count($search_results); 

				// If we've gotten more then we wanted
				if (!empty($limit) && $total > $limit) { 
					// We don't want ot re-count every loop
					$i = $total; 
					while ($i > $limit) { 
						array_pop($raw_results); 
						$i--;
					} 

					debug_event('amazon-xml',"Found $total, Limit $limit reducing and breaking from loop",'5'); 
					// Merge the results and BREAK!
					$search_results = array_merge($search_results,$raw_results); 
					break;
				} // if limit defined

				$search_results = array_merge($search_results,$raw_results);
				$pages_to_search = min($max_pages_to_search, $amazon->_maxPage);
				debug_event('amazon-xml', "Searched results page " . ($amazon->_currentPage+1) . "/" . $pages_to_search,'5');
				$amazon->_currentPage++;

			} while($amazon->_currentPage < $pages_to_search);
			

			// Only do the second search if the first actually returns something
			if (count($search_results)) { 
				$final_results = $amazon->lookup($search_results);
			}

			/* Log this if we're doin debug */
			debug_event('amazon-xml',"Searched using $keywords with " . Config::get('amazon_developer_key') . " as key " . count($final_results) . " results found",'5');

			// If we've hit our limit
			if (!empty($limit) && count($final_results) >= $limit) { 
				break; 
			} 
		
		} // end foreach

		/* Foreach through what we've found */
		foreach ($final_results as $result) { 

			/* Recurse through the images found */
			foreach ($possible_keys as $key) { 
				if (strlen($result[$key])) { 
					break;
				} 
			} // foreach

			// Rudimentary image type detection, only JPG and GIF allowed.
			if (substr($result[$key], -4 == '.jpg')) {
				$mime = "image/jpg";
			}
			elseif (substr($result[$key], -4 == '.gif')) { 
				$mime = "image/gif";
			}
			elseif (substr($result[$key], -4 == '.png')) { 
				$mime = "image/png";
			}
			else {
				/* Just go to the next result */
				continue;
			}

	                $data['url'] 	= $result[$key];
			$data['mime']	= $mime;
			
			$images[] = $data;

			if (!empty($limit)) { 
				if (count($images) >= $limit) { 
					return $images; 
				} 
			} 

                } // if we've got something
	
		return $images;

	} // get_amazon_art() 

	/**
	 * get_random_songs
	 * gets a random number, and a random assortment of songs from this album
	 */
	function get_random_songs() { 

		$sql = "SELECT `id` FROM `song` WHERE `album`='$this->id' ORDER BY RAND()";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_row($db_results)) { 
			$results[] = $r['0'];
		}

		return $results;

	} // get_random_songs

	/*!
		@function clear_art
		@discussion clears the album art from the DB
	*/
	function clear_art() { 
	
		$sql = "UPDATE album SET art=NULL, art_mime=NULL WHERE id='$this->id'";
		$db_results = mysql_query($sql, dbh());

	} // clear_art

	/*!
		@function insert_art
		@discussion this takes a string representation of an image
			and inserts it into the database. You must pass the
			mime type as well
	*/
	function insert_art($image, $mime) { 

		/* Have to disable this for Demo because people suck and try to
 		 * insert PORN :( 
		 */
		if (Config::get('demo_mode')) { return false; } 

                // Check for PHP:GD and if we have it make sure this image is of some size
        	if (function_exists('ImageCreateFromString')) {
			$im = @ImageCreateFromString($image);
			if (@imagesx($im) == 1 || @imagesy($im) == 1 && $im) {
	                	return false;
	               	}
		} // if we have PHP:GD

                // Push the image into the database
                $sql = "REPLACE INTO `album_data` SET `art` = '" . Dba::escape($image) . "'," .
                        " `art_mime` = '" . Dba::escape($mime) . "'" .
        	        ", `album_id` = '$this->id'";
	        $db_results = Dba::query($sql);

		return true;

	} // insert_art

	/**
	 * save_resized_art
	 * This takes data from a gd resize operation and saves
	 * it back into the database as a thumbnail
	 */
	public static function save_resized_art($data,$mime,$album) { 

		$data = Dba::escape($data); 
		$mime = Dba::escape($mime); 
		$album = Dba::escape($album); 

		$sql = "UPDATE `album` SET `thumb`='$data',`thumb_mime`='$mime' " . 
			"WHERE `album`.`id`='$album'";
		$db_results = Dba::query($sql); 

	} // save_resized_art

} //end of album class

?>
