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
	var $_mpd;

	/**
	 * Constructor
	 * This returns the array map for the localplay object
	 * REQUIRED for Localplay
	 */
	function AmpacheMpd() { 
	
		/* Do a Require Once On the needed Libraries */
		require_once(conf('prefix') . '/modules/mpd/mpd.class.php');

	} // AmpacheMpd


	/**
	 * function_map
	 * This function returns a named array of the functions
	 * that this player supports and their names in this local
	 * class. This is a REQUIRED function
	 */
	function function_map() { 

                $map = array();

		/* Required Functions */
                $map['add']             = 'add_songs';
                $map['delete']          = 'delete_songs';
                $map['play']            = 'play';
                $map['stop']            = 'stop';
                $map['get']             = 'get_songs';
		$map['status']		= 'get_status';
                $map['connect']         = 'connect';
		
		/* Recommended Functions */
		$map['skip']		= 'skip';
		$map['next']		= 'next';
		$map['prev']		= 'prev';
		$map['pause']		= 'pause';
		$map['volume_up']       = 'volume_up';
		$map['volume_down']	= 'volume_down';
		$map['random']          = 'random';
		$map['repeat']		= 'loop';

		/* Optional Functions */
		$map['move']		= 'move';
		$map['delete_all']	= 'clear_playlist';

                return $map;

	} // function_map

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

		$preferences[] = array('name'=>'hostname','default'=>'localhost','type'=>'string','description'=>'MPD Hostname');
		$preferences[] = array('name'=>'port','default'=>'6600','type'=>'integer','description'=>'MPD Port');
		$preferences[] = array('name'=>'password','default'=>'','type'=>'string','description'=>'MPD Password');

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
			if (is_null($this->_mpd->PlAdd($url))) { 
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

			if (is_null($this->_mpd->PLRemove($uid))) { $return = false; } 

		} // foreach of songs

		return $return;

	} // delete_songs
	

	/**
	 * clear_playlist
	 * This deletes the entire MPD playlist... nuff said
	 */
	function clear_playlist() { 

		if (is_null($this->_mpd->PLClear())) { return false; }

		return true;

	} // clear_playlist

	/**
	 * play
	 * This just tells MPD to start playing, it does not
	 * take any arguments
	 */
	function play() { 

		if (is_null($this->_mpd->Play())) { return false; } 
		return true;

	} // play

	/**
	 * stop
	 * This just tells MPD to stop playing, it does not take
	 * any arguments
	 */
	function stop() { 

		if (is_null($this->_mpd->Stop())) { return false; } 
		return true;

	} // stop

	/**
	 * skip
	 * This tells MPD to skip to the specified song
	 */
	function skip($song) { 

		if (is_null($this->_mpd->SkipTo($song))) { return false; }
		return true; 

	} // skip

	/**
	 * This tells MPD to increase the volume by 5
	 */
	function volume_up() { 

		if (is_null($this->_mpd->AdjustVolume('5'))) { return false; } 
		return true;

	} // volume_up

	/**
	 * This tells MPD to decrese the volume by 5
	 */
	function volume_down() { 

		if (is_null($this->_mpd->AdjustVolume('-5'))) { return false; }
		return true;
		
	} // volume_down

	/**
	 * next
	 * This just tells MPD to skip to the next song 
	 */
	function next() { 

		if (is_null($this->_mpd->Next())) { return false; } 
		return true;

	} // next

	/**
	 * prev
	 * This just tells MPD to skip to the prev song
	 */
	function prev() { 

		if (is_null($this->_mpd->Previous())) { return false; } 
		return true;
	
	} // prev

	/**
	 * pause
	 * This tells MPD to pause the current song 
	 */
	function pause() { 
		
		if (is_null($this->_mpd->Pause())) { return false; } 
		return true;

	} // pause 


        /**
        * volume
        * This tells MPD to set the volume to the parameter
        */
       function volume($volume) {

               if (is_null($this->_mpd->SetVolume($volume))) { return false; }
               return true;

       } // volume

       /**
        * loop
        * This tells MPD to set the repeating the playlist (i.e. loop) to either on or off
        */
       function loop($state) {
	
		if (is_null($this->_mpd->SetRepeat($state))) { return false; }
       		return true;

       } // loop


       /**
        * random
        * This tells MPD to turn on or off the playing of songs from the playlist in random order
        */
       function random($onoff) {

               if (is_null($this->_mpd->SetRandom($onoff))) { return false; }
               return true;

       } // random

       /**
        * move
        * This tells MPD to move song from SrcPos to DestPos
        */
       function move($SrcPos, $DestPos) {

		if (is_null($this->_mpd->PLMoveTrack($SrcPos, $DestPos))) { return false; }

        	return true;
	} // move

	/**
	 * get_songs
	 * This functions returns an array containing information about
	 * The songs that MPD currently has in it's playlist. This must be
	 * done in a standardized fashion
	 */
	function get_songs() { 

		/* Get the Current Playlist */
		$playlist = $this->_mpd->playlist;
		
		foreach ($playlist as $entry) { 
			$data = array();

			/* Required Elements */
			$data['id'] 	= $entry['Pos'];
			$data['raw']	= $entry['file'];		

			/* Parse out the song ID and then create the song object */
			preg_match("/song=(\d+)\&/",$entry['file'],$matches);
			
			/* If we don't know it, look up by filename */
			if (!$song->title) { 
				$filename = sql_escape($entry['file']);
				$sql = "SELECT id FROM song WHERE file = '$filename'";
				$db_results = mysql_query($sql, dbh());
				if ($results = mysql_fetch_assoc($db_results)) { 
					$song = new Song($results['id']);
				}	
				else { 
					$song = new Song(); 
					$song->title = _('Unknown');
				}
			}
			else { 
				$song = new Song($matches['1']);
			}

			/* Make the name pretty */
			$song->format_song();
			$data['name']	= $song->f_title . ' - ' . $song->f_album . ' - ' . $song->f_artist;

			/* Optional Elements */
			$data['link']   = '';
			$data['track']	= $entry['Pos'];

			$results[] = $data;

		} // foreach playlist items
		
		return $results;

	} // get_songs

	/**
	 * get_status
	 * This returns bool/int values for features, loop, repeat and any other features
	 * That this localplay method support
	 */
	function get_status() { 

		$track = $this->_mpd->current_track_id;

		/* Construct the Array */
		$array['state'] 	= $this->_mpd->state;
		$array['volume']	= $this->_mpd->volume;
		$array['repeat']	= $this->_mpd->repeat;
		$array['random']	= $this->_mpd->random;
		$array['track']		= $track;

		preg_match("/song=(\d+)\&/",$this->_mpd->playlist[$track]['file'],$matches);
		$song_id = $matches['1'];
		$song = new Song($song_id);
		$array['track_title'] 	= $song->title;
		$array['track_artist'] 	= $song->get_artist_name();
		$array['track_album']	= $song->get_album_name();

		return $array;

	} // get_status

	/**
	 * connect
	 * This functions creates the connection to MPD and returns
	 * a boolean value for the status, to save time this handle
	 * is stored in this class
	 */
	function connect() { 
		
		$this->_mpd = new mpd(conf('localplay_mpd_hostname'),conf('localplay_mpd_port'),conf('localplay_mpd_password'));

		if ($this->_mpd->connected) { return true; } 

		return false;

	} // connect
	
} //end of AmpacheMpd

?>
