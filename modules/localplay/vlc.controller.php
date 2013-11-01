<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. 
 *
 */

/**
 * AmpacheVlc Class
 *
 * This is the class for the vlc localplay method to remote control
 * a VLC Instance
 *
 */
class AmpacheVlc extends localplay_controller {

    /* Variables */
    private $version    = 'Beta 0.2'; 
    private $description    = 'Controls a Vlc instance'; 
    

    /* Constructed variables */
    private $_vlc;

    /**
     * Constructor
     * This returns the array map for the localplay object
     * REQUIRED for Localplay
     */
    public function __construct() { 
    
        /* Do a Require Once On the needed Libraries */
        require_once Config::get('prefix') . '/modules/vlc/vlcplayer.class.php';
        
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
         * This returns true or false if vlc controller is installed
         */
        public function is_installed() {

        $sql = "DESCRIBE `localplay_vlc`"; 
        $db_results = Dba::query($sql); 

        return Dba::num_rows($db_results); 


        } // is_installed

        /**
         * install
         * This function installs the VLC localplay controller
         */
        public function install() {
    
            $sql = "CREATE TABLE `localplay_vlc` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , ". 
                "`name` VARCHAR( 128 ) COLLATE utf8_unicode_ci NOT NULL , " . 
                "`owner` INT( 11 ) NOT NULL, " . 
                "`host` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " . 
                "`port` INT( 11 ) UNSIGNED NOT NULL , " . 
                "`password` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " . 
                "`access` SMALLINT( 4 ) UNSIGNED NOT NULL DEFAULT '0'" . 
                ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"; 
            $db_results = Dba::query($sql); 

            // Add an internal preference for the users current active instance
            Preference::insert('vlc_active','VLC Active Instance','0','25','integer','internal'); 
            User::rebuild_all_preferences(); 

            return true; 

        } // install

        /**
         * uninstall
         * This removes the localplay controller 
         */
        public function uninstall() {

            $sql = "DROP TABLE `localplay_vlc`"; 
            $db_results = Dba::query($sql); 
    
            // Remove the pref we added for this        
            Preference::delete('vlc_active'); 
    
            return true; 

        } // uninstall

        /**
         * add_instance
         * This takes key'd data and inserts a new vlc instance
         */
        public function add_instance($data) {

            $sql = "INSERT INTO `localplay_vlc` (`name`,`host`,`port`,`password`,`owner`) VALUES (?, ?, ?, ?, ?)"; 
            $db_results = Dba::query($sql, array($data['name'], $data['host'], $data['port'], $data['password'], $GLOBALS['user']->id)); 

            return $db_results; 
    
        } // add_instance

        /**
         * delete_instance
         * This takes a UID and deletes the instance in question
         */
        public function delete_instance($uid) {
    
            $sql = "DELETE FROM `localplay_vlc` WHERE `id` = ?"; 
            $db_results = Dba::query($sql, array($uid)); 
    
            return true; 

        } // delete_instance

        /**
         * get_instances
         * This returns a key'd array of the instance information with 
         * [UID]=>[NAME]
         */
        public function get_instances() {

            $sql = "SELECT * FROM `localplay_vlc` ORDER BY `name`"; 
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
        
            $sql = "UPDATE `localplay_vlc` SET `host` = ?, `port` = ?, `name` = ?, `password` = ? WHERE `id` = ?"; 
            $db_results = Dba::query($sql, array($data['host'], $data['port'], $data['name'], $data['password'], $uid)); 

            return true; 

        } // update_instance

        /**
         * instance_fields
         * This returns a key'd array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
         * fields so that we can on-the-fly generate a form
         */
        public function instance_fields() {

                $fields['name']         = array('description' => T_('Instance Name'),'type'=>'textbox');
                $fields['host']         = array('description' => T_('Hostname'),'type'=>'textbox');
                $fields['port']         = array('description' => T_('Port'),'type'=>'textbox');
                $fields['password']     = array('description' => T_('Password'),'type'=>'textbox');

            return $fields; 

    } // instance_fields

    /** 
    * get_instance
    * This returns a single instance and all it's variables
    */
    public function get_instance($instance='') { 

        $instance = $instance ? $instance : Config::get('vlc_active'); 
    
        $sql = "SELECT * FROM `localplay_vlc` WHERE `id` = ?"; 
        $db_results = Dba::query($sql, array($instance)); 

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

        Preference::update('vlc_active',$user_id,intval($uid)); 
        Config::set('vlc_active', intval($uid), true); 

        return true; 

    } // set_active_instance        

    /**
     * get_active_instance
     * This returns the UID of the current active instance
     * false if none are active
     */
    public function get_active_instance() {


    } // get_active_instance

    public function add_url(Stream_URL $url) {
    if (is_null($this->_vlc->add($url->title, $url->url))) {
        debug_event('vlc', 'add_url failed to add: ' . json_encode($url), 1);
        return false;
    }

    return true;
    }

    /**
     * delete_track
     * This must take an array of ID's (as passed by get function) from Ampache
     * and delete them from vlc webinterface
     */
    public function delete_track($object_id) { 

        if (is_null($this->_vlc->delete_pos($object_id))) { 
            debug_event('vlc_del','ERROR Unable to delete ' . $object_id . ' from Vlc','1'); 
            return false; 
        } 

        return true; 

    } // delete_track
    
    /**
     * clear_playlist
     * This deletes the entire vlc playlist... nuff said
     */
    public function clear_playlist() { 

        if (is_null($this->_vlc->clear())) { return false; }

        // If the clear worked we should stop it!
        $this->stop(); 

        return true;

    } // clear_playlist

    /**
     * play
     * This just tells vlc to start playing, it does not
     * take any arguments
     */
    public function play() { 
        /* A play when it's already playing causes a track restart
         * which we don't want to doublecheck its state
         */
        if ($this->_vlc->state() == 'play') {
            return true; 
        } 

        if (is_null($this->_vlc->play())) { return false; } 
        return true;

    } // play

    /**
     * stop
     * This just tells vlc to stop playing, it does not take
     * any arguments
     */
    public function stop() { 

        if (is_null($this->_vlc->stop())) { return false; } 
        return true;

    } // stop

    /**
     * skip
     * This tells vlc to skip to the specified song
     */
    public function skip($song) { 

        if (is_null($this->_vlc->skip($song))) { return false; }
        return true; 

    } // skip

    /**
     * This tells vlc to increase the volume by in vlcplayerclass set amount
     */
    public function volume_up() { 

        if (is_null($this->_vlc->volume_up())) { return false; } 
        return true;

    } // volume_up

    /**
     * This tells vlc to decrease the volume by vlcplayerclass set amount
     */
    public function volume_down() { 

        if (is_null($this->_vlc->volume_down())) { return false; }
        return true;
        
    } // volume_down

    /**
     * next
     * This just tells vlc to skip to the next song, if you play a song by direct
     * clicking and hit next vlc will start with the first song , needs work. 
     */
    public function next() { 

        if (is_null($this->_vlc->next())) { return false; } 

        return true;

    } // next

    /**
     * prev
     * This just tells vlc to skip to the prev song
     */
    public function prev() { 

        if (is_null($this->_vlc->prev())) { return false; } 

        return true;
    
    } // prev

    /**
     * pause
     * This tells vlc to pause the current song 
     */
    public function pause() { 
        
        if (is_null($this->_vlc->pause())) { return false; } 
        return true;

    } // pause 

        /**
        * volume
        * This tells vlc to set the volume to the specified amount this
    * is 0-400 procent
        */
       public function volume($volume) {

               if (is_null($this->_vlc->set_volume($volume))) { return false; }
               return true;

       } // volume

       /**
        * repeat
        * This tells vlc to set the repeating the playlist (i.e. loop) to either on or off
        */
       public function repeat($state) {
    
        if (is_null($this->_vlc->repeat($state))) { return false; }
               return true;

       } // repeat

       /**
        * random
        * This tells vlc to turn on or off the playing of songs from the playlist in random order
        */
       public function random($onoff) {

               if (is_null($this->_vlc->random($onoff))) { return false; }
               return true;

       } // random

    /**
     * get
     * This functions returns an array containing information about
     * The songs that vlc currently has in it's playlist. This must be
     * done in a standardized fashion
     * Warning ! if you got files in vlc medialibary those files will be sent to the php xml parser 
     * to, not to your browser but still this can take a lot of work for your server.
     * The xml files of vlc need work, not much documentation on them....
     */
    public function get() { 

        /* Get the Current Playlist */
        $list = $this->_vlc->get_tracks();

        if (!$list) { return array(); } 
         $counterforarray = 0;
                   // here we look if there are song in the playlist when media libary is used
            if ($list['node']['node'][0]['leaf'][$counterforarray]['attr']['uri'])  {
                while ($list['node']['node'][0]['leaf'][$counterforarray]){
                    $songs[] = htmlspecialchars_decode($list['node']['node'][0]['leaf'][$counterforarray]['attr']['uri'], ENT_NOQUOTES);
                    $songid[] = $list['node']['node'][0]['leaf'][$counterforarray]['attr']['id'];
                    $counterforarray++;
                }
                // if there is only one song look here,and media libary is used
            }
            elseif($list['node']['node'][0]['leaf']['attr']['uri']) {
                 $songs[] = htmlspecialchars_decode($list['node']['node'][0]['leaf']['attr']['uri'], ENT_NOQUOTES);
                 $songid[] = $list['node']['node'][0]['leaf']['attr']['id'];
            }
            // look for songs when media libary isn't used
            elseif   ($list['node']['node']['leaf'][$counterforarray]['attr']['uri'])  {
                while ($list['node']['node']['leaf'][$counterforarray]){
                    $songs[] = htmlspecialchars_decode($list['node']['node']['leaf'][$counterforarray]['attr']['uri'], ENT_NOQUOTES);
                    $songid[] = $list['node']['node']['leaf'][$counterforarray]['attr']['id'];
                    $counterforarray++;
                }
            }
             elseif ($list['node']['node']['leaf']['attr']['uri']) {
                 $songs[] = htmlspecialchars_decode($list['node']['node']['leaf']['attr']['uri'], ENT_NOQUOTES);
                 $songid[] = $list['node']['node']['leaf']['attr']['id'];  
             }
                
               else { return array(); }
            
            $counterforarray = 0;
             
           foreach ($songs as $key=>$entry) { 
            $data = array();
             
            /* Required Elements */
            $data['id']     = $songid[$counterforarray]; // id number of the files in the vlc playlist, needed for other operations 
            $data['raw']    = $entry;        

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
                                        $data['name'] = T_('Democratic') . ' - ' . $democratic->name;
                                        $data['link']   = '';
                                break;
                case 'random': 
                    $data['name'] = T_('Random') . ' - ' . scrub_out(ucfirst($url_data['type'])); 
                    $data['link'] = ''; 
                break; 
                                default:
                                        /* If we don't know it, look up by filename */
                                        $filename = Dba::escape($entry);
                                        $sql = "SELECT `name` FROM `live_stream` WHERE `url`='$filename' ";

                                        $db_results = Dba::read($sql);
                                        if ($row = Dba::fetch_assoc($db_results)) {
                                            //if stream is known just send name
                                           $data['name'] = htmlspecialchars(substr($row['name'], 0, 50));
                                                                
                                            } 
                                            //if it's a http stream not in ampacha's database just show the url'
                                          elseif ( strncmp($entry, 'http', 4)== 0)  {
                                            $data['name'] = htmlspecialchars("(VLC stream) " . substr($entry, 0, 50));
                                          }
                                          //if it's a file get the last output after  and show that, hard to take every output possible in account
                                          else {
                                              $getlast = explode("/",$entry);
                                              $lastis = count($getlast) - 1;
                                              $data['name'] = htmlspecialchars("(VLC local) " . substr($getlast[$lastis], 0, 50));
                                          } // end if loop
                                break;
                        } // end switch on primary key type

            $data['track']    = $key+1;
            $counterforarray++;
            $results[] = $data;

        } // foreach playlist items
        
        return $results;

    } // get

