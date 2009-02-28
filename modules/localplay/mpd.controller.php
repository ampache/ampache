<?php
/*

 Copyright Ampache.org
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
 * AmpacheMpd Class
 * the Ampache Mpd Controller, this is the glue between
 * the MPD class and the Ampahce Localplay class
 */
class AmpacheMpd extends localplay_controller {

	/* Variables */
	private $version 	= '000001'; 
	private $description	= 'Controls an instance of MPD'; 

	/* Constructed variables */
	private $_mpd;

	/**
	 * Constructor
	 * This returns the array map for the localplay object
	 * REQUIRED for Localplay
	 */
	public function __construct() { 
	
		/* Do a Require Once On the needed Libraries */
		require_once Config::get('prefix') . '/modules/mpd/mpd.class.php';

	} // AmpacheMpd

	/**
	 * get_description
	 * Returns the description
	 */
	public function get_description() { 

		return $this->description; 
	
	} // get_description

	/**
	 * get_version
	 * This returns the version information
	 */
	public function get_version() { 

		return $this->version; 

	} // get_version

	/**
	 * is_installed
	 * This returns true or false if MPD controller is installed
	 */
	public function is_installed() { 

                $sql = "DESCRIBE `localplay_mpd`";
                $db_results = Dba::query($sql);

                return Dba::num_rows($db_results);

	} // is_installed

	/**
	 * install
	 * This function installs the MPD localplay controller
	 */
	public function install() { 

                /* We need to create the MPD table */
                $sql = "CREATE TABLE `localplay_mpd` ( `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
                        "`name` VARCHAR( 128 ) COLLATE utf8_unicode_ci NOT NULL , " .
                        "`owner` INT( 11 ) NOT NULL , " .
                        "`host` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
                        "`port` INT( 11 ) UNSIGNED NOT NULL DEFAULT '6600', " .
                        "`password` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
                        "`access` SMALLINT( 4 ) UNSIGNED NOT NULL DEFAULT '0'" .
                        ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
                $db_results = Dba::query($sql);
		
		// Add an internal preference for the users current active instance
		Preference::insert('mpd_active','MPD Active Instance','0','25','integer','internal'); 
		User::rebuild_all_preferences(); 

                return true;

	} // install

	/**
	 * uninstall
	 * This removes the localplay controller 
	 */
	public function uninstall() { 

                $sql = "DROP TABLE `localplay_mpd`";
                $db_results = Dba::query($sql);

		Preference::delete('mpd_active'); 

                return true;

	} // uninstall

	/**
	 * add_instance
	 * This takes key'd data and inserts a new MPD instance
	 */
	public function add_instance($data) { 

		foreach ($data as $key=>$value) { 
			switch ($key) { 
				case 'name': 
				case 'host': 
				case 'port': 
				case 'password': 
					${$key} = Dba::escape($value); 
				break;
				default: 

				break;
			} // end switch 
		} // end foreach

		$user_id = Dba::escape($GLOBALS['user']->id); 

		$sql = "INSERT INTO `localplay_mpd` (`name`,`host`,`port`,`password`,`owner`) " . 
			"VALUES ('$name','$host','$port','$password','$user_id')";
		$db_results = Dba::query($sql); 
		
		return $db_results; 

	} // add_instance

	/**
 	 * delete_instance
	 * This takes a UID and deletes the instance in question
	 */
	public function delete_instance($uid) { 
		
		$uid = Dba::escape($uid); 

		// Go ahead and delete this mofo!
		$sql = "DELETE FROM `localplay_mpd` WHERE `id`='$uid'"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // delete_instance

	/**
 	 * get_instances
	 * This returns a key'd array of the instance information with 
	 * [UID]=>[NAME]
	 */
	public function get_instances() { 

		$sql = "SELECT * FROM `localplay_mpd` ORDER BY `name`"; 
		$db_results = Dba::query($sql); 

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[$row['id']] = $row['name']; 
		} 

		return $results; 

	} // get_instances

	/**
	 * get_instance
	 * This returns the specified instance and all it's pretty variables
	 * If no instance is passed current is used
	 */
	public function get_instance($instance='') { 

		$instance = $instance ? $instance : Config::get('mpd_active');
		$instance = Dba::escape($instance); 

		$sql = "SELECT * FROM `localplay_mpd` WHERE `id`='$instance'";  
		$db_results = Dba::query($sql); 

		$row = Dba::fetch_assoc($db_results); 

		return $row; 

	} // get_instance

