<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
 * AmpacheUPnp Class
 *
 * This is the class for the UPnP localplay method to remote control
 * a UPnP player Instance
 *
 */
class AmpacheUPnP extends localplay_controller
{
    /* Variables */
    private $_version = '000001';

    private $_description = 'Controls a UPnP instance';

    /* Constructed variables */
    private $_upnp;
    

    /**
     * Constructor
     * This returns the array map for the localplay object
     * REQUIRED for Localplay
     */
    public function __construct()
    {
        /* Do a Require Once On the needed Libraries */
        require_once AmpConfig::get('prefix') . '/modules/upnp/upnpplayer.class.php';
    } 

    /**
     * get_description
     * This returns the description of this localplay method
     */
    public function get_description()
    {
        return $this->_description;
    } 

    /**
     * get_version
     * This returns the current version
     */
    public function get_version()
    {
        return $this->_version;
    } 

    /**
     * is_installed
     * This returns true or false if upnp controller is installed
     */
    public function is_installed()
    {
        $sql = "SHOW TABLES LIKE 'localplay_upnp'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    } 

    /**
     * install
     * This function installs the upnp localplay controller
     */
    public function install()
    {
        $sql = "CREATE TABLE `localplay_upnp` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , ".
            "`name` VARCHAR( 128 ) COLLATE utf8_unicode_ci NOT NULL , " .
            "`owner` INT( 11 ) NOT NULL, " .
            "`url` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL  " .
            ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db_results = Dba::query($sql);

        // Add an internal preference for the users current active instance
        Preference::insert('upnp_active', 'UPnP Active Instance', '0', '25', 'integer', 'internal');
        User::rebuild_all_preferences();

        return true;
    }

    /**
     * uninstall
     * This removes the localplay controller
     */
    public function uninstall()
    {
        $sql = "DROP TABLE `localplay_upnp`";
        $db_results = Dba::query($sql);

        // Remove the pref we added for this
        Preference::delete('upnp_active');

        return true;
    }

    /**
     * add_instance
     * This takes key'd data and inserts a new upnp instance
     */
    public function add_instance($data)
    {
        $sql = "INSERT INTO `localplay_upnp` (`name`,`url`, `owner`) " .
            "VALUES (?, ?, ?)";
        $db_results = Dba::query($sql, array($data['name'], $data['url'], $GLOBALS['user']->id));

        return $db_results;
    }

    /**
     * delete_instance
     * This takes a UID and deletes the instance in question
     */
    public function delete_instance($uid)
    {
        $sql = "DELETE FROM `localplay_upnp` WHERE `id` = ?";
        $db_results = Dba::query($sql, array($uid));

        return true;
    }

    /**
     * get_instances
     * This returns a key'd array of the instance information with
     * [UID]=>[NAME]
     */
    public function get_instances()
    {
        $sql = "SELECT * FROM `localplay_upnp` ORDER BY `name`";
        $db_results = Dba::query($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        return $results;
    }

    /**
     * update_instance
     * This takes an ID and an array of data and updates the instance specified
     */
    public function update_instance($uid, $data)
    {
        $sql = "UPDATE `localplay_upnp` SET `url` = ?, `name` = ?  WHERE `id` = ?";
        $db_results = Dba::query($sql, array($data['url'], $data['name'], $uid));

        return true;
    }

    /**
     * instance_fields
     * This returns a key'd array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
     * fields so that we can on-the-fly generate a form
     */
    public function instance_fields()
    {
        $fields['name'] = array('description' => T_('Instance Name'), 'type'=>'textbox');
        $fields['url']  = array('description' => T_('URL'), 'type'=>'textbox');

        return $fields;
    }

    /**
    * get_instance
    * This returns a single instance and all it's variables
    */
    public function get_instance($instance='')
    {
        $instance = $instance ? $instance : AmpConfig::get('upnp_active');

        $sql = "SELECT * FROM `localplay_upnp` WHERE `id` = ?";
        $db_results = Dba::query($sql, array($instance));

        $row = Dba::fetch_assoc($db_results);
    
        return $row;
    }

    /**
     * set_active_instance
     * This sets the specified instance as the 'active' one
     */
    public function set_active_instance($uid, $user_id='')
    {
        // Not an admin? bubkiss!
        if (!$GLOBALS['user']->has_access('100')) {
            $user_id = $GLOBALS['user']->id;
        }
        $user_id = $user_id ? $user_id : $GLOBALS['user']->id;
        debug_event('upnp', 'set_active_instance userid: ' . $user_id, 5);

        Preference::update('upnp_active', $user_id, intval($uid));
        AmpConfig::set('upnp_active', intval($uid), true);

        return true;
    }

    /**
     * get_active_instance
     * This returns the UID of the current active instance
     * false if none are active
     */
    public function get_active_instance()
    {
        $instance = AmpConfig::get('upnp_active');
        return $instance;
    }

    
    public function add_url(Stream_URL $url)
    {
        debug_event('upnp', 'add_url: ' . $url->title . " | " . $url->url, 5);
        
        if (!$this->_upnp) {
            return false;
        }

        try {
            $this->_upnp->PlaylistAdd($url->title, $url->url);
            return true;
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'add_url failed: ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * delete_track
     * Delete a track from the upnp playlist
     */
    public function delete_track($track)
    {
        debug_event('upnp', 'delete_track: ' . $track, 5);
        
        if (!$this->_upnp) {
            return false;
        }

        try {
            $this->_upnp->PlaylistRemove($track);
            return true;
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'delete_track failed: ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * clear_playlist
     * This deletes the entire upnp playlist.
     */
    public function clear_playlist()
    {
        debug_event('upnp', 'clear_playlist', 5);

        if (!$this->_upnp) {
            return false;
        }

        try {
            $this->_upnp->PlayListClear();
            return true;
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'clear_playlist failed: ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * play
     * This just tells upnp to start playing, it does not
     * take any arguments
     */
    public function play()
    {
        debug_event('upnp', 'play', 5);

        if (!$this->_upnp) {
            return false;
        }

        try {
            return $this->_upnp->Play();
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'play failed: ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * pause
     * This tells upnp to pause the current song
     */
    public function pause()
    {
        debug_event('upnp', 'pause', 5);

        if (!$this->_upnp) {
            return false;
        }

        try {
            return $this->_upnp->Pause();
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'pause failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * stop
     * This just tells upnp to stop playing, it does not take
     * any arguments
     */
    public function stop()
    {
        debug_event('upnp', 'stop', 5);

        if (!$this->_upnp) {
            return false;
        }

        try {
            return $this->_upnp->Stop();
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'stop failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * skip
     * This tells upnp to skip to the specified song
     */
    public function skip($song)
    {
        debug_event('upnp', 'skip', 5);

        if (!$this->_upnp) {
            return false;
        }

        try {
            $this->_upnp->Skip($song);
            return true;
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'skip failed, is the player started?: ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * next
     * This just tells upnp to skip to the next song
     */
    public function next()
    {
        debug_event('upnp', 'next', 5);

        if (!$this->_upnp) {
            return false;
        }

        try {
            $this->_upnp->PlaySkip('next');
            return true;
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'next failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * prev
     * This just tells upnp to skip to the prev song
     */
    public function prev()
    {
        debug_event('upnp', 'prev', 5);

        if (!$this->_upnp) {
            return false;
        }

        try {
            $this->_upnp->PlaySkip('previous');
            return true;
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'prev failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * volume
     * This tells upnp to set the volume to the specified amount
     */
    public function volume($volume)
    {
        debug_event('upnp', 'volume: ' . $volume, 5);
        
        if (!$this->_upnp) {
            return false;
        }

        try {
            $this->_upnp->SetVolume($volume);
            return true;
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'volume failed: ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * This tells upnp to increase the volume
     */
    public function volume_up()
    {
        debug_event('upnp', 'volume+', 5);
        
        if (!$this->_upnp) {
            return false;
        }

        try {
            return $this->_upnp->VolumeUp();
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'volume_up failed: ' . $ex->getMessage(), 1);
            return false;
        }
    }
    
    /**
     * This tells upnp to decrease the volume
     */
    public function volume_down()
    {
        debug_event('upnp', 'volume-', 5);
        
        if (!$this->_upnp) {
            return false;
        }

        try {
            return $this->_upnp->VolumeDown();            
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'volume_down failed: ' . $ex->getMessage(), 1);
            return false;
        }
    }
    
    /**
     * repeat
     * This tells upnp to set the repeating the playlist (i.e. loop) to either on or off
     */
    public function repeat($state)
    {
        debug_event('upnp', 'repeat: ' . $state, 5);

        if (!$this->_upnp) {
            return false;
        }

        try {
            $this->_upnp->Repeat(array(
                'repeat' => ($state ? 'all' : 'off')
            ));
            return true;
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'repeat failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * random
     * This tells upnp to turn on or off the playing of songs from the playlist in random order
     */
    public function random($onoff)
    {
        debug_event('upnp', 'random: ' . $onoff, 5);

        if (!$this->_upnp) {
            return false;
        }

        try {
            $this->_upnp->PlayShuffle($onoff);
            return true;
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'random failed, is the player started? ' . $ex->getMessage(), 1);
            return false;
        }
    }

    /**
     * get
     * This functions returns an array containing information about
     * The songs that upnp currently has in it's playlist. This must be
     * done in a standardized fashion
     */
    public function get()
    {
        debug_event('upnp', 'get', 5);

        if (!$this->_upnp) {
            return false;
        }

        $results = array();

        try {
            $playlist = $this->_upnp->GetPlayListItems();

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
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'get failed: ' . $ex->getMessage(), 1);
        }

        return $results;
    }

    /**
     * status
     * This returns bool/int values for features, loop, repeat and any other features
     * that this localplay method supports.
     * This works as in requesting the upnp properties
     */
    public function status()
    {
        debug_event('upnp', 'status', 5);

        if (!$this->_upnp) {
            return false;
        }

        $array = array();
        try {
            $array['state'] = 'play';
            $array['volume'] = $this->_upnp->GetVolume();
            $array['repeat'] = false;
            $array['random'] = false;
            $array['track'] = 'TrackName';

            $array['track_title'] = 'Songtitle';
            $array['track_artist'] = 'ArtistName';
            $array['track_album'] = 'AlbumName';
        } catch (UPNP_Exception $ex) {
            debug_event('upnp', 'status failed: ' . $ex->getMessage(), 1);
        }
        return $array;
    } 

    /**
     * connect
     * This functions creates the connection to upnp and returns
     * a boolean value for the status, to save time this handle
     * is stored in this class
     */
    public function connect()
    {
        $options = self::get_instance();
        try {
            debug_event('upnp', 'Trying to connect upnp instance ' . $options['name'] . ' ( ' . $options['url'] . ' )', '5');
            $this->_upnp = new UPnPPlayer($options['name'], $options['url']);
            debug_event('upnp', 'Connected.', '5');
            return true;
        } catch (UPNP_ConnectionException $ex) {
            debug_event('upnp', 'upnp connection failed: ' . $ex->getMessage(), 1);
            return false;
        }
    }

}
