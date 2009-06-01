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
 * Album Class
 * This is the class responsible for handling the Album object
 * it is related to the album table in the database.
 */
class Album extends database_object {

	/* Variables from DB */
	public $id;
	public $name;
	public $full_name; // Prefix + Name, genereated by format(); 
	public $disk; 
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
	public function __construct($id='') {

		if (!$id) { return false; } 

		/* Get the information from the db */
		$info = $this->get_info($id);
	
		// Foreach what we've got
		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} 

		// Little bit of formating here
		$this->full_name = trim($info['prefix'] . ' ' . $info['name']);

		return true; 

	} // constructor

	/**
	 * construct_from_array
	 * This is often used by the metadata class, it fills out an album object from a
	 * named array, _fake is set to true
	 */
	public static function construct_from_array($data) { 

		$album = new Album(0); 
		foreach ($data as $key=>$value) { 
			$album->$key = $value; 
		} 

		// Make sure that we tell em it's fake
		$album->_fake = true; 

		return $album; 

	} // construct_from_array

	/**
	 * build_cache
	 * This takes an array of object ids and caches all of their information
	 * with a single query
	 */
	public static function build_cache($ids,$extra=false) {

		// Nothing to do if they pass us nothing
		if (!is_array($ids) OR !count($ids)) { return false; } 

		$idlist = '(' . implode(',', $ids) . ')';

		$sql = "SELECT * FROM `album` WHERE `id` IN $idlist";
		$db_results = Dba::query($sql);
	  
		while ($row = Dba::fetch_assoc($db_results)) {
			parent::add_to_cache('album',$row['id'],$row); 
		}

		// If we're extra'ing cache the extra info as well
		if ($extra) { 
			$sql = "SELECT COUNT(DISTINCT(song.artist)) as artist_count,COUNT(song.id) AS song_count,artist.name AS artist_name" .
				",artist.prefix AS artist_prefix,album_data.art AS has_art,album_data.thumb AS has_thumb, artist.id AS artist_id,`song`.`album`".
		                "FROM `song` " .
		                "INNER JOIN `artist` ON `artist`.`id`=`song`.`artist` " .
		                "LEFT JOIN `album_data` ON `album_data`.`album_id` = `song`.`album` " .
		                "WHERE `song`.`album` IN $idlist GROUP BY `song`.`album`";

			$db_results = Dba::read($sql); 

			while ($row = Dba::fetch_assoc($db_results)) { 
		                $row['has_art'] = make_bool($row['has_art']); 
		                $row['has_thumb'] = make_bool($row['has_thumb']); 
				parent::add_to_cache('album_extra',$row['album'],$row); 
			} // while rows
		} // if extra

		return true;

	} // build_cache

	/**
	 * _get_extra_info
	 * This pulls the extra information from our tables, this is a 3 table join, which is why we don't normally
	 * do it
	 */
	private function _get_extra_info() { 

		if (parent::is_cached('album_extra',$this->id)) { 
			return parent::get_from_cache('album_extra',$this->id); 
		} 

		$sql = "SELECT COUNT(DISTINCT(song.artist)) as artist_count,COUNT(song.id) AS song_count,artist.name AS artist_name" . 
			",artist.prefix AS artist_prefix,album_data.art AS has_art,album_data.thumb AS has_thumb, artist.id AS artist_id ".
			"FROM `song` " .
			"INNER JOIN `artist` ON `artist`.`id`=`song`.`artist` " .
			"LEFT JOIN `album_data` ON `album_data`.`album_id` = `song`.`album` " . 
			"WHERE `song`.`album`='$this->id' GROUP BY `song`.`album`";
		$db_results = Dba::query($sql); 

		$results = Dba::fetch_assoc($db_results); 

		if ($results['has_art']) { $results['has_art'] = 1; } 
		if ($results['has_thumb']) { $results['has_thumb'] = 1; } 

		parent::add_to_cache('album_extra',$this->id,$results); 

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
	 * has_track
	 * This checks to see if this album has a track of the specified title
	 */
	public function has_track($title) { 

		$title = Dba::escape($title); 

		$sql = "SELECT `id` FROM `song` WHERE `album`='$this->id' AND `title`='$title'"; 
		$db_results = Dba::query($sql); 

		$data = Dba::fetch_assoc($db_results); 

		return $data; 

	} // has_track

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
	  	$this->f_name		= truncate_with_ellipsis($this->full_name,Config::get('ellipse_threshold_album'));

		$this->f_name_link	= "<a href=\"$web_path/albums.php?action=show&amp;album=" . scrub_out($this->id) . "\" title=\"" . scrub_out($this->full_name) . "\">" . $this->f_name;
		// If we've got a disk append it
		if ($this->disk) { 
			$this->f_name_link .= " <span class=\"discnb disc" .$this->disk. "\">[" . _('Disk') . " " . $this->disk . "]</span>";
		} 
		$this->f_name_link .="</a>";
		
		$this->f_link 		= $this->f_name_link; 
		$this->f_title		= $full_name; 
		if ($this->artist_count == '1') { 
			$artist = scrub_out(truncate_with_ellipsis(trim($this->artist_prefix . ' ' . $this->artist_name),Config::get('ellipse_threshold_artist')));
		        $this->f_artist_link = "<a href=\"$web_path/artists.php?action=show&amp;artist=" . $this->artist_id . "\" title=\"" . scrub_out($this->artist_name) . "\">" . $artist . "</a>";
			$this->f_artist = $artist; 
		}
		else {
			$this->f_artist_link = "<span title=\"$this->artist_count " . _('Artists') . "\">" . _('Various') . "</span>"; 
			$this->f_artist = _('Various');
		}

		if ($this->year == '0') { 
			$this->year = "N/A";
		}

		$tags = Tag::get_top_tags('album',$this->id); 
		$this->tags = $tags; 

		$this->f_tags = Tag::get_display($tags,$this->id,'album'); 	
		

		// Format the artist name to include the prefix
		$this->f_artist_name = trim($this->artist_prefix . ' ' . $this->artist_name); 

	} // format

	/**
	 * get_art
	 * This function only pulls art from the database, if thumb is passed
	 * it trys to pull the resized art instead, if resized art is found then
	 * it returns an additional resized=true in the array
	 */
	public function get_art($return_raw=false) { 

		// Attempt to get the resized art first
		if (!$return_raw) { 
			$art = $this->get_resized_db_art(); 
		} 
		
		if (!is_array($art)) { 
			$art = $this->get_db_art(); 
		}

		return $art['0'];

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
					case 'get_lastfm_art':
						$data = $this->{$method_name}($limit,$options); 
					break;
					default:
						$data = $this->{$method_name}($limit); 
					break; 
				} 

				// Add the results we got to the current set
				$total_results += count($data); 
				// HACK for PHP 5, $data must be cast as array $results = array_merge($results, (array)$data); 
				$results = array_merge($results,(array)$data); 
				
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
	public function get_lastfm_art($limit,$options='') { 

		// Create the parser object
		$lastfm = new LastFMSearch(); 

		if (is_array($options)) { 
			$artist	= $options['artist'];
			$album	= $options['album_name']; 
		} 
		else { 
			$artist = $this->artist_name; 
			$album = $this->full_name; 
		} 

		if(Config::get('proxy_host') AND Config::get('proxy_port')) {
			$proxyhost = Config::get('proxy_host');
			$proxyport = Config::get('proxy_port');
			$proxyuser = Config::get('proxy_user');
			$proxypass = Config::get('proxy_pass');
			debug_event("lastfm", "set Proxy", "5");
			$lastfm->setProxy($proxyhost, $proxyport, $proxyuser, $proxypass);
		}
		$raw_data = $lastfm->search($artist,$album); 

		if (!count($raw_data)) { return array(); } 

		$coverart = $raw_data['coverart']; 

		ksort($coverart); 
		
		foreach ($coverart as $key=>$value) { 
			$i++; 
			$url = $coverart[$key]; 

			// We need to check the URL for the /noimage/ stuff
			if (strstr($url,"/noimage/")) { 
				debug_event('LastFM','Detected as noimage, skipped ' . $url,'3'); 
				continue; 
			} 

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
			try { $id3 = $getID3->analyze($song->file); } 
			catch (Exception $error) { 
				debug_event('getid3',$error->message,'1'); 
			} 

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

					if ($extension == 'jpg') { $extension = 'jpeg'; } 

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

		$data = array(array('db_resized'=>$this->id,'raw'=>$results['art'],'mime'=>$results['art_mime']));

		return $data;

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

		$data = array(array('db'=>$this->id,'raw'=>$results['art'],'mime'=>$results['art_mime'])); 
		
		return $data;

	} // get_db_art

	/**
	 * get_amazon_art
	 * This takes keywords and performs a search of the Amazon website
	 * for album art. It returns an array of found objects with mime/url keys
	 */
	public function get_amazon_art($keywords = '',$limit='') {

		$images 	= array();
		$final_results 	= array();
		$possible_keys = array("LargeImage","MediumImage","SmallImage");
	
		// Prevent the script from timing out
		set_time_limit(0);

		if (empty($keywords)) { 		
			$keywords = $this->full_name;
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
				if(Config::get('proxy_host') AND Config::get('proxy_port')) {
					$proxyhost = Config::get('proxy_host');
					$proxyport = Config::get('proxy_port');
					$proxyuser = Config::get('proxy_user');
					$proxypass = Config::get('proxy_pass');
					debug_print("amazon", "setProxy", "5");
					$amazon->setProxy($proxyhost, $proxyport, $proxyuser, $proxypass);
				}

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
				$mime = "image/jpeg";
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

	} // get_amazon_art 

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

	/**
	 * update
	 * This function takes a key'd array of data and updates this object
	 * as needed, and then throws down with a flag
	 */
	public function update($data) { 


		$year 		= $data['year']; 
		$artist		= $data['artist']; 
		$name		= $data['name']; 
		$disk		= $data['disk'];

		$current_id = $this->id; 

		if ($artist != $this->artist_id AND $artist) { 
			// Update every song
			$songs = $this->get_songs(); 
			foreach ($songs as $song_id) { 
				Song::update_artist($artist,$song_id); 
			} 
			$updated = 1; 
			Catalog::clean_artists(); 
		} 

		$album_id = Catalog::check_album($name,$year,$disk); 
		if ($album_id != $this->id) { 
			if (!is_array($songs)) { $songs = $this->get_songs(); } 
			foreach ($songs as $song_id) { 
				Song::update_album($album_id,$song_id); 
				Song::update_year($year,$song_id);
			} 
			$current_id = $album_id; 
			$updated = 1; 
			Catalog::clean_albums(); 
		} 

		if ($updated) { 
			// Flag all songs
			foreach ($songs as $song_id) { 
				Flag::add($song_id,'song','retag','Interface Album Update'); 
				Song::update_utime($song_id); 
			} // foreach song of album
			Catalog::clean_stats(); 
		} // if updated


		return $current_id; 

	} // update

	/**
	 * clear_art
	 * clears the album art from the DB
	 */
	public function clear_art() { 
	
		$sql = "UPDATE `album_data` SET `art`=NULL, `art_mime`=NULL, `thumb`=NULL, `thumb_mime`=NULL WHERE `album_id`='$this->id'";
		$db_results = Dba::query($sql);

	} // clear_art

	/**
	 * insert_art
	 * this takes a string representation of an image
	 * and inserts it into the database. You must pass the mime type as well
	 */
	public function insert_art($image, $mime) { 

		/* Have to disable this for Demo because people suck and try to
 		 * insert PORN :( 
		 */
		if (Config::get('demo_mode')) { return false; } 

                // Check for PHP:GD and if we have it make sure this image is of some size
        	if (function_exists('ImageCreateFromString')) {
			$im = ImageCreateFromString($image);
			if (imagesx($im) <= 5 || imagesy($im) <= 5 || !$im) {
	                	return false;
	               	}
		} // if we have PHP:GD
		elseif (strlen($image) < 5) { 
			return false; 
		} 

		// Default to image/jpeg as a guess if there is no passed mime type
		$mime = $mime ? $mime : 'image/jpeg'; 

                // Push the image into the database
                $sql = "REPLACE INTO `album_data` SET `art` = '" . Dba::escape($image) . "'," .
                        " `art_mime` = '" . Dba::escape($mime) . "'" .
        	        ", `album_id` = '$this->id'," . 
			"`thumb` = NULL, `thumb_mime`=NULL";
	        $db_results = Dba::query($sql);

		return true;

	} // insert_art

	/**
	 * save_resized_art
	 * This takes data from a gd resize operation and saves
	 * it back into the database as a thumbnail
	 */
	public static function save_resized_art($data,$mime,$album) { 

		// Make sure there's actually something to save
		if (strlen($data) < '5') { return false; } 

		$data = Dba::escape($data); 
		$mime = Dba::escape($mime); 
		$album = Dba::escape($album); 

		$sql = "UPDATE `album_data` SET `thumb`='$data',`thumb_mime`='$mime' " . 
			"WHERE `album_data`.`album_id`='$album'";
		$db_results = Dba::query($sql); 

	} // save_resized_art

	/**
	 * get_random_albums
	 * This returns a random number of albums from the catalogs
	 * this is used by the index to return some 'potential' albums to play
	 */
	public static function get_random_albums($count=6) {

	        $sql = 'SELECT `id` FROM `album` ORDER BY RAND() LIMIT ' . ($count*2);
	        $db_results = Dba::query($sql);

	        $in_sql = '`album_id` IN (';

	        while ($row = Dba::fetch_assoc($db_results)) {
	                $in_sql .= "'" . $row['id'] . "',";
	                $total++;
	        }

	        if ($total < $count) { return false; }

	        $in_sql = rtrim($in_sql,',') . ')';

	        $sql = "SELECT `album_id`,ISNULL(`art`) AS `no_art` FROM `album_data` WHERE $in_sql";
	        $db_results = Dba::query($sql);
	        $results = array();

	        while ($row = Dba::fetch_assoc($db_results)) {
	                $results[$row['album_id']] = $row['no_art'];
	        } // end for
	
	        asort($results);
	        $albums = array_keys($results);
	        $results = array_slice($albums,0,$count);
	
	        return $results;

	} // get_random_albums

	/**
	 * get_image_from_source
	 * This gets an image for the album art from a source as 
	 * defined in the passed array. Because we don't know where
	 * its comming from we are a passed an array that can look like
	 * ['url']      = URL *** OPTIONAL ***
	 * ['file']     = FILENAME *** OPTIONAL ***
	 * ['raw']      = Actual Image data, already captured
	 */
	public static function get_image_from_source($data) {

	        // Already have the data, this often comes from id3tags
	        if (isset($data['raw'])) {
	                return $data['raw'];
	        }

	        // If it came from the database
	        if (isset($data['db'])) {
	                // Repull it 
	                $album_id = Dba::escape($data['db']);
	                $sql = "SELECT * FROM `album_data` WHERE `album_id`='$album_id'";
	                $db_results = Dba::query($sql);
	                $row = Dba::fetch_assoc($db_results);
	                return $row['art'];
	        } // came from the db

	        // Check to see if it's a URL
	        if (isset($data['url'])) {
	                $snoopy = new Snoopy();
					if(Config::get('proxy_host') AND Config::get('proxy_port')) {
						$snoopy->proxy_user = Config::get('proxy_host');
						$snoopy->proxy_port = Config::get('proxy_port');
						$snoopy->proxy_user = Config::get('proxy_user');
						$snoopy->proxy_pass = Config::get('proxy_pass');
					}
	                $snoopy->fetch($data['url']);
	                return $snoopy->results;
	        }

	        // Check to see if it's a FILE
	        if (isset($data['file'])) {
	                $handle = fopen($data['file'],'rb');
	                $image_data = fread($handle,filesize($data['file']));
	                fclose($handle);
	                return $image_data;
        	}
	
	        // Check to see if it is embedded in id3 of a song
	        if (isset($data['song'])) {
	                // If we find a good one, stop looking
	                $getID3 = new getID3();
	                $id3 = $getID3->analyze($data['song']);
	
	                if ($id3['format_name'] == "WMA") {
	                        return $id3['asf']['extended_content_description_object']['content_descriptors']['13']['data'];
	                }
	                elseif (isset($id3['id3v2']['APIC'])) {
	                        // Foreach incase they have more then one 
	                        foreach ($id3['id3v2']['APIC'] as $image) {
	                                return $image['data'];
	                        }
	                }
	        } // if data song

	        return false;

	} // get_image_from_source

	/**
	 * get_art_url
	 * This returns the art URL for the album
	 */
	public static function get_art_url($album_id,$sid=false) { 

		$sid = $sid ? scrub_out($sid) : session_id(); 

		$sql = "SELECT `art_mime`,`thumb_mime` FROM `album_data` WHERE `album_id`='" . Dba::escape($album_id) . "'"; 
		$db_results = Dba::read($sql); 

		$row = Dba::fetch_assoc($db_results); 

		$mime = $row['thumb_mime'] ? $row['thumb_mime'] : $row['art_mime']; 

		switch ($type) { 
			case 'image/gif': 
				$type = 'gif'; 
			break; 
			case 'image/png': 
				$type = 'png'; 
			break; 
			default:
			case 'image/jpeg': 
				$type = 'jpg'; 
			break; 
		} // end type translation

		$name = 'art.' . $type;  

		return Config::get('web_path') . '/image.php?id=' . scrub_out($album_id) . '&auth=' . $sid . '&name=' . $name;

	} // get_art_url

} //end of album class

?>
