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

 * Written by snuffels * 

*/

/**
 * HttpQPlayer
 * This player controls an instance of HttpQ 
 * which in turn controls WinAmp all functions 
 * return null on failure
 */
class HttpQPlayer {

  	var $host;
  	var $port;
	var $password;

	/**
	 * HttpQPlayer
	 * This is the constructor, it defaults to localhost
	 * with port 4800
	 */	
	function HttpQPlayer($h = "localhost", $pw = "", $p = 4800) {

		$this->host = $h;
		$this->port = $p;
		$this->password = $pw;

	} // HttpQPlayer
  	
	/**
	 * add
	 * append a song to the playlist
	 * $name	Name to be shown in the playlist
	 * $url		URL of the song
	 */  	
  	function add($name, $url) {

  	  	$args['name'] = urlencode($name);
  	  	$args['url'] = urlencode($url);
		
		$results = $this->sendCommand('playurl', $args);
		
		if ($results == '0') { $results = null; } 

		return $results;

	} // add

	/**
	 * version
	 * This gets the version of winamp currently
	 * running, use this to test for a valid connection
	 */
	function version() { 

		$args = array(); 
		$results = $this->sendCommand('getversion',$args); 
		
		// a return of 0 is a bad value
		if ($results == '0') { $results = null; } 


		return $results; 

	} // version

	/**
	 * clear
	 * clear the playlist
	 */  	
	function clear() {
		$args = array();
		$results = $this->sendCommand("delete", $args);

		if ($results == '0') { $results = null; } 

		return $results; 
	
	} // clear
	
	/**
	 * next
	 * go to next song
	 */  	
	function next() {

		$args = array();
		$results = $this->sendCommand("next", $args);

		if ($results == '0') { return null; } 

		return true; 

	} // next		

	/**
	 * prev
	 * go to previous song
	 */  	
	function prev() {

		$args = array();
		$results = $this->sendCommand("prev", $args);

		if ($results == '0') { return null; } 
	
		return true;

	} // prev	

	/**
	 * skip
	 * This skips to POS in the playlist
	 */
	function skip($pos) { 

		$args = array('index'=>$pos); 
		$results = $this->sendCommand('sendplaylistpos',$args); 

		if ($results == '0') { return null; } 

		return true; 

	} // skip
	
	/** 
	 * play
	 * play the current song
	 */  	
	function play() {

		$args = array();
		$results = $this->sendCommand("play", $args);

		if ($results == '0') { $results = null; } 

		return $results; 

	} // play	
		
	/** 
	 * pause
	 * toggle pause mode on current song
	 */  	
	function pause() {

		$args = array();
		$results = $this->sendCommand("pause", $args);

		if ($results == '0') { $results = null; } 

		return $results; 

	} // pause
	
	/** 
	 * stop
	 * stops the current song amazing!
	 */  	
	function stop() {

		$args = array();
		$results = $this->sendCommand('fadeoutandstop', $args);

		if ($results == '0') { $results = null; } 

		return $results; 

	} // stop			

	/** 
 	 * repeat
	 * This toggles the repeat state of HttpQ
	 */
	function repeat($value) { 
		
		$args = array('enable'=>$value); 
		$results = $this->sendCommand('repeat',$args); 
		
		if ($results == '0') { $results = null; } 

		return $results;  

	} // repeat

	/** 
	 * random
	 * this toggles the random state of HttpQ
	 */
	function random($value) { 

		$args = array('enable'=>$value); 
		$results = $this->sendCommand('shuffle',$args); 

		if ($results == '0') { $results = null; } 

		return $results; 

	} // random

	/**
	 * delete_pos
	 * This deletes a specific track
	 */
	function delete_pos($track) { 
	
		$args = array('index'=>$track); 
		$results = $this->sendCommand('deletepos',$args); 
		
		if ($results == '0') { $results = null; } 

		return $results; 

	} // delete_pos

	/**
	 * state
	 * This returns the current state of the httpQ player
	 */
	function state() { 

		$args = array(); 
		$results = $this->sendCommand('isplaying',$args);

		if ($results == '1') { $state = 'play'; } 
		if ($results == '0') { $state = 'stop'; } 
		if ($results == '3') { $state = 'pause'; } 
		
		return $state; 

	} // state

