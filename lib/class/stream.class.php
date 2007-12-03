<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
	public $songs = array();
	public $urls  = array(); 
	public $sess;
	public $user_id; 

	// Generate once an object is constructed
	public static $session; 

	/**
	 * Constructor for the stream class takes a type and an array
	 * of song ids
	 */
	public function __construct($type='m3u', $song_ids=0) {

		$this->type = $type;
		$this->songs = $song_ids;
		$this->web_path = Config::get('web_path');
		$this->user_id = $GLOBALS['user']->id;
		
		if (Config::get('force_http_play')) { 
			$this->web_path = preg_replace("/https/", "http",$this->web_path);
		}

	} // Constructor

	/**
	 * start
	 *runs this and depending on the type passed it will
	 *call the correct function
	 */
	public function start() {

		if (!is_array($this->songs)) { 
			debug_event('stream','Error: No Songs Passed on ' . $this->type . ' stream','2');
			return false; 
		}

		// We're starting insert the session into session_stream
		if (!$this->insert_session()) { 
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
	 * manual_url_add
	 * This manually adds a URL to the stream object for passing
	 * to whatever, this is an exception for when we don't actually
	 * have a object_id but instead a weird or special URL
	 */
	public function manual_url_add($url) { 

		$this->urls[] = $url; 

	} // manual_url_add

	/**
	 * get_session
	 * This returns the current stream session
	 */
	public static function get_session() { 

		return self::$session; 

	} // get_session

	/**
	 * insert_session
	 * This inserts a row into the session_stream table
	 */
	private function insert_session() { 

		$expire = time() + Config::get('stream_length'); 

		$sql = "INSERT INTO `session_stream` (`id`,`expire`,`user`) " . 
			"VALUES('" . self::$session . "','$expire','$this->user_id')"; 
		$db_results = Dba::query($sql); 

		if (!$db_results) { return false; } 

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
	 */
	public static function gc_session($ip='',$agent='',$uid='',$sid='') { 

		$time = time(); 
		$sql = "DELETE FROM `session_stream` WHERE `expire` < '$time'"; 
		$db_results = Dba::query($sql); 

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
		$ip	= ip2int($_SERVER['REMOTE_ADDR']); 
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
		header("Content-Disposition: filename=playlist.m3u");
		header("Content-Type: audio/x-mpegurl;");

		// Flip for the poping!
		asort($this->urls); 

		/* Foreach songs */
		foreach ($this->songs as $song_id) { 
			// If it's a place-holder
			if ($song_id == '-1') { 
				echo array_pop($this->urls) . "\n"; 
				continue; 
			} 
			$song = new Song($song_id);
			if ($song->type == ".flac") { $song->type = ".ogg"; }
	                if ($GLOBALS['user']->prefs['play_type'] == 'downsample') {
	                	$ds = $GLOBALS['user']->prefs['sample_rate'];
	                }
			echo $song->get_url(); 
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
	public public function create_m3u() { 

	        // Send the client an m3u playlist
	        header("Cache-control: public");
	        header("Content-Disposition: filename=ampache_playlist.m3u");
	        header("Content-Type: audio/x-mpegurl;");
	        echo "#EXTM3U\n";

		// Flip for the popping
		asort($this->urls); 

		// Foreach the songs in this stream object
	        foreach ($this->songs as $song_id) {
			if ($song_id == '-1') { 
				echo "#EXTINF: URL-Add\n"; 
				echo array_pop($this->urls) . "\n"; 
				continue; 
			} 
	        	$song = new Song($song_id);
	                $song->format();

	                echo "#EXTINF:$song->time," . $song->f_artist_full . " - " . $song->title . "\n";
			echo $song->get_url(self::$session) . "\n";
                } // end foreach

		/* Foreach URLS */
		foreach ($this->urls as $url) { 
			echo "#EXTINF: URL-Add\n";
			echo $url . "\n";
		}

	} // create_m3u

	/*!
		@function create_pls
		@discussion creates a pls file
	*/
	function create_pls() { 

		/* Count entries */
		$total_entries = count($this->songs) + count($this->urls); 

		// Send the client a pls playlist
		header("Cache-control: public");
		header("Content-Disposition: filename=ampache-playlist.pls");
		header("Content-Type: audio/x-scpls;");
		echo "[Playlist]\n";
		echo "NumberOfEntries=$total_entries\n";
		foreach ($this->songs as $song_id) { 
			$i++;
			$song = new Song($song_id);
			$song->format();
			$song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;
			$song_url = $song->get_url(self::$session);
			echo "File" . $i . "=$song_url\n";
			echo "Title" . $i . "=$song_name\n";
			echo "Length" . $i . "=$song->time\n";
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

	/*!
		@function create_asx
		@discussion creates an ASZ playlist (Thx Samir Kuthiala)
	*/
	function create_asx() { 

	        header("Cache-control: public");
        	header("Content-Disposition: filename=playlist.asx");
		header("Content-Type: video/x-ms-asf;");
 
		echo "<ASX version = \"3.0\" BANNERBAR=\"AUTO\">\n";
                echo "<TITLE>Ampache ASX Playlist</TITLE>";
                
		foreach ($this->songs as $song_id) {
                	$song = new Song($song_id);
                        $song->format();   
			$url = $song->get_url(self::$session);
                        $song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;
			
                        echo "<ENTRY>\n";
                        echo "<TITLE>".$song->f_album_full ." - ". $song->f_artist_full ." - ". $song->title ."</TITLE>\n";
                        echo "<AUTHOR>".$song->f_artist_full."</AUTHOR>\n";
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
	function create_xspf() { 

		$flash_hack = ''; 

		if (isset($_REQUEST['flash_hack'])) { 
			$flash_hack = '&flash_hack=' . $_REQUEST['flash_hack']; 
			if (!Config::get('require_session')) { $flash_hack .= '&sid=' . session_id(); } 
		} 

		// Itterate through the songs
		foreach ($this->songs as $song_id) {
				
	        	$song = new Song($song_id);
	                $song->format();

	                $xml = array();
			$xml['track']['location'] = $song->get_url(self::$session) . $flash_hack;
			$xml['track']['identifier'] = $xml['track']['location'];
			$xml['track']['title'] = $song->title;
			$xml['track']['creator'] = $song->f_artist_full;
			$xml['track']['info'] = Config::get('web_path') . "/albums.php?action=show&album=" . $song->album;
			$xml['track']['image'] = Config::get('web_path') . "/image.php?id=" . $song->album . "&thumb=3&sid=" . session_id();
			$xml['track']['album'] = $song->f_album_full;
			$xml['track']['duration'] = $song->time;
			$result .= xml_from_array($xml,1,'xspf');

                } // end foreach

	        header("Cache-control: public");
        	header("Content-Disposition: filename=ampache-playlist.xspf");
		header("Content-Type: application/xspf+xml; charset=utf-8");
		echo xml_get_header('xspf');
		echo $result;
		echo xml_get_footer('xspf');

	} // create_xspf

	/**
	 * create_xspf_player
	 * due to the fact that this is an integrated player (flash) we actually
	 * have to do a little 'cheating' to make this work, we are going to take
	 * advantage of tmp_playlists to do all of this hotness
	 */
	function create_xspf_player() { 

		/* First insert the songs we've got into
		 * a tmp_playlist
		 */
		$tmp_playlist = new tmpPlaylist(); 
		$playlist_id = $tmp_playlist->create($this->sess,'xspf','song',''); 
		$tmp_playlist = new tmpPlaylist($playlist_id);

		/* Add the songs to this new playlist */
		foreach ($this->songs as $song_id) { 
			$tmp_playlist->add_object($song_id,'song');
		} // end foreach		
		
		/* Build the extra info we need to have it pass */
		$play_info = "?action=show&tmpplaylist_id=" . $tmp_playlist->id;

	        // start ugly evil javascript code
		//FIXME: This needs to go in a template, here for now though
		//FIXME: This preference doesn't even exists, we'll eventually
		//FIXME: just make it the default
		if ($GLOBALS['user']->prefs['embed_xspf'] == 1 ){ 
			header("Location: ".Config::get('web_path')."/index.php?xspf&play_info=".$tmp_playlist->id);
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
	function create_localplay() { 

		// First figure out what their current one is and create the object
		$localplay = new Localplay($GLOBALS['user']->prefs['localplay_controller']); 
		$localplay->connect(); 
		//HACK!!!
		// Yea.. you know the baby jesus... he's crying right meow
		foreach ($this->songs as $song_id) { 
			$this->objects[] = new Song($song_id); 
		} 
		

		// Foreach the stuff we've got and add it
		foreach ($this->objects as $object) { 
			$localplay->add($object); 
		} 

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
		$democratic->vote($this->songs);

	} // create_democratic

	/**
	 * create_download
	 * This prompts for a download of the song, only a single
	 * element can by in song_ids
	 */
	private function create_download() { 

		// Build up our object
		$song_id = $this->songs['0']; 
		$song = new Song($song_id); 
		$url = $song->get_url(); 

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
	function create_ram() { 

                header("Cache-control: public");
                header("Content-Disposition: filename=playlist.ram");
                header("Content-Type: audio/x-pn-realaudio ram;");
                foreach ($this->songs as $song_id) {
                        $song = new Song($song_id);
			echo $song->get_url(); 
		} // foreach songs

	} // create_ram

	/**
	 * start_downsample
	 * This is a rather complext function that starts the downsampling of a song and returns the 
	 * opened file handled a reference to the song object is passed so that the changes we make
	 * in here affect the external object, References++ 
	 */
	public static function start_downsample(&$song,$now_playing_id=0,$song_name=0) {
	
	        /* Check to see if bitrates are set if so let's go ahead and optomize! */
	        $max_bitrate = Config::get('max_bit_rate');
	        $min_bitrate = Config::get('min_bit_rate');
	        $time = time();
	        $user_sample_rate = $GLOBALS['user']->prefs['sample_rate'];
	        $browser = new Browser();

	        if (!$song_name) {
	                $song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;
	        }

	        if ($max_bitrate > 1 AND $min_bitrate < $max_bitrate) {
	                $last_seen_time = $time - 1200; //20 min.

	                $sql = "SELECT COUNT(*) FROM now_playing, user_preference, preference " .
	                        "WHERE preference.name = 'play_type' AND user_preference.preference = preference.id " .
	                        "AND now_playing.user = user_preference.user AND user_preference.value='downsample'";
	                $db_results = Dba::query($sql);
	                $results = Dba::fetch_row($db_results);

	                // Current number of active streams (current is already in now playing)
	                $active_streams = $results[0];

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
	                $sample_rate = self::validate_bitrate($song->bitrate)/1000;
	                $sample_ratio = '1';
	        }
	        else {
	                /* Set the Sample Ratio */
	                $sample_ratio = $sample_rate/($song->bitrate/1000);
	        }
	        
		// Set the new size for the song
		$song->size  = floor($sample_ratio*$song->size);

	        /* Get Offset */
	        $offset = ( $start*$song->time )/( $sample_ratio*$song->size );
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
	        $downsample_command = str_replace("%FILE%",$song_file,$downsample_command);
	        $downsample_command = str_replace("%OFFSET%",$offset,$downsample_command);
	        $downsample_command = str_replace("%EOF%",$eof,$downsample_command);
	        $downsample_command = str_replace("%SAMPLE%",$sample_rate,$downsample_command);

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

	        // Setup an array of valid bitrates for Lame (yea yea, others might be different :P)
	        $valid_rate = array('32','40','56','64','80','96','112','128','160','192','224','256','320');

	        /* Round to standard bitrates */
	        $sample_rate = 8*(floor($bitrate/8));

	        if (in_array($sample_rate,$valid_rate)) {
	                return $sample_rate;
	        }

	        /* See if it's less than the lowest one */
	        if ($sample_rate < $valid_rate['0']) {
	                return $valid_rate['0'];
	        }

	        /* Check to see if it's over 320 */
	        if ($sample_rate > 320) {
	                return '320';
	        }

	        foreach ($valid_rate as $key=>$rate) {
	                $next_key = $key+1;

	                if ($sample_rate > $rate AND $sample_rate < $valid_rate[$next_key]) {
	                        return $rate;
	                }
	        } // end foreach

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
	public static function insert_now_playing($song_id,$uid,$song_length,$sid) {

	        $time = time()+$song_length;
	        $session_id = Dba::escape($sid);

	        // Do a replace into ensuring that this client always only has a single row
	        $sql = "REPLACE INTO `now_playing` (`id`,`song_id`, `user`, `expire`)" .
	                " VALUES ('$session_id','$song_id', '$uid', '$time')";
	        $db_result = Dba::query($sql);

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

		switch ($GLOBALS['user']->prefs['playlist_method']) { 
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

} //end of stream class

?>
