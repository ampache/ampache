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

 * Written by snuffels * 

*/


class HttpQPlayer {

  	var $host;
  	var $port;
	var $password;
	
	function HttpQPlayer($h = "localhost", $pw = "", $p = 4800) {
		$this->host = $h;
		$this->port = $p;
		$this->password = $pw;
	} // HttpQPlayer
  	
	/*!
		@function add
		@discussion append a song to the playlist
		@param $name	Name to be shown in the playlist
		@param $url		URL of the song
	 */  	
  	function add($name, $url) {
  	  	$args["name"] = $name;
  	  	$args["url"] = str_replace("&","%26",$url);;
	    $this->sendCommand("playurl", $args);
	}

	/*!
		@function clear
		@discussion clear the playlist
	 */  	
	function clear() {
		$args = array();
		$this->sendCommand("delete", $args);
	}
	
	/*!
		@function next
		@discussion go to next song
	 */  	
	function next() {
		$args = array();
		$this->sendCommand("next", $args);
	}		

	/*!
		@function prev
		@discussion go to previous song
	 */  	
	function prev() {
		$args = array();
		$this->sendCommand("prev", $args);
	}		
	
	/*!
		@function play
		@discussion play the current song
	 */  	
	function play() {
		$args = array();
		$this->sendCommand("play", $args);
	}		
		
	/*!
		@function pause
		@discussion toggle pause mode on current song
	 */  	
	function pause() {
		$args = array();
		$this->sendCommand("pause", $args);
	}		
	
	/*!
		@function stop
		@discussion stop the current song
	 */  	
	function stop() {
		$args = array();
		$this->sendCommand("stop", $args);
	}			
	
	function sendCommand($cmd, $args) {
  		$fp = fsockopen($this->host, $this->port, &$errno, &$errstr); 
  		if(!$fp) {
    			debug_event('httpq',"HttpQPlayer: $errstr ($errno)",'1');
  		} 
		else {
  			$msg = "GET /$cmd?p=$this->password";  			
  			foreach ($args AS $key => $val) {
  				$msg = $msg . "&$key=$val";					
			}
	      		$msg = $msg . " HTTP/1.0\r\n\r\n";      		
	    		fputs($fp, $msg);
	    		while(!feof($fp)) {
				fgets($fp);      			
			}
  		fclose($fp);
	    	}  		
	}

} // End HttpQPlayer Class


?> 
