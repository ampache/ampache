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

class Localplay {

	/* Base Variables */
	public $type;

	/* Built Variables */
	private $_function_map = array(); 
	private $_template;
	private $_preferences = array();
	private $_player; 

	/**
	 * Constructor
	 * This must be called with a localplay type, it then loads the config
	 * file for the specified type and attempts to load in the function
	 * map, the preferences and the template
	 */
	public function __construct($type) { 

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
	private function _get_info() { 

		$this->_load_player();

	} // _get_info

	/**
	 * player_loaded
	 * This returns true / false if the player load
	 * failed / worked
	 */
	public function player_loaded() { 

		if (is_object($this->_player)) { 
			return true; 
		} 
		else { 
			return false; 
		} 

	} // player_loaded

	/**
 	 * format
	 * This makes the localplay/plugin information 
	 * human readable 
	 */
	public function format() { 

		if (!is_object($this->_player)) { return false; } 

		$this->f_name 		= ucfirst($this->type); 
		$this->f_description 	= $this->_player->get_description(); 
		$this->f_version	= $this->_player->get_version(); 


	} // format

	/**
	 * _load_player
	 * This function attempts to load the player class that localplay
	 * Will interface with in order to make all this magical stuf work
	 * all LocalPlay modules should be located in /modules/<name>/<name>.class.php
	 */
	private function _load_player() { 

		if (!$this->type) { return false; } 

		$filename = Config::get('prefix') . '/modules/localplay/' . $this->type . '.controller.php';
		$include = require_once ($filename);
		
		if (!$include) { 
			/* Throw Error Here */
			debug_event('localplay','Unable to load ' . $this->type . ' controller','2');
			return false; 
		} // include
		else { 
			$class_name = "Ampache" . $this->type;
			$this->_player = new $class_name();
			if (!($this->_player instanceof localplay_controller)) { 
				debug_event('Localplay',$this->type . ' not an instance of controller abstract, unable to load','1'); 
				unset($this->_player); 
				return false; 
			} 
		}
		
	} // _load_player

	/**
	 * format_name
	 * This function takes the track name and checks to see if 'skip' 
	 * is supported in the current player, if so it returns a 'skip to'
	 * link, otherwise it returns just the text
	 */
	public function format_name($name,$id) { 

		$name = scrub_out($name);
		$name = Ajax::text('?page=localplay&action=command&command=skip&id=' . $id,$name,'localplay_skip_' . $id); 
		return $name;

	} // format_name

	/**
	 * get_controllers
	 * This returns the controllers that are currently loaded into this instance
	 */
	public static function get_controllers() { 

		/* First open the dir */
		$handle = opendir(Config::get('prefix') . '/modules/localplay'); 
		
		if (!is_resource($handle)) { 
			debug_event('Localplay','Error: Unable to read localplay controller directory','1'); 
			return array(); 
		}

		$results = array(); 

		while ($file = readdir($handle)) { 

			if (substr($file,-14,14) != 'controller.php') { continue; } 

			/* Make sure it isn't a dir */
			if (!is_dir($file)) { 
				/* Get the basename and then everything before controller */
				$filename = basename($file,'.controller.php'); 
				$results[] = $filename; 
			} 
		} // end while

		return $results; 

	} // get_controllers

	/**
	 * is_enabled
	 * This returns true or false depending on if the specified controller
	 * is currently enabled
	 */
	public static function is_enabled($controller) { 

		// Load the controller and then check for its preferences
		$localplay = new Localplay($controller); 
		// If we can't even load it no sense in going on 
		if (!isset($localplay->_player)) { return false; } 

		return $localplay->_player->is_installed();  

	} // is_enabled

	/**
	 * install
	 * This runs the install for the localplay controller we've
	 * currently got pimped out
	 */
	public function install() { 

		// Run the player's installer
		$installed = $this->_player->install(); 
		
		return $installed; 

	} // install

	/**
	 * uninstall
	 * This runs the uninstall for the localplay controller we've 
	 * currently pimped out
	 */
	public function uninstall() { 

		// Run the players uninstaller
		$this->_player->uninstall(); 

		// If its our current player, reset player to nothing
		if (Config::get('localplay_controller') == $this->type) { 
			Preference::update('localplay_controller',$GLOBALS['user']->id,''); 
		} 

		return true; 

	} // uninstall

	/**
	 * connect
	 * This function attempts to connect to the localplay 
	 * player that we are using
	 */
	public function connect() { 

		if (!$this->_player->connect()) { 
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
	public function play() { 
	
		if (!$this->_player->play()) { 
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
	public function stop() { 

		if (!$this->_player->stop()) { 
			debug_event('localplay','Error Unable to stop playback, check ' . $this->type . ' controller','1');
			return false;
		}

		return true;

	} // stop

	/**
	 * add
	 * This function takes a single object and then passes it to
	 * to the player, this is a required function. 
	 */
	public function add($object) { 

		if (!$this->_player->add($object)) { 
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
	public function add_url($urls) { 

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
	public function repeat($state) { 

		$data = $this->_player->repeat($state);

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
	public function random($state) { 
		
		$data = $this->_player->random($state); 

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
	public function status() { 

		$data = $this->_player->status();

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
	public function get() { 

		$data = $this->_player->get();
		
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
	public function volume_set($value) { 
		
		/* Make sure it's int and 0 - 100 */
		$value = int($value);

		/* Make sure that it's between 0 and 100 */
		if ($value > 100 OR $value < 0) { return false; }

		if (!$this->_player->volume($value)) { 
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
	public function volume_up() { 

		if (!$this->_player->volume_up()) { 
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
	public function volume_down() { 

		if (!$this->_player->volume_down()) { 
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
	public function volume_mute() { 

		if (!$this->_player->volume(0)){ 
			debug_event('localplay','Error: Unable to mute volume, check ' . $this->type . ' controller','1');
			return false; 
		}

		return true; 

	} // volume_mute

	/**
	 * skip
	 * This isn't a required function, it tells the daemon to skip to the specified song
	 */
	public function skip($track_id) { 

		if (!$this->_player->skip($track_id)) { 
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
	public function next() { 

		if (!$this->_player->next()) { 
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
	public function prev() { 
		

		if (!$this->_player->prev()) { 
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
        public function pause() {

                if (!$this->_player->pause()) {
                        debug_event('localplay','Error: Unable to pause song, check ' . $this->type . ' controller','1');
                        return false;
                }

                return true;

        } // pause

	/**
	 * get_instances
	 * This returns the instances of the current type
	 */
	public function get_instances() { 

		$instances = $this->_player->get_instances(); 

		return $instances; 

	} // get_instances

	/**
	 * current_instance
	 * This returns the UID of the current Instance
	 */
	public function current_instance() { 

		$data = $this->_player->get_instance(); 

		return $data['id']; 

	} // current_instance

	/**
	 * get_instance
	 * This returns the specified instance
	 */
	public function get_instance($uid) { 

		$data = $this->_player->get_instance($uid); 
		
		return $data; 

	} // get_instance

	/**
	 * update_instance
	 * This updates the specified instance with a named array of data (_POST most likely)
	 */
	public function update_instance($uid,$data) { 

		$data = $this->_player->update_instance($uid,$data); 

		return $data; 

	} // update_instance

	/**
	 * add_instance
	 * This adds a new instance for the current controller type
	 */
	public function add_instance($data) { 

		$this->_player->add_instance($data); 

	} // add_instance

	/**
	 * delete_instance
	 * This removes an instance (it actually calls the players function)
	 */
	public function delete_instance($instance_uid) { 

		$this->_player->delete_instance($instance_uid); 

	} // delete_instance

	/**
	 * set_active_instance
	 * This sets the active instance of the localplay controller
	 */
	public function set_active_instance($instance) { 

		$this->_player->set_active_instance($instance); 

	} // set_active_instance

	/**
	 * delete_track
	 * This removes songs from the players playlist it takes a single ID as provided
	 * by the get command
	 */
	public function delete_track($object_id) { 

		if (!$this->_player->delete_track($object_id)) { 
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
	public function delete_all() { 

		if (!$this->_player->clear_playlist()) { 
			debug_event('localplay','Error: Unable to delete entire playlist, check ' . $this->type . ' controller','1');
			return false; 
		}

		return true;

	} // delete_all

	/**
	 * get_instance_fields
	 * This loads the fields from the localplay 
	 * player and returns them 
	 */
	public function get_instance_fields() { 

		$fields = $this->_player->instance_fields(); 

		return $fields;

	} // get_instance_fields

	/**
	 * get_user_state
	 * This function returns a user friendly version
	 * of the current player state
	 */
	public function get_user_state($state) { 
		
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
	public function get_user_playing() { 

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


} // end localplay class
?>