    /**
     * status
     * This returns bool/int values for features, loop, repeat and any other features
     * That this localplay method supports. required function
     * This works as in requesting the status.xml file from vlc.
     */
    public function status() { 
             
        $arrayholder = $this->_vlc->fullstate();    //get status.xml via parser xmltoarray
        /* Construct the Array */
        $currentstat = $arrayholder['root']['state']['value'];

        if ($currentstat == 'playing') { $state = 'play'; }   //change to something ampache understands
        if ($currentstat == 'stop') { $state = 'stop'; } 
        if ($currentstat == 'paused') { $state = 'pause'; } 
        
        $array['state']     = $state;
        $array['volume']    = intval((intval($arrayholder['root']['volume']['value'])/2.6));
        $array['repeat']    = $arrayholder['root']['repeat']['value'];
        $array['random']    = $arrayholder['root']['random']['value'];
        $array['track'] =   htmlspecialchars_decode($arrayholder['root']['information']['meta-information']['title']['value'], ENT_NOQUOTES);
                                     
        $url_data = $this->parse_url($array['track']); 
        $song = new Song($url_data['oid']);
        if ($song->title || $song->get_artist_name() || $song->get_album_name()) {
        $array['track_title']     = $song->title;
        $array['track_artist']     = $song->get_artist_name();
        $array['track_album']    = $song->get_album_name();
        } 
        // if not a known format
        else {
            $array['track_title'] = htmlspecialchars(substr($arrayholder['root']['information']['meta-information']['title']['value'], 0, 25));
            $array['track_artist'] =  htmlspecialchars(substr($arrayholder['root']['information']['meta-information']['artist']['value'], 0, 20));
        }
        return $array;

    } // status

    /**
     * connect
     * This functions creates the connection to vlc and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect() { 
    
        $options = self::get_instance();     
        $this->_vlc = new VlcPlayer($options['host'],$options['password'],$options['port']);

        // Test our connection by retriving the version, no version in status file, just need to see if returned
        //Not yet working all values returned are true for beta testing purpose
        if (!is_null($this->_vlc->version())) { return true; } 

        return false;

    } // connect
    
} //end of AmpacheVlc

?>
