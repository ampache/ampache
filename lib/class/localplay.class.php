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

class Localplay {

	/* Base Variables */
	var $type;



	/* Built Variables */
	var $_function_map = array(); 
	var $_template;
	var $_preferences = array();
	var $_player; 


	/**
	 * Constructor
	 * This must be called with a localplay type, it then loads the config
	 * file for the specified type and attempts to load in the function
	 * map, the preferences and the template
	 */
	function Localplay($type) { 


		$this->type = $type;

		$this->_get_info();

	} // Localplay


	/**
	 * _get_info
	 * This functions takes the type and attempts to get all the 
	 * information needed to load it. Will log errors if there are
	 * any failures, fatal errors will actually return something to the
	 * gui
	 */
	function _get_info() { 

		$this->_load_player();


	} // _get_info


	/**
	 * _load_player
	 * This function attempts to load the player class that localplay
	 * Will interface with in order to make all this magical stuf work
	 * all LocalPlay modules should be located in /modules/<name>/<name>.class.php
	 */
	function _load_player() { 

		if (!$this->type) { return false; } 

		$filename = conf('prefix') . '/modules/localplay/' . $this->type . '.controller.php';
		$include = require_once ($filename);
		
		if (!$include) { 
			/* Throw Error Here */
			debug_event('localplay','Unable to load ' . $this->type . ' controller','2');
			return false; 
		} // include
		else { 
			$class_name = "Ampache" . $this->type;
			$this->_player = new $class_name();
			$function_map = $this->_player->function_map();
			$this->_map_functions($function_map);
		}
		
	} // _load_player

	/**
	 * has_function
	 * This is used to check the function map and see if the current
	 * player type supports the indicated function. 
	 */
	function has_function($function_name) { 

		/* Check the function map, if it's got a value it must 
		 * be possible 
		 */
		if (strlen($this->_function_map[$function_name]) > 0) { return true; } 

		return false;

	} // has_function

	/**
	 * format_name
	 * This function takes the track name and checks to see if 'skip' 
	 * is supported in the current player, if so it returns a 'skip to'
	 * link, otherwise it returns just the text
	 */
	function format_name($name,$id) { 

		$name = scrub_out($name);

		if ($this->has_function('skip')) { 
			$url = conf('ajax_url') . "?action=localplay&amp;cmd=skip&amp;value=$id" . conf('ajax_info'); 
			
			$name = "<span style=\"cursor:pointer;text-decoration:underline;\" onclick=\"ajaxPut('$url');return true;\">$name</span>";
		}

		return $name;

	} // format_name

	/**
	 * _map_functions
	 * This takes the results from the loaded from the target player
	 * and maps them to the defined functions that Ampache currently 
	 * supports, this is broken into require and optional componets
	 * Failure of required componets will cause log entry and gui
	 * warning. The value of the elements in the $data array should
	 * be function names that are called on the action in question
	 */
	function _map_functions($data) { 
		
		/* Required Functions */
		$this->_function_map['add']	= $data['add'];
		$this->_function_map['delete']	= $data['delete'];
		$this->_function_map['play']	= $data['play'];
		$this->_function_map['stop']	= $data['stop'];
		$this->_function_map['get']	= $data['get'];
		$this->_function_map['connect'] = $data['connect'];
		$this->_function_map['status']		= $data['status'];

		/* Recommended Functions */
		$this->_function_map['pause']		= $data['pause'];
		$this->_function_map['next']		= $data['next'];
		$this->_function_map['prev']		= $data['prev'];
		$this->_function_map['skip']		= $data['skip'];
		$this->_function_map['get_playlist']	= $data['get_playlist'];
		$this->_function_map['get_playing']	= $data['get_playing'];
		$this->_function_map['repeat']		= $data['repeat'];
		$this->_function_map['random']		= $data['random'];
		$this->_function_map['loop']		= $data['loop'];

		/* Optional Functions */
		$this->_function_map['volume_set']	= $data['volume_set'];
		$this->_function_map['volume_up']	= $data['volume_up'];
		$this->_function_map['volume_down']	= $data['volume_down'];
		$this->_function_map['delete_all']	= $data['delete_all'];
		$this->_function_map['randomize']	= $data['randomize'];
		$this->_function_map['move']		= $data['move'];
		$this->_function_map['add_url']		= $data['add_url'];

	} // _map_functions