	/**
	 * update_instance
	 * This takes an ID and an array of data and updates the instance specified
	 */
	public function update_instance($uid,$data) { 

		$uid 	= Dba::escape($uid); 
		$host	= $data['host'] ? Dba::escape($data['host']) : '127.0.0.1'; 
		$port	= $data['port'] ? Dba::escape($data['port']) : '6600'; 
		$name	= Dba::escape($data['name']); 
		$pass	= Dba::escape($data['password']); 

		$sql = "UPDATE `localplay_mpd` SET `host`='$host', `port`='$port', `name`='$name', `password`='$pass' WHERE `id`='$uid'"; 
		$db_results = Dba::query($sql); 

		return true; 

	} // update_instance

	/**
	 * instance_fields
	 * This returns a key'd array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
	 * fields so that we can on-the-fly generate a form
	 */
	public function instance_fields() { 

		$fields['name'] 	= array('description'=>_('Instance Name'),'type'=>'textbox'); 
		$fields['host'] 	= array('description'=>_('Hostname'),'type'=>'textbox'); 
		$fields['port']		= array('description'=>_('Port'),'type'=>'textbox'); 
		$fields['password']	= array('description'=>_('Password'),'type'=>'textbox'); 

		return $fields; 

	} // instance_fields

	/**
	 * set_active_instance
	 * This sets the specified instance as the 'active' one
	 */
	public function set_active_instance($uid,$user_id='') { 

		// Not an admin? bubkiss!
		if (!$GLOBALS['user']->has_access('100')) { 
			$user_id = $GLOBALS['user']->id; 
		} 

		$user_id = $user_id ? $user_id : $GLOBALS['user']->id; 

		Preference::update('mpd_active',$user_id,intval($uid)); 
		Config::set('mpd_active',intval($uid),'1'); 

		return true; 

	} // set_active_instance	

	/**
	 * get_active_instance
	 * This returns the UID of the current active instance
	 * false if none are active
	 */
	public function get_active_instance() { 


	} // get_active_instance

	/**
	 * add
	 * This takes a single object and adds it in, it uses the built in 
	 * functions to generate the URL it needs
	 */
	public function add($object) { 

		// If we haven't added anything then check to see if we should clear
		if ($this->_add_count < '1') { 
			if (is_null($this->_mpd->ClearPLIfStopped())) {
		                debug_event('mpd_add', 'Error: Unable to clear the MPD playlist ' . $this->_mpd->errStr,'1');
	         	}
		} // edn if no add count

		$url = $this->get_url($object); 

		if (is_null($this->_mpd->PlAdd($url))) { 
			debug_event('mpd_add',"Error: Unable to add $url to MPD " . $this->_mpd->errStr,'1'); 
			return false; 
		} 
		else { 
			$this->_add_count++; 
		} 
		
		return true;

	} // add_songs

	/**
	 * delete_track
	 * This must take a single ID (as passed by get function) from Ampache
	 * and delete it from the current playlist
	 */
	public function delete_track($object_id) { 

		if (is_null($this->_mpd->PLRemove($object_id))) { return false; } 

		return true;

	} // delete_track
	
	/**
	 * clear_playlist
	 * This deletes the entire MPD playlist... nuff said
	 */
	public function clear_playlist() { 

		if (is_null($this->_mpd->PLClear())) { return false; }

		return true;

	} // clear_playlist

	/**
	 * play
	 * This just tells MPD to start playing, it does not
	 * take any arguments
	 */
	public function play() { 

		if (is_null($this->_mpd->Play())) { return false; } 
		return true;

	} // play

	/**
	 * stop
	 * This just tells MPD to stop playing, it does not take
	 * any arguments
	 */
	public function stop() { 

		if (is_null($this->_mpd->Stop())) { return false; } 
		return true;

	} // stop

	/**
	 * skip
	 * This tells MPD to skip to the specified song
	 */
	public function skip($song) { 

		if (is_null($this->_mpd->SkipTo($song))) { return false; }
		return true; 

	} // skip

	/**
	 * This tells MPD to increase the volume by 5
	 */
	public function volume_up() { 

		if (is_null($this->_mpd->AdjustVolume('5'))) { return false; } 
		return true;

	} // volume_up

	/**
	 * This tells MPD to decrese the volume by 5
	 */
	public function volume_down() { 

		if (is_null($this->_mpd->AdjustVolume('-5'))) { return false; }
		return true;
		
	} // volume_down

	/**
	 * next
	 * This just tells MPD to skip to the next song 
	 */
	public function next() { 

		if (is_null($this->_mpd->Next())) { return false; } 
		return true;

	} // next

	/**
	 * prev
	 * This just tells MPD to skip to the prev song
	 */
	public function prev() { 

		if (is_null($this->_mpd->Previous())) { return false; } 
		return true;
	
	} // prev

	/**
	 * pause
	 * This tells MPD to pause the current song 
	 */
	public function pause() { 
		
		if (is_null($this->_mpd->Pause())) { return false; } 
		return true;

	} // pause 


