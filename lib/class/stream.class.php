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
 * Stream
 * This class is used to generate the Playlists and pass them on
 * With Localplay this actually just sends the commands to the localplay
 * module in question. It has two sources for data
 * songs (array of ids) and urls (array of full urls)
 */
class Stream {

	/* Variables from DB */
	public $type;
	public $web_path;
	public $media = array();
	public $urls  = array(); 
	public $sess;
	public $user_id; 

	// Generate once an object is constructed
	public static $session; 

	// Let's us tell if the session has been activated
	private static $session_inserted; 

	/**
	 * Constructor for the stream class takes a type and an array
	 * of song ids
	 */
	public function __construct($type='m3u', $media_ids) {

		$this->type = $type;
		$this->media = $media_ids;
		$this->user_id = $GLOBALS['user']->id;
		
		if (!is_array($this->media)) { settype($this->media,'array'); } 

	} // Constructor

	/**
	 * start
	 *runs this and depending on the type passed it will
	 *call the correct function
	 */
	public function start() {

		if (!count($this->media) AND !count($this->urls)) { 
			debug_event('stream','Error: No Songs Passed on ' . $this->type . ' stream','2');
			return false; 
		}

		// We're starting insert the session into session_stream
		if (!self::get_session()) { 
			debug_event('stream','Session Insertion failure, aborting','3'); 
			return false; 
		}

		$methods = get_class_methods('Stream');
		$create_function = "create_" . $this->type;	

		// If in the class, call it
                if (in_array($create_function,$methods)) {
	                $this->{$create_function}();
                }
		// Assume M3u incase they've pooched the type
		else { 
			$this->create_m3u();
		}

	} // start

	/**
	 * add_urls
	 * Add an array of urls, it may be a single one who knows, this 
	 * is used for things that aren't coming from media objects
	 */
	public function add_urls($urls=array()) { 

		if (!is_array($urls)) { return false; } 
		
		$this->urls = array_merge($urls,$this->urls); 

	} // manual_url_add

	/**
	 * get_session
	 * This returns the current stream session
	 */
	public static function get_session() { 

		if (!self::$session_inserted) { 
			self::insert_session(self::$session);
		} 

		return self::$session; 

	} // get_session

	/**
	 * set_session
	 * This overrides the normal session value, without adding
	 * an additional session into the database, should be called
	 * with care
	 */
	public static function set_session($sid) { 

		self::$session_inserted = true; 
		self::$session=$sid; 

	} // set_session

	/**
	 * insert_session
	 * This inserts a row into the session_stream table
	 */
	public static function insert_session($sid='',$uid='') { 

		$sid = $sid ? Dba::escape($sid) : Dba::escape(self::$session); 
		$uid = $uid ? Dba::escape($uid) : Dba::escape($GLOBALS['user']->id); 

		$expire = time() + Config::get('stream_length'); 

		$sql = "INSERT INTO `session_stream` (`id`,`expire`,`user`) " . 
			"VALUES('$sid','$expire','$uid')"; 
		$db_results = Dba::query($sql); 

		if (!$db_results) { return false; } 

		self::$session_inserted = true; 

		return true; 

	} // insert_session

	/**
	 * session_exists
	 * This checks to see if the passed stream session exists and is valid 
	 */
	public static function session_exists($sid) { 

		$sid 	= Dba::escape($sid); 
		$time	= time(); 

		$sql = "SELECT * FROM `session_stream` WHERE `id`='$sid' AND `expire` > '$time'"; 
		$db_results = Dba::query($sql); 

		if ($row = Dba::fetch_assoc($db_results)) { 
			return true; 
		} 
		 
		return false; 

	} // session_exists