	/**
	 * get_volume
	 * This returns the current volume 
	 */
	function get_volume() { 

		$args = array(); 
		$results = $this->sendCommand('getvolume',$args); 

		if ($results == '0') { $results = null; } 
		else { 
			/* Need to make this out of 100 */ 
			$results = round((($results / 255) * 100),2);
		}

		return $results; 

	} // get_volume

	/**
	 * volume_up
	 * This increases the volume by Wimamp's defined amount
	 */
	function volume_up() { 

		$args = array(); 
		$results = $this->sendCommand('volumeup',$args); 
		
		if ($results == '0') { return null; } 

		return true; 

	} // volume_up

	/**
	 * volume_down
	 * This decreases the volume by Winamp's defined amount
	 */
	function volume_down() { 

		$args = array(); 
		$results = $this->sendCommand('volumedown',$args); 
		
		if ($results == '0') { return null; } 

		return true; 

	} // volume_down

	/**
	 * set_volume
	 * This sets the volume as best it can, we go from a resolution
	 * of 100 --> 255 so it's a little fuzzy
	 */
	function set_volume($value) { 

		// Convert it to base 255
		$value = $value*2.55; 
		$args = array('level'=>$value); 
		$results = $this->sendCommand('setvolume',$args); 

		if ($results == '0') { return null; } 

		return true; 

	} // set_volume

	/**
	 * clear_playlist
	 * this flushes the playlist cache (I hope this means clear)
	 */
	function clear_playlist() { 

		$args = array(); 
		$results = $this->sendcommand('flushplaylist',$args); 
		
		if ($results == '0') { return null; } 

		return true; 

	} // clear_playlist

	/** 
	 * get_repeat
	 * This returns the current state of the repeat 
	 */
	function get_repeat() { 

		$args = array(); 
		$results = $this->sendCommand('repeat_status',$args); 

		return $results; 		

	} // get_repeat

	/**
	 * get_random
	 * This returns the current state of shuffle
	 */
	function get_random() { 

		$args = array(); 
		$results = $this->sendCommand('shuffle_status',$args); 
		
		return $results; 

	} // get_random

	/**
	 * get_now_playing
	 * This returns the file information for the currently
	 * playing song
	 */
	function get_now_playing()  { 

		// First get the current POS
		$pos = $this->sendCommand('getlistpos',array()); 
		
		// Now get the filename
		$file = $this->sendCommand('getplaylistfile',array('index'=>$pos)); 

		return $file; 

	} // get_now_playing

	/**
	 * get_tracks
	 * This returns a delimiated string of all of the filenames
	 * current in your playlist
	 */
	function get_tracks() { 

		// Pull a delimited list of all tracks
		$results = $this->sendCommand('getplaylistfile',array('delim'=>'::'));
		
		if ($results == '0') { $results = null; } 
	
		return $results; 

	} // get_tracks

	/** 
 	 * sendCommand
	 * This is the core of this library it takes care of sending the HTTP
	 * request to the HttpQ server and getting the response 
	 */	
	function sendCommand($cmd, $args) {

  		$fp = fsockopen($this->host, $this->port, $errno, $errstr); 

  		if(!$fp) {
    			debug_event('httpq',"HttpQPlayer: $errstr ($errno)",'1');
			return null; 
  		} 

		// Define the base message  
		$msg = "GET /$cmd?p=$this->password";  			

		// Foreach our arguments 
		foreach ($args AS $key => $val) {
			$msg = $msg . "&$key=$val";					
		}

      		$msg = $msg . " HTTP/1.0\r\n\r\n";      		
    		fputs($fp, $msg);

		$data = '';

    		while(!feof($fp)) {
			$data .= fgets($fp);      			
		}
  		fclose($fp);

		// Explode the results by line break and take 4th line (results)
		$data = explode("\n",$data); 
		
		$result = $data['4'];
		
		return $result; 

	} // sendCommand

} // End HttpQPlayer Class
?>
