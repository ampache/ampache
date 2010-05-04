<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
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
 * Art
 * This class handles the images / artwork in ampache
 * This was initially in the album class, but was pulled out
 * to be more general, and apply to albums, artists, movies etc
 */
class Art extends database_object {

	public $type;
	public $uid; // UID of the object not ID because it's not the ART.ID
	public $raw; // Raw art data
	public $raw_mime;
	
	public $thumb;
	public $thumb_mime;
	

	/**
	 * Constructor
	 * Art constructor, takes the UID of the object and the
	 * object type.
	 */
	public function __construct($uid,$type) {

		$this->type = Art::validate_type($type);
		$this->uid = $uid; 		

	} // constructor

	/**
	 * validate_type
	 * This validates the type
	 */
	public static function validate_type($type) {

		switch ($type) {
			case 'album':
			case 'artist':
			case 'video':
				return $type;
			break;
			default:
				return 'album';
			break;
		}

	} // validate_type

	/**
	 * extension
	 * This returns the file extension for the currently loaded art
	 */
	public static function extension($mime) {
		
		$data = explode("/",$mime);
		$extension = $data['1'];

		if ($extension == 'jpeg') { $extension = 'jpg'; }

		return $extension;

	} // extension

	/**
	 * get
	 * This returns the art for our current object, this can
	 * look in the database and will return the thumb if it
	 * exists, if it doesn't depending on settings it will try
	 * to create it.
	 */
	public function get($raw=false) {

		// Get the data either way
		if (!$this->get_db()) {
			return false;
		}

		if ($raw) {
			return $this->raw;
		}
		else {
			return $this->thumb;
		} 		
			
	} // get


	/**
	 * get_db
	 * This pulls the information out from the database, depending
	 * on if we want to resize and if there is not a thumbnail go
	 * ahead and try to resize
	 */
	public function get_db() {

		$type = Dba::escape($this->type);
		$id = Dba::escape($this->uid);

		$sql = "SELECT `thumb`,`thumb_mime`,`art`,`art_mime` FROM `" . $type . "_data` WHERE `" . $type . "_id`='$id'";
		$db_results = Dba::read($sql);
		
		$results = Dba::fetch_assoc($db_results);

		// If we get nothing or there is non mime type return false
		if (!count($results) OR !strlen($results['art_mime'])) { return false; }

		// If there is no thumb, and we want thumbs
		if (!strlen($results['thumb_mime']) AND Config::get('resize_images')) {
			$data = $this->generate_thumb($results['art'],array('width'=>275,'height'=>275),$results['art_mime']);
			// If it works save it!
			if ($data) {
				$this->save_thumb($data['thumb'],$data['thumb_mime']);
				$results['thumb'] = $data['thumb'];
				$results['thumb_mime'] = $data['thumb_mime'];
			}
			else {
				debug_event('Art','Unable to retrieve/generate thumbnail for ' . $type . '::' . $id,1);
			}
		} // if no thumb, but art and we want to resize

		$this->raw = $results['art'];
		$this->raw_mime = $results['art_mime'];
		$this->thumb = $results['thumb'];
		$this->thumb_mime = $results['thumb_mime'];

		return true;

	} // get_db

	/**
	 * insert
	 * This takes the string representation of an image and inserts it into the database. You
	 * must also pass the mime type
	 */
	public function insert($source,$mime) {

		// Disabled in demo mode cause people suck and upload porn
		if (Config::get('demo_mode')) { return false; }

		// Do a low impact test is this image of any size?
		if (strlen($source) < 10) {
			debug_event('Art','Invalid Image passed, not inserting',1);
			return false;
		}

		// Check to make sure PHP:GD exists if so we can sanity check this
		// image
		if (function_exists('ImageCreateFromString')) {
			$image = ImageCreateFromString($source);
			if (!$image OR imagesx($image) < 5 OR imagesy($image) < 5) {
				debug_event('Art','Image failed PHP-GD test, not inserting',1);
				return false;
			}
		} // if we have GD

		// Default to image/jpeg if they don't pass anything
		$mime = $mime ? $mime : 'image/jpeg';

		$image = Dba::escape($source);
		$mime = Dba::escape($mime);
		$uid = Dba::escape($this->uid);
		$type = Dba::escape($this->type);

		// Insert it!
		$sql = "REPLACE INTO `" . $type . "_data` SET `art`='$image',`art_mime`='$mime', `" . $type . "_id`='$uid', " .
			"`thumb`=NULL, `thumb_mime`=NULL";
		$db_results = Dba::write($sql);

		return true;

	} // insert