	/**
	 * gc_session
	 * This function performes the garbage collection stuff, run on extend and on now playing refresh
	 * There is an array of agents that we will never GC because of their nature, MPD being the best example
	 */
	public static function gc_session($ip='',$agent='',$uid='',$sid='') { 

		$append_array = array('MPD'); 

		$time = time(); 
		$sql = "DELETE FROM `session_stream` WHERE `expire` < '$time'"; 
		$db_results = Dba::query($sql); 
		
		foreach ($append_array as $append_agent) { 
			if (strstr(strtoupper($agent),$append_agent)) { 
				// We're done here jump ship!
				return true; 
			} 
		} // end foreach

		// We need all of this to run this query
		if ($ip AND $agent AND $uid AND $sid) { 
			$sql = "DELETE FROM `session_stream` WHERE `ip`='$ip' AND `agent`='$agent' AND `user`='$uid' AND `id` != '$sid'"; 
			$db_results = Dba::query($sql); 
		} 

	} // gc_session 

	/**
	 * extend_session
	 * This takes the passed sid and does a replace into also setting the user
	 * agent and IP also do a little GC in this function
	 */
	public static function extend_session($sid,$uid) { 

		$expire = time() + Config::get('stream_length'); 
		$sid 	= Dba::escape($sid); 
		$agent	= Dba::escape($_SERVER['HTTP_USER_AGENT']); 
		$ip	= Dba::escape(inet_pton($_SERVER['REMOTE_ADDR'])); 
		$uid	= Dba::escape($uid); 

		$sql = "UPDATE `session_stream` SET `expire`='$expire', `agent`='$agent', `ip`='$ip' " . 
			"WHERE `id`='$sid'"; 
		$db_results = Dba::query($sql); 

		self::gc_session($ip,$agent,$uid,$sid); 

		return true; 

	} // extend_session

	/**
	 * create_simplem3u
	 * this creates a simple m3u without any of the extended information
	 */
	public function create_simple_m3u() {

		header("Cache-control: public");
		header("Content-Disposition: filename=ampache_playlist.m3u");
		header("Content-Type: audio/x-mpegurl;");

		// Flip for the poping!
		asort($this->urls); 

		/* Foreach songs */
		foreach ($this->media as $element) { 
			$type = array_shift($element);
			echo call_user_func(array($type,'play_url'),array_shift($element)) . "\n"; 
		} // end foreach

		/* Foreach the additional URLs */
		foreach ($this->urls as $url) { 
			echo "$url\n";
		}

	} // simple_m3u

	/**
	 * create_m3u
	 * creates an m3u file, this includes the EXTINFO and as such can be
	 * large with very long playlsits
	 */
	public function create_m3u() { 

	        // Send the client an m3u playlist
	        header("Cache-control: public");
	        header("Content-Disposition: filename=ampache_playlist.m3u");
	        header("Content-Type: audio/x-mpegurl;");
	        echo "#EXTM3U\n";

		// Foreach the songs in this stream object
	        foreach ($this->media as $element) {
			$type = array_shift($element); 
			$media = new $type(array_shift($element)); 
			$media->format(); 
			switch ($type) { 
				case 'song': 
					echo "#EXTINF:$media->time," . $media->f_artist_full . " - " . $media->title . "\n";
				break;
				case 'video': 
					echo "#EXTINF: Video - $media->title\n";
				break;
				case 'radio': 
					echo "#EXTINF: Radio - $media->name [$media->frequency] ($media->site_url)\n"; 
				break; 
				case 'random': 
					echo "#EXTINF:Random URL\n"; 
				break; 
				default: 
					echo "#EXTINF:URL-Add\n";
				break;
			} 
			echo call_user_func(array($type,'play_url'),$media->id) . "\n";  
                } // end foreach

		/* Foreach URLS */
		foreach ($this->urls as $url) { 
			echo "#EXTINF: URL-Add\n";
			echo $url . "\n";
		}

	} // create_m3u

