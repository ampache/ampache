<?php
/*

 Copyright 2001 - 2006 Ampache.org
 All Rights Reserved

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
 * AmpacheHttpQ Class
 * This is the class for the HttpQ localplay method to remote control
 * a WinAmp Instance
 */
class AmpacheHttpq {

	/* Variables */
	

	/* Constructed variables */
	var $_httpq;

	/**
	 * Constructor
	 * This returns the array map for the localplay object
	 * REQUIRED for Localplay
	 */
	function AmpacheHttpq() { 
	
		/* Do a Require Once On the needed Libraries */
		require_once(conf('prefix') . '/modules/httpq/httpqplayer.class.php');

	} // AmpacheHttpq


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
		$map['delete_all']	= 'clear_playlist';
		$map['add_url']		= 'add_url';

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

		$preferences[] = array('name'=>'hostname','default'=>'localhost','type'=>'string','description'=>'HttpQ Hostname');
		$preferences[] = array('name'=>'port','default'=>'4800','type'=>'integer','description'=>'HttpQ Port');
		$preferences[] = array('name'=>'password','default'=>'','type'=>'string','description'=>'HttpQ Password');

		return $preferences;

	} // preferences


	/**
	 * add_songs
	 * This must take an array of URL's from Ampache
	 * and then add them to HttpQ
	 */
	function add_songs($songs) { 

		foreach ($songs as $song_id) { 
			$song = new Song($song_id);
			$url = $song->get_url(0,1);
			if (is_null($this->_httpq->add($song->title,$url))) { 
				debug_event('httpq_add',"Error: Unable to add $url to Httpq",'1');
			}

		} // end foreach

		return true;

	} // add_songs

	/**
 	 * add_url
	 * This adds urls directly to the playlist, recieves an array of urls 
	 */
	function add_url($urls) { 

		foreach ($urls as $url) { 
			if (is_null($this->_httpq->add('URL',$url))) { 
				debug_event('httpq_add',"Error: Unable to add $url to Httpq ",'1');
			}

		} // end foreach

		return true; 

	} // add_url 

	/**
	 * delete_songs
	 * This must take an array of ID's (as passed by get function) from Ampache
	 * and delete them from Httpq
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

			if (is_null($this->_httpq->delete_pos($uid))) { $return = false; } 

		} // foreach of songs

		return $return;

	} // delete_songs
	
	/**
	 * clear_playlist
	 * This deletes the entire Httpq playlist... nuff said
	 */
	function clear_playlist() { 

		if (is_null($this->_httpq->clear())) { return false; }

		// If the clear worked we should stop it!
		$this->stop(); 

		return true;

	} // clear_playlist

	/**
	 * play
	 * This just tells HttpQ to start playing, it does not
	 * take any arguments
	 */
	function play() { 

		/* A play when it's already playing causes a track restart
		 * which we don't want to doublecheck its state
		 */
		if ($this->_httpq->state() == 'play') { 
			return true; 
		} 

		if (is_null($this->_httpq->play())) { return false; } 
		return true;

	} // play

	/**
	 * stop
	 * This just tells HttpQ to stop playing, it does not take
	 * any arguments
	 */
	function stop() { 

		if (is_null($this->_httpq->stop())) { return false; } 
		return true;

	} // stop

	/**
	 * skip
	 * This tells HttpQ to skip to the specified song
	 */
	function skip($song) { 

		if (is_null($this->_httpq->skip($song))) { return false; }
		return true; 

	} // skip

	/**
	 * This tells Httpq to increase the volume by WinAmps default amount
	 */
	function volume_up() { 

		if (is_null($this->_httpq->volume_up())) { return false; } 
		return true;

	} // volume_up

	/**
	 * This tells HttpQ to decrease the volume by Winamps default amount
	 */
	function volume_down() { 

		if (is_null($this->_httpq->volume_down())) { return false; }
		return true;
		
	} // volume_down

	/**
	 * next
	 * This just tells MPD to skip to the next song 
	 */
	function next() { 

		if (is_null($this->_httpq->next())) { return false; } 

		return true;

	} // next

	/**
	 * prev
	 * This just tells MPD to skip to the prev song
	 */
	function prev() { 

		if (is_null($this->_httpq->prev())) { return false; } 

		return true;
	
	} // prev

	/**
	 * pause
	 * This tells MPD to pause the current song 
	 */
	function pause() { 
		
		if (is_null($this->_httpq->pause())) { return false; } 
		return true;

	} // pause 

        /**
        * volume
        * This tells HttpQ to set the volume to the specified amount this
	* is 0-100
        */
       function volume($volume) {

               if (is_null($this->_httpq->set_volume($volume))) { return false; }
               return true;

       } // volume

       /**
        * loop
        * This tells HttpQ to set the repeating the playlist (i.e. loop) to either on or off
        */
       function loop($state) {
	
		if (is_null($this->_httpq->repeat($state))) { return false; }
       		return true;

       } // loop

       /**
        * random
        * This tells HttpQ to turn on or off the playing of songs from the playlist in random order
        */
       function random($onoff) {

               if (is_null($this->_httpq->random($onoff))) { return false; }
               return true;

       } // random

	/**
	 * get_songs
	 * This functions returns an array containing information about
	 * The songs that HttpQ currently has in it's playlist. This must be
	 * done in a standardized fashion
	 */
	function get_songs() { 

		/* Get the Current Playlist */
		$list = $this->_httpq->get_tracks();

		$songs = explode("::",$list); 
		
		foreach ($songs as $key=>$entry) { 
			$data = array();
			
			/* Required Elements */
			$data['id'] 	= $key;
			$data['raw']	= $entry;		

			/* Parse out the song ID and then create the song object */
			preg_match("/song=(\d+)\&/",$entry,$matches);

			/* Attempt to build the new song */
			$song = new Song($matches['1']);
			
			/* If we don't know it, look up by filename */
			if (!$song->title) { 
				$filename = sql_escape($entry);
				$sql = "SELECT id FROM song WHERE file LIKE '%$filename'";
				$db_results = mysql_query($sql, dbh());
				if ($r = mysql_fetch_assoc($db_results)) { 
					$song = new Song($r['id']);
				}	
				else { 
					$song->title = _('Unknown');
				}
			}

			/* Make the name pretty */
			$song->format_song();
			$data['name']	= $song->f_title . ' - ' . $song->f_album . ' - ' . $song->f_artist;

			/* Optional Elements */
			$data['link']   = '';
			$data['track']	= $key+1;

			$results[] = $data;

		} // foreach playlist items
		
		return $results;

	} // get_songs

	/**
	 * get_status
	 * This returns bool/int values for features, loop, repeat and any other features
	 * That this localplay method supports. required function
	 */
	function get_status() { 

		/* Construct the Array */
		$array['state'] 	= $this->_httpq->state();
		$array['volume']	= $this->_httpq->get_volume();
		$array['repeat']	= $this->_httpq->get_repeat();
		$array['random']	= $this->_httpq->get_random();
		$array['track']		= $this->_httpq->get_now_playing();

		preg_match("/song=(\d+)\&/",$array['track'],$matches);
		$song_id = $matches['1'];
		$song = new Song($song_id);
		$array['track_title'] 	= $song->title;
		$array['track_artist'] 	= $song->get_artist_name();
		$array['track_album']	= $song->get_album_name();

		return $array;

	} // get_status

	/**
	 * connect
	 * This functions creates the connection to HttpQ and returns
	 * a boolean value for the status, to save time this handle
	 * is stored in this class
	 */
	function connect() { 
		
		$this->_httpq = new HttpQPlayer(conf('localplay_httpq_hostname'),conf('localplay_httpq_password'),conf('localplay_httpq_port'));

		// Test our connection by retriving the version
		if (!is_null($this->_httpq->version())) { return true; } 

		return false;

	} // connect
	
} //end of AmpacheHttpq

?>
