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
 * AmpacheXbmc Class
 *
 * This is the class for the xbmc localplay method to remote control
 * a XBMC Instance
 *
 */
class AmpacheXbmc extends localplay_controller {

    /* Variables */
    private $version    = '000001'; 
    private $description    = 'Controls a XBMC instance'; 
    

    /* Constructed variables */
    private $_xbmc;
    // Always use player 0 for now
    private $_playerId = 0;
    // Always use playlist 0 for now
    private $_playlistId = 0;

    /**
     * Constructor
     * This returns the array map for the localplay object
     * REQUIRED for Localplay
     */
    public function __construct() { 
    
        /* Do a Require Once On the needed Libraries */
        require_once Config::get('prefix') . '/modules/xbmc-php-rpc/TCPClient.php';
        
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
     * This returns true or false if xbmc controller is installed
     */
    public function is_installed() {

        $sql = "DESCRIBE `localplay_xbmc`"; 
        $db_results = Dba::query($sql); 

        return Dba::num_rows($db_results); 


    } // is_installed

    /**
     * install
     * This function installs the xbmc localplay controller
     */
    public function install() {

        $sql = "CREATE TABLE `localplay_xbmc` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , ". 
            "`name` VARCHAR( 128 ) COLLATE utf8_unicode_ci NOT NULL , " . 
            "`owner` INT( 11 ) NOT NULL, " . 
            "`host` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " . 
            "`port` INT( 11 ) UNSIGNED NOT NULL , " . 
            "`user` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`pass` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL" .
            ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"; 
        $db_results = Dba::query($sql); 

        // Add an internal preference for the users current active instance
        Preference::insert('xbmc_active','XBMC Active Instance','0','25','integer','internal'); 
        User::rebuild_all_preferences(); 

        return true; 

    } // install

    /**
     * uninstall
     * This removes the localplay controller 
     */
    public function uninstall() {

        $sql = "DROP TABLE `localplay_xbmc`"; 
        $db_results = Dba::query($sql); 

        // Remove the pref we added for this        
        Preference::delete('xbmc_active'); 

        return true; 

    } // uninstall

    /**
     * add_instance
     * This takes key'd data and inserts a new xbmc instance
     */
    public function add_instance($data) {

        $sql = "INSERT INTO `localplay_xbmc` (`name`,`host`,`port`, `user`, `pass`,`owner`) " . 
            "VALUES (?, ?, ?, ?, ?, ?)"; 
        $db_results = Dba::query($sql, array($data['name'], $data['host'], $data['port'], $data['user'], $data['pass'], $GLOBALS['user']->id)); 

        return $db_results; 

    } // add_instance

    /**
     * delete_instance
     * This takes a UID and deletes the instance in question
     */
    public function delete_instance($uid) {

        $sql = "DELETE FROM `localplay_xbmc` WHERE `id` = ?"; 
        $db_results = Dba::query($sql, array($uid)); 

        return true; 

    } // delete_instance

    /**
     * get_instances
     * This returns a key'd array of the instance information with 
     * [UID]=>[NAME]
     */
    public function get_instances() {

        $sql = "SELECT * FROM `localplay_xbmc` ORDER BY `name`"; 
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
    
        $sql = "UPDATE `localplay_xbmc` SET `host` = ?, `port` = ?, `name` = ?, `user` = ?, `pass` = ? WHERE `id` = ?"; 
        $db_results = Dba::query($sql, array($data['host'], $data['port'], $data['name'], $data['user'], $data['pass'], $uid)); 

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
        $fields['user']         = array('description' => T_('Username'),'type'=>'textbox');
        $fields['pass']         = array('description' => T_('Password'),'type'=>'textbox');

        return $fields; 

    } // instance_fields

    /** 
    * get_instance
    * This returns a single instance and all it's variables
    */
    public function get_instance($instance='') { 

        $instance = $instance ? $instance : Config::get('xbmc_active'); 
    
        $sql = "SELECT * FROM `localplay_xbmc` WHERE `id` = ?"; 
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

        Preference::update('xbmc_active', $user_id, intval($uid)); 
        Config::set('xbmc_active', intval($uid), true); 

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

        try {
            $this->_xbmc->Playlist->Add(array(
                'playlistid' => $this->_playlistId,
                'item' => array('file' => $url->url)
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'add_url failed: ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * delete_track
     * Delete a track from the xbmc playlist
     */
    public function delete_track($track) { 

        try {
            $this->_xbmc->Playlist->Remove(array(
                'playlistid' => $this->_playlistId,
                'position' => $track
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'delete_track failed: ' . $ex->getMessage(), 1);
            return false;
        }

    } // delete_track
    
    /**
     * clear_playlist
     * This deletes the entire xbmc playlist.
     */
    public function clear_playlist() { 

        try {
            $this->_xbmc->Playlist->Clear(array(
                'playlistid' => $this->_playlistId
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'clear_playlist failed: ' . $ex->getMessage(), 1);
            return false;
        }

    } // clear_playlist

    /**
     * play
     * This just tells xbmc to start playing, it does not
     * take any arguments
     */
    public function play() { 
        
        try {
            // XBMC requires to load a playlist to play. We don't know if this play is after a new playlist or after pause
            // So we get current status
            $status = $this->status();
            if ($status['state'] == 'stop') {
                $this->_xbmc->Player->Open(array(
                    'item' => array('playlistid' => $this->_playlistId))
                );
            } else {
                $this->_xbmc->Player->PlayPause(array(
                    'playerid' => $this->_playlistId,
                    'play' => true)
                );
            }
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'play failed: ' . $ex->getMessage(), 1);
            return false;
        }

    } // play
    
    /**
     * pause
     * This tells xbmc to pause the current song 
     */
    public function pause() { 
        
        try {
            $this->_xbmc->Player->PlayPause(array(
                'playerid' => $this->_playerId,
                'play' => false)
            );
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'pause failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }

    } // pause 

    /**
     * stop
     * This just tells xbmc to stop playing, it does not take
     * any arguments
     */
    public function stop() { 

        try {
            $this->_xbmc->Player->Stop(array(
                'playerid' => $this->_playerId
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'stop failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }

    } // stop

    /**
     * skip
     * This tells xbmc to skip to the specified song
     */
    public function skip($song) { 

        try {
            $this->_xbmc->Player->GoTo(array(
                'playerid' => $this->_playerId,
                'to' => $song
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'skip failed, is the player started?: ' . $ex->getMessage(), 1);
            return false;
        }

    } // skip

    /**
     * This tells xbmc to increase the volume
     */
    public function volume_up() { 

        try {
            $this->_xbmc->Application->SetVolume(array(
                'volume' => 'increment'
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'volume_up failed: ' . $ex->getMessage(), 1);
            return false;
        }

    } // volume_up

    /**
     * This tells xbmc to decrease the volume
     */
    public function volume_down() { 

        try {
            $this->_xbmc->Application->SetVolume(array(
                'volume' => 'decrement'
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'volume_down failed: ' . $ex->getMessage(), 1);
            return false;
        }
        
    } // volume_down

    /**
     * next
     * This just tells xbmc to skip to the next song
     */
    public function next() { 

        try {
            $this->_xbmc->Player->GoTo(array(
                'playerid' => $this->_playerId,
                'to' => 'next'
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'next failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }

    } // next

    /**
     * prev
     * This just tells xbmc to skip to the prev song
     */
    public function prev() { 

        try {
            $this->_xbmc->Player->GoTo(array(
                'playerid' => $this->_playerId,
                'to' => 'previous'
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'prev failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }
    
    } // prev

    /**
     * volume
     * This tells xbmc to set the volume to the specified amount
     */
    public function volume($volume) {

        try {
            $this->_xbmc->Application->SetVolume(array(
                'volume' => $volume
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'volume failed: ' . $ex->getMessage(), 1);
            return false;
        }

    } // volume

    /**
     * repeat
     * This tells xbmc to set the repeating the playlist (i.e. loop) to either on or off
     */
    public function repeat($state) {

        try {
            $this->_xbmc->Player->SetRepeat(array(
                'playerid' => $this->_playerId,
                'repeat' => ($state ? 'all' : 'off')
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'repeat failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }
        
    } // repeat

    /**
     * random
     * This tells xbmc to turn on or off the playing of songs from the playlist in random order
     */
    public function random($onoff) {

        try {
            $this->_xbmc->Player->SetShuffle(array(
                'playerid' => $this->_playerId,
                'shuffle' => $onoff
            ));
            return true;
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'random failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }
        
    } // random

    /**
     * get
     * This functions returns an array containing information about
     * The songs that xbmc currently has in it's playlist. This must be
     * done in a standardized fashion
     */
    public function get() { 

        $results = array();
        
        try {
            $playlist = $this->_xbmc->Playlist->GetItems(array(
                'playlistid' => $this->_playlistId,
                'properties' => array('file')
            ));
            
            for ($i = $playlist['limits']['start']; $i < $playlist['limits']['end']; ++$i) {
                $item = $playlist['items'][$i];
                
                $data = array();
                $data['link'] = $item['file'];
                $data['id'] = $i;
                $data['track'] = $i + 1;
                
                $url_data = $this->parse_url($data['link']); 
                if ($url_data != null) {
                    $song = new Song($url_data['oid']);
                    if ($song != null) {
                        $data['name'] = $song->get_artist_name() . ' - ' . $song->title;
                    }
                }
                if (!$data['name']) {
                    $data['name'] = $item['label'];
                }
                $results[] = $data;
            }
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'get failed: ' . $ex->getMessage(), 1);
        }
        
        return $results;

    } // get

    /**
     * status
     * This returns bool/int values for features, loop, repeat and any other features
     * that this localplay method supports.
     * This works as in requesting the xbmc properties
     */
    public function status() { 
        
        $array = array();
        try {
            $appprop = $this->_xbmc->Application->GetProperties(array(
                'properties' => array('volume')
            ));
            $array['volume']    = intval($appprop['volume']);
            
            try {
                $currentplay = $this->_xbmc->Player->GetItem(array(
                    'playerid' => $this->_playerId,
                    'properties' => array('file')
                ));
                // We assume it's playing. No pause detection support.
                $array['state'] = 'play';
                
                $playprop = $this->_xbmc->Player->GetProperties(array(
                    'playerid' => $this->_playerId,
                    'properties' => array('repeat', 'shuffled')
                ));
                $array['repeat']    = ($playprop['repeat'] != "off");
                $array['random']    = (strtolower($playprop['shuffled']) == 1) ;
                $array['track']     =   $currentplay['file'];
                
                $url_data = $this->parse_url($array['track']); 
                $song = new Song($url_data['oid']);
                if ($song->title || $song->get_artist_name() || $song->get_album_name()) {
                    $array['track_title']     = $song->title;
                    $array['track_artist']     = $song->get_artist_name();
                    $array['track_album']    = $song->get_album_name();
                }
            } catch (XBMC_RPC_Exception $ex) {
                debug_event('xbmc', 'get current item failed, player probably stopped. ' . $ex->getMessage(), 1);
                $array['state'] = 'stop';
            }
        } catch (XBMC_RPC_Exception $ex) {
            debug_event('xbmc', 'status failed: ' . $ex->getMessage(), 1);
        }
        return $array;

    } // status

    /**
     * connect
     * This functions creates the connection to xbmc and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect() { 
    
        $options = self::get_instance();
        try {
            $this->_xbmc = new XBMC_RPC_TCPClient($options);
            return true;
        } catch (XBMC_RPC_ConnectionException $ex) {
            debug_event('xbmc', 'xbmc connection failed: ' . $ex->getMessage(), 1);
            return false;
        }

    } // connect
    
} //end of AmpacheXbmc

?>