	/**
 	 * create_pls
	 * This creates a new pls file from an array of songs and
	 * urls, exciting I know
	 */
	public function create_pls() { 

		/* Count entries */
		$total_entries = count($this->media) + count($this->urls); 

		// Send the client a pls playlist
		header("Cache-control: public");
		header("Content-Disposition: filename=ampache_playlist.pls");
		header("Content-Type: audio/x-scpls;");
		echo "[Playlist]\n";
		echo "NumberOfEntries=$total_entries\n";
		foreach ($this->media as $element) { 
			$i++;
			$type = array_shift($element); 
			$media = new $type(array_shift($element)); 
			$media->format(); 
			switch ($type) { 
				case 'song': 
					$name = $media->f_artist_full . " - " . $media->title . "." . $media->type;
					$length = $media->time; 
				break; 
				default: 
					$name = 'URL-Add'; 
					$length='-1'; 
				break; 
			}  

			$url = call_user_func(array($type,'play_url'),$media->id);
			echo "File" . $i . "=$url\n";
			echo "Title" . $i . "=$name\n";
			echo "Length" . $i . "=$length\n";
		} // end foreach songs	

		/* Foreach Additional URLs */
		foreach ($this->urls as $url) { 
			$i++;
			echo "File" . $i ."=$url\n";
			echo "Title". $i . "=AddedURL\n";
			echo "Length" . $i . "=-1\n";
		} // end foreach urls

		echo "Version=2\n";

	} // create_pls

	/**
	 * create_asx
	 * creates an ASX playlist (Thx Samir Kuthiala) This should really only be used
	 * if all of the content is ASF files. 
	 */
	public function create_asx() { 

	        header("Cache-control: public");
        	header("Content-Disposition: filename=ampache_playlist.asx");
		header("Content-Type: video/x-ms-wmv;");
 
		echo "<ASX version = \"3.0\" BANNERBAR=\"AUTO\">\n";
                echo "<TITLE>Ampache ASX Playlist</TITLE>";
                
		foreach ($this->media as $element) {
			$type = array_shift($element); 
			$media = new $type(array_shift($element)); 
			$media->format(); 
			switch ($type) { 
				case 'song': 
					$name = $media->f_album_full . " - " . $media->title . "." . $media->type;
					$author = $media->f_artist_full; 
				break; 
				default:
					$author = 'Ampache'; 
					$name = 'URL-Add';
				break; 
			} // end switch 
			$url = call_user_func(array($type,'play_url'),$media->id); 

                        echo "<ENTRY>\n";
                        echo "<TITLE>$name</TITLE>\n";
			echo "<AUTHOR>$author</AUTHOR>\n";
			echo "\t\t<COPYRIGHT>".$media->year."</COPYRIGHT>\n";
			echo "\t\t<DURATION VALUE=\"00:00:".$media->time."\" />\n";
			echo "\t\t<PARAM NAME=\"Album\" Value=\"".$media->f_album_full."\" />\n";
			echo "\t\t<PARAM NAME=\"Genre\" Value=\"".$media->get_genre_name()."\" />\n";
			echo "\t\t<PARAM NAME=\"Composer\" Value=\"".$media->f_artist_full."\" />\n";
			echo "\t\t<PARAM NAME=\"Prebuffer\" Value=\"false\" />\n";
        	        echo "<REF HREF = \"". $url . "\" />\n";
                        echo "</ENTRY>\n";
			
                } // end foreach

		/* Foreach urls */
		foreach ($this->urls as $url) { 
			echo "<ENTRY>\n";
			echo "<TITLE>AddURL</TITLE>\n";
			echo "<AUTHOR>AddURL</AUTHOR>\n";
			echo "<REF HREF=\"$url\" />\n";
			echo "</ENTRY>\n";
		} // end foreach 

                echo "</ASX>\n";

	} // create_asx

