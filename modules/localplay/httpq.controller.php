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
 * AmpacheHttpQ Class
 * This is the class for the HttpQ localplay method to remote control
 * a WinAmp Instance
 */
class AmpacheHttpq extends localplay_controller {

	/* Variables */
	private $version	= '000001'; 
	private $description	= 'Controls a Httpq instance, requires Ampaches Httpq version'; 
	

	/* Constructed variables */
	private $_httpq;

	/**
	 * Constructor
	 * This returns the array map for the localplay object
	 * REQUIRED for Localplay
	 */
	public function __construct() { 
	
		/* Do a Require Once On the needed Libraries */
		require_once Config::get('prefix') . '/modules/httpq/httpqplayer.class.php';

	} // Constructor

	/**
	 * get_description
	 * This returns the description of this localplay method
	 */
	public function get_description() { 

		return $this->description;  
	
	} // get_description

	/**
	 * get_version
	 * This returns the current version
	 */
	public function get_version() { 

		return $this->version;  

	} // get_version

        /**
         * is_installed
         * This returns true or false if MPD controller is installed
         */
        public function is_installed() {

		$sql = "DESCRIBE `localplay_httpq`"; 
		$db_results = Dba::query($sql); 

		return Dba::num_rows($db_results); 


        } // is_installed

        /**
         * install
         * This function installs the MPD localplay controller
         */
        public function install() {
	
		$sql = "CREATE TABLE `localplay_httpq` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , ". 
			"`name` VARCHAR( 128 ) COLLATE utf8_unicode_ci NOT NULL , " . 
			"`owner` INT( 11 ) NOT NULL, " . 
			"`host` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " . 
			"`port` INT( 11 ) UNSIGNED NOT NULL , " . 
			"`password` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " . 
			"`access` SMALLINT( 4 ) UNSIGNED NOT NULL DEFAULT '0'" . 
			") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"; 
		$db_results = Dba::query($sql); 

		// Add an internal preference for the users current active instance
		Preference::insert('httpq_active','HTTPQ Active Instance','0','25','integer','internal'); 
		User::rebuild_all_preferences(); 

		return true; 

        } // install

        /**
         * uninstall
         * This removes the localplay controller 
         */
        public function uninstall() {

		$sql = "DROP TABLE `localplay_httpq`"; 
		$db_results = Dba::query($sql); 

		// Remove the pref we added for this		
		Preference::delete('httpq_active'); 

		return true; 

        } // uninstall

        /**
         * add_instance
         * This takes key'd data and inserts a new Httpq instance
         */
        public function add_instance($data) {

		// Foreach and clean up what we need
		foreach ($data as $key=>$value) { 
			switch ($key) { 
				case 'name': 
				case 'host': 
				case 'port': 
				case 'password': 
					${$key} = Dba::escape($value); 
				break;
				default: 
					// Rien a faire
				break; 
			} // end switch on key
		} // end foreach 

		$user_id = Dba::escape($GLOBALS['user']->id); 

		$sql = "INSERT INTO `localplay_httpq` (`name`,`host`,`port`,`password`,`owner`) " . 
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

		$sql = "DELETE FROM `localplay_httpq` WHERE `id`='$uid'"; 
		$db_results = Dba::query($sql); 

		return true; 

        } // delete_instance

        /**
         * get_instances
         * This returns a key'd array of the instance information with 
         * [UID]=>[NAME]
         */
        public function get_instances() {

		$sql = "SELECT * FROM `localplay_httpq` ORDER BY `name`"; 
		$db_results = Dba::query($sql); 

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[$row['id']] = $row['name']; 
		} 

