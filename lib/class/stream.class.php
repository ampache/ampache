<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
	var $type;
	var $web_path;
	var $songs = array();
	var $urls  = array(); 
	var $sess;

	/*!
		@function stream 
		@discussion constructor for the stream class
	*/
	function Stream($type='m3u', $song_ids=0) {

		$this->type = $type;
		$this->songs = $song_ids;
		$this->web_path = conf('web_path');
		
		if (conf('force_http_play')) { 
			$port = conf('http_port');
			$this->web_path = preg_replace("/https/", "http",$this->web_path);
			$this->web_path = preg_replace("/:\d+/",":$port",$this->web_path);
		}
		
		$this->sess = session_id();
		$this->user_id = $_SESSION['userdata']['username'];

	} //constructor

	/*!
		@function start
		@discussion runs this and depending on the type passed it will
			call the correct function
	*/
	function start() {

		if (!is_array($this->songs)) { 
			debug_event('stream','Error: No Songs Passed on ' . $this->type . ' stream','2');
			return false; 
		}

		$methods = get_class_methods('Stream');
		$create_function = "create_" . $this->type;	
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
	function manual_url_add($url) { 

		$this->urls[] = $url; 

	} // manual_url_add

	/*!
		@function create_simplem3u
		@discussion this creates a simple m3u
			without any of the extended information
	*/
	function create_simple_m3u() {

		header("Cache-control: public");
		header("Content-Disposition: filename=playlist.m3u");
		header("Content-Type: audio/x-mpegurl;");

		/* Foreach songs */
		foreach ($this->songs as $song_id) { 
			$song = new Song($song_id);
			if ($song->type == ".flac") { $song->type = ".ogg"; }
                        if($GLOBALS['user']->prefs['play_type'] == 'downsample') {
                                $ds = $GLOBALS['user']->prefs['sample_rate'];
                        }
			echo "$this->web_path/play/index.php?song=$song_id&uid=$this->user_id&sid=$this->sess&ds=$ds&stupidwinamp=." . $song->type . "\n"; 
		} // end foreach

		/* Foreach the additional URLs */
		foreach ($this->urls as $url) { 
			echo "$url\n";
		}

	} // simple_m3u

	/*!
		@function create_m3u
		@discussion creates an m3u file
	*/
	function create_m3u() { 

	        // Send the client an m3u playlist
	        header("Cache-control: public");
	        header("Content-Disposition: filename=playlist.m3u");
	        header("Content-Type: audio/x-mpegurl;");
	        echo "#EXTM3U\n";
	        foreach($this->songs as $song_id) {
	        	$song = new Song($song_id);
	                $song->format_song();
			if ($song->type == ".flac") { $song->type = ".ogg"; }
	                $song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;
	                echo "#EXTINF:$song->time," . $song->f_artist_full . " - " . $song->title . "\n";
	                $sess = $_COOKIE[libglue_param('sess_name')];
	                if($GLOBALS['user']->prefs['play_type'] == 'downsample') {
	                	$ds = $GLOBALS['user']->prefs['sample_rate'];
			}
			echo $song->get_url() . "\n";
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
		header("Content-Disposition: filename=playlist.pls");
		header("Content-Type: audio/x-scpls;");
		echo "[Playlist]\n";
		echo "NumberOfEntries=$total_entries\n";
		foreach ($this->songs as $song_id) { 
			$i++;
			$song = new Song($song_id);
			$song->format_song();
			$song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;
			$song_url = $song->get_url();
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
                        $song->format_song();   
			$url = $song->get_url();
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
			if (!conf('require_session')) { $flash_hack .= '&sid=' . session_id(); } 
		} 

		// Itterate through the songs
		foreach ($this->songs as $song_id) {
				
	        	$song = new Song($song_id);
	                $song->format_song();

	                $xml = array();
			$xml['track']['location'] = $song->get_url() . $flash_hack;
			$xml['track']['identifier'] = $xml['track']['location'];
			$xml['track']['title'] = $song->title;
			$xml['track']['creator'] = $song->f_artist_full;
			$xml['track']['info'] = conf('web_path') . "/albums.php?action=show&album=" . $song->album;
			$xml['track']['image'] = conf('web_path') . "/image.php?id=" . $song->album . "&thumb=3&sid=" . session_id();
			$xml['track']['album'] = $song->f_album_full;
			$xml['track']['duration'] = $song->time;
			$result .= xml_from_array($xml,1,'xspf');

                } // end foreach

	        header("Cache-control: public");
        	header("Content-Disposition: filename=playlist.xspf");
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
			$tmp_playlist->add_object($song_id);
		} // end foreach		
		
		/* Build the extra info we need to have it pass */
		$play_info = "?action=show&tmpplaylist_id=" . $tmp_playlist->id;

	        // start ugly evil javascript code
		//FIXME: This needs to go in a template, here for now though
	        echo "<html><head>\n";
	        echo "<title>" . conf('site_title') . "</title>\n";
	        echo "<script language=\"javascript\" type=\"text/javascript\">\n";
	        echo "<!-- begin\n";
	        echo "function PlayerPopUp(URL) {\n";
	        echo "window.open(URL, 'XSPF_player', 'width=350,height=300,scrollbars=0,toolbar=0,location=0,directories=0,status=0,resizable=0');\n";
	        echo "window.location = '" .  return_referer() . "';\n";
	        echo "return false;\n";
	        echo "}\n";
	        echo "// end -->\n";
	        echo "</script>\n";
	        echo "</head>\n";

	        echo "<body onLoad=\"javascript:PlayerPopUp('" . conf('web_path') . "/modules/flash/xspf_player.php" . $play_info . "')\">\n";
	        echo "</body>\n";
	        echo "</html>\n";
		
	} // create_xspf_player
		

	/**
	 * create_localplay
	 * This calls the Localplay API and attempts to 
	 * add, and then start playback
	 */
	function create_localplay() { 

		if (!$localplay = init_localplay()) { 
			debug_event('localplay','Player failed to init on song add','3');
			echo "Error: Localplay Init Failed check config";
			return false; 
		} 

		if (!$localplay->connect()) { 
			debug_event('localplay','Localplay Player Connect failed','3'); 
			echo "Error: Localplay connect failed check config";
			return false; 
		} 

		$localplay->add($this->songs);

		/* Check for Support */ 
		if ($localplay->has_function('add_url')) { 
			$localplay->add_url($this->urls); 
		} 

		$localplay->play();

                header("Location: " . return_referer());

	} // create_localplay

	/**
 	 * create_democratic
	 * This 'votes' on the songs it inserts them into
	 * a tmp_playlist with user of -1 (System)
	 */
	function create_democratic() { 

		$tmp_playlist	= get_democratic_playlist('-1');
		$tmp_playlist->vote($this->songs);
		
		header("Location: " . return_referer());

	} // create_democratic

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
 			echo "$this->web_path/play/index.php?song=$song_id&uid=$this->user_id&sid=$this->sess&stupidwinamp=." . $song->type . "\n";	
		} // foreach songs

	} // create_ram

} //end of stream class

?>