	/**
	 * create_xspf
	 * creates an XSPF playlist (Thx PB1DFT)
	 */
	public function create_xspf() { 

		$flash_hack = ''; 

		if (isset($_REQUEST['flash_hack'])) { 
			if (!Config::get('require_session')) { $flash_hack = '&sid=' . session_id(); } 
		} 

		// Itterate through the songs
		foreach ($this->media as $element) {
			$type = array_shift($element); 
			$media = new $type(array_shift($element)); 
			$media->format(); 

			$xml = array();

			switch ($type) { 
				default:
				case 'song': 
					$xml['track']['title'] = $media->title;
					$xml['track']['creator'] = $media->f_artist_full;
					$xml['track']['info'] = Config::get('web_path') . "/albums.php?action=show&album=" . $media->album;
					$xml['track']['image'] = Config::get('web_path') . "/image.php?id=" . $media->album . "&thumb=3&sid=" . session_id();
					$xml['track']['album'] = $media->f_album_full;
					$length = $media->time; 
				break; 
			} // type

			$xml['track']['location'] = call_user_func(array($type,'play_url'),$media->id) . $flash_hack;
			$xml['track']['identifier'] = $xml['track']['location'];
			$xml['track']['duration'] = $length * 1000;

			$result .= xmlData::keyed_array($xml,1);

                } // end foreach
		
		xmlData::set_type('xspf'); 

	        header("Cache-control: public");
        	header("Content-Disposition: filename=ampache_playlist.xspf");
		header("Content-Type: application/xspf+xml; charset=utf-8");
		echo xmlData::header(); 
		echo $result;
		echo xmlData::footer(); 

	} // create_xspf

	/**
	 * create_xspf_player
	 * due to the fact that this is an integrated player (flash) we actually
	 * have to do a little 'cheating' to make this work, we are going to take
	 * advantage of tmp_playlists to do all of this hotness
	 */
	public function create_xspf_player() { 

		/* Build the extra info we need to have it pass */
		$play_info = "?action=show&tmpplaylist_id=" . $GLOBALS['user']->playlist->id;

	        // start ugly evil javascript code
		//FIXME: This needs to go in a template, here for now though
		//FIXME: This preference doesn't even exists, we'll eventually
		//FIXME: just make it the default
		if (Config::get('embed_xspf') == 1 ){ 
			header("Location: ".Config::get('web_path')."/index.php?xspf&play_info=".$GLOBALS['user']->playlist->id);
		}
		else {
	        echo "<html><head>\n";
	        echo "<title>" . Config::get('site_title') . "</title>\n";
	        echo "<script language=\"javascript\" type=\"text/javascript\">\n";
	        echo "<!-- begin\n";
	        echo "function PlayerPopUp(URL) {\n";
		// We do a little check here to see if it's a Wii!
		if (false !== stristr($_SERVER['HTTP_USER_AGENT'], 'Nintendo Wii')) {
			echo "window.location=URL;\n";
		} 
		// Else go ahead and do the normal stuff
		else {
		        echo "window.open(URL, 'XSPF_player', 'width=400,height=170,scrollbars=0,toolbar=0,location=0,directories=0,status=0,resizable=0');\n";
		        echo "window.location = '" .  return_referer() . "';\n";
		        echo "return false;\n";
		} 
	        echo "}\n";
	        echo "// end -->\n";
	        echo "</script>\n";
	        echo "</head>\n";

	        echo "<body onLoad=\"javascript:PlayerPopUp('" . Config::get('web_path') . "/modules/flash/xspf_player.php" . $play_info . "')\">\n";
	        echo "</body>\n";
	        echo "</html>\n";
	    }
	} // create_xspf_player
		
	/**
	 * create_localplay
	 * This calls the Localplay API and attempts to 
	 * add, and then start playback
	 */
	public function create_localplay() { 

		// First figure out what their current one is and create the object
		$localplay = new Localplay(Config::get('localplay_controller')); 
		$localplay->connect(); 
		foreach ($this->media as $element) { 
			$type = array_shift($element);
			switch ($type) { 
				case 'video': 
					// Add check for video support
				case 'song': 
				case 'radio': 
				case 'random': 
					$media = new $type(array_shift($element)); 
				break; 
				default: 
					$media = array_shift($element); 
				break; 
			} // switch on types 
			$localplay->add($media); 
		} // foreach object

		/**
 		 * Add urls after the fact
	 	 */
		foreach ($this->urls as $url) { 
			$localplay->add($url); 
		} 
		
		$localplay->play();

	} // create_localplay