		return $results; 

        } // get_instances

        /**
         * update_instance
         * This takes an ID and an array of data and updates the instance specified
         */
        public function update_instance($uid,$data) { 

		$uid	= Dba::escape($uid); 
		$port	= Dba::escape($data['port']);
		$host	= Dba::escape($data['host']); 
		$name	= Dba::escape($data['name']); 
		$pass	= Dba::escape($data['password']); 
		
		$sql = "UPDATE `localplay_httpq` SET `host`='$host', `port`='$port', `name`='$name', `password`='$pass' WHERE `id`='$uid'"; 
		$db_results = Dba::query($sql); 

		return true; 

        } // update_instance

        /**
         * instance_fields
         * This returns a key'd array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
         * fields so that we can on-the-fly generate a form
         */
        public function instance_fields() {

                $fields['name']         = array('description'=>_('Instance Name'),'type'=>'textbox');
                $fields['host']         = array('description'=>_('Hostname'),'type'=>'textbox');
                $fields['port']         = array('description'=>_('Port'),'type'=>'textbox');
                $fields['password']     = array('description'=>_('Password'),'type'=>'textbox');

		return $fields; 

	} // instance_fields

	/** 
	 * get_instance
	 * This returns a single instance and all it's variables
	 */
	public function get_instance($instance='') { 

		$instance = $instance ? $instance : Config::get('httpq_active'); 
		$instance = Dba::escape($instance); 

		$sql = "SELECT * FROM `localplay_httpq` WHERE `id`='$instance'"; 
		$db_results = Dba::query($sql); 

		$row = Dba::fetch_assoc($db_results); 

		return $row; 

        } // get_instance

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

		Preference::update('httpq_active',$user_id,intval($uid)); 
		Config::set('httpq_active',intval($uid),'1'); 

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
	 * This must take an array of URL's from Ampache
	 * and then add them to HttpQ
	 */
	public function add($object) { 

		$url = $this->get_url($object); 		

		// Try to pass a title (if we can)
		if (is_object($object)) { 
			$title = $object->title; 
		} 

		if (is_null($this->_httpq->add($title,$url))) { 
			debug_event('httpq_add',"Error: Unable to add $url to Httpq",'1');
		}

		return true;

	} // add

	/**
	 * delete_track
	 * This must take an array of ID's (as passed by get function) from Ampache
	 * and delete them from Httpq
	 */
	public function delete_track($object_id) { 

		if (is_null($this->_httpq->delete_pos($object_id))) { 
			debug_event('httpq_del','ERROR Unable to delete ' . $object_id . ' from Httpq','1'); 
			return false; 
		} 

		return true; 

	} // delete_track
	
	/**
	 * clear_playlist
	 * This deletes the entire Httpq playlist... nuff said
	 */
	public function clear_playlist() { 

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
	public function play() { 
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
	public function stop() { 

		if (is_null($this->_httpq->stop())) { return false; } 
		return true;

	} // stop

	/**
	 * skip
	 * This tells HttpQ to skip to the specified song
	 */
	public function skip($song) { 

		if (is_null($this->_httpq->skip($song))) { return false; }
		return true; 

	} // skip

	/**
	 * This tells Httpq to increase the volume by WinAmps default amount
	 */
	public function volume_up() { 

		if (is_null($this->_httpq->volume_up())) { return false; } 
		return true;

	} // volume_up

	/**
	 * This tells HttpQ to decrease the volume by Winamps default amount
	 */
	public function volume_down() { 

		if (is_null($this->_httpq->volume_down())) { return false; }
		return true;
		
	} // volume_down

	/**
	 * next
	 * This just tells HttpQ to skip to the next song 
	 */
	public function next() { 

		if (is_null($this->_httpq->next())) { return false; } 

		return true;

	} // next

	/**
	 * prev
	 * This just tells HttpQ to skip to the prev song
	 */
	public function prev() { 

		if (is_null($this->_httpq->prev())) { return false; } 

		return true;
	
	} // prev

	/**
	 * pause
	 * This tells HttpQ to pause the current song 
	 */
	public function pause() { 
		
		if (is_null($this->_httpq->pause())) { return false; } 
		return true;

	} // pause 

        /**
        * volume
        * This tells HttpQ to set the volume to the specified amount this
	* is 0-100
        */
       public function volume($volume) {

               if (is_null($this->_httpq->set_volume($volume))) { return false; }
               return true;

       } // volume

       /**
        * repeat
        * This tells HttpQ to set the repeating the playlist (i.e. loop) to either on or off
        */
       public function repeat($state) {
	
		if (is_null($this->_httpq->repeat($state))) { return false; }
       		return true;

       } // repeat

       /**
        * random
        * This tells HttpQ to turn on or off the playing of songs from the playlist in random order
        */
       public function random($onoff) {

               if (is_null($this->_httpq->random($onoff))) { return false; }
               return true;

       } // random

	/**
	 * get
	 * This functions returns an array containing information about
	 * The songs that HttpQ currently has in it's playlist. This must be
	 * done in a standardized fashion
	 */
	public function get() { 

		/* Get the Current Playlist */
		$list = $this->_httpq->get_tracks();

		if (!$list) { return array(); } 
	
		$songs = explode("::",$list); 

		foreach ($songs as $key=>$entry) { 
			$data = array();
			
			/* Required Elements */
			$data['id'] 	= $key;
			$data['raw']	= $entry;		

			$url_data = $this->parse_url($entry); 
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

                                break;
                        } // end switch on primary key type

			$data['track']	= $key+1;

			$results[] = $data;

		} // foreach playlist items
		
		return $results;

	} // get

	/**
	 * status
	 * This returns bool/int values for features, loop, repeat and any other features
	 * That this localplay method supports. required function
	 */
	public function status() { 

		/* Construct the Array */
		$array['state'] 	= $this->_httpq->state();
		$array['volume']	= $this->_httpq->get_volume();
		$array['repeat']	= $this->_httpq->get_repeat();
		$array['random']	= $this->_httpq->get_random();
		$array['track']		= $this->_httpq->get_now_playing();

		$url_data = $this->parse_url($array['track']); 
		$song = new Song($url_data['oid']);
		$array['track_title'] 	= $song->title;
		$array['track_artist'] 	= $song->get_artist_name();
		$array['track_album']	= $song->get_album_name();

		return $array;

	} // status

	/**
	 * connect
	 * This functions creates the connection to HttpQ and returns
	 * a boolean value for the status, to save time this handle
	 * is stored in this class
	 */
	public function connect() { 
	
		$options = self::get_instance(); 	
		$this->_httpq = new HttpQPlayer($options['host'],$options['password'],$options['port']);

		// Test our connection by retriving the version
		if (!is_null($this->_httpq->version())) { return true; } 

		return false;

	} // connect
	
} //end of AmpacheHttpq

?>