	/**
	 * connect
	 * This function attempts to connect to the localplay 
	 * player that we are using
	 */
	function connect() { 

		$function = $this->_function_map['connect'];
	
		/* This is very bad, that means they don't even 
		 * have a connection function defined
		 */
		if (!$function) { return false; } 
	
		if (!$this->_player->$function()) { 
			debug_event('localplay','Error Unable to connect, check ' . $this->type . ' controller','1');
			return false;
		}

		return true;
	
	} // connect

	/**
	 * play
	 * This function passes NULL and calls the play function of the player
	 * object
	 */
	function play() { 
	
		$function = $this->_function_map['play'];

		if (!$this->_player->$function()) { 
			debug_event('localplay','Error Unable to start playback, check ' . $this->type . ' controller','1');
			return false;
		}
		
		return true;

	} // play

	/**
	 * stop
	 * This functions passes NULl and calls the stop function of the player
	 * object, it should recieve a true/false boolean value
	 */
	function stop() { 

		$function = $this->_function_map['stop'];

		if (!$this->_player->$function()) { 
			debug_event('localplay','Error Unable to stop playback, check ' . $this->type . ' controller','1');
			return false;
		}

		return true;

	} // stop

	/**
	 * add
	 * This function takes an array of song_ids and then passes the full URL
	 * to the player, this is a required function. 
	 */
	function add($songs) { 


		/* Call the Function Specified in the Function Map */
		$function = $this->_function_map['add'];

		if (!$this->_player->$function($songs)) { 
			debug_event('localplay','Error Unable to add songs, check ' . $this->type . ' controller','1');
			return false;
		}

		
		return true;

	} // add

	/**
	 * add_url	
	 * This directly adds an array of URLs to the localplay module. This is really how I should
	 * have done add, will migrate to this eventually
	 */
	function add_url($urls) { 

		$function = $this->_function_map['add_url'];
		
		if (!$this->_player->$function($urls)) { 
			debug_event('localplay','Error Unable to add urls, check ' . $this->type . ' controller','1');
			return false; 
		} 


		return true; 

	} // add_url

	/**
	 * repeat
	 * This turns the repeat feature of a localplay method on or 
	 * off, takes a 0/1 value
	 */
	function repeat($state) { 

		$function = $this->_function_map['repeat'];

		$data = $this->_player->$function($state);

		if (!$data) { 
			debug_event('localplay',"Error Unable to set Repeat to $state",'1');
		}

		return $data;

	} // repeat

	/**
 	 * random
	 * This turns on the random feature of a localplay method
	 * It takes a 0/1 value 
	 */
	function random($state) { 
		
		$function = $this->_function_map['random'];

		$data = $this->_player->$function($state); 

		if (!$data) { 
			debug_event('localplay',"Error Unable to set Random to $state",'1');
		}
	
		return $data;

	} // random

	/**
	 * status
	 * This returns current information about the state of the player
	 * There is an expected array format
	 */
	function status() { 

		$function = $this->_function_map['status'];

		$data = $this->_player->$function();

		if (!count($data)) { 
			debug_event('localplay','Error Unable to get status, check ' . $this->type . ' controller','1');
			return false;
		}

		return $data;

	} // status

	/**
	 * get
	 * This calls the get function of the player and then returns
	 * the array of current songs for display or whatever
	 * an empty array is passed on failure
	 */
	function get() { 

		$function = $this->_function_map['get'];

		$data = $this->_player->$function();

		if (!count($data) OR !is_array($data)) { 
			debug_event('localplay','Error Unable to get song info, check ' . $this->type . ' controller','1');
			return array(); 
		}
		
		return $data;

	} // get

	/**
	 * volume_set
	 * This isn't a required function, it sets the volume to a specified value
	 * as passed in the variable it is a 0 - 100 scale the controller is 
	 * responsible for adjusting the scale if nessecary
	 */
	function volume_set($value) { 
		
		/* Make sure it's int and 0 - 100 */
		$value = int($value);

		/* Make sure that it's between 0 and 100 */
		if ($value > 100 OR $value < 0) { return false; }

		$function = $this->_function_map['volume_set'];
		
		if (!$this->_player->$function($value)) { 
			debug_event('localplay','Error: Unable to set volume, check ' . $this->type . ' controller','1');
			return false;
		}

		return true;

	} // volume_set

	/**
	 * volume_up
	 * This function isn't required. It tells the daemon to increase the volume
	 * by a pre-defined amount controlled by the controller
	 */
	function volume_up() { 

		$function = $this->_function_map['volume_up'];

		if (!$this->_player->$function()) { 
			debug_event('localplay','Error: Unable to increase volume, check ' . $this->type . ' controller','1');
			return false; 
		}

		return true;

	} // volume_up