	/**
 	 * create_democratic
	 * This 'votes' on the songs it inserts them into
	 * a tmp_playlist with user of -1 (System)
	 */
	public function create_democratic() { 

		$democratic	= Democratic::get_current_playlist();
		$democratic->set_parent(); 
		$democratic->vote($this->media);

	} // create_democratic

	/**
	 * create_download
	 * This prompts for a download of the song, only a single
	 * element can by in song_ids
	 */
	private function create_download() { 

		// Build up our object
		$song_id = $this->media['0']; 
		$url = Song::play_url($song_id); 

		// Append the fact we are downloading
		$url .= '&action=download'; 

		// Header redirect baby!
		header("Location: $url"); 
		exit; 

	} //create_download

	/**
	 * create_ram
	 *this functions creates a RAM file for use by Real Player
	 */
	public function create_ram() { 

                header("Cache-control: public");
                header("Content-Disposition: filename=ampache_playlist.ram");
                header("Content-Type: audio/x-pn-realaudio ram;");
                foreach ($this->media as $element) {
			$type = array_shift($element);
			echo $url = call_user_func(array($type,'play_url'),array_shift($element)) . "\n"; 
		} // foreach songs

	} // create_ram

	/**
	 * start_downsample
	 * This is a rather complext function that starts the downsampling of a song and returns the 
	 * opened file handled a reference to the song object is passed so that the changes we make
	 * in here affect the external object, References++ 
	 */
	public static function start_downsample(&$song,$now_playing_id=0,$song_name=0,$start=0) {
	
	        /* Check to see if bitrates are set if so let's go ahead and optomize! */
	        $max_bitrate = Config::get('max_bit_rate');
	        $min_bitrate = Config::get('min_bit_rate');
	        $time = time();
	        $user_sample_rate = Config::get('sample_rate');
	        $browser = new Browser();

	        if (!$song_name) {
	                $song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;
	        }

	        if ($max_bitrate > 1 AND $min_bitrate < $max_bitrate AND $min_bitrate > 0) {
	                $last_seen_time = $time - 1200; //20 min.

	                $sql = "SELECT COUNT(*) FROM now_playing, user_preference, preference " .
	                        "WHERE preference.name = 'play_type' AND user_preference.preference = preference.id " .
	                        "AND now_playing.user = user_preference.user AND user_preference.value='downsample'";
	                $db_results = Dba::query($sql);
	                $results = Dba::fetch_row($db_results);

	                // Current number of active streams (current is already in now playing, worst case make it 1)
	                $active_streams = intval($results[0]); 
			if (!$active_streams) { $active_streams = '1'; } 

	                /* If only one user, they'll get all available.  Otherwise split up equally. */
	                $sample_rate = floor($max_bitrate/$active_streams);

	                /* If min_bitrate is set, then we'll exit if the bandwidth would need to be split up smaller than the min. */
	                if ($min_bitrate > 1 AND ($max_bitrate/$active_streams) < $min_bitrate) {

	                        /* Log the failure */
	                        debug_event('downsample',"Error: Max bandwidith already allocated. $active_streams Active Streams",'2');
	                        echo "Maximum bandwidth already allocated.  Try again later.";
	                        exit();

	                }
        	        else {
	                        $sample_rate = floor($max_bitrate/$active_streams);
	                } // end else

	                // Never go over the users sample rate 
	                if ($sample_rate > $user_sample_rate) { $sample_rate = $user_sample_rate; }

	                debug_event('downsample',"Downsampled: $active_streams current active streams, downsampling to $sample_rate",'2');

	        } // end if we've got bitrates

	        else {
	                $sample_rate = $user_sample_rate;
	        }

	        /* Validate the bitrate */
	        $sample_rate = self::validate_bitrate($sample_rate);

	        /* Never Upsample a song */
	        if (($sample_rate*1000) > $song->bitrate) {
	                $sample_rate = self::validate_bitrate($song->bitrate/1000);
	                $sample_ratio = '1';
	        }
	        else {
	                /* Set the Sample Ratio */
	                $sample_ratio = $sample_rate/($song->bitrate/1000);
	        }
	        
		// Set the new size for the song
		$song->size  = floor($sample_ratio*$song->size);

	        /* Get Offset */
	        $offset = ( $start*$song->time )/( $song->size );
	        $offsetmm = floor($offset/60);
	        $offsetss = floor($offset-$offsetmm*60);
	        $offset   = sprintf("%02d.%02d",$offsetmm,$offsetss);

	        /* Get EOF */
	        $eofmm  = floor($song->time/60);
	        $eofss  = floor($song->time-$eofmm*60);
	        $eof    = sprintf("%02d.%02d",$eofmm,$eofss);

	        $song_file = escapeshellarg($song->file);

	        /* Replace Variables */
	        $downsample_command = Config::get($song->stream_cmd());
	        $downsample_command = str_replace("%FILE%",$song_file,$downsample_command,$file_exists);
	        $downsample_command = str_replace("%OFFSET%",$offset,$downsample_command,$offset_exists);
	        $downsample_command = str_replace("%EOF%",$eof,$downsample_command,$eof_exists);
	        $downsample_command = str_replace("%SAMPLE%",$sample_rate,$downsample_command,$sample_exists);

		if (!$file_exists || !$offset_exists || !$eof_exists || !$sample_exists) { 
			debug_event('downsample','Error: Downsample command missing a varaible values are File:' . $file_exists . ' Offset:' . $offset_exists . ' Eof:' . $eof_exists . ' Sample:' . $sample_exists,'1'); 
		} 

	        // If we are debugging log this event
	        $message = "Start Downsample: $downsample_command";
	        debug_event('downsample',$message,'3');

	        $fp = popen($downsample_command, 'rb');

		// Return our new handle
	        return ($fp);

	} // start_downsample

