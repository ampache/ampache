<?php
/*

 Copyright 2001 - 2006 Ampache.org
 All Rights Reserved

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

/**
 * AmpacheMpd Class
 * the Ampache Mpd Controller, this is the glue between
 * the MPD class and the Ampahce Localplay class
 */
class AmpacheMpd {

	/* Variables */
	

	/* Constructed variables */
	$_mpd;

	/**
	 * Constructor
	 * This returns the array map for the localplay object
	 * REQUIRED for Localplay
	 */
	function AmpacheMpd() { 
	
		/* Do a Require Once On the needed Libraries */
		require_once(conf('prefix') . '/modules/mpd/mpd.class.php');

		$map = array(); 

		$map['add'] 		= 'add_songs';
		$map['delete']		= 'delete_songs';
		$map['play']		= 'play';
		$map['stop']		= 'stop';
		$map['get']		= 'get_songs';
		$map['connect']		= 'connect';

		return $map;

	} // AmpacheMpd


	/**
	 * preference
	 * This function returns an array of the preferences and their 
	 * information for Ampache to use All preferences will get a 
	 * localplay_mpd_ appended to their name to avoid conflicts
	 * however this controller does not need to take that into acount
	 * REQUIRE for Locaplay
	 */
	function preferences() { 

		$preferences = array(); 

		$preferences[] = array('name'=>'hostname','default'=>'localhost','type'=>'string');
		$preferences[] = array('name'=>'port','default'=>'6600','type'=>'integer');
		$preferences[] = array('name'=>'password','default'=>'','type'=>'string');

		return $preferences;

	} // preferences


	/**
	 * add_songs
	 * This must take an array of URL's from Ampache
	 * and then add them to MPD
	 */
	function add_songs($songs) { 

		foreach ($songs as $song_id) { 
			$song = new Song($song_id);
			$url = $song->get_url();
			if (is_null($this->_mpd->PlAdd($url)) { 
				debug_event('mpd_add','Error: Unable to add $url to MPD ' . $this->_mpd->errStr,'1');
			}

		} // end foreach

		return true;

	} // add_songs


	/**
	 * delete_songs
	 * This must take an array of ID's (as passed by get function) from Ampache
	 * and delete them from MPD
	 */
	function delete_songs($songs) { 

		/* Default to true */
		$return = true;

		/* This should be an array of UID's as returned by
		 * the get function so that we can just call the class based 
		 * functions to remove them or if there isn't a uid for 
		 * the songs, then however ya'll have stored them
		 * in this controller 
		 */
		foreach ($songs as $uid) { 

			if (is_null($this->_mpd->PLRemove($uid)) { $return = false; } 

		} // foreach of songs

		return $return;

	} // delete_songs
	

	/**
	 * play
	 * This just tells MPD to start playing, it does not
	 * take any arguments
	 */
	function play() { 

		if (is_null($this->_mpd->Play()) { return false; } 

		return true;

	} // play

	/**
	 * stop
	 * This just tells MPD to stop playing, it does not take
	 * any arguments
	 */
	function stop() { 

		if (is_null($this->_mpd->Stop()) { return false; } 

		return true;

	} // stop


	/**
	 * get_songs
	 * This functions returns an array containing information about
	 * The songs that MPD currently has in it's playlist. This must be
	 * done in a standardized fashion
	 */
	function get_songs() { 

		/* Get the Current Playlist */
		$playlist = $this->_mpd->playlist;

		foreach ($playlist as $key=>$entry) { 
		
			$data = array();

			/* Required Elements */
			$data['id'] 	= $entry['Pos'];
			$data['raw']	= $entry['file'];

			/* Optional Elements */
			$data['name']	= '';

			$results[] = $data;

		} // foreach playlist items

	} // get_songs

	/**
	 * get_status
	 * This returns bool/int values for features, loop, repeat and any other features
	 * That this localplay method support
	 */
	function get_status() { 




	} // get_status

	/**
	 * connect
	 * This functions creates the connection to MPD and returns
	 * a boolean value for the status, to save time this handle
	 * is stored in this class
	 */
	function connect() { 

		$this->_mpd = new mpd(conf('localplay_mpd_hostnmae'),conf('localplay_mpd_port'));

		if ($this->_mpd->connected) { return true; } 

		return false;

	} // connect
	
} //end of AmpacheMpd

?>