	/**
	 * clear
	 * This resets the art in the database
	 */
	public function reset() {

		$type = Dba::escape($this->type);
		$uid = Dba::escape($this->uid);

		$sql = "UPDATE `" . $type . "_data` SET `art`=NULL, `art_mime`=NULL, `thumb`=NULL, `thumb_mime`=NULL " .
			"WHERE `" . $type . "_id`='$uid'";
		$db_results = Dba::write($sql);

	} // clear

	/**
	 * save_thumb
	 * This saves the thumbnail that we're passing
	 */
	public function save_thumb($source,$mime) {

		// Quick sanity check
		if (strlen($source) < 5 OR !strlen($mime)) {
			debug_event('Art','Unable to save thumbnail, invalid data passed',1);
			return false;
		}
		
		$source = Dba::escape($source);
		$mime = Dba::escape($mime);
		$uid = Dba::escape($this->uid);
		$type = Dba::escape($this->type);
		
		$sql = "UPDATE `" . $type . "_data` SET `thumb`='$source', `thumb_mime`='$mime' " .
			"WHERE `" . $type . "_id`='$uid'";
		$db_results = Dba::write($sql);

	} // save_thumb

	/**
	 * generate_thumb
	 * Automatically resizes the image for thumbnail viewing.
	 * Only works on gif/jpg/png/bmp. Fails if PHP-GD isn't available
	 * or lacks support for the requested image type.
	 */
	public function generate_thumb($image,$size,$mime) {

		$data = explode("/",$mime);
		$type = strtolower($data['1']);

		if (!function_exists('gd_info')) {
			debug_event('Art','PHP-GD Not found - unable to resize art',1);
			return false;
		}

		// Check and make sure we can resize what you've asked us to	
		if (($type == 'jpg' OR $type == 'jpeg') AND !(imagetypes() & IMG_JPG)) {
			debug_event('Art','PHP-GD Does not support JPGs - unable to resize',1);
			return false;
		}
		if ($type == 'png' AND !imagetypes() & IMG_PNG) {
			debug_event('Art','PHP-GD Does not support PNGs - unable to resize',1);
			return false;
		}
		if ($type == 'gif' AND !imagetypes() & IMG_GIF) {
			debug_event('Art','PHP-GD Does not support GIFs - unable to resize',1);
			return false;
		}
		if ($type == 'bmp' AND !imagetypes() & IMG_WBMP) {
			debug_event('Art','PHP-GD Does not support BMPs - unable to resize',1);
			return false;
		}
	
		$source = imagecreatefromstring($image); 	

		if (!$source) {
			debug_event('Art','Failed to create Image from string - Source Image is damaged / malformed',1);
			return false;
		}

		$source_size = array('height'=>imagesy($source),'width'=>imagesx($source));

		// Create a new blank image of the correct size
		$thumbnail = imagecreatetruecolor($size['width'],$size['height']);

		if (!imagecopyresampled($thumbnail,$source,0,0,0,0,$size['width'],$size['height'],$source_size['width'],$source_size['height'])) {
			debug_event('Art','Unable to create resized image',1);
			return false;
		}

		// Start output buffer
		ob_start();
		
		// Generate the image to our OB
		switch ($type) {
			case 'jpg':
			case 'jpeg':
				imagejpeg($thumbnail,null,75);
				$mime_type = image_type_to_mime_type(IMAGETYPE_JPEG);
			break;
			case 'gif':
				imagegif($thumbnail);
				$mime_type = image_type_to_mime_type(IMAGETYPE_GIF);
			break;
			// Turn bmps into pngs
			case 'bmp':
				$type = 'png';
			case 'png':
				imagepng($thumbnail);
				$mime_type = image_type_to_mime_type(IMAGETYPE_PNG);
			break;
		} // resized
		
		$data = ob_get_contents();
		ob_end_clean();
	
		if (!strlen($data)) {
			debug_event('Art','Unknown Error resizing art',1);
			return false;
		}

		return array('thumb'=>$data,'thumb_mime'=>$mime_type);
			
	} // generate_thumb