	/** 
	 * validate_bitrate
	 * this function takes a bitrate and returns a valid one
	 */
	public static function validate_bitrate($bitrate) {

	        /* Round to standard bitrates */
	        $sample_rate = 16*(floor($bitrate/16));

		return $sample_rate; 

	} // validate_bitrate


	/**
 	 * gc_now_playing
	 * This will garbage collect the now playing data, 
	 * this is done on every play start
	 */
	public static function gc_now_playing() { 

        	// Remove any now playing entries for session_streams that have been GC'd
	        $sql = "DELETE FROM `now_playing` USING `now_playing` " .
	                "LEFT JOIN `session_stream` ON `session_stream`.`id`=`now_playing`.`id` " .
	                "WHERE `session_stream`.`id` IS NULL OR `now_playing`.`expire` < '" . time() . "'";
	        $db_results = Dba::query($sql);

	} // gc_now_playing

	/**
 	 * insert_now_playing
	 * This will insert the now playing data
	 * This fucntion is used by the /play/index.php song
	 * primarily, but could be used by other people
	 */
	public static function insert_now_playing($oid,$uid,$length,$sid,$type) {

	        $time = time()+$length;
	        $session_id = Dba::escape($sid);
		$object_type = 'song'; 

	        // Do a replace into ensuring that this client always only has a single row
	        $sql = "REPLACE INTO `now_playing` (`id`,`object_id`,`object_type`, `user`, `expire`)" .
	                " VALUES ('$session_id','$oid','$object_type', '$uid', '$time')";
	        $db_result = Dba::write($sql);

	} // insert_now_playing

	 /**
	  * clear_now_playing
	  * There really isn't anywhere else for this function, shouldn't have deleted it in the first
	  * place
	  */
	public static function clear_now_playing() {

	        $sql = "TRUNCATE `now_playing`";
	        $db_results = Dba::query($sql);

	        return true;

	} // clear_now_playing

