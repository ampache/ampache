<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.  

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

/*!
	@header Stream Class
*/

class Stream {

	/* Variables from DB */
	var $type;
	var $web_path;
	var $songs = array();
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

	/*!
		@function create_simplem3u
		@discussion this creates a simple m3u
			without any of the extended information
	*/
	function create_simple_m3u() {

		header("Cache-control: public");
		header("Content-Disposition: filename=playlist.m3u");
		header("Content-Type: audio/x-mpegurl;");
		foreach ($this->songs as $song_id) { 
			$song = new Song($song_id);
			if ($song->type == ".flac") { $song->type = ".ogg"; }
                        if($GLOBALS['user']->prefs['play_type'] == 'downsample') {
                                $ds = $GLOBALS['user']->prefs['sample_rate'];
                        }
			echo "$this->web_path/play/index.php?song=$song_id&uid=$this->user_id&sid=$this->sess&ds=$ds&stupidwinamp=." . $song->type . "\n"; 
		} // end foreach

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
	                echo "#EXTINF:$song->time,$song_name\n";
	                $sess = $_COOKIE[libglue_param('sess_name')];
	                if($GLOBALS['user']->prefs['play_type'] == 'downsample') {
	                	$ds = $GLOBALS['user']->prefs['sample_rate'];
			}
                        echo "$this->web_path/play/index.php?song=$song_id&uid=$this->user_id&sid=$this->sess&ds=$ds&name=/" . rawurlencode($song_name) . "\n";
                } // end foreach

	} // create_m3u

	/*!
		@function create_pls
		@discussion creates a pls file
	*/
	function create_pls() { 

		// Send the client a pls playlist
		header("Cache-control: public");
		header("Content-Disposition: filename=playlist.pls");
		header("Content-Type: audio/x-scpls;");
		echo "[Playlist]\n";
		echo "NumberOfEntries=" . count($this->songs) . "\n";
		foreach ($this->songs as $song_id) { 
			$i++;
			$song = new Song($song_id);
			$song->format_song();
			if ($song->type == ".flac") { $song->type = ".ogg"; }
			$song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;
                        if($GLOBALS['user']->prefs['play_type'] == 'downsample') {
                                $ds = $GLOBALS['user']->prefs['sample_rate'];
                        }
			$song_url = $this->web_path . "/play/index.php?song=$song_id&uid=$this->user_id&sid=$this->sess&ds=$ds&stupidwinamp=." . $song->type; 
			echo "File" . $i . "=$song_url\n";
			echo "Title" . $i . "=$song_name\n";
			echo "Length" . $i . "=-1\n";
		} // end foreach songs	
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
                        $song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;
                        echo "<ENTRY>\n";
                        echo "<TITLE>".$song->f_album_full ." - ". $song->f_artist_full ." - ". $song->title ."</TITLE>\n";
                        echo "<AUTHOR>".$song->f_artist_full."</AUTHOR>\n";
                        $sess = $_COOKIE[libglue_param('sess_name')];
                        if ($GLOBALS['user']->prefs['play_type'] == 'downsample') {
	                        $ds = $GLOBALS['user']->prefs['sample_rate'];
			}
        	        echo "<REF HREF = \"". conf('web_path') . "/play/index.php?song=$song_id&uid=$this->user_id&sid=$sess&ds=$ds&name=/" . rawurlencode($song_name) . "\" />\n";
                        echo "</ENTRY>\n";
			
                } // end foreach

                echo "</ASX>\n";

	} // create_asx

	/*! 
		@function create_icecast2
		@discussion pushes an icecast stream
	*/
	function create_icecast2() { 

	        echo "ICECAST2<br>\n";

		// Play the song locally using local play configuration
	        if (count($this->songs) > 0) {
	        echo "ICECAST2<br>\n";
	        exec("killall ices");
	        $filename = conf('icecast_tracklist');
	        echo "$filename " . _("Opened for writing") . "<br>\n";

		/* Open the file for writing */
		if (!$handle = @fopen($filename, "w")) {
			log_event($_SESSION['userdata']['username'],"icecast","Fopen: $filename Failed");
		        echo _("Error, cannot write") . " $filename<br>\n";
	        	exit;
		}

		/* Foreach through songs */
		foreach($this->songs as $song_id) {
        		$song = new Song($song_id);
	        	echo "$song->file<br>\n";
	        	$line = "$song->file\n";
	                if (!fwrite($handle, $line)) {
				log_event($_SESSION['userdata']['username'],"icecast","Fwrite: Unabled to write $line into $filename");
	                	echo _("Error, cannot write song in file") . " $song->file --&gt; $filename";
				exit;
			} // if write fails

		} // foreach songs

		echo $filename . " " . _("Closed after write") . "<br>\n";
		fclose($handle);
		$cmd = conf('icecast_command');
                $cmd = str_replace("%FILE%", $filename, $cmd);
		if (conf('debug')) { 
			log_event($_SESSION['userdata']['username'],"icecast","Exec: $cmd");
		} 
		exec($cmd);
		exit;
	
		} // if songs


	} // create_icecast2

	/**
	 * create_localplay
	 * This calls the Localplay API and attempts to 
	 * add, and then start playback
	 */
	function create_localplay() { 

		$localplay = init_localplay();
		$localplay->connect(); 
		$localplay->add($this->songs);
		$localplay->play();

                header("Location: " . return_referer());

	} // create_localplay

	/*!
		@function create_mpd
		@discussion function that passes information to 
			MPD
	*/
	function create_mpd() { 

		/* Create the MPD object */
		$myMpd = @new mpd(conf('mpd_host'),conf('mpd_port'),conf('mpd_pass'));

		/* Add the files to the MPD playlist */
		addToPlaylist($myMpd,$this->songs);

		/* If we've added songs we should start playing */
		$myMpd->Play();

		header ("Location: " . conf('web_path') . "/index.php");

	} // create_mpd


	/*!
		@function create_slim
		@discussion this function passes the correct mojo to the slim
			class which is in turn passed to the slimserver
	*/
	function create_slim() { 





	} // create_slim

	/*!
		@function create_ram
		@discussion this functions creates a RAM file for use by Real Player
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