	/**
	 * volume_down
	 * This function isn't required. It tells the daemon to decrese the volume
	 * by a pre-defined amount controlled by the controller.
	 */
	function volume_down() { 

		$function = $this->_function_map['volume_down'];

		if (!$this->_player->$function()) { 
			debug_event('localplay','Error: Unable to decrese volume, check ' . $this->type . ' controller','1');
			return false; 
		} 

		return true;

	} // volume_down

	/**
	 * volume_mute
	 * This function isn't required, It tells the daemon to mute all output
	 * It's up to the controller to decide what that actually entails
	 */
	function volume_mute() { 

		$function = $this->_function_map['volume_mute'];

		if (!$this->_player->$function()){ 
			debug_event('localplay','Error: Unable to mute volume, check ' . $this->type . ' controller','1');
			return false; 
		}

		return true; 

	} // volume_mute

	/**
	 * skip
	 * This isn't a required function, it tells the daemon to skip to the specified song
	 */
	function skip($song_id) { 

		$function = $this->_function_map['skip'];

		if (!$this->_player->$function($song_id)) { 
			debug_event('localplay','Error: Unable to skip to next song, check ' . $this->type . ' controller','1');
			return false; 
		}

		return true;

	} // skip

	/**
	 * next
	 * This isn't a required function, it tells the daemon to go to the next 
	 * song
	 */
	function next() { 

		$function = $this->_function_map['next'];
		
		if (!$this->_player->$function()) { 
			debug_event('localplay','Error: Unable to skip to next song, check ' . $this->type . ' controller','1');
			return false; 
		}

		return true;

	} // next

	/**
	 * prev
	 * This isn't a required function, it tells the daemon to go the the previous
	 * song
	 */
	function prev() { 
		
		$function = $this->_function_map['prev'];

		if (!$this->_player->$function()) { 
			debug_event('localplay','Error: Unable to skip to previous song, check ' . $this->type . ' controller','1');
			return false;
		}

		return true;

	} // prev

       /**
        * pause
        * This isn't a required function, it tells the daemon to pause the
        * song
        */
        function pause() {

                $function = $this->_function_map['pause'];

                if (!$this->_player->$function()) {
                        debug_event('localplay','Error: Unable to pause song, check ' . $this->type . ' controller','1');
                        return false;
                }

                return true;

        } // pause

	/**
	 * get_preferences
	 * This functions returns an array of the preferences that the localplay 
	 * controller needs in order to actually work
	 */
	function get_preferences() { 

		$preferences = $this->_player->preferences();
		
		return $preferences;

	} // get_preferences

	/**
	 * delete
	 * This removes songs from the players playlist as defined get function
	 */
	function delete($songs) { 

		$function = $this->_function_map['delete'];

		if (!$this->_player->$function($songs)) { 
			debug_event('localplay','Error: Unable to remove songs, check ' . $this->type . ' controller','1');
			return false;
		}


		return true;

	} // delete

	/**
	 * delete_all
	 * This removes every song from the players playlist as defined by the delete_all function
	 * map
	 */
	function delete_all() { 


		$function = $this->_function_map['delete_all'];

		if (!$this->_player->$function($songs)) { 
			debug_event('localplay','Error: Unable to delete entire playlist, check ' . $this->type . ' controller','1');
			return false; 
		}

		return true;

	} // delete_all

	/**
	 * get_user_state
	 * This function returns a user friendly version
	 * of the current player state
	 */
	function get_user_state($state) { 
		
		switch ($state) { 
			case 'play':
				return _('Now Playing');
			break;
			case 'stop':
				return _('Stopped');
			break;
			case 'pause':
				return _('Paused');
			break;
			default:
				return _('Unknown');
			break;
		} // switch on state	

	} // get_user_state

	/**
	 * get_user_playing
	 * This attempts to return a nice user friendly
	 * currently playing string
	 */
	function get_user_playing() { 

		$status = $this->status();
		
		/* Format the track name */
		$track_name = $status['track_artist'] . ' - ' . $status['track_album'] . ' - ' . $status['track_title'];

		/* This is a cheezball fix for when we were unable to find a
		 * artist/album (or one wasn't provided)
		 */
		$track_name = ltrim(ltrim($track_name,' - '),' - ');

		$track_name = "[" . $status['track'] . "] - " . $track_name;

		return $track_name;
	
	} // get_user_playing


} //end localplay class
?>
