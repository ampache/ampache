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
 * Artist Class
 */
class Artist extends database_object {

	/* Variables from DB */
	public $id;
	public $name;
	public $songs;
	public $albums;
	public $prefix;

	// Constructed vars
	public $_fake = false; // Set if construct_from_array() used

	/**
	 * Artist
	 * Artist class, for modifing a artist
	 * Takes the ID of the artist and pulls the info from the db
	 */
	public function __construct($id='') {

		/* If they failed to pass in an id, just run for it */
		if (!$id) { return false; } 	

		/* Get the information from the db */
		$info = $this->get_info($id);
			
		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} // foreach info

		return true; 

	} //constructor

	/**
	 * construct_from_array
	 * This is used by the metadata class specifically but fills out a Artist object
	 * based on a key'd array, it sets $_fake to true
	 */
	public static function construct_from_array($data) { 

		$artist = new Artist(0); 
		foreach ($data as $key=>$value) { 
			$artist->$key = $value; 
		} 

		//Ack that this is not a real object from the DB
		$artist->_fake = true; 

		return $artist;

	} // construct_from_array

	/**
	 * this attempts to build a cache of the data from the passed albums all in one query
	 */
	public static function build_cache($ids,$extra=false) {
		if(!is_array($ids) OR !count($ids)) { return false; }

		$idlist = '(' . implode(',', $ids) . ')';

		$sql = "SELECT * FROM `artist` WHERE `id` IN $idlist";
		$db_results = Dba::query($sql);

	  	while ($row = Dba::fetch_assoc($db_results)) {
	  		parent::add_to_cache('artist',$row['id'],$row); 
		}

		// If we need to also pull the extra information, this is normally only used when we are doing the human display
		if ($extra) { 
	                $sql = "SELECT `song`.`artist`, COUNT(`song`.`id`) AS `song_count`, COUNT(DISTINCT `song`.`album`) AS `album_count`, SUM(`song`.`time`) AS `time` FROM `song` " .
                        "WHERE `song`.`artist` IN $idlist GROUP BY `song`.`artist`";
	                $db_results = Dba::query($sql);

			while ($row = Dba::fetch_assoc($db_results)) { 
				parent::add_to_cache('artist_extra',$row['artist'],$row); 
			} 

		} // end if extra

		return true;

	} // build_cache

	/**
	 * get_from_name
	 * This gets an artist object based on the artist name
	 */
	public static function get_from_name($name) { 

		$name = Dba::escape($name); 
		$sql = "SELECT `id` FROM `artist` WHERE `name`='$name'"; 
		$db_results = Dba::query($sql); 

		$row = Dba::fetch_assoc($db_results); 

		$object = new Artist($row['id']); 

		return $object; 

	} // get_from_name 

	/**
	 * get_albums
	 * gets the album ids that this artist is a part
	 * of
	 */
	public function get_albums() { 

		$results = array();

		$sql = "SELECT `album`.`id` FROM album LEFT JOIN `song` ON `song`.`album`=`album`.`id` " . 
			"WHERE `song`.`artist`='$this->id' GROUP BY `album`.`id` ORDER BY `album`.`name`,`album`.`disk`,`album`.`year`";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;
		
	} // get_albums

	/** 
	 * get_songs
	 * gets the songs for this artist
	 */
	public function get_songs() { 
	
		$sql = "SELECT `song`.`id` FROM `song` WHERE `song`.`artist`='" . Dba::escape($this->id) . "' ORDER BY album, track";
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;

	} // get_songs

        /**
         * get_random_songs
	 * Gets the songs from this artist in a random order
         */
        public function get_random_songs() {

                $results = array();

                $sql = "SELECT `id` FROM `song` WHERE `artist`='$this->id' ORDER BY RAND()";
                $db_results = Dba::query($sql);

                while ($r = Dba::fetch_assoc($db_results)) {
                        $results[] = $r['id'];
                }

                return $results;

        } // get_random_songs

	/**
	 * _get_extra info
	 * This returns the extra information for the artist, this means totals etc
	 */
	private function _get_extra_info() { 

		// Try to find it in the cache and save ourselves the trouble
		if (parent::is_cached('artist_extra',$this->id)) { 
			$row = parent::get_from_cache('artist_extra',$this->id); 
		} 
		else { 
			$uid = Dba::escape($this->id); 
			$sql = "SELECT `song`.`artist`,COUNT(`song`.`id`) AS `song_count`, COUNT(DISTINCT `song`.`album`) AS `album_count`, SUM(`song`.`time`) AS `time` FROM `song` " . 
				"WHERE `song`.`artist`='$uid' GROUP BY `song`.`artist`";
			$db_results = Dba::query($sql);
			$row = Dba::fetch_assoc($db_results); 
			parent::add_to_cache('artist_extra',$row['artist'],$row); 
		} 
		
		/* Set Object Vars */
		$this->songs = $row['song_count'];
		$this->albums = $row['album_count'];
		$this->time = $row['time']; 

		return $row; 

	} // _get_extra_info

	/**
	 * format
         * this function takes an array of artist
	 * information and reformats the relevent values
	 * so they can be displayed in a table for example
	 * it changes the title into a full link.
 	 */
	public function format() {

		/* Combine prefix and name, trim then add ... if needed */
                $name = truncate_with_ellipsis(trim($this->prefix . " " . $this->name),Config::get('ellipse_threshold_artist'));
		$this->f_name = $name;
		$this->f_full_name = trim($this->prefix . " " . $this->name); 

		// If this is a fake object, we're done here
		if ($this->_fake) { return true; } 

	        $this->f_name_link = "<a href=\"" . Config::get('web_path') . "/artists.php?action=show&amp;artist=" . $this->id . "\" title=\"" . $this->full_name . "\">" . $name . "</a>";
		$this->f_link = Config::get('web_path') . '/artists.php?action=show&amp;artist=' . $this->id; 

		// Get the counts 
		$extra_info = $this->_get_extra_info(); 

		//Format the new time thingy that we just got
		$min = sprintf("%02d",(floor($extra_info['time']/60)%60)); 
		
		$sec = sprintf("%02d",($extra_info['time']%60)); 
		$hours = floor($extra_info['time']/3600);

		$this->f_time = ltrim($hours . ':' . $min . ':' . $sec,'0:'); 

		$this->tags = Tag::get_top_tags('artist',$this->id); 

		$this->f_tags = Tag::get_display($this->tags,$this->id,'artist'); 

		return true; 

	} // format

	/**
	 * update
	 * This takes a key'd array of data and updates the current artist
	 * it will flag songs as neeed
	 */
	public function update($data) { 

		// Save our current ID
		$current_id = $this->id; 

		$artist_id = Catalog::check_artist($data['name']); 

		// If it's changed we need to update
		if ($artist_id != $this->id) { 
			$songs = $this->get_songs(); 
			foreach ($songs as $song_id) { 
				Song::update_artist($artist_id,$song_id); 
			} 
			$updated = 1; 
			$current_id = $artist_id; 
			Catalog::clean_artists(); 
		} // end if it changed

		if ($updated) { 
			foreach ($songs as $song_id) { 
				Flag::add($song_id,'song','retag','Interface Artist Update'); 
				Song::update_utime($song_id); 
			} 
			Catalog::clean_stats(); 
		} // if updated

		return $current_id;

	} // update

	/**
	 * get_song_lyrics
	 * gets the lyrics of $this->song
	 * if they are not in the database, fetch using LyricWiki (SOAP) and insert
	 */
	public function get_song_lyrics($song_id, $artist_name, $song_title) {

		debug_event("lyrics", "Initialized Function", "5");
		$sql = "SELECT `song_data`.`lyrics` FROM `song_data` WHERE `song_id`='" . Dba::escape($song_id) . "'";
		$db_results = Dba::read($sql); 
		$results = Dba::fetch_assoc($db_results); 

		// Get Lyrics From id3tag (Lyrics3)
		$rs = Dba::read("SELECT `song`.`file` FROM `song` WHERE `id`='" . Dba::escape($song_id) . "'");
		$filename = Dba::fetch_row($rs);
		$vainfo = new vainfo($filename[0], '','','',$catalog->sort_pattern,$catalog->rename_pattern);
		$vainfo->get_info();
		$key = vainfo::get_tag_type($vainfo->tags);
		$tag_lyrics = vainfo::clean_tag_info($vainfo->tags,$key,$filename);

		$lyrics = $tag_lyrics['lyrics'];

		if (strlen($results['lyrics']) > 1) {
			debug_event("lyrics", "Use DB", "5");
			return html_entity_decode($results['lyrics'], ENT_QUOTES);
		} elseif (strlen($lyrics) > 1) {
			// encode lyrics utf8
			if (function_exists('mb_detect_encoding') AND function_exists('mb_convert_encoding')) {
				$enc = mb_detect_encoding($lyrics);
				if ($enc != "ASCII" OR $enc != "UTF-8") {
					$lyrics = mb_convert_encoding($lyrics, "UTF-8", $enc);
				}
			}
			$sql = "UPDATE `song_data` SET `lyrics` = '" . Dba::escape(htmlspecialchars($lyrics, ENT_QUOTES)) . "' WHERE `song_id`='" . Dba::escape($song_id) . "'";
			$db_results = Dba::write($sql);

			debug_event("lyrics", "Use id3v2 tag (USLT or lyrics3)", "5");
			return $lyrics;
		}
		else {
			debug_event("lyrics", "Start to get from lyricswiki", "5");
			$proxyhost = $proxyport = $proxyuser = $proxypass = false;
			if(Config::get('proxy_host') AND Config::get('proxy_port')) {
				$proxyhost = Config::get('proxy_host');
				$proxyport = Config::get('proxy_port');
				debug_event("lyrics", "Use proxy server: $proxyhost:$proxyport", '5');
				if(Config::get('proxy_user')) { $proxyuser = Config::get('proxy_user'); }
				if(Config::get('proxy_pass')) { $proxypass = Config::get('proxy_pass'); }
			}
			$client = new nusoap_client('http://lyricwiki.org/server.php?wsdl', 'wsdl', $proxyhost, $proxyport, $proxyuser, $proxypass);

			$err = $client->getError();

			if ($err) { return $results =  $err; }

			// sall SOAP method
			$result = $client->call("getSongResult", array("artist" => $artist_name, "song" => $song_title ));
			// check for fault
			if ($client->fault) {
				debug_event("lyrics", "Can't get lyrics", "1");
				return $results = "<h2>" . _('Fault') . "</h2>" . print_r($result);
			}
			else {
				// check for errors
				$err = $client->getError();

				if ($err) {
					debug_event("lyrics", "Getting error: $err", "1");
					return $results = "<h2>" . _('Error') . "</h2>" . $err;
				}
				else {
					// if returned "Not found" do not add
					if($result['lyrics'] == "Not found") {
						$sorry = _('Sorry Lyrics Not Found.');
						return $sorry;
					}
					else {
						$lyrics = str_replace(array("\r\n","\r","\n"), '<br />',strip_tags($result['lyrics']));
						// since we got lyrics, might as well add them to the database now (for future use)
						$sql = "UPDATE `song_data` SET `lyrics` = '" . Dba::escape(htmlspecialchars($lyrics, ENT_QUOTES)) . "' WHERE `song_id`='" . Dba::escape($song_id) . "'";
						$db_results = Dba::write($sql);
						// display result (lyrics)
						debug_event("lyrics", "get successful", "5");
						return $results = strip_tags($result['lyrics']);
					}
				}
			}
		}
	} // get_song_lyrics
} // end of artist class
?>