	/**
	 * get_now_playing
	 * This returns the now playing information
	 */
	public static function get_now_playing($filter=NULL) { 

		$sql = "SELECT `session_stream`.`agent`,`now_playing`.* " .
			"FROM `now_playing` " .
			"LEFT JOIN `session_stream` ON `session_stream`.`id`=`now_playing`.`id` " .
			"ORDER BY `now_playing`.`expire` DESC";
	        $db_results = Dba::read($sql);

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$type = $row['object_type']; 
			$media = new $type($row['object_id']); 
			$media->format(); 
			$client = new User($row['user']); 
			$results[] = array('media'=>$media,'client'=>$client,'agent'=>$row['agent'],'expire'=>$row['expire']); 
		} // end while

		return $results; 

	} // get_now_playing

	/**
 	 * check_lock_media
	 * This checks to see if the media is already being played, if it is then it returns false
	 * else return true
	 */
	public static function check_lock_media($media_id,$type) { 

		$media_id = Dba::escape($media_id); 
		$type = Dba::escape($type); 

		$sql = "SELECT `object_id` FROM `now_playing` WHERE `object_id`='$media_id' AND `object_type`='$type'"; 
		$db_results = Dba::read($sql); 

		if (Dba::num_rows($db_results)) { 
			debug_event('Stream','Unable to play media currently locked by another user','3'); 
			return false;
		} 

		return true; 

	} // check_lock_media

	/**
	 * auto_init
	 * This is called on class load it sets the session
	 */
	public static function _auto_init() { 

		// Generate the session ID
		self::$session = md5(uniqid(rand(), true));

	} // auto_init

	/**
	 * run_playlist_method
	 * This takes care of the different types of 'playlist methods' the reason this is here
	 * is because it deals with streaming rather then playlist mojo. If something needs to happen
	 * this will echo the javascript required to cause a reload of the iframe. 
	 */
	public static function run_playlist_method() { 

		// If this wasn't ajax included run away 
		if (AJAX_INCLUDE != '1') { return false; } 

		// If we're doin the flash magic then run away as well
		if (Config::get('play_type') == 'xspf_player') { return false; } 

		switch (Config::get('playlist_method')) { 
			default: 
			case 'clear': 
			case 'default': 
				return true; 
			break;
			case 'send': 
				$_SESSION['iframe']['target'] = Config::get('web_path') . '/stream.php?action=basket';
			break;
			case 'send_clear': 
				$_SESSION['iframe']['target'] = Config::get('web_path') . '/stream.php?action=basket&playlist_method=clear'; 
			break;		
		} // end switch on method 

		// Load our javascript	
	        echo "<script type=\"text/javascript\">";
	        //echo "reload_util();";
	        echo "reload_util('".$_SESSION['iframe']['target']."');";
	        echo "</script>";

	} // run_playlist_method

	/**
	 * get_base_url
	 * This returns the base requirements for a stream URL this does not include anything after the index.php?sid=????
	 */
	public static function get_base_url() { 

                if (Config::get('require_session')) {
                        $session_string = 'ssid=' . Stream::get_session() . '&';
                }

                $web_path = Config::get('web_path');

                if (Config::get('force_http_play') OR !empty(self::$force_http)) {
                        $web_path = str_replace("https://", "http://",$web_path);
                }
		if (Config::get('http_port') != '80') { 
			if (preg_match("/:(\d+)/",$web_path,$matches)) { 
				$web_path = str_replace(':' . $matches['1'],':' . Config::get('http_port'),$web_path); 
			} 
			else { 
				$web_path = str_replace($_SERVER['HTTP_HOST'],$_SERVER['HTTP_HOST'] . ':' . Config::get('http_port'),$web_path); 
			} 	
		} 

		$url = $web_path . "/play/index.php?$session_string"; 

		return $url; 

	} // get_base_url

} //end of stream class

?>