        /**
        * volume
        * This tells MPD to set the volume to the parameter
        */
	public function volume($volume) {

               if (is_null($this->_mpd->SetVolume($volume))) { return false; }
               return true;

       } // volume

       /**
        * repeat
        * This tells MPD to set the repeating the playlist (i.e. loop) to either on or off
        */
	public function repeat($state) {
	
		if (is_null($this->_mpd->SetRepeat($state))) { return false; }
       		return true;

       } // repeat

       /**
        * random
        * This tells MPD to turn on or off the playing of songs from the playlist in random order
        */
       public function random($onoff) {

               if (is_null($this->_mpd->SetRandom($onoff))) { return false; }
               return true;

       } // random

       /**
        * move
        * This tells MPD to move song from SrcPos to DestPos
        */
       public function move($SrcPos, $DestPos) {

		if (is_null($this->_mpd->PLMoveTrack($SrcPos, $DestPos))) { return false; }

        	return true;
	} // move

	/**
	 * get_songs
	 * This functions returns an array containing information about
	 * The songs that MPD currently has in it's playlist. This must be
	 * done in a standardized fashion
	 */
	public function get() { 

		// If we don't have the playlist yet, pull it
		if (!isset($this->_mpd->playlist)) {
			$this->_mpd->GetPlaylist(); 
		} 

		/* Get the Current Playlist */
		$playlist = $this->_mpd->playlist;
		
		foreach ($playlist as $entry) { 
			$data = array();

			/* Required Elements */
			$data['id'] 	= $entry['Pos'];
			$data['raw']	= $entry['file'];		

			$url_data = $this->parse_url($entry['file']); 
		
			switch ($url_data['primary_key']) { 
				case 'oid': 	
					$song = new Song($url_data['oid']); 
					$song->format(); 
					$data['name'] = $song->f_title . ' - ' . $song->f_album . ' - ' . $song->f_artist;					
					$data['link']   = $song->f_link; 
				break; 
				case 'demo_id': 
					$democratic = new Democratic($url_data['demo_id']); 
					$data['name'] = _('Democratic') . ' - ' . $democratic->name; 	
					$data['link']   = '';
				break; 
				case 'random':
					$data['name'] = _('Random') . ' - ' . scrub_out(ucfirst($url_data['type'])); 
					$data['link'] = ''; 
				break;
				default: 

					/* If we don't know it, look up by filename */
					$filename = Dba::escape($entry['file']);
					$sql = "SELECT `id`,'song' AS `type` FROM `song` WHERE `file` LIKE '%$filename' " . 
						"UNION ALL " . 
						"SELECT `id`,'radio' AS `type` FROM `live_stream` WHERE `url`='$filename' "; 
					
					$db_results = Dba::read($sql);
					if ($row = Dba::fetch_assoc($db_results)) { 
						$media = new $row['type']($row['id']);
						$media->format(); 
						switch ($row['type']) { 
							case 'song': 
								$data['name'] = $media->f_title . ' - ' . $media->f_album . ' - ' . $media->f_artist;
								$data['link'] = $media->f_link; 
							break; 
							case 'radio': 
								$frequency = $media->frequency ? '[' . $media->frequency . ']' : ''; 
								$site_url = $media->site_url ? '(' . $media->site_url . ')' : ''; 
								$data['name'] = "$media->name $frequency $site_url";
								$data['link'] = $media->site_url; 
							break; 
						} // end switch on type 
					} // end if results

					else { 
						$data['name'] = _('Unknown');
						$data['link']   = '';
					}

				break; 
			} // end switch on primary key type
	
			/* Optional Elements */
			$data['track']	= $entry['Pos']+1;

			$results[] = $data;

		} // foreach playlist items
		
		return $results;

	} // get

	/**
	 * get_status
	 * This returns bool/int values for features, loop, repeat and any other features
	 * That this localplay method support
	 */
	public function status() { 

		$track = $this->_mpd->current_track_id;

		/* Construct the Array */
		$array['state'] 	= $this->_mpd->state;
		$array['volume']	= $this->_mpd->volume;
		$array['repeat']	= $this->_mpd->repeat;
		$array['random']	= $this->_mpd->random;
		$array['track']		= $track+1;
		
		$url_data = $this->parse_url($this->_mpd->playlist[$track]['file']);
		$song = new Song($url_data['oid']);
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
	public function connect() { 
	
		// Look at the current instance and pull the options for said instance
		$options = self::get_instance(); 
		$this->_mpd = new mpd($options['host'],$options['port'],$options['password']);

		if ($this->_mpd->connected) { return true; } 

		return false;

	} // connect

} //end of AmpacheMpd

?>