	/**
	 * get_from_source
	 * This gets an image for the album art from a source as
	 * defined in the passed array. Because we don't know where
	 * its comming from we are a passed an array that can look like
	 * ['url']      = URL *** OPTIONAL ***
	 * ['file']     = FILENAME *** OPTIONAL ***
	 * ['raw']      = Actual Image data, already captured
	 */
	public function get_from_source($data) {

		// Already have the data, this often comes from id3tags
		if (isset($data['raw'])) {
			return $data['raw'];
		}

		// If it came from the database
		if (isset($data['db'])) {
			// Repull it
			$uid = Dba::escape($data['db']);
			$type = Dba::escape($this->type);

			$sql = "SELECT * FROM `" . $type . "_data` WHERE `" . $type . "_id`='$uid'";
			$db_results = Dba::read($sql);
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

	} // get_from_source

	/**
	 * url
	 * This returns the constructed URL for the art in question
	 */
	public static function url($uid,$type,$sid=false) {

		$sid = $sid ? scrub_out($sid) : scrub_out(session_id());
		$type = self::validate_type($type);

		$type = Dba::escape($type);
		$uid = Dba::escape($uid);

		$sql = "SELECT `art_mime`,`thumb_mime` FROM `" . $type . "_data` WHERE `" . $type . "_id`='$uid'";
		$db_results = Dba::read($sql);

		$row = Dba::fetch_assoc($db_results);

		$mime = $row['thumb_mime'] ? $row['thumb_mime'] : $row['art_mime'];
		$extension = self::extension($mime);
		
		$name = 'art.' . $extension;
		$url = Config::get('web_path') . '/image.php?id=' . scrub_out($uid) . 'object_type=' . scrub_out($type) . '&auth=' . $sid . '&name=' . $name;
		
		return $url; 	

	} // url

	/**
	 * gather
	 * This tries to get the art in question
	 */
	public function gather($options=array(),$limit) {

		// Define vars
		$results = array();

		switch ($this->type) {
			case 'album':
				$allowed_methods = array('lastfm','folder','amazon','google','musicbrainz','tag');
			break;
			case 'artist':
				$allowed_methods = array();
			break;
			case 'video':
				$allowed_methods = array();
			break;
		}
	
		$config = Config::get('art_order'); 	
		$methods = get_class_methods('Art');

		/* If it's not set */
		if (empty($config)) {
			// They don't want art!
			return array();
		}
		elseif (!is_array($config)) {
			$config = array($config);
		}

		debug_event('Art','Searching using:' . print_r($config,1),3);

		foreach ($config AS $method) {

			$data = array();

			$method_name = "gather_" . $method;
			if (in_array($method_name,$methods)) {
				// Some of these take options!
				switch ($method_name) {
					case 'gather_amazon':
						$data = $this->{$method_name}($options['keyword'],$limit);
					break;
					case 'gather_lastfm':
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

	} // gather


	///////////////////////////////////////////////////////////////////////
	// Art Methods
	///////////////////////////////////////////////////////////////////////
	
	/**
	 * gather_musicbrainz
	 * This function retrives art based on MusicBrainz' Advanced Relationships
	 */
	public function gather_musicbrainz($limit=0) {
		$images	 = array();
		$num_found      = 0;
		$mbquery	= new MusicBrainzQuery();

		if ($this->mbid) {
			debug_event('mbz-gatherart', "Album MBID: " . $this->mbid, '5');
		}
		else {
			return $images;
		}

		$includes = new mbReleaseIncludes();
		try {
			$release = $mbquery->getReleaseByID($this->mbid, $includes->urlRelations());
		} catch (Exception $e) {
			return $images;
		}

		$asin = $release->getAsin();

		if ($asin) {
			debug_event('mbz-gatherart', "Found ASIN: " . $asin, '5');
			$base_urls = array(
				"01" => "ec1.images-amazon.com",
				"02" => "ec1.images-amazon.com",
				"03" => "ec2.images-amazon.com",
				"08" => "ec1.images-amazon.com",
				"09" => "ec1.images-amazon.com",
			);
			foreach ($base_urls as $server_num => $base_url) {
				// to avoid complicating things even further, we only look for large cover art
				$url = 'http://' . $base_url . '/images/P/' . $asin . '.' . $server_num . '.LZZZZZZZ.jpg';
				debug_event('mbz-gatherart', "Evaluating Amazon URL: " . $url, '5');
				$snoopy = new Snoopy();
				if(Config::get('proxy_host') AND Config::get('proxy_port')) {
					$snoopy->proxy_user = Config::get('proxy_host');
					$snoopy->proxy_port = Config::get('proxy_port');
					$snoopy->proxy_user = Config::get('proxy_user');
					$snoopy->proxy_pass = Config::get('proxy_pass');
				}
				if ($snoopy->fetch($url)) {
					$num_found++;
					debug_event('mbz-gatherart', "Amazon URL added: " . $url, '5');
					$images[] = array(
						'url'  => $url,
						'mime' => 'image/jpeg',
					);
					if ($num_found >= $limit) {
						return $images;
					}
				}
			}
		}
		// The next bit is based directly on the MusicBrainz server code that displays cover art.
		// I'm leaving in the releaseuri info for the moment, though it's not going to be used.
		$coverartsites[] = array(
			name	    => "CD Baby",
			domain	  => "cdbaby.com",
			regexp	  => '@http://cdbaby\.com/cd/(\w)(\w)(\w*)@',
			imguri	  => 'http://cdbaby.name/$matches[1]/$matches[2]/$matches[1]$matches[2]$matches[3].jpg',
			releaseuri      => 'http://cdbaby.com/cd/$matches[1]$matches[2]$matches[3]/from/musicbrainz',
		);
		$coverartsites[] = array(
			name	    => "CD Baby",
			domain	  => "cdbaby.name",
			regexp	  => "@http://cdbaby\.name/([a-z0-9])/([a-z0-9])/([A-Za-z0-9]*).jpg@",
			imguri	  => 'http://cdbaby.name/$matches[1]/$matches[2]/$matches[3].jpg',
			releaseuri      => 'http://cdbaby.com/cd/$matches[3]/from/musicbrainz',
		);
		$coverartsites[] = array(
			name	    => 'archive.org',
			domain	  => 'archive.org',
			regexp	  => '/^(.*\.(jpg|jpeg|png|gif))$/',
			imguri	  => '$matches[1]',
			releaseuri      => '',
		);
		$coverartsites[] = array(
			name	    => "Jamendo",
			domain	  => "www.jamendo.com",
			regexp	  => '/http://www\.jamendo\.com/(\w\w/)?album/(\d+)/',
			imguri	  => 'http://img.jamendo.com/albums/$matches[2]/covers/1.200.jpg',
			releaseuri      => 'http://www.jamendo.com/album/$matches[2]',
		);
		$coverartsites[] = array(
			name	    => '8bitpeoples.com',
			domain	  => '8bitpeoples.com',
			regexp	  => '/^(.*)$/',
			imguri	  => '$matches[1]',
			releaseuri      => '',
		);
		$coverartsites[] = array(
			name	    => 'Encyclopédisque',
			domain	  => 'encyclopedisque.fr',
			regexp	  => '/http://www.encyclopedisque.fr/images/imgdb/(thumb250|main)/(\d+).jpg/',
			imguri	  => 'http://www.encyclopedisque.fr/images/imgdb/thumb250/$matches[2].jpg',
			releaseuri      => 'http://www.encyclopedisque.fr/',
		);
		$coverartsites[] = array(
			name	    => 'Thastrom',
			domain	  => 'www.thastrom.se',
			regexp	  => '/^(.*)$/',
			imguri	  => '$matches[1]',
			releaseuri      => '',
		);
		$coverartsites[] = array(
			name	    => 'Universal Poplab',
			domain	  => 'www.universalpoplab.com',
			regexp	  => '/^(.*)$/',
			imguri	  => '$matches[1]',
			releaseuri      => '',
		);
		foreach ($release->getRelations($mbRelation->TO_URL) as $ar) {
			$arurl = $ar->getTargetId();
			debug_event('mbz-gatherart', "Found URL AR: " . $arurl , '5');
			foreach ($coverartsites as $casite) {
				if (strstr($arurl, $casite['domain'])) {
					debug_event('mbz-gatherart', "Matched coverart site: " . $casite['name'], '5');
					if (preg_match($casite['regexp'], $arurl, $matches) == 1) {
						$num_found++;
						eval("\$url = \"$casite[imguri]\";");
						debug_event('mbz-gatherart', "Generated URL added: " . $url, '5');
						$images[] = array(
							'url'  => $url,
							'mime' => 'image/jpeg',
						);
						if ($num_found >= $limit) {
							return $images;
						}
					}
				}
			} // end foreach coverart sites
		} // end foreach

		return $images;

	} // gather_musicbrainz

	/**
	 * gather_amazon
	 * This takes keywords and performs a search of the AMazon website
	 * for the art. It returns an array of found objects with mime/url keys
	 */
	public function gather_amazon($keywords='',$limit=5) {


		$images	 = array();
		$final_results  = array();
		$possible_keys = array("LargeImage","MediumImage","SmallImage");

		// Prevent the script from timing out
		set_time_limit(0);

		if (empty($keywords)) {
			$keywords = $this->full_name;
			/* If this isn't a various album combine with artist name */
			if ($this->artist_count == '1') { $keywords .= ' ' . $this->artist_name; }
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
			$amazon = new AmazonSearch(Config::get('amazon_developer_public_key'), Config::get('amazon_developer_private_key'), $amazon_base);
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
			debug_event('amazon-xml',"Searched using $keywords with " . Config::get('amazon_developer_key') . " as key " . count($final_results),1);

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

			$data['url']    = $result[$key];
			$data['mime']   = $mime;

			$images[] = $data;

			if (!empty($limit)) {
				if (count($images) >= $limit) {
					return $images;
				}
			}

		} // if we've got something

		return $images;

	} // gather_amazon

	/**
	 * gather_folder
	 * This returns the art from the folder of the files
	 * If a limit is passed or the preferred filename is found the current results set
	 * is returned
	 */
	public function gather_folder($limit=5) {

		$media = new Album($this->uid);
		$songs = $media->get_songs();
		$data = array();

		/* See if we are looking for a specific filename */
		$preferred_filename = Config::get('album_art_preferred_filename');

		// Init a horrible hack array of lameness
		$cache =array();

		/* Thanks to dromio for origional code */
		/* Added search for any .jpg, png or .gif - Vollmer */
		foreach($songs as $song_id) {
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
				if ($extension == "jpg" || $extension == "gif" || $extension == "png" || $extension == "jp2" || $extension == "bmp") {

					if ($extension == 'jpg') { $extension = 'jpeg'; }

					// HACK ALERT this is to prevent duplicate filenames
					$full_filename  = $dir . '/' . $file;
					$index	  = md5($full_filename);

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

	} // gather_folder

	/**
	 * gather_tags
	 * This looks for the art in the meta-tags of the file
	 * itself
	 */
	public function gather_tags($limit=5) {

		// We need the filenames
		$album = new Album($this->uid);

		// grab the songs and define our results
		$songs = $album->get_songs();
		$data = array();

		// Foreach songs in this album
		foreach ($songs as $song_id) {
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

	} // gather_tags

	/**
	 * gather_google
	 * Raw google search to retrive the art, not very reliable
	 */
	public function gather_google($limit=5) {

		$images = array();
		$media = new $this->type($this->uid);
		$media->format();

		$search = $media->full_name;

		if ($media->artist_count == '1')
			$search = $media->artist_name . ', ' . $search;

		$search = rawurlencode($search);

		$size = '&imgsz=m'; // Medium
		//$size = '&imgsz=l'; // Large

		$html = file_get_contents("http://images.google.com/images?source=hp&q=$search&oq=&um=1&ie=UTF-8&sa=N&tab=wi&start=0&tbo=1$size");

		if(preg_match_all("|\ssrc\=\"(http.+?)\"|", $html, $matches, PREG_PATTERN_ORDER))
			foreach ($matches[1] as $match) {
				$extension = "image/jpeg";

				if (strrpos($extension, '.') !== false) $extension = substr($extension, strrpos($extension, '.') + 1);

				$images[] = array('url' => $match, 'mime' => $extension);
			}

		return $images;

	} // gather_google

	/**
	 * gather_lastfm
	 * This returns the art from lastfm. It doesn't require an account currently
	 * but may in the future
	 */
	public function gather_lastfm($limit,$options=false) {

		// Create the parser object
		$lastfm = new LastFMSearch();

		switch ($this->type) {
			case 'album':
				if (is_array($options)) {
					$artist = $options['artist'];
					$album  = $options['album_name'];
				}
				else {
					$media = new Album($this->uid);
					$media->format();
					$artist = $media->artist_name;
					$album = $media->full_name;
				}
			break;
		}

		if(Config::get('proxy_host') AND Config::get('proxy_port')) {
			$proxyhost = Config::get('proxy_host');
			$proxyport = Config::get('proxy_port');
			$proxyuser = Config::get('proxy_user');
			$proxypass = Config::get('proxy_pass');
			debug_event("lastfm", "set Proxy", "5");
			$lastfm->setProxy($proxyhost, $proxyport, $proxyuser, $proxypass);
		}
		$raw_data = $lastfm->album_search($artist,$album);

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

	} // gather_lastfm

} // Art
